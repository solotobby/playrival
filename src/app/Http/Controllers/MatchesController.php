<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchesRequest;
use App\Http\Requests\UpdateMatchesRequest;
use App\Models\Event;
use App\Models\Matches;
use Illuminate\Support\Facades\Auth;

class MatchesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            
            $matches = [];
            $user = Auth::user();
            $myevents = Event::where('user_id', $user->id)->get('id');
            $matches = Matches::with(['event'])->whereIn('event_id', $myevents)->orderBy('created_at', 'asc')->get();

        }catch(\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
            return response()->json(['status' => true, 'message' => 'Matches', 'data' =>  $matches], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMatchesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Matches $matches)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Matches $matches)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMatchesRequest $request, $id)
    {
        $validated = $request->validated();
        try{
            $matches = Matches::where('id', $id)->firstOrFail();
            $updatedMatch = $matches->update([
                'home_team_goals' => $validated['home_team_goals'],
                'away_team_goals' => $validated['away_team_goals'],
                'is_completed' => $validated['is_completed']
            ]);

            $event = Event::with('teams.team', 'matches')->findorfail($id);
            $teams = $event->teams->pluck('team')->toArray();

            if ($event->game_type_id == 2) {
                // Ensure the number of teams is a power of 2

               $res = $this->handleKnockOutTornament($teams,  $event);
               if($res->status){
                if($res->teams == 2){
                   $event->is_start=true;
                   $event->save();
                }
               
               }else{
                   return response()->json(['status' => false,   'message' => $res->message], 500);
               }

           }
        
        }catch(\Exception $exception){
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        return response()->json(['status' => true, 'message' => 'Updated Match', 'data' =>  $matches], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matches $matches)
    {
        //
    }


    public function handleKnockOutTornament($teams,  $event)
    {

        $db_matches = $event->matches;
        $team_count = count($teams);
        $last_match = count($db_matches) > 1 ?  $db_matches[count($db_matches) - 1] : null;

        if ($last_match == null) {
            // Shuffle the teams to randomize matchups
            shuffle($teams);
            // Generate the first round matches (quarter-finals)
            $matches = $this->genrateSimpleMatches($teams);
            $schedule = $this->CreateMatches($matches, $event, "Round 1");
            return (object)['status' => true, 'teams'=> $team_count,  'data' => $schedule];
         
        }else{
           
            $match_by_rounds = $db_matches->groupBy('tag');
            $last_match = $db_matches->last();
            $last_round = $last_match->tag;
            $last_round_matches = $match_by_rounds[$last_round];

            $is_complete = $last_round_matches->pluck('is_completed')->toArray();

             if (in_array(0,  $is_complete)){
            
                return (object)['status' => false, 'message'=>"All games in the current round is not completed" ];
             }

            $next_round_team=[];

            foreach ($last_round_matches as $roundMatches) {
    
                if ($roundMatches->home_team_goals > $roundMatches->away_team_goals) {
                       $ht =  collect($teams)->firstWhere('id', $roundMatches->home_team_id );
                       array_push($next_round_team, $ht);
                }else{
                    $at =  collect($teams)->firstWhere('id', $roundMatches->away_team_id );
                    array_push($next_round_team, $at);
                }  
            }
            
            $matches = $this->genrateSimpleMatches($next_round_team);
            $schedule = $this->CreateMatches($matches, $event, "Round ". explode(' ', $last_round)[1] + 1);
         
            return (object)['status' => true, 'teams'=> count($next_round_team),  'data' => $schedule];
        }
    }
    public function genrateSimpleMatches($teams){
        $matches = [];
        $team_count = count($teams);
        for ($i = 0; $i < $team_count; $i += 2) {
            $matches[] = [
                'home' => $teams[$i],
                'away' => $teams[$i + 1],
            ];
        }

        return $matches ;
    }

    public function CreateMatches($matches, $event, $round)
    {


        $ready = [];

        foreach ($matches as $match) {
            $one = [
                'event_id' => $event->id,
                'home_team' => $match['home']['name'],
                'home_team_id' => $match['home']['id'],
                'away_team' => $match['away']['name'],
                'away_team_id' => $match['away']['id'],
                'home_team_goals' => 0,
                'away_team_goals' => 0,
                'is_completed' => false,
                'tag' => $round,

            ];
            Matches::create($one);
            // array_push($ready,$one );
        }
        //  dd( $ready);
        return true;
    }
}

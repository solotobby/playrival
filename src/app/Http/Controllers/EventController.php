<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\JoinEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventTeam;
use App\Models\Matches;
use App\Models\Team;
use App\Models\User;
use App\Services\Event\CreateService;
use App\Services\Event\JoinService;
use App\Services\Event\ListService;
use App\Services\Match\CreateService as MatchCreateService;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        try {
            $events = (new ListService($user->id))->run();
            $teams = EventTeam::where('user_id', $user->id)->get(); //$event->teams;
            $list = [];
            foreach($teams as $team){
                $list[] = ['id'=> @$team->id, 'team_name'=> @$team->team->name, 'event_name' => @$team->event->name];
            }

            $data['my_events'] = $events;
            $data['joinedevents'] = $list;
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        return response()->json(['status' => true, 'message' => 'Events List', 'data' =>  $data], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();
        try {
            $new_event = (new CreateService($validated, $user))->run();
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'New Event created', 'data' =>  $new_event], 201);
    }

    public function teams($id)
    {
        try {
            
            //$event = Event::with('teams')->findorfail($id); // 
            $teams = EventTeam::where('event_id', $id)->get(); //$event->teams;
            $list = [];
            foreach($teams as $team){
                $list[] = ['team_name'=> $team->team->name, 'id' => $team->team->id];
            }
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return $list;
    }

    public function join(JoinEventRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

       // dd($user);

        try {
            // if (!in_array($validated['team_id'], $user->userTeamsIds())) {
            //     return response()->json(['status' => false, 'message' => 'You are do not own this team'], 403);
            // }

            $event = Event::where("code", $validated['code'])->first();

            $eventUsers = $event->teamsIds()->toArray();

            if($event->teams->count() >= $event->number_of_teams ){
                return response()->json(['status' => false, 'message' => 'The Number of specified team is complete'], 403);
            }

            if (in_array($user->id, $eventUsers)) {
                return response()->json(['status' => false, 'message' => 'You are already part of this league'], 403);
            }
            $team = Team::where('user_id', $user->id)->latest()->get()[0];

           // $team = Team::findorfail($validated['team_id']);
            $new_event = (new JoinService($event, $team, $user))->run();

            return $new_event;
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'New Event created', 'data' =>  $new_event], 201);
    }


    /**
     * Display the specified resource.
     */
    public function start($id)
    {
        try {
            $event = Event::with('teams.team', 'matches')->findorfail($id);
            $teams = $event->teams->pluck('team')->toArray();
            if($event->is_start){
                return response()->json(['status' => false, 'message' => 'This has already started'], 403);
            }

            if ($event->game_type_id == 1) {
                $numTeams = count($teams);
                if ($numTeams > 3) {
                    return response()->json(['status' => false, 'message' => 'This of teams in this tornament is not enough for a tornament'], 403);
                }
                $schedule = $this->generateRoundRobinSchedule($teams);
                $schedule = (new MatchCreateService($schedule ,$event))->run();
                $event->is_start=true;
                $event->save();

            } else  if ($event->game_type_id == 2) {
                 // Ensure the number of teams is a power of 2
                    $numTeams = count($teams);
                    if (!($numTeams && (($numTeams & ($numTeams - 1)) == 0))) {
                        return response()->json(['status' => false, 'message' => 'This of teams in this tornament is not enough for a tornament'], 403);
                    }
                $res = $this->handleKnockOutTornament($teams,  $event);
                if($res->status){
                 if($res->teams == 2){
                    $event->is_start=true;
                    $event->save();
                 }
                
                }else{
                    return response()->json(['status' => false,   'message' => $res->message], 500);
                }

            } else  if ($event->game_type_id == 3) {

            }
            

        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true,  'message' => 'Started'], 200);
    }

    public function generateRoundRobinSchedule($teams)
    {
        $totalTeams = count($teams);
        if ($totalTeams % 2 != 0) {
            array_push($teams, 'BYE');
        }

        $totalRounds = $totalTeams - 1;
        $matchesPerRound = $totalTeams / 2;

        $schedule = [];

        for ($round = 1; $round <= $totalRounds; $round++) {
            $roundMatches = [];

            for ($match = 0; $match < $matchesPerRound; $match++) {
                $home = $teams[$match];
                $away = $teams[$totalTeams - 1 - $match];

                if ($home !== 'BYE' && $away !== 'BYE') {
                    $matchInfo = [
                        'home' => $home,
                        'away' => $away,
                    ];
                    $roundMatches[] = $matchInfo;
                }
            }

            $schedule[$round] = $roundMatches;

            // Rotate teams for the next round
            $temp = $teams[1];
            for ($i = 1; $i < $totalTeams - 1; $i++) {
                $teams[$i] = $teams[$i + 1];
            }
            $teams[$totalTeams - 1] = $temp;
        }
        return $schedule;
    }


    public function info($id)
    {

        $result = [];
        $fixture = [];

        try {
            $event = Event::with('matches')->findorfail($id);
            $matches = $event->matches;

            foreach ($matches as $match) {
                if ($match->is_completed) {
                    array_push($result, $match);
                } else {
                    array_push($fixture, $match);
                }
            }

            if ($event->game_type_id == 1) {
                $table = $this->generateLeagueTable($matches);
            } else  if ($event->game_type_id == 2) {
                $table = $matches->groupBy('tag');

            }


        } catch (\Exception $exception) {
            return response()->json(['status' => false,   'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        $data = ["table" =>  $table, "result" =>  $result, "fixture" =>  $fixture];
        return response()->json(['status' => true,  'data' => $data, 'message' => 'Info'], 200);
    }


    public  function generateLeagueTable($matches)
    {
        // Initialize an empty array to hold team data
        $teams = [];

        // Loop through the matches
        foreach ($matches as $match) {
            // Determine the home and away teams
            $homeTeam = $match->home_team;
            $awayTeam = $match->away_team;

            // Determine the goals scored by each team
            $homeGoals = $match->home_team_goals;
            $awayGoals = $match->away_team_goals;

            // Determine if the match is a result or a fixture
            $status = $match->is_completed;

            // Update team data based on match result
            if ($status) {
                if (!isset($teams[$homeTeam])) {
                    $teams[$homeTeam] = new Team(['name' => $homeTeam]);
                }
                if (!isset($teams[$awayTeam])) {
                    $teams[$awayTeam] = new Team(['name' => $awayTeam]);
                }

                // Update team statistics
                $teams[$homeTeam]->goals_for += $homeGoals;
                $teams[$homeTeam]->goals_against += $awayGoals;
                $teams[$awayTeam]->goals_for += $awayGoals;
                $teams[$awayTeam]->goals_against += $homeGoals;

                // Update points based on match result (you'll need to define your points rules)
                if ($homeGoals > $awayGoals) {
                    $teams[$homeTeam]->points += 3;
                } elseif ($homeGoals < $awayGoals) {
                    $teams[$awayTeam]->points += 3;
                } else {
                    $teams[$homeTeam]->points += 1;
                    $teams[$awayTeam]->points += 1;
                }
            }
        }

        // Sort teams based on points and other criteria (e.g., goal difference)
        usort($teams, function ($a, $b) {
            if ($a->points === $b->points) {
                return ($b->goals_for - $b->goals_against) - ($a->goals_for - $a->goals_against);
            }
            return $b->points - $a->points;
        });

        return $teams;
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

    public function search(Request $request){
        $validated = $request->validate([
            'type_id' => 'required|numeric',
            'name' => 'required|string',
        ]);
      
        try{
            $events = Event::where([
                [function ($query) use ($validated) {
                    
                        $query->where('is_private', false)
                            ->where('game_type_id', 'LIKE', '%' . $validated['type_id'] . '%')
                            ->where('name', 'LIKE', '%' . $validated['name'] . '%')
                            ->get();
                    
                }]
            ])->get();
        }catch (\Exception $exception) {
            return response()->json(['status' => false,   'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        return response()->json(['status' => true,  'data' => $events, 'message' => 'Search result'], 200);
    }

}

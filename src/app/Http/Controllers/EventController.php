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
                $list[] = ['id'=> @$team->event_id, 'team_name'=> @$team->team->name, 'event_name' => @$team->event->name, 'type_id' => @$team->event->game_type_id];
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
            $team = Team::findorfail($validated['team_id']);

            $data_team = [
                'name' =>  $team->name . "-" .substr( $user->username, 0, 3),
                'team_type' => 2,
                'country_id' => $team->country_id,
                'logo' =>  $team->logo,
            ];
            
            $newTeams= Team::create($data_team);

            EventTeam::create([
                'user_id' => $user->id,
                'team_id'=> $newTeams->id,
                'event_id' => $new_event->id
            ]);

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
                $list[] = ['team_name'=> $team->team->name, 'id' => $team->id];
            }
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return $list;
    }
    public function getEventByCode(Request $request){
        $validated = $request->validate([
            'code' => 'required|max:255',
        ]);
       
        try {
            $event = Event::where("code", $validated['code'])->first();
            $teams = EventTeam::with('team')->where('event_id', $event->id)->get()->pluck('team'); 

            $event['teams']= $teams;

        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'Get Event by Code', 'data' =>  $event], 200);

    }

    public function join(JoinEventRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

      

        try {

            $event = Event::where("code", $validated['code'])->first();
            $team = Team::findorfail($validated['team_id']);
            

            if($team ->team_type !=1 ){
                return response()->json(['status' => false, 'message' => 'This team can not be use to join the any events'], 403);
            }

            $data_team = [
                'name' =>  $team->name . "-" .substr( $user->username, 0, 3),
                'team_type' => 2,
                'country_id' => $team->country_id,
                'logo' =>  $team->logo,
            ];
            $newTeams= Team::create($data_team);
          

            $eventTeams = $event->teamsIds()->toArray();
            $eventUsers = $event->userIds()->toArray();


            if($event->teams->count() >= $event->number_of_teams ){
                return response()->json(['status' => false, 'message' => 'The Number of specified team is complete'], 403);
            }

            if (in_array($user->id, $eventUsers)) {
                return response()->json(['status' => false, 'message' => 'You are already part of this league'], 403);
            }

            if (in_array($team->id, $eventTeams)) {
                return response()->json(['status' => false, 'message' => 'This Team already part of this league select a different'], 403);
            }

            $new_event = (new JoinService($event,  $newTeams, $user))->run();

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
                if ($numTeams < 3) {
                    return response()->json(['status' => false, 'message' => 'The number of teams in this tornament is not enough for a tornament'], 403);
                }
                $schedule = $this->generateRoundRobinSchedule($teams);
                $schedule = (new MatchCreateService($schedule ,$event, $event->is_home_away, count($teams)-1))->run();


                $event->is_start=true;
                $event->save();

            } else  if ($event->game_type_id == 2) {
                 // Ensure the number of teams is a power of 2
                    $numTeams = count($teams);
                    if (!($numTeams && (($numTeams & ($numTeams - 1)) == 0))) {
                        return response()->json(['status' => false, 'message' => 'The number of teams in this tornament is not enough for a tornament'], 403);
                    }
                $res = $this->handleKnockOutTornament($teams,  $event);
                // if($res->status){
                //  if($res->teams == 2){
                //     $event->is_start=true;
                //     $event->save();
                //  }
                
                // }else{
                //     return response()->json(['status' => false,   'message' => $res->message], 500);
                // }

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
        $winner = null;
      
        try {
            $event = Event::with('matches', 'teams')->findorfail($id);
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
                if(count($table)){
                    $winner = $table[0];
                }
            } else  if ($event->game_type_id == 2) {
                $table = $matches->groupBy('tag');
                $keys=[];

                foreach ($table as $key => $value) {
                    array_push($keys, $key);
                }

                $number = count($event->teams);
                if($this->isPowerOf2($number)) {
                    $exponent = $this->findExponentOf2($number);

                    if(in_array("Round " .$exponent, $keys)){
                        $lastMatch= $table["Round " .$exponent][0];
                       // dd( $lastMatch);
                        $winner = [
                            'team' =>  $lastMatch->home_team_goals > $lastMatch->away_team_goals ?  $lastMatch->home_team :  $lastMatch->away_team,
                            'team_id' =>  $lastMatch->home_team_goals > $lastMatch->away_team_goals ?  $lastMatch->home_team_id :  $lastMatch->away_team_id,
                        ];
                    } 
                }

            }


        } catch (\Exception $exception) {
            return response()->json(['status' => false,   'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        $data = [ "winner" =>  $winner,  "table" =>  $table, "result" =>  $result, "fixture" =>  $fixture];
        return response()->json(['status' => true,  'data' => $data, 'message' => 'Info'], 200);
    }

   public function isPowerOf2($num) {
        return ($num & ($num - 1)) === 0 && $num > 0;
    }
    
    public function findExponentOf2($num) {
        if ($this->isPowerOf2($num)) {
            $exponent = 0;
            while ($num > 1) {
                $num = $num >> 1; // Right shift by 1 (equivalent to dividing by 2)
                $exponent++;
            }
            return $exponent;
        } else {
            return null; // Not a power of 2
        }
    }


    public  function generateLeagueTable($matches)
    {
        // Initialize an empty array to hold team data
        $teams = [];
        $teamStats = [];
        // Loop through the matches
       // dd($matches);
        foreach ($matches as $match) {
            // Determine the home and away teams
            $homeTeam = $match->home_team;
            $awayTeam = $match->away_team;

            // Determine the goals scored by each team
            $homeGoals = $match->home_team_goals;
            $awayGoals = $match->away_team_goals;

            // Determine if the match is a result or a fixture
            $status = $match->is_completed;

            if (!isset($teamStats[$homeTeam])) {
                $teamStats[$homeTeam] = [
                    'team' => $homeTeam,
                    'team_id' => $match->home_team_id,
                    'Pl' => 0,
                    'W' => 0,
                    'D' => 0,
                    'L' => 0,
                    'GF' => 0,
                    'GA' => 0,
                    'GD' => 0,
                    'Pts' => 0,
                ];
            }
            if (!isset($teamStats[$awayTeam])) {
                $teamStats[$awayTeam] = [
                    'team' => $awayTeam,
                    'team_id' => $match->away_team_id,
                    'Pl' => 0,
                    'W' => 0,
                    'D' => 0,
                    'L' => 0,
                    'GF' => 0,
                    'GA' => 0,
                    'GD' => 0,
                    'Pts' => 0,
                ];
            }

            // Update team data based on match result
            if ($status) {
                $homeGoals = $match->home_team_goals;
                $awayGoals = $match->away_team_goals;
    
                $teamStats[$homeTeam]['Pl']++;
                $teamStats[$awayTeam]['Pl']++;
                //Goals
                $teamStats[$homeTeam]['GF'] += $homeGoals;
                $teamStats[$homeTeam]['GA'] += $awayGoals;

                $teamStats[$awayTeam]['GF'] += $awayGoals;
                $teamStats[$awayTeam]['GA'] += $homeGoals;
    
                if ($homeGoals > $awayGoals) {
                    $teamStats[$homeTeam]['W']++;
                    $teamStats[$awayTeam]['L']++;
                    $teamStats[$homeTeam]['Pts'] +=3;
                } elseif ($homeGoals < $awayGoals) {
                    $teamStats[$awayTeam]['W']++;
                    $teamStats[$homeTeam]['L']++;
                    $teamStats[$awayTeam]['Pts'] +=3;
                } else {
                    $teamStats[$homeTeam]['D']++;
                    $teamStats[$awayTeam]['D']++;
                    $teamStats[$awayTeam]['Pts'] +=1;
                    $teamStats[$homeTeam]['Pts'] +=1;

                }
               
            }
        }

        foreach ($teamStats as &$teamStat) {
            $teamStat['GD'] = $teamStat['GF'] - $teamStat['GA'];
           // $teamStat['PT'] = $teamStat['GW'] * 3 + $teamStat['GD'];
        }
      
        foreach ($teamStats as $key => $value) {
            array_push($teams, $value);
        }

        usort($teams, function ($a, $b) {
            if ($a['Pts'] === $b['Pts']) {
                return $b['GD'] - $a['GD'];
            }
            return $b['Pts'] - $a['Pts'];
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

    /**
     * Display a listing of the resource.
     */
    public function getFlag($flag= null)
    {

        $user = Auth::user();
        try {

            if($flag == "deleted"){
                $events = Event::where('user_id', $user->id)
                ->where('is_deleted', 1)
                ->latest()->get();
            }
            if($flag == "archived"){
                $events = Event::where('user_id', $user->id)
                ->where('is_archive', 1)
                ->latest()->get();
            }
       
            $data['my_events'] = $events;
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        return response()->json(['status' => true, 'message' => 'List of '. $flag .'Events', 'data' =>  $data], 200);
    }


    public function delete($id)
    {
        try {
            $event = Event::findorfail($id);
          
            if($event->is_deleted){
                return response()->json(['status' => false, 'message' => 'This has already been deleted'], 403);
            }

            $event->is_deleted=true;
            $event->save();

        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true,  'message' => 'Deleted'], 200);
    }

    public function archive($id)
    {
        try {
            $event = Event::findorfail($id);
          
            if($event->is_archive){
                return response()->json(['status' => false, 'message' => 'This has already been archived'], 403);
            }

            $event->is_archive=true;
            $event->save();
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true,  'message' => 'Archived'], 200);
    }

    public function winner($id)
    {

        $result = [];
        $fixture = [];
        $winner = null;
      
        try {
            $event = Event::with('matches', 'teams')->findorfail($id);
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
                if(count($table)){
                    $winner = $table[0];
                }
            } else  if ($event->game_type_id == 2) {
                $table = $matches->groupBy('tag');
                $keys=[];

                foreach ($table as $key => $value) {
                    array_push($keys, $key);
                }

                $number = count($event->teams);
                if($this->isPowerOf2($number)) {
                    $exponent = $this->findExponentOf2($number);

                    if(in_array("Round " .$exponent, $keys)){
                        $lastMatch= $table["Round " .$exponent][0];
                       // dd( $lastMatch);
                        $winner = [
                            'team' =>  $lastMatch->home_team_goals > $lastMatch->away_team_goals ?  $lastMatch->home_team :  $lastMatch->away_team,
                            'team_id' =>  $lastMatch->home_team_goals > $lastMatch->away_team_goals ?  $lastMatch->home_team_id :  $lastMatch->away_team_id,
                        ];
                    } 
                }

            }


        } catch (\Exception $exception) {
            return response()->json(['status' => false,   'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        $data = [ "winner" =>  $winner];
        return response()->json(['status' => true,  'data' => $data, 'message' => 'Info'], 200);
    }
   
    public function dashboard(){
        $user = Auth::user();
        $leaques=[];
        $fixtures=[];
        $is_started= false;
        try {

            $user_event = EventTeam::with('team')->where('user_id',  $user->id)->get(); 
            if(count( $user_event ) > 0){
                $is_started= true;
            }

            foreach ( $user_event as $value) {
             $stat= $this->cinfo($value->event_id, $value->team_id);
             if($stat){
                array_push($leaques,  $stat);
             } }

            foreach ( $user_event as $value) {
                $fix= $this->cfixture($value->event_id, $value->team_id);
                if($fix){
                    $mergedArray = array_merge($fixtures,  $fix);
                    $fixtures= $mergedArray;
                }
            }
             //  dd($fixtures);
          
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        $data = ["Stats" =>  $leaques, "fixture" => array_slice( $fixtures, 0, 3) , "is_started" =>  $is_started, ];
        return response()->json(['status' => true,  'data' => $data, 'message' => 'Recent Stats'], 200);
    }


    public function cinfo($id, $team)
    {

        $result = [];
        $fixture = [];
        $winner = null;
      
        try {
            $event = Event::with('matches', 'teams')->findorfail($id);
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
                $pos = 0;
                if(count($table)>0){

                    foreach ($table as $value) {
                        $pos++;
                      if( $value['team_id']==  $team){
                        $winner = [
                            'pos' =>  $pos,
                            'Leaque' =>  $event->name,
                            'type'=> "league"
                        ];
                      }
                    }
                }

            } else  if ($event->game_type_id == 2) {

                $table = $matches->groupBy('tag');
                $keys=[];
                foreach ($table as $key => $value) {
                    array_push($keys, $key);
                }
               
                $number = count($event->teams);
                if($this->isPowerOf2($number)) {
                    $exponent = $this->findExponentOf2($number);
                    if(in_array("Round " .$exponent, $keys)){
                        $lastMatch= $table["Round " .$exponent][0];
                        if( $lastMatch->home_team_id ==  $team || $lastMatch->away_team_id ==  $team){

                            if($lastMatch->home_team_id ==  $team) {
                                $hah = $lastMatch->home_team_goals > $lastMatch->away_team_goals ? "Winner" :  "Runner Up";
                            }else{
                                $hah = $lastMatch->away_team_goals > $lastMatch->home_team_goals ? "Winner" :  "Runner Up";
                            }

                            $winner = [
                                'pos' =>  $hah,
                                'Leaque' =>  $event->name,
                                'type'=> "tornament"
                            ];
                        }else{
                            foreach(array_reverse($keys) as $value){
                                $preRound = $value;
                                $MatchInRound= $table[$value];
                                foreach ($MatchInRound as $match) {
                                    if( $match->home_team_id ==  $team || $match->away_team_id ==  $team){
                                        $winner = [
                                            'pos' =>   $preRound,
                                            'Leaque' =>  $event->name,
                                            'type'=> "tornament"
                                        ];
                                        break 2;
                                    }
                                }
                           }
            

                        }
    
                    } 
                }

            }


        } catch (\Exception $exception) {
            return throw($exception);
        }

        return $winner;
    }

    public function cfixture($id, $team){

        $winner = [];
        try {
            $event = Event::with('matches', 'teams')->findorfail($id);
            if(count($event->matches) > 0){
                 foreach ($event->matches as $value) {
                    if(!$value->is_completed){
                        if($value->home_team_id==$team || $value->away_team_id==$team ){
                            $value["name"] =  $event->name;
                            $value["game_type_id"] =  $event->game_type_id;
                            array_push($winner, $value);
                        }
                     
                    }
                }
            }
            
          
        } catch (\Exception $exception) {
            return throw($exception);
        }

        return $winner;

    }
}

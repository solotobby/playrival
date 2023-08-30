<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\JoinEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Services\Event\CreateService;
use App\Services\Event\JoinService;
use App\Services\Event\ListService;
use App\Services\Match\CreateService as MatchCreateService;
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
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        return response()->json(['status' => true, 'message' => 'Events List', 'data' =>  $events], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();
        // dd($validated);
        $user = Auth::user();
        try {

            $new_event = (new CreateService($validated, $user))->run();
            
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'New Event created', 'data' =>  $new_event], 201);
    }

    public function teams($id){
        try {
            $event = Event::with('teams')->findorfail($id);
            $teams = $event->teams;
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return $teams;
    }

    public function join(JoinEventRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        try {
            if(!in_array($validated['team_id'], $user->userTeamsIds())){
                return response()->json(['status' => false, 'message' => 'You are do not own this team'], 403);
            }

            $event = Event::where("code", $validated['code'])->first();
            $eventUsers = $event->teamsIds()->toArray();
            if(in_array($user->id, $eventUsers)){
                return response()->json(['status' => false, 'message' => 'You are already part of this league'], 403);
            }
            $team = Team::findorfail($validated['team_id']);
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
            $event = Event::with('teams.team')->findorfail($id);
            $teams = $event->teams->pluck('team')->toArray();
            if($event->is_start){
                return response()->json(['status' => false, 'message' => 'This has already started'], 403);
            }

            if ($event->game_type_id == 1) {

                $schedule = $this->generateRoundRobinSchedule($teams);
                $matches = (new MatchCreateService($schedule ,$event))->run();

            } else  if ($event->game_type_id == 2) {
                $schedule = $this->generateSeasonMatches($teams);
                $matches = (new MatchCreateService($schedule, $event))->run();
                
            } else  if ($event->game_type_id == 3) {

            }
            $event->is_start=true;
            $event->save();
            // check tonament type and build matched
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return (array) $matches;
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


    public function info($id){

        $result = [];
        $fixture =[];

        try {
            $event = Event::with('matches')->findorfail($id);
            $matches = $event->matches;

            foreach ($matches as $match) {
                if($match->is_completed){
                    array_push($result, $match);
                }else{
                    array_push($fixture, $match);
                }
            }

            $table = $this->generateLeagueTable($matches);




        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

       $data = ["table" =>  $table, "result" =>  $result, "fixture" =>  $fixture];
        return response()->json(['status' => false,  'data' => $data, 'message' => 'Error processing request'], 200);

    
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

    /*
    
    **************************************
    *
    *
    *
    *
    *
    */
    public function   generateSeasonMatches($teams)
    {

        $rounds = 1;
        $roundMatches = [];
        foreach ($teams as $homeTeam) {
            $matches = [];
            foreach ($teams as $awayTeam) {
                if ($homeTeam !== $awayTeam) {
                    $match = [
                        'home' => $homeTeam,
                        'away' => $awayTeam,
                    ];
                    $matches[] = $match;
                }
            }

            $roundMatches[$rounds] = $matches;
            $rounds++;
        }

        return $roundMatches;
    }

    function generateKnockoutSchedule($teams)
    {
        $schedule = [];
        $totalTeams = count($teams);

        // Ensure the number of teams is a power of 2
        $powerOfTwo = pow(2, ceil(log($totalTeams, 2)));
        $requiredByes = $powerOfTwo - $totalTeams;

        // Add bye teams if necessary
        $teams = array_merge($teams, array_fill(0, $requiredByes, 'BYE'));

        // Initialize the first round with matches
        $schedule[] = array_chunk($teams, 2);

        while (count($teams) > 1) {
            $roundMatches = [];
            foreach ($schedule[count($schedule) - 1] as $match) {
                $winner = $match[0];
                $roundMatches[] = [$winner];
            }
            $schedule[] = array_chunk($roundMatches, 2);
            $teams = array_column($roundMatches, 0);
        }

        return $schedule;
    }

    public function  generateLeagueSchedule($teams)
    {
        $totalTeams = count($teams);
        $totalRounds = $totalTeams - 1;

        $schedule = [];

        for ($round = 1; $round <= $totalRounds; $round++) {
            $roundMatches = [];

            for ($match = 0; $match < $totalTeams / 2; $match++) {
                $homeTeam = $teams[$match];
                $awayTeam = $teams[$totalTeams - 1 - $match];

                $matchInfo = [
                    'home' => $homeTeam,
                    'away' => $awayTeam,
                ];

                $roundMatches[] = $matchInfo;
            }

            $schedule[$round] = $roundMatches;

            // Rotate teams for the next round
            $lastTeam = array_pop($teams);
            array_splice($teams, 1, 0, $lastTeam);
        }

        return $schedule;
    }



    public function generateMatchSchedule($teams)
    {
        $totalTeams = count($teams);
        if ($totalTeams % 2 !== 0) {
            // If odd number of teams, add a bye team
            array_push($teams, 'BYE');
            $totalTeams++;
        }

        $totalRounds = $totalTeams - 1;
        $matchesPerRound = $totalTeams / 2;

        $matches = [];

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

            $matches[] = $roundMatches;

            // Rotate teams for the next round
            $temp = $teams[1];
            for ($i = 1; $i < $totalTeams - 1; $i++) {
                $teams[$i] = $teams[$i + 1];
            }
            $teams[$totalTeams - 1] = $temp;
        }

        return $matches;
    }

    
}

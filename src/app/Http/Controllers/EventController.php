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

            if ($event->game_type_id == 1) {

                $schedule = $this->generateRoundRobinSchedule($teams);
                $matches = (new MatchCreateService($schedule ,$event))->run();

            } else  if ($event->game_type_id == 2) {
                
            } else  if ($event->game_type_id == 3) {

            }
            // check tonament type and build matched
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return (array) $teams;
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

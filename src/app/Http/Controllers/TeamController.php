<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Country;
use App\Models\Team;
use App\Services\Team\CreateService;
use App\Services\Team\ListService;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        try{
           $events= (new ListService($user->id))->run();
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error'=>$exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
        return response()->json(['status' => true, 'message' => 'Events List', 'data' =>  $events], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();
        try {
            $new_event = (new CreateService($validated, $user))->run();
            return $new_event;
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error'=>$exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'New Event created', 'data' =>  $new_event], 201);
       
    }

    public function getTeamsByCountry($id){
       
        try{
            $teams= Team::where("team_type", 1)->where("country_id", $id)->get();

        } catch (\Exception $exception) {
             return response()->json(['status' => false,  'error'=>$exception->getMessage(), 'message' => 'Error processing request'], 500);
         }
         return response()->json(['status' => true, 'message' => 'Events List', 'data' => $teams], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\JoinEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\Team;
use App\Services\Event\CreateService;
use App\Services\Event\JoinService;
use App\Services\Event\ListService;
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
        $user = Auth::user();
        try {
            $new_event = (new CreateService($validated, $user))->run();
            return $new_event;
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
            $event = Event::where("code", $validated['code'])->first();
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
            $event = Event::findorfail($id);
            // check tonament type and build matched
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return $event;
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        //
    }
}

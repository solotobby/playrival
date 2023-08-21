<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchesRequest;
use App\Http\Requests\UpdateMatchesRequest;
use App\Models\Matches;

class MatchesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $matches = [];
            $matches =  Matches::with(['event'])->orderBy('created_at', 'asc')->get();

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
}

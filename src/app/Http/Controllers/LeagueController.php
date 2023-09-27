<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    //

    public function index($country_id)
    {
        try {
            $leagues= League::where('country_id', $country_id)->get();

        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'New Country', 'data' =>  $leagues], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'country_id'=> 'required|numeric|exists:countries,id',
            'logo' => 'sometimes',
        ]);
        try {
            $new= League::create($validated);

        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }

        return response()->json(['status' => true, 'message' => 'New Country', 'data' =>  $new], 201);
    }
}

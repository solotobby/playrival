<?php

namespace App\Services\Match;

use App\Models\Event;
use App\Models\Matches;
use App\Services\BaseServiceInterface;
use Illuminate\Support\Facades\Log;

class CreateService implements BaseServiceInterface
{
    protected $event;
    protected $data;


    public function __construct($data, $event)
    {
        $this->data = $data;
        $this->event = $event;
    }

    public function run()
    {
        foreach ($this->data as $round => $roundMatches) {
            foreach ($roundMatches as $match) {
                $one= [
                    'event_id'=> $this->event->id,
                    'home_team'=> $match['home']['name'],
                    'home_team_id'=> $match['home']['id'],
                    'away_team'=> $match['away']['name'],
                    'away_team_id' => $match['away']['id'], 
                    'home_team_goals'=> 0, 
                    'away_team_goals'=> 0, 
                    'is_completed'=> false, 
                    'tag'=> "Leaque ". $round,

                ];
                Matches::create($one);
            }
        }
    }


 
}



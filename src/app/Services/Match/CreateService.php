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
    protected $is_home_away;
    protected $roundQ;
   


    public function __construct($data, $event, $is_home_away, $roundQ)
    {
        $this->data = $data;
        $this->event = $event;
        $this->is_home_away = $is_home_away;
        $this->roundQ = $roundQ;
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
        if($this->is_home_away){
            foreach ($this->data as $round => $roundMatches) {
                foreach ($roundMatches as $match) {
                    $one= [
                        'event_id'=> $this->event->id,
                        'home_team'=> $match['away']['name'],
                        'home_team_id'=> $match['away']['id'],
                        'away_team'=> $match['home']['name'],
                        'away_team_id' => $match['home']['id'], 
                        'home_team_goals'=> 0, 
                        'away_team_goals'=> 0, 
                        'is_completed'=> false, 
                        'tag'=> "Leaque ". $round+ $this->roundQ,
                    ];
                    Matches::create($one);
                }
            }
        }

 
    }


 
}



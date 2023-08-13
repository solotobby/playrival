<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventTeam;
use App\Models\Team;
use App\Services\BaseServiceInterface;


class JoinService implements BaseServiceInterface
{
    protected $user;
    protected $event;
    protected $team;


    public function __construct($event,$team, $user)
    {
        $this->event = $event;
        $this->team = $team;
        $this->user = $user;
    }

    public function run()
    {
        return \DB::transaction(function () {
            $new = EventTeam::create([
                'user_id' => $this->user->id,
                'team_id' =>  $this->team->id,
                'event_id' =>    $this->event->id
            ]);
            return $new;
        });
    }
}

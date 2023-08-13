<?php

namespace App\Services\Team;

use App\Models\Team;
use App\Services\BaseServiceInterface;


class CreateService implements BaseServiceInterface
{
    protected $user;
    protected $data;


    public function __construct($data, $user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    public function run()
    {
        return \DB::transaction(function () {
            $new_post = Team::create([
                'user_id' => $this->user->id,
                'name' => $this->data['name'],
                'logo' => $this->data['logo'],
                'team_type' => $this->data['team_type'],
            ]);
            return $new_post;
        });
    }
}

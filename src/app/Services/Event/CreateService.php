<?php

namespace App\Services\Event;

use App\Models\Event;
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
            $new_post = Event::create([
                'user_id' => $this->user->id,
                'name' => $this->data['name'],
                'game_type_id'=> $this->data['type_id'],//1,
                'is_start' => false,
                'start_date' => $this->data['start_date'],
                'end_date' => $this->data['end_date'],
                //'type_id' => $this->data['type_id'],
                'code' => $this->generateCode(10),
                'is_home_away' => $this->data['is_home_away'],
                'is_owner_participate' => $this->data['is_owner_participate'],
                'banner' => $this->data['banner'],
                'number_of_teams' => $this->data['number_of_teams'],
            ]);
            return $new_post;
        });
    }


    public function generateCode($n)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}

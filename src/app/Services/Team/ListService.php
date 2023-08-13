<?php

namespace App\Services\Team;

use App\Models\Event;
use App\Models\Post;
use App\Models\Team;
use App\Services\BaseServiceInterface;


class ListService implements BaseServiceInterface
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function run()
    {
        if ($this->id) {
            return Team::where('user_id', $this->id)->latest()->get();
        }
        return [];
    }
}
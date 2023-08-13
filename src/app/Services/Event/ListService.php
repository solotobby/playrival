<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\Post;
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
            return Event::where('user_id', $this->id)->latest()->get();
        }
        return [];
    }
}
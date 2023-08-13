<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Services\BaseServiceInterface;


class StartService implements BaseServiceInterface
{
    protected $user;
    protected $id;


    public function __construct($id)
    {
        $this->id = $id;
    }

    public function run()
    {
      
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    use HasFactory;
    

    protected $fillable = ['event_id', 'home_team', 'away_team', 'home_team_goals', 'away_team_goals'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'home_team', 'home_team_id', 'away_team', 'away_team_id', 'home_team_goals', 'away_team_goals', 'is_completed', 'tag'];

    public function event(){
        return $this->belongsTo(Event::class, 'event_id');
    }
}

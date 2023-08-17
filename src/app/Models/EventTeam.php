<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTeam extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'user_id', 'team_id', 'is_approved'];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function team(){
        return $this->belongsTo(Team::class);
    }
}

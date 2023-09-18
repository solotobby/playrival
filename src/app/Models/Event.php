<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'banner', 'is_start', 'game_type_id', 'is_owner_participate','is_home_away', 'code', 'number_of_teams', 'start_date', 'end_date', 'is_private'];

    // protected $appends = [
    //     'teams'
    // ];

    public function teams()
    {
        return $this->hasMany(EventTeam::class);
    }

    public function teamsIds()
    {
        return $this->teams()->pluck('team_id');
    }

    public function userIds()
    {
        return $this->teams()->pluck('user_id');
    }
    
    public function matches()
    {
        return $this->hasMany(Matches::class);
    }

    // public function teamsArray()
    // {
    //     $result_array = (array) null;

	// 	// foreach($this->teams as $team)
	// 	// {
	// 	// 	array_push($result_array, $team->id);
	// 	// }

    //     return $this->teams;
    // }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'banner', 'is_start', 'game_type_id', 'is_owner_participate','is_home_away', 'code', 'number_of_teams', 'start_date', 'end_date'];
}

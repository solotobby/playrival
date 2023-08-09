<?php

namespace Database\Seeders;

use App\Models\GameType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GameTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gameTypes = [
            ['name' => 'League'],
            ['name' => 'Knockout'],
            ['name' => 'Tournament']
        ];

        foreach($gameTypes as $type){
            GameType::updateOrCreate($type);
        }
    }
}

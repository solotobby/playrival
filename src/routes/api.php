<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MatchesController;
use App\Http\Controllers\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group(['namespace' => 'auth'], function () {
    Route::post('register', [AuthController::class,'register']);
    Route::post('login',  [AuthController::class,'login']);
});


Route::middleware(['auth:api', 'cors'])->group(function () {
    Route::post('/update',  [AuthController::class,'update']); 
    Route::get('/logout',  [AuthController::class,'logout']); 
});

Route::prefix('tornament')->middleware(['auth:api', 'cors'])->group(function () {
    Route::post('/', [EventController::class,'store'])->name('create.tornament');
    Route::get('/', [EventController::class,'index'])->name('get.tornament');
    Route::post('/join', [EventController::class,'join'])->name('join.tornament');
    Route::get('/{id}/start', [EventController::class,'start'])->name('start.tornament');
    Route::get('/{id}/teams', [EventController::class,'teams'])->name('start.tornament');
    Route::get('/{id}/info', [EventController::class,'info'])->name('info.tornament');
    Route::post('search', [EventController::class,'search'])->name('search');
});


Route::prefix('team')->middleware(['auth:api', 'cors'])->group(function () {
    Route::post('/', [TeamController::class,'store'])->name('create.team');
    Route::get('/', [TeamController::class,'index'])->name('get.team');
});

Route::prefix('match')->middleware(['auth:api', 'cors'])->group(function () {
    Route::get('/', [MatchesController::class,'index'])->name('get.matches');
    Route::patch('/{id}', [MatchesController::class,'update'])->name('update.matches');
});
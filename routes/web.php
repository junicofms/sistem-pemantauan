<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LidarController;

Route::get('/', [LidarController::class, 'index'])->name('home');
Route::post('/upload', [LidarController::class, 'upload'])->name('lidar.upload');
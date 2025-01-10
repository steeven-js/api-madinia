<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TerminalController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/listen-terminal', [TerminalController::class, 'listenCardData']);

require __DIR__.'/auth.php';

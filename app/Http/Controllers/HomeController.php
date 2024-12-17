<?php

namespace App\Http\Controllers;

use App\Models\Event;

class HomeController extends Controller
{
    // Home page
    public function index()
    {
        // Récupérer les évènements
        $events = Event::orderBy('scheduled_date', 'desc')->get();

        return view('home', [
            'events' => $events
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;

class TmetricController extends Controller
{
    public function index()
    {
        return Inertia::render('Index', [
            'username' => 'Martin',
        ]);
    }
}

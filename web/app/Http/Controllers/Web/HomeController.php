<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        if (auth()->check()) {
            return view('app.home-auth');
        }

        return view('app.home');
    }
}

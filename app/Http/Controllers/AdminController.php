<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Show the application's administration dashboard.
     * * This route is protected by the 'can:view admin panel' middleware.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Return a new Blade view for your admin panel.
        return view('admin');
    }
}

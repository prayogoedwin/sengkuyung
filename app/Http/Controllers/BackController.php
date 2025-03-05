<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BackController extends Controller
{
    //
    public function index () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.dashboard.index');
        }
    }

}
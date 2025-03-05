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

    public function download () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.download');
        }
    }

    public function verifikasi () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.verifikasi');
        }
    }

    public function verifikasi_detail () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.verifikasi-detail');
        }
    }

    public function pelaporan () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.pelaporan');
        }
    }

}
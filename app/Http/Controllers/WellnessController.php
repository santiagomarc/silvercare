<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WellnessController extends Controller
{
    public function index()
    {
        return view('elderly.wellness.index');
    }

    public function breathing()
    {
        return view('elderly.wellness.breathing');
    }

    public function memoryMatch()
    {
        return view('elderly.wellness.memory');
    }

    public function morningStretch()
    {
        return view('elderly.wellness.stretch');
    }

    public function wordOfDay()
    {
        return view('elderly.wellness.word');
    }
}
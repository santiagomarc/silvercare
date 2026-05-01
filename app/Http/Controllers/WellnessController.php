<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WellnessController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function index()
    {
        return view('elderly.wellness.index', [
            'unreadNotifications' => $this->getUnreadCount()
        ]);
    }

    public function breathing()
    {
        return view('elderly.wellness.breathing', [
            'unreadNotifications' => $this->getUnreadCount()
        ]);
    }

    public function memoryMatch()
    {
        return view('elderly.wellness.memory', [
            'unreadNotifications' => $this->getUnreadCount()
        ]);
    }

    public function morningStretch()
    {
        return view('elderly.wellness.stretch', [
            'unreadNotifications' => $this->getUnreadCount()
        ]);
    }

    public function wordOfDay()
    {
        return view('elderly.wellness.word', [
            'unreadNotifications' => $this->getUnreadCount()
        ]);
    }

    private function getUnreadCount(): int
    {
        $elderlyId = Auth::user()->profile?->id;
        return $elderlyId ? $this->notificationService->getUnreadCount($elderlyId) : 0;
    }
}
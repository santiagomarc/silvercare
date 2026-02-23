<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Gemini\Laravel\Facades\Gemini;
use App\Models\Medication;
use App\Models\Checklist;
use Carbon\Carbon;

class AiAssistantController extends Controller
{
    /**
     * Handle chat requests to the AI.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $user = Auth::user();
            $today = Carbon::now()->format('l'); // e.g., "Monday"
            
            // Gather user's context for the AI
            $medications = Medication::where('elderly_id', $user->profile->id)
                ->whereJsonContains('days_of_week', $today)
                ->get()
                ->map(function($med) {
                    return "{$med->name} ({$med->dosage} {$med->dosage_unit}) at " . implode(', ', $med->times_of_day ?? []);
                })->implode('; ');

            $tasks = Checklist::where('elderly_id', $user->profile->id)
                ->whereDate('due_date', Carbon::today())
                ->get()
                ->map(function($task) {
                    $status = $task->is_completed ? 'Completed' : 'Pending';
                    return "{$task->task} ({$status})";
                })->implode('; ');
            
            // System prompt to give the AI context about its role
            $systemPrompt = "You are the SilverCare AI Assistant, a helpful, empathetic, and patient companion for elderly users. " .
                            "The user's name is {$user->name}. " .
                            "Your goal is to assist them with their daily tasks, remind them about health metrics, and provide friendly conversation. " .
                            "Keep your answers concise, easy to read, and encouraging. Avoid overly technical jargon. " .
                            "Here is the user's context for today:\n" .
                            "Medications: " . ($medications ?: "None scheduled for today.") . "\n" .
                            "Tasks: " . ($tasks ?: "No tasks for today.");

            $result = Gemini::generativeModel('gemini-2.5-flash')->generateContent($systemPrompt . "\n\nUser: " . $request->message);

            $response = $result->text();

            return response()->json([
                'success' => true,
                'message' => $response,
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gemini API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "I'm sorry, I'm having a little trouble connecting right now. Please try again in a moment.",
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

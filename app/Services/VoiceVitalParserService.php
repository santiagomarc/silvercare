<?php

namespace App\Services;

use App\Models\Medication;
use App\Models\UserProfile;
use Illuminate\Support\Str;

class VoiceVitalParserService
{
    /**
     * Parse natural voice input transcript into a structured clinical reading or action.
     */
    public function parse(string $transcript, UserProfile $elderly): array
    {
        $normalized = $this->normalizeTranscript($transcript);

        // 1. Blood Pressure: e.g. "blood pressure is 125 over 82", "bp 130/85", "120 over 80"
        if (preg_match('/(?:blood\s*pressure|bp|presyon)?\s*(?:is|ay)?\s*(\d{2,3})\s*(?:over|\/|slash)\s*(\d{2,3})/i', $normalized, $matches)) {
            $systolic = (int) $matches[1];
            $diastolic = (int) $matches[2];

            if ($systolic >= 60 && $systolic <= 260 && $diastolic >= 30 && $diastolic <= 180) {
                return [
                    'intent' => 'vital_reading',
                    'type' => 'blood_pressure',
                    'value' => null,
                    'value_text' => "{$systolic}/{$diastolic}",
                    'systolic' => $systolic,
                    'diastolic' => $diastolic,
                    'unit' => 'mmHg',
                    'confidence' => 0.95,
                    'summary' => "Blood Pressure: {$systolic}/{$diastolic} mmHg",
                    'raw_transcript' => $transcript,
                ];
            }
        }

        // 2. Blood Sugar / Glucose: e.g. "blood sugar is 115", "glucose 130", "sugar 95"
        if (preg_match('/(?:blood\s*sugar|sugar\s*level|glucose|asukal|sugar)\s*(?:is|ay|level)?\s*(\d{2,3}(?:\.\d+)?)/i', $normalized, $matches)) {
            $value = (float) $matches[1];
            if ($value >= 30 && $value <= 600) {
                return [
                    'intent' => 'vital_reading',
                    'type' => 'sugar_level',
                    'value' => $value,
                    'value_text' => (string) $value,
                    'unit' => 'mg/dL',
                    'confidence' => 0.93,
                    'summary' => "Blood Sugar: {$value} mg/dL",
                    'raw_transcript' => $transcript,
                ];
            }
        }

        // 3. Heart Rate / Pulse: e.g. "heart rate 72", "pulse is 80", "pulso 75 bpm"
        if (preg_match('/(?:heart\s*rate|pulse|pulso|heartbeat|bpm)\s*(?:is|ay)?\s*(\d{2,3})/i', $normalized, $matches)) {
            $value = (int) $matches[1];
            if ($value >= 30 && $value <= 220) {
                return [
                    'intent' => 'vital_reading',
                    'type' => 'heart_rate',
                    'value' => (float) $value,
                    'value_text' => (string) $value,
                    'unit' => 'BPM',
                    'confidence' => 0.92,
                    'summary' => "Heart Rate: {$value} BPM",
                    'raw_transcript' => $transcript,
                ];
            }
        }

        // 4. Temperature: e.g. "temperature is 36.8", "temp 37.2", "lagnat 38.5"
        if (preg_match('/(?:temperature|temp|lagnat|fever|init)\s*(?:is|ay)?\s*(\d{2}(?:\.\d+)?)/i', $normalized, $matches)) {
            $value = (float) $matches[1];
            // If entered in Fahrenheit (e.g. 98.6), convert or validate
            if ($value >= 90 && $value <= 110) {
                $value = round(($value - 32) * (5 / 9), 1);
            }

            if ($value >= 32.0 && $value <= 43.0) {
                return [
                    'intent' => 'vital_reading',
                    'type' => 'temperature',
                    'value' => $value,
                    'value_text' => (string) $value,
                    'unit' => '°C',
                    'confidence' => 0.92,
                    'summary' => "Temperature: {$value} °C",
                    'raw_transcript' => $transcript,
                ];
            }
        }

        // 5. Medication Administration: e.g. "I took my Aspirin", "uminom ako ng Losartan", "took Metformin"
        $medications = $elderly->trackedMedications()->get();
        foreach ($medications as $med) {
            $medName = strtolower($med->name);
            if (Str::contains(strtolower($transcript), $medName)) {
                return [
                    'intent' => 'medication_taken',
                    'medication_id' => $med->id,
                    'medication_name' => $med->name,
                    'confidence' => 0.90,
                    'summary' => "Confirm Dose for {$med->name}",
                    'raw_transcript' => $transcript,
                ];
            }
        }

        // 6. Daily Check-in: e.g. "I'm feeling okay today", "ayos lang ako", "I need help"
        if (preg_match('/(?:i am ok|i\'m ok|feeling ok|feeling okay|feeling good|ok today|okay today|doing well|all good|okay lang|ayos lang|mabuti naman)/i', $transcript)) {
            return [
                'intent' => 'checkin',
                'status' => 'ok',
                'notes' => $transcript,
                'confidence' => 0.90,
                'summary' => "Daily Wellness Check-in: I'm Doing OK",
                'raw_transcript' => $transcript,
            ];
        }

        if (preg_match('/(?:i need help|need help|help me|tulungan nyo ako|masama pakiramdam)/i', $transcript)) {
            return [
                'intent' => 'checkin',
                'status' => 'need_help',
                'notes' => $transcript,
                'confidence' => 0.95,
                'summary' => "Daily Check-in: Needs Assistance",
                'raw_transcript' => $transcript,
            ];
        }

        return [
            'intent' => 'unknown',
            'confidence' => 0.20,
            'summary' => "Could not extract vital or action from speech.",
            'raw_transcript' => $transcript,
        ];
    }

    /**
     * Convert common spoken number words to numeric representation.
     */
    protected function normalizeTranscript(string $text): string
    {
        $normalized = strtolower(trim($text));

        // Spoken number words mapping
        $replacements = [
            'zero' => '0', 'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4',
            'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9',
            'ten' => '10', 'twenty' => '20', 'thirty' => '30', 'forty' => '40',
            'fifty' => '50', 'sixty' => '60', 'seventy' => '70', 'eighty' => '80',
            'ninety' => '90', 'hundred' => '100', 'point' => '.', 'tuldok' => '.',
        ];

        return strtr($normalized, $replacements);
    }
}

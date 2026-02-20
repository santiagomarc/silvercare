<?php

use App\Http\Controllers\Auth\CaregiverSetPasswordController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CaregiverDashboardController;
use App\Http\Controllers\CaregiverProfileController;
use App\Http\Controllers\CaregiverAnalyticsController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ElderlyDashboardController;
use App\Http\Controllers\HealthMetricController;
use App\Http\Controllers\GoogleFitController;
use App\Http\Controllers\WellnessController; // <--- ADDED THIS
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Welcome landing page - redirect logged-in users to their dashboard
Route::get('/', function () {
    return view('welcome');
})->middleware('role.redirect')->name('welcome');

// Caregiver Password Setup (Signed Route - No Auth Required)
Route::get('/caregiver/set-password/{userId}', [CaregiverSetPasswordController::class, 'show'])
    ->name('caregiver.password.show');

Route::post('/caregiver/set-password/{userId}', [CaregiverSetPasswordController::class, 'store'])
    ->name('caregiver.password.store');

// Elderly Routes - Protected by 'elderly' middleware
Route::middleware(['auth', 'verified', 'elderly'])->group(function () {
    Route::get('/dashboard', [ElderlyDashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-medications', [ElderlyDashboardController::class, 'medications'])->name('elderly.medications');
    Route::get('/my-checklists', [ElderlyDashboardController::class, 'checklists'])->name('elderly.checklists');
    Route::post('/my-checklists/{checklist}/toggle', [ElderlyDashboardController::class, 'toggleChecklist'])->name('elderly.checklists.toggle');
    
    // Medication dose tracking
    Route::post('/my-medications/{medication}/take', [ElderlyDashboardController::class, 'takeMedication'])->name('elderly.medications.take');
    Route::post('/my-medications/{medication}/undo', [ElderlyDashboardController::class, 'undoMedication'])->name('elderly.medications.undo');

    // Health Metrics (Vitals)
    Route::post('/my-vitals', [HealthMetricController::class, 'store'])->name('elderly.vitals.store');
    Route::post('/my-mood', [HealthMetricController::class, 'storeMood'])->name('elderly.mood.store');
    Route::get('/my-mood/today', [HealthMetricController::class, 'getTodayMood'])->name('elderly.mood.today');
    Route::get('/my-vitals/today', [HealthMetricController::class, 'today'])->name('elderly.vitals.today');
    Route::get('/my-vitals/{type}/history', [HealthMetricController::class, 'history'])->name('elderly.vitals.history');
    Route::delete('/my-vitals/{metric}', [HealthMetricController::class, 'destroy'])->name('elderly.vitals.destroy');

    // Individual Vital Screens
    Route::get('/my-vitals/analytics', [HealthMetricController::class, 'analytics'])->name('elderly.vitals.analytics');
    Route::get('/my-vitals/analytics/export', [HealthMetricController::class, 'exportPdf'])->name('elderly.vitals.export');
    Route::get('/my-vitals/blood-pressure', [HealthMetricController::class, 'bloodPressureScreen'])->name('elderly.vitals.blood_pressure');
    Route::get('/my-vitals/sugar-level', [HealthMetricController::class, 'sugarLevelScreen'])->name('elderly.vitals.sugar_level');
    Route::get('/my-vitals/temperature', [HealthMetricController::class, 'temperatureScreen'])->name('elderly.vitals.temperature');
    Route::get('/my-vitals/heart-rate', [HealthMetricController::class, 'heartRateScreen'])->name('elderly.vitals.heart_rate');

    // Google Fit Integration
    Route::get('/google-fit/connect', [GoogleFitController::class, 'connect'])->name('elderly.googlefit.connect');
    Route::get('/google-fit/callback', [GoogleFitController::class, 'callback'])->name('elderly.googlefit.callback');
    Route::post('/google-fit/sync', [GoogleFitController::class, 'sync'])->name('elderly.googlefit.sync');
    Route::post('/google-fit/disconnect', [GoogleFitController::class, 'disconnect'])->name('elderly.googlefit.disconnect');

    // ---------------------------------------------------------------------
    // WELLNESS ROUTES (Added)
    // ---------------------------------------------------------------------
    Route::get('/wellness', [WellnessController::class, 'index'])->name('elderly.wellness.index');
    Route::get('/wellness/breathing', [WellnessController::class, 'breathing'])->name('elderly.wellness.breathing');
    Route::get('/wellness/memory-match', [WellnessController::class, 'memoryMatch'])->name('elderly.wellness.memory');
    Route::get('/wellness/morning-stretch', [WellnessController::class, 'morningStretch'])->name('elderly.wellness.stretch');
    Route::get('/wellness/word-of-day', [WellnessController::class, 'wordOfDay'])->name('elderly.wellness.word');

    // ---------------------------------------------------------------------
    // NOTIFICATION ROUTES
    // ---------------------------------------------------------------------
    Route::get('/notifications', [NotificationController::class, 'index'])->name('elderly.notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('elderly.notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('elderly.notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'delete'])->name('elderly.notifications.delete');
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('elderly.notifications.clear-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('elderly.notifications.unread-count');
    Route::get('/notifications/latest', [NotificationController::class, 'getLatest'])->name('elderly.notifications.latest');
});

// Caregiver Routes - Protected by 'caregiver' middleware
Route::middleware(['auth', 'verified', 'caregiver'])->prefix('caregiver')->name('caregiver.')->group(function () {
    Route::get('/dashboard', [CaregiverDashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/profile', [CaregiverProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [CaregiverProfileController::class, 'update'])->name('profile.update');
    
    Route::get('/analytics', [CaregiverAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [CaregiverAnalyticsController::class, 'exportPdf'])->name('analytics.export');
    
    Route::resource('medications', MedicationController::class);
    Route::resource('checklists', ChecklistController::class);
    Route::post('checklists/{checklist}/toggle', [ChecklistController::class, 'toggleComplete'])->name('checklists.toggle');
});

// Profile Completion Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile/completion', [ProfileCompletionController::class, 'show'])
        ->name('profile.completion');
    
    Route::post('/profile/completion', [ProfileCompletionController::class, 'store'])
        ->name('profile.completion.store');
    
    Route::get('/profile/completion/skip', [ProfileCompletionController::class, 'skip'])
        ->name('profile.completion.skip');
});

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Profile Photo
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo.upload');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::delete('/calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
});

require __DIR__.'/auth.php';
# 🏥 SilverCare — Complete Project Master Reference & Architecture Guide

---

## 📌 1. Executive Summary & Core Mission

**SilverCare** is an advanced, senior-first healthcare and medication management ecosystem built with **Laravel 12, PHP 8.2+, PostgreSQL 17, Tailwind CSS, Alpine.js, Chart.js, and Google Gemini AI**. It bridges the critical care gap between independent elderly individuals and their caregivers (family members, guardians, and professional healthcare providers).

### The Problem
* **Medication Non-Adherence & Mismanagement:** Complex dosage schedules, forgotten pills, and dangerous drug interactions.
* **Silent Health Deterioration:** Vital signs (blood pressure, sugar levels, heart rate) fluctuating without early detection.
* **Caregiver Disconnect:** Family members lack real-time visibility into daily routines and urgent health changes.
* **Cognitive Decline & Isolation:** Need for gentle cognitive stimulation and accessible, empathetic daily engagement.

### The Solution
SilverCare unifies daily health logging, medication schedules, real-time vital telemetry (manual and Google Fit wearable sync), interactive cognitive exercises, emergency SOS dispatch, direct caregiver-patient messaging, and dual AI agents (an empathetic companion for the senior and a clinical data analyst for the caregiver).

---

## 🛠️ 2. Technology Stack & Key Dependencies

### Backend & Core Infrastructure
* **Framework:** Laravel 12 (`laravel/framework: ^12.0`)
* **Language Runtime:** PHP 8.2+
* **Database:** PostgreSQL 17 (JSON casting, compound indexes, unique constraint deduplication)
* **Realtime & WebSockets:** Laravel Reverb (`laravel/reverb: ^1.0`), Laravel Echo, Pusher JS
* **Task Scheduling:** Laravel Task Scheduler running via CRON
* **PDF Engine:** Barryvdh Laravel DomPDF (`barryvdh/laravel-dompdf: ^3.1`)
* **QR Generation:** SimpleSoftwareIO Simple QrCode (`simplesoftwareio/simple-qrcode: ^4.2`)
* **Social Auth & OAuth:** Laravel Socialite (`laravel/socialite: ^5.23`) & Google Fit REST APIs

### Frontend & UI Architecture
* **Templating:** Laravel Blade Component Architecture
* **CSS Framework:** Tailwind CSS 3.x / 4.x with `@tailwindcss/forms` and `@tailwindcss/vite`
* **Reactive Logic:** Alpine.js (`alpinejs: ^3.4.2`), Vanilla JS, Axios
* **Data Visualization:** Chart.js (`chart.js: ^4.5.1`)
* **Iconography:** Lucide Icons (`lucide: ^1.8.0`, `mallardduck/blade-lucide-icons: ^1.26`)
* **Enhanced UI Controls:**
  * **Tom Select** (`tom-select: ^2.6.0`) for searchable selects
  * **Flatpickr** (`flatpickr: ^4.6.13`) for accessible date/time picking
  * **SweetAlert2** (`sweetalert2: ^11.26.24`) for accessible confirmations
  * **Cropper.js** (`cropperjs: ^1.6.2`) for profile photo cropping

### AI & LLM Engine
* **SDK:** Google Gemini PHP for Laravel (`google-gemini-php/laravel: ^2.0`)
* **Default Model:** `gemini-2.5-flash`
* **Communication Modes:** Synchronous JSON responses & Server-Sent Events (SSE) streaming
* **Function Calling (Tools):** Native JSON Schema declarations for automated task completion and medication logging

---

## 👥 3. Dual-Dashboard Architecture & User Flows

SilverCare enforces Role-Based Access Control (RBAC) via middleware (`elderly` and `caregiver`).

```
                              ┌─────────────────────────┐
                              │     Guest / Welcome     │
                              └────────────┬────────────┘
                                           │
                                 [Authentication]
                                           │
                        ┌──────────────────┴──────────────────┐
                        │                                     │
           ┌────────────▼────────────┐           ┌────────────▼────────────┐
           │      Elderly Role       │           │     Caregiver Role      │
           └────────────┬────────────┘           └────────────┬────────────┘
                        │                                     │
         ┌──────────────┴──────────────┐        ┌─────────────┴─────────────┐
         ▼                             ▼        ▼                           ▼
  Elderly Dashboard            Wellness & AI  Caregiver Command        Analytics &
  (Meds, Vitals, SOS)          (Silvia, Games)(Multi-Patient Roster)   PDF Reports
```

---

### A. Elderly Portal (`/dashboard`)
*Designed specifically for senior citizens with high-contrast UI, larger touch targets, low cognitive load, and positive reinforcement.*

1. **Hero Action Card:** Dynamically evaluates the current time window (Morning, Afternoon, Evening, Bedtime) and prompts the senior with their next urgent dose or checklist task.
2. **Medication Management:**
   * One-touch **"Take Dose"**, **"Undo"**, and **"Request Refill"** operations.
   * Visual pill indicators (color, shape, dosage instructions).
3. **Vital Signs Telemetry:**
   * Four core vitals: **Heart Rate**, **Blood Pressure (Systolic / Diastolic)**, **Sugar Level (Blood Glucose)**, and **Body Temperature**.
   * Instant feedback states (Normal, Elevated, Low) based on clinical thresholds.
   * Modal logging and historical trend charts.
4. **Daily Mood Tracker:**
   * 1 to 5 scale (Awful, Bad, Okay, Good, Great) with daily sentiment logging.
5. **"Garden of Wellness" (Gamified Compliance):**
   * Visual adherence tracker. When seniors take medications and complete daily tasks, their virtual garden blooms; consecutive missed tasks cause the garden to wilt, encouraging positive daily habits.
6. **Wellness & Cognitive Stimulation Center:**
   * Guided Breathing (`/wellness/breathing`): Calming visual breath pacer.
   * Memory Match (`/wellness/memory-match`): Interactive card matching game.
   * Morning Stretch (`/wellness/morning-stretch`): Safe mobility routines.
   * Word of the Day (`/wellness/word-of-day`): Daily vocabulary & cognitive exercise.
7. **Emergency SOS Alert:**
   * Prominent panic button. When clicked, instantly triggers high-severity in-app notifications and sends an emergency email broadcast to the linked caregiver.
8. **Silvia — Ambient AI Companion:**
   * Warm, empathetic assistant with custom visual themes (Coast, Sunrise, Grove).
   * Real-time SSE streaming.
   * **Function Calling capabilities:** Can complete tasks (`mark_task_complete`) and log meds (`log_medication`) directly from natural chat.
   * Built-in safety heuristics: Scans for emergency phrases (chest pain, fallen, trouble breathing) and silently dispatches caregiver alerts.

---

### B. Caregiver Command Center (`/caregiver/dashboard`)
*Clinical, multi-patient monitoring dashboard designed for family or healthcare aides.*

1. **Multi-Patient Roster:**
   * Link multiple elderly patients using a secure 6-digit PIN or signed QR code.
   * Switch between active patients, inspect profiles, archive, or restore patients.
2. **Real-time Health & Compliance KPIs:**
   * Today's medication adherence percentage.
   * Active vital sign values vs. baseline thresholds.
   * Daily checklist progress and recent activity audit logs.
3. **Clinical Analytics & PDF Export:**
   * 7-day and 30-day trend lines for all vital metrics.
   * One-click **PDF Health Summary Export** formatted for doctor visits.
4. **Medication & Task Management:**
   * Full CRUD over prescriptions: dosage, frequency, specific days of the week, custom dates, time slots, and stock inventory tracking with low-stock alerts.
   * Task checklist scheduling with automatic daily recycling.
5. **Direct Care Messaging:**
   * Dedicated, asynchronous messaging inbox between caregiver and elderly patient.
6. **Caregiver AI Analyst:**
   * Clinical assistant powered by Gemini. Synthesizes 7-day vitals, adherence trends, missed doses, and checks for OTC drug interactions with prescribed regimens.

---

## 🗄️ 4. Database Schema & Data Models

### Entity Relationship Overview

```
                       ┌─────────────────────────┐
                       │          User           │
                       └────────────┬────────────┘
                                    │ 1:1
                       ┌────────────▼────────────┐
                       │       UserProfile       │
                       │ (Role: elderly/caregiver)│
                       └────────────┬────────────┘
         ┌──────────────────────────┼──────────────────────────┐
         │ 1:N                      │ 1:N                      │ 1:N
┌────────▼────────┐        ┌────────▼────────┐        ┌────────▼────────┐
│   Medication    │        │  HealthMetric   │        │    Checklist    │
│  (Prescriptions)│        │ (Vitals / Steps)│        │ (Daily Tasks)   │
└────────┬────────┘        └─────────────────┘        └─────────────────┘
         │ 1:N
┌────────▼────────┐
│ MedicationLog   │
│ (Dose History)  │
└─────────────────┘
```

### Key Models & Table Structures

| Model | Table | Purpose & Key Columns |
| :--- | :--- | :--- |
| `User` | `users` | Core auth: `name`, `email`, `password`, `google_id`, `google_avatar`, `email_verified_at`. |
| `UserProfile` | `user_profiles` | Extended profile: `user_type` (`elderly`/`caregiver`), `caregiver_id` (FK), `phone_number`, `medical_conditions` (JSON), `medications` (JSON), `allergies` (JSON), `emergency_name`, `emergency_phone`, `emergency_relationship`, `profile_completed`, `archived_at`. |
| `Medication` | `medications` | Prescriptions: `elderly_id`, `caregiver_id`, `name`, `dosage`, `dosage_unit`, `frequency`, `instructions`, `appearance_color`, `appearance_shape`, `days_of_week` (JSON), `specific_dates` (JSON), `times_of_day` (JSON), `track_inventory`, `current_stock`, `low_stock_threshold`, `is_active`. |
| `MedicationSchedule`| `medication_schedules` | Normalized schedules: `medication_id`, `schedule_type` (`daily`, `weekly`, `specific_date`), `time_of_day`, `days_of_week` (JSON), `specific_date`. |
| `MedicationLog` | `medication_logs` | Dose execution audit: `medication_id`, `elderly_id`, `scheduled_time`, `taken_at`, `status` (`taken`, `missed`, `skipped`, `refill_requested`), `notes`. |
| `HealthMetric` | `health_metrics` | Health readings: `elderly_id`, `type` (`heart_rate`, `blood_pressure`, `sugar_level`, `temperature`, `steps`, `mood`), `value`, `value_text`, `unit`, `measured_at`, `source` (`manual`, `google_fit`), `notes`. |
| `Checklist` | `checklists` | Routine tasks: `elderly_id`, `caregiver_id`, `task`, `category`, `due_date`, `time_of_day`, `priority`, `is_recurring`, `recurrence_type`, `is_completed`, `completed_at`. |
| `CareMessage` | `care_messages` | In-app messaging: `caregiver_id`, `elderly_id`, `sender_profile_id`, `message`, `read_at`. |
| `LinkCode` | `link_codes` | 6-digit PIN & QR token: `code`, `caregiver_profile_id`, `expires_at`, `used_at`, `used_by_profile_id`. |
| `ChatSession` & `ChatMessage` | `chat_sessions`, `chat_messages` | AI conversation storage: `user_id`, `title`, `role` (`user`, `model`), `content`. |
| `GoogleFitToken` | `google_fit_tokens` | OAuth tokens: `user_id`, `access_token`, `refresh_token`, `expires_at`. |
| `Notification` | `notifications` | In-app alerts: `elderly_id`, `type`, `title`, `message`, `severity` (`reminder`, `warning`, `sos_alert`), `metadata` (JSON), `read_at`. |
| `CalendarEvent` | `calendar_events` | Appointments: `user_id`, `title`, `event_date`, `start_time`, `location`, `notes`. |

---

## ⚙️ 5. Service Layer Architecture (`app/Services/`)

SilverCare uses a **Service-Oriented Architecture (SOA)** to keep controllers lean and ensure high testability:

* **`AiAssistantService.php`:**
  * Constructs system prompts for Silvia and Caregiver AI Analyst.
  * Injects contextual health metrics, today's doses, and task schedules only when relevant.
  * Handles Gemini 2.5 Flash streaming via Server-Sent Events (SSE).
  * Executes Function Calling tools (`mark_task_complete`, `log_medication`).
  * Scans user messages for critical emergencies and triggers background caregiver notifications.
* **`ElderlyDashboardService.php`:**
  * Compiles complete dashboard state: today's doses, pending checklists, vitals summary, Google Fit steps, and garden health streak.
* **`CaregiverDashboardService.php`:**
  * Computes multi-patient statistics, adherence metrics, recent activity feeds, and link QR codes.
* **`GoogleFitService.php`:**
  * Handles OAuth token exchange, token refreshes, dataset queries for steps/calories/active minutes, and deduplicated database insertion.
* **`HealthAnalyticsService.php`:**
  * Computes 7-day and 30-day vital sign averages, BMI indices, trend classifications, and Chart.js datasets.
* **`MedicationService.php` & `MedicationAdherenceService.php`:**
  * Manages prescription lifecycle, schedule validation, dose logging, inventory stock decrementing, and refill requests.
* **`MedicationWindowService.php`:**
  * Encapsulates time-window logic (Morning: 05:00-11:59, Afternoon: 12:00-16:59, Evening: 17:00-20:59, Bedtime: 21:00-04:59).
* **`ChecklistService.php`:**
  * Handles checklist creation, toggle updates, and recurring task duplication.
* **`NotificationService.php`:**
  * Dispatches typed in-app notifications with severity tiers.
* **`ProfileCompletionService.php`:**
  * Verifies personal, medical, and emergency contact completeness.

---

## ⏰ 6. Scheduled Tasks & Console Commands (`routes/console.php`)

SilverCare relies on Laravel's Task Scheduler for automated maintenance, health auditing, and alert dispatching:

```php
// 1. Send pending medication & checklist reminders every 30 mins (8 AM – 9 PM)
Schedule::command('silvercare:send-reminders')->everyThirtyMinutes()->between('08:00', '21:00');

// 2. Check medication inventory levels daily at 9:00 AM
Schedule::command('medications:check-stock')->dailyAt('09:00');

// 3. Check upcoming calendar appointments every 15 mins
Schedule::command('appointments:send-reminders')->everyFifteenMinutes();

// 4. Send weekly summary PDF reports every Monday at 7:30 AM
Schedule::command('reports:send-weekly-health')->weeklyOn(1, '07:30');

// 5. Recycle recurring checklist tasks daily at 00:01 AM
Schedule::command('checklists:recycle-recurring')->dailyAt('00:01');

// 6. Track cognitive sentiment from senior chat logs daily at 11:00 PM
Schedule::command('ai:track-cognitive-sentiment')->dailyAt('23:00');

// 7. Background Google Fit sync every 2 hours
Schedule::command('app:sync-google-fit')->everyTwoHours();

// 8. Delete abandoned incomplete profiles (>24h old) hourly
Schedule::command('profiles:delete-incomplete --hours=24')->hourly();
```

---

## 🌐 7. Routing & Controller Map (`routes/web.php`)

### Public & Authentication Routes
* `GET /` (`welcome`): Landing page (redirects logged-in users according to role).
* `GET /caregiver/set-password/{userId}` (`caregiver.password.show`): Signed onboarding route for invited caregivers.
* `GET /profile/completion`: Mandatory onboarding form for incomplete profiles.

### Elderly Routes (`middleware: ['auth', 'elderly', 'profile.complete']`)
* **Dashboard & Navigation:**
  * `GET /dashboard`: Main senior dashboard.
  * `GET /my-medications`: Prescriptions view.
  * `GET /my-checklists`: Daily tasks view.
  * `GET /wellness`: Wellness & cognitive hub.
* **Medication & Task Actions:**
  * `POST /my-medications/{id}/take`: Log dose as taken.
  * `POST /my-medications/{id}/undo`: Undo taken dose.
  * `POST /my-medications/{id}/refill`: Request prescription refill.
  * `POST /my-checklists/{id}/toggle`: Toggle task completion.
* **Vitals & Health:**
  * `POST /my-vitals`: Record a vital metric.
  * `POST /my-mood`: Record daily mood.
  * `GET /my-vitals/analytics`: Senior analytics page.
  * `GET /my-vitals/analytics/export`: Export PDF report.
  * `GET /my-vitals/{blood-pressure|sugar-level|temperature|heart-rate}`: Individual vital views.
* **Google Fit Integration:**
  * `GET /google-fit/connect`: Initiate OAuth connection.
  * `GET /google-fit/callback`: OAuth return URL.
  * `POST /google-fit/sync`: Manual trigger for sync.
  * `POST /google-fit/disconnect`: Revoke Google Fit link.
* **Silvia AI Companion:**
  * `GET /ai-assistant`: Chat view.
  * `POST /ai-assistant/chat`: Standard chat POST.
  * `POST /ai-assistant/stream`: SSE token streaming endpoint.
  * `GET /ai-assistant/history`: Fetch conversation history.
  * `POST /ai-assistant/new-session`: Create a new session.
* **Emergency & Communication:**
  * `POST /sos`: Emergency panic trigger.
  * `GET /messages` & `POST /messages`: Direct messages with caregiver.
  * `POST /link-caregiver/validate` & `confirm`: PIN/QR pairing flow.

### Caregiver Routes (`middleware: ['auth', 'caregiver', 'profile.complete']`, prefix: `caregiver/`)
* **Dashboard & Patients:**
  * `GET /caregiver/dashboard`: Main command center.
  * `GET /caregiver/patients`: Patient list management.
  * `POST /caregiver/patients/{id}/remove` & `restore`: Archive/Restore patients.
  * `POST /caregiver/link-code`: Generate 6-digit PIN and QR pairing code.
* **Clinical Records & Prescriptions:**
  * `RESOURCE /caregiver/medications`: Full prescription CRUD.
  * `RESOURCE /caregiver/checklists`: Full task CRUD.
  * `GET /caregiver/analytics`: Clinical analytics view.
  * `GET /caregiver/analytics/export`: Generate clinical PDF report.
* **Messaging & AI Analyst:**
  * `GET /caregiver/messages` & `POST /caregiver/messages`: Patient messaging.
  * `POST /caregiver/ai-analyst/chat`: Clinical query POST.
  * `POST /caregiver/ai-analyst/stream`: SSE stream for clinical AI insights.

---

## 🎨 8. Blade Component Ecosystem (`resources/views/components/`)

SilverCare features a rich, reusable Blade component library:

* `<x-ai-chat-widget>`: Full-featured sliding AI assistant panel with theme controls (Coast, Sunrise, Grove), auto-resizing text inputs, quick action chips, and SSE token streaming.
* `<x-vital-card>`: Individual vital metrics card with status badges, threshold color indicators, and trend sparklines.
* `<x-vital-record-modal>`: Accessible modal with pre-configured input ranges for heart rate, blood pressure, blood glucose, and temperature.
* `<x-task-list>`: Interactive checklist container with priority tagging and immediate AJAX toggling.
* `<x-medication-list>` & `<x-medication-dose-button>`: Accessible prescription lists with dosage pills, refill warning badges, and quick-take buttons.
* `<x-elderly-garden>`: Gamified adherence garden visualizer with streak counters and blooming/wilting states.
* `<x-elderly-mood-tracker>`: Interactive 5-point emoji mood selector.
* `<x-elderly-steps-card>`: Google Fit step counter card with daily goal progress circle.
* `<x-elderly-hero-action>`: Context-aware prominent call-to-action banner for the senior's next immediate daily task.
* `<x-logout-confirm-modal>`: Accessible confirmation dialog preventing accidental logouts.

---

## 🚀 9. Local Development & Testing Instructions

### Initial Setup
```bash
# 1. Clone repository & install dependencies
git clone git@github.com:santiagomarc/silvercare.git
cd silvercare
composer install
npm install

# 2. Environment configuration
cp .env.example .env
php artisan key:generate

# 3. Database migrations & seeders (PostgreSQL)
php artisan migrate --seed
```

### Running the Application Locally
Run all required dev processes (Web Server, Queue Worker, and Vite Asset Bundler) concurrently:
```bash
composer run dev
# Executes: php artisan serve + php artisan queue:listen + npm run dev
```

### Running Tests
```bash
composer run test
# Or directly via Artisan:
php artisan test
```

---

## 🔒 10. AI Agents & Prompt Engineering Directives

When building features or extending SilverCare using AI:
1. **Senior Persona ("Silvia"):** Always maintain a warm, respectful, empathetic, "grandmotherly" tone. Keep answers concise (2–4 short paragraphs), use large typography principles, and never provide definitive medical diagnoses (always defer serious symptoms to healthcare providers or caregiver SOS).
2. **Caregiver AI Analyst:** Deliver concise, clinical, data-backed insights formatted in Markdown with bold key metrics and actionable recommendations.
3. **Safety Heuristics:** Never bypass the emergency keyword scanner or the rate-limiters (`throttle:30,1`) on AI endpoints.
4. **Adherence Accuracy:** Ensure any modifications to dose logging properly update `MedicationLog`, decrement inventory stock when `track_inventory` is enabled, and refresh the Garden of Wellness streak calculations.

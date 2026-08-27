# SilverCare AI Agent Context & Handover Guide

> **To any AI Agent continuing work on SilverCare:**
> This document summarizes the architectural overhaul, domain kernel implementation, new modules, and complete file index implemented across Sprints 0–6.

---

## 1. Project Architecture Overview

SilverCare is an elderly caregiving and clinical health-tracking web application built on **Laravel 12 (PHP 8.2+)**, **TailwindCSS**, **Alpine.js**, **Vite**, **PostgreSQL / SQLite**, and **Laravel Reverb (WebSockets)**.

It serves two distinct user personas:
1. **Elderly (Seniors):** Simplified, accessible UI (high contrast, 48px touch targets, Web Speech voice input, 1-tap wellness check-in, medication intake confirmation with grace periods).
2. **Caregivers:** Clinical dashboard, live real-time alert center (WebSockets), composite risk briefings (0–100 risk score), customizable patient thresholds, and weekly PDF reports.

---

## 2. Core Modules & Domain Services

### A. Medication Correctness Kernel (`DoseAdministrationService`)
- Single authoritative writer for medication adherence.
- Prevents double-taking doses via pessimistic database row locking (`lockForUpdate()`).
- Atomic inventory decrements with low stock alerting.
- Timezone-safe UTC accessors (`Asia/Manila` default).
- Pre-generates rolling 7-day scheduled dose horizon in `medication_dose_instances` table (`DoseInstanceGeneratorService`).
- Configurable 15-minute undo grace period (`config('medications.undo_grace_period_minutes', 15)`).

### B. Escalating Clinical Alert Center (`ClinicalRulesService` & `AlertDeliveryService`)
- Multi-tier alert hierarchy: `info`, `warning`, `critical`, `emergency`.
- Evaluates vitals (BP systolic ≥180 or diastolic ≥110 triggers Critical; systolic ≥140 or diastolic ≥90 triggers Warning; Sugar <70 or >250 triggers Warning; Temp >38.5°C triggers Warning).
- Realtime dispatch via Laravel Reverb (`CriticalAlertFired` on `private-caregiver.{id}`).
- Custom patient threshold overrides (`patient_alert_thresholds` table).
- Scheduled escalation daemon: `alerts:escalate` runs every 5 minutes for unacknowledged criticals.

### C. Caregiver Risk Briefing & Check-ins (`ClinicalInsightService` & `CareCheckinService`)
- 1-tap senior "I'm OK" check-in (`POST /checkin`).
- If status is `need_help`, automatically fires a warning alert to the linked caregiver.
- Scheduled cutoff monitor (`checkins:check-missed`) at 18:30 daily.
- 0–100 Composite Patient Risk Score based on 7-day adherence, alerts, and check-in history.

### D. Senior Voice & Camera Multimodal Capture
- **Voice:** [`public/js/voice-vital-capture.js`](file:///Users/marcsantiago/Herd/silvercare/public/js/voice-vital-capture.js) uses Web Speech API to parse spoken vital readings ("Blood pressure is 120 over 80", "Sugar 110", "I took my Aspirin") via [`VoiceVitalParserService`](file:///Users/marcsantiago/Herd/silvercare/app/Services/VoiceVitalParserService.php).
- **Camera/OCR:** [`PrescriptionCaptureService`](file:///Users/marcsantiago/Herd/silvercare/app/Services/PrescriptionCaptureService.php) processes prescription bottle photos and vital monitor screen shots with human confirmation dialogs.

### E. Realtime & Offline Reconciliation (`OfflineReconciliationService`)
- WebSockets live sync over Reverb (`DoseConfirmedEvent`, `CheckinReceivedEvent`, `AlertStatusUpdated`).
- Offline mutation queue ([`public/js/offline-sync-queue.js`](file:///Users/marcsantiago/Herd/silvercare/public/js/offline-sync-queue.js)) buffers actions when network is disconnected and auto-flushes on reconnect to `POST /api/offline/sync` with idempotency.

### F. Telemetry Health Monitoring (`TelemetryMonitorService`)
- Logs third-party integration sync attempts in `sync_telemetry_logs`.
- Detects expired Google Fit OAuth tokens or stale integrations (>72h) and alerts caregivers via `telemetry:monitor-integrations` daily at 04:00.

---

## 3. Complete File Map

### Migrations (Consolidated):
- `database/migrations/2026_08_24_190000_sprint1_medication_correctness_kernel.php`
- `database/migrations/2026_08_24_200000_sprint2_clinical_alert_center.php`
- `database/migrations/2026_08_25_100000_sprint3_care_checkins_and_insights.php`
- `database/migrations/2026_08_25_200000_sprint4_multimodal_capture_sessions.php`
- `database/migrations/2026_08_27_100000_sprint5_offline_reconciliation.php`
- `database/migrations/2026_08_27_200000_sprint6_integration_telemetry.php`

### Models:
- `app/Models/DoseInstance.php`
- `app/Models/PrescriptionRevision.php`
- `app/Models/Alert.php`
- `app/Models/AlertDelivery.php`
- `app/Models/PatientAlertThreshold.php`
- `app/Models/CareCheckin.php`
- `app/Models/CaptureSession.php`
- `app/Models/OfflineSyncLog.php`
- `app/Models/SyncTelemetryLog.php`
- `app/Models/UserProfile.php`
- `app/Models/Medication.php`
- `app/Models/GoogleFitToken.php`

### Services:
- `app/Services/DoseAdministrationService.php`
- `app/Services/DoseInstanceGeneratorService.php`
- `app/Services/ClinicalRulesService.php`
- `app/Services/AlertDeliveryService.php`
- `app/Services/CareCheckinService.php`
- `app/Services/ClinicalInsightService.php`
- `app/Services/VoiceVitalParserService.php`
- `app/Services/PrescriptionCaptureService.php`
- `app/Services/OfflineReconciliationService.php`
- `app/Services/TelemetryMonitorService.php`

### Controllers:
- `app/Http/Controllers/DoseInstanceController.php`
- `app/Http/Controllers/AlertController.php`
- `app/Http/Controllers/CareCheckinController.php`
- `app/Http/Controllers/PatientThresholdController.php`
- `app/Http/Controllers/VoiceCaptureController.php`
- `app/Http/Controllers/CaptureSessionController.php`
- `app/Http/Controllers/OfflineSyncController.php`
- `app/Http/Controllers/SosController.php`
- `app/Http/Controllers/HealthMetricController.php`
- `app/Http/Controllers/CaregiverDashboardController.php`
- `app/Http/Controllers/ElderlyDashboardController.php`
- `app/Http/Controllers/CaregiverAnalyticsController.php`

### Realtime Broadcast Events:
- `app/Events/CriticalAlertFired.php`
- `app/Events/DoseConfirmedEvent.php`
- `app/Events/CheckinReceivedEvent.php`
- `app/Events/AlertStatusUpdated.php`

### Console Commands (`routes/console.php`):
- `doses:generate-instances --days=7` (Daily 00:05)
- `alerts:escalate` (Every 5 minutes)
- `medications:check-stock` (Daily 09:00)
- `checkins:check-missed` (Daily 18:30)
- `appointments:send-reminders` (Every 15 minutes)
- `reports:send-weekly-health` (Weekly Mondays 07:30)
- `checklists:recycle-recurring` (Daily 00:01)
- `app:sync-google-fit` (Every 2 hours)
- `telemetry:monitor-integrations` (Daily 04:00)

### Frontend Assets:
- `public/js/voice-vital-capture.js`
- `public/js/offline-sync-queue.js`
- `public/js/caregiver-realtime.js`
- `resources/css/app.css`

### Automated Test Suite:
Run with: `php artisan test` (138 passing tests, 435 assertions, 0 failures).

---

## 4. Prompt for AI Agent

Copy and paste this prompt when handing over the project to another AI agent:

```text
You are working on SilverCare, an enterprise-grade Laravel 12 elderly caregiving & health monitoring system.
The codebase has completed an end-to-end architectural overhaul across Sprints 0 through 6, fully verified with 138 passing PHPUnit/Pest tests (435 assertions).

Key architectural guidelines to follow:
1. Medication Adherence: Always use DoseAdministrationService for confirming/undoing doses. Never mutate medication_logs or dose status directly. Dose instances are pre-generated on a rolling 7-day horizon.
2. Clinical Alerts: Always use ClinicalRulesService and AlertDeliveryService when evaluating vitals or emergency actions. Alerts broadcast in realtime over Laravel Reverb on private-caregiver.{id}.
3. Daily Check-ins: Use CareCheckinService for recording senior check-ins and managing cutoff checks (18:30 daily).
4. Multimodal Inputs: Spoken vital voice inputs flow through VoiceVitalParserService (/api/voice/parse & confirm). Photo OCR flows through PrescriptionCaptureService (/api/capture/upload & confirm).
5. Offline Reconciliation: Client mutations must pass client_mutation_id through OfflineReconciliationService (/api/offline/sync) for atomic, idempotent synchronization.
6. Scheduled Daemons: All console commands are registered in routes/console.php.
7. High-Contrast & Accessibility: Adhere to WCAG 2.1 AA standards (minimum 48px touch targets, contrast ratios >= 4.5:1, :focus-visible outlines, aria labels).

Consult SILVERCARE_AI_AGENT_HANDOVER.md and SILVERCARE_ARCHITECTURAL_AUDIT.md in the project root for detailed documentation.
```

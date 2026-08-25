# SilverCare Architectural Audit & High-Impact Expansion Roadmap

**Audit date:** 2026-08-24  
**Scope:** Laravel/PostgreSQL backend, Blade/Alpine/PWA frontend, scheduling, notification and emergency workflows, Google Fit, and Gemini function calling.  
**Constraint applied:** Recommendations use only Laravel/PostgreSQL/Reverb, SMTP, browser Web APIs, VAPID web push, and Gemini free-tier capabilities with graceful fallbacks. No paid SMS provider or dedicated hardware gateway is required.

## Executive outcome

SilverCare has a strong product foundation, but it is **not yet safe to position as a live clinical-alerting system**. The central gap is that medication, emergency, and notification workflows are implemented as UI actions and database records—not as a reliable, auditable safety system.

The normal medication route already uses a transaction and pessimistic lock, and Google Fit tokens are encrypted at rest. Those are good foundations. The system must now move critical correctness from UI behavior and convention into database-enforced workflow rules.

No code changes were made as part of this audit.

### Validation performed

- `npm run build` completed successfully. Vite warned that the main JavaScript bundle exceeds 500 kB after minification.
- `php artisan test` produced **94 passing tests and 3 existing failures**:
  - missing `profile.completion.skip` route;
  - two care-link tests whose expected redirect contract does not match the current JSON/422 responses.
- Focused medication, SOS, stock-alert, and appointment-reminder tests pass, but current coverage does not protect the production risks described below, especially concurrent first-dose insertion, AI dose actions, and scheduled missed-dose detection.

## Architectural and Clinical Vulnerability Matrix

| Priority | Production edge case | Evidence and consequence | Required correction |
|---|---|---|---|
| **P0** | **Missed doses are not detected by the scheduler.** | The reminder command reads `scheduled_times`, but the actual medication model uses `times_of_day`; its loop is therefore empty. It also does not apply schedule day, start/end-date, or specific-date rules. See [SendDailyReminders.php](app/Console/Commands/SendDailyReminders.php#L143-L145) and [Medication.php](app/Models/Medication.php#L68-L105). | Generate real dose instances from the normalized schedule; alert only for an overdue instance. Run the checker 24/7, not only 08:00–21:00. |
| **P0** | **Duplicate-dose protection is not enforced by PostgreSQL.** | The controller locks an existing row, but there is no unique constraint for `(elderly_id, medication_id, scheduled_time)`. Two simultaneous first inserts can both succeed and decrement stock twice. See [ElderlyDashboardController.php](app/Http/Controllers/ElderlyDashboardController.php#L210-L246) and [create_medication_logs_table.php](database/migrations/2025_11_17_133556_create_medication_logs_table.php#L14-L26). | Add a database uniqueness constraint and use one idempotent dose-administration service for every pathway. |
| **P0** | **AI medication logging bypasses dose-window, schedule, inventory, and concurrency safeguards.** | Gemini’s function call writes `MedicationLog` directly; a supplied HH:mm can be unscheduled, and this path neither validates the dose window nor decrements stock. See [AiAssistantService.php](app/Services/AiAssistantService.php#L582-L621) and [AiAssistantService.php](app/Services/AiAssistantService.php#L1193-L1244). | AI should request a deterministic confirmation action, then call the same protected dose service as the senior UI. Never let an LLM directly mutate a clinical event. |
| **P0** | **Critical vitals and AI-detected emergencies may not reach a caregiver.** | Vitals are stored with no threshold-triggered alert workflow. Thresholds currently drive display color only. See [HealthMetricController.php](app/Http/Controllers/HealthMetricController.php#L54-L78) and [vitals.php](config/vitals.php#L16-L21). Worse: AI emergency alerts use severity `critical`, but the PostgreSQL enum does not permit it—so the write can fail and is swallowed. See [AiAssistantService.php](app/Services/AiAssistantService.php#L692-L708) and [create_notifications_table.php](database/migrations/2025_11_17_133617_create_notifications_table.php#L14-L31). | Introduce an alert/escalation domain model, caregiver recipients, acknowledgement, browser push/email delivery, and a visible “call emergency services” instruction. |
| **P1** | **Medication eligibility, time, and offline state can drift from reality.** | The main dashboard includes every active medication without enforcing `start_date`/`end_date`; an expired prescription can remain takeable. See [ElderlyDashboardService.php](app/Services/ElderlyDashboardService.php#L80-L97). Offline actions are optimistically shown as taken, then discarded on a later 4xx response without reconciling the medication card. See [offline-queue.js](resources/js/utils/offline-queue.js#L104-L145), [offline-queue.js](resources/js/utils/offline-queue.js#L187-L212), and [medication-tracker.js](resources/js/components/medication-tracker.js#L93-L116). | Store a patient IANA timezone, persist server-confirmed dose state, and show “pending sync” rather than “taken” until confirmed. |
| **P1** | **Healthcare privacy and release readiness are incomplete.** | Public debug routes disclose database/runtime configuration. See [web.php](routes/web.php#L30-L66). The service worker caches authenticated navigation responses, risking residual PHI on a shared device. See [sw.js](public/sw.js#L55-L68). | Remove debug routes, cache only static assets, clear client state on logout, and make a green test suite a deployment gate. |

### Additional observations

- Reverb/Echo is configured, but the codebase contains no `ShouldBroadcast` events or Echo channel subscriptions for notifications. `NotificationService` writes database rows only. See [echo.js](resources/js/echo.js#L6-L20), [NotificationService.php](app/Services/NotificationService.php#L14-L37), and [channels.php](routes/channels.php#L1-L7).
- SOS creates an in-app notification and attempts synchronous email, but it does not create a recipient-specific, acknowledged emergency escalation. A caregiver must open the dashboard to see the in-app record. See [SosController.php](app/Http/Controllers/SosController.php#L43-L90).
- The dose window allows a late administration indefinitely (`can_take` is true after the grace period), without medication-specific minimum intervals or escalation rules. See [MedicationWindowService.php](app/Services/MedicationWindowService.php#L28-L47).
- The main application timezone is `Asia/Singapore`; individual patient timezones are not modeled. Browser-local time is also used for parts of the medication UI. See [app.php](config/app.php#L57-L69) and [medication-tracker.js](resources/js/components/medication-tracker.js#L159-L174).
- The repository has a voice vital-recorder component, but it is not imported or registered in the main application bundle. If it were wired in as written, its speech-result and blood-pressure assignments require correction. See [vital-recorder.js](resources/js/components/vital-recorder.js#L94-L145) and [app.js](resources/js/app.js#L13-L24).
- The architecture reference has drifted from the implementation. For example, it describes medication-log statuses not present in the actual migration. Source code and migrations should be treated as authoritative until documentation is reconciled.

## High-Impact Feature Additions

### 1. Dose Administration Safety Kernel

**Clinical impact:** Critical  
**Implementation effort:** Medium–Large

#### Clinical problem and real-world use case

“Taken” must mean one specific prescribed dose was confirmed, not a mutable toggle, an optimistic offline tap, or an LLM guess. A senior taking a late morning dose shortly before an afternoon dose needs a clear, clinically governed workflow rather than two unrelated check marks.

#### Technical architecture and data flow

- Add `medication_dose_instances` with `elderly_id`, `medication_id`, `scheduled_at_utc`, `local_date`, `timezone`, `state` (`pending`, `taken`, `missed`, `held`, `skipped`), `taken_at`, `source`, `idempotency_key`, `actor_id`, and `version`.
- Enforce a database unique constraint on each scheduled dose instance and an idempotency constraint on patient action requests.
- Generate the next 72 hours of instances whenever a caregiver creates or revises a medication schedule. Never mutate historical instances after a prescription edit; preserve the prior prescription revision and audit history.
- Introduce `DoseAdministrationService` as the only writer. It is used by the senior REST endpoint, offline replay, AI function calling, caregiver correction, missed-dose scheduler, inventory, and notifications.
- Use an endpoint such as `POST /api/doses/{doseInstance}/confirm` with an idempotency header. PostgreSQL is the final arbiter through `INSERT ... ON CONFLICT` or a uniqueness constraint plus transaction.
- Treat “late” as a clinical review state. Medication-specific minimum intervals or caregiver escalation rules must decide whether an overdue dose remains administrable.

#### Demonstration and presentation impact

The caregiver can see an exact timeline: “Taken 08:07,” “Pending device sync,” “Missed,” or “Held by caregiver,” instead of a percentage that hides clinically important timing.

### 2. Escalating Clinical Alert Center

**Clinical impact:** Critical  
**Implementation effort:** Medium

#### Clinical problem and real-world use case

A critical BP reading, SOS event, or repeated missed dose cannot depend on a caregiver actively watching a dashboard.

#### Technical architecture and data flow

- Add `alerts` and `alert_deliveries`: `severity`, `source_type`, `source_id`, `recipient_profile_id`, `state`, `acknowledged_at`, `escalate_at`, `resolved_at`, and delivery result/error fields.
- Add a deterministic `ClinicalRulesService` invoked by vital recording, SOS, overdue-dose detection, and AI emergency intent. Gemini may classify conversational context, but a rules service must create the alert.
- Add patient-specific thresholds and escalation plans approved by the caregiver/clinical owner; generic display thresholds must not become undisclosed treatment rules.
- Send private Reverb events to linked caregivers, VAPID browser push when permission is granted, and queued SMTP email as fallback. All are free-tier/native approaches.
- Add `POST /caregiver/alerts/{alert}/acknowledge` and `POST /caregiver/alerts/{alert}/resolve`. Re-escalate when acknowledgement is absent at the configured deadline.
- Clearly display “Call local emergency services now” during a critical event. SilverCare must not claim it can contact emergency services or guarantee delivery.

#### Demonstration and presentation impact

A red “Needs acknowledgement” card, delivery states, and an acknowledgement timeline make the caregiver portal operational rather than dashboard-only.

### 3. Caregiver Risk Briefing and Data-Freshness Intelligence

**Clinical impact:** High  
**Implementation effort:** Medium

#### Clinical problem and real-world use case

A seven-day average can hide one critical reading, repeated late evening doses, medication depletion, or a silent loss of telemetry.

#### Technical architecture and data flow

- Create `ClinicalInsightService` that computes deterministic facts: threshold crossings, consecutive missed doses, dose-timing deviation, medication run-out date, manual-versus-device provenance, and “last meaningful reading.”
- Persist optional `clinical_insights` snapshots for auditability, or calculate them from dose instances and metrics.
- Expose `GET /caregiver/patients/{patient}/clinical-summary` and an alert queue endpoint.
- Gemini may summarize returned facts in natural language but may not invent risk, thresholds, diagnoses, or remediation decisions.
- Include `as_of`, source, data freshness, and confidence/provenance in every chart, insight, and PDF handoff.

#### Demonstration and presentation impact

Replace generic statistics with a prioritized “Act today” list, for example: “No BP reading for 36 h,” “Two consecutive metformin doses missed,” or “Stock ends Friday.”

### 4. Senior-Assisted Voice and Camera Capture

**Clinical impact:** High  
**Implementation effort:** Medium

#### Clinical problem and real-world use case

Typing `120/80`, reading tiny labels, and selecting the correct medicine are high-friction tasks for low vision, tremor, and cognitive load.

#### Technical architecture and data flow

- Use browser `SpeechRecognition` and `SpeechSynthesis` for narrow, deterministic phrases such as “120 over 80.” Display parsed values and require explicit confirmation.
- Use `getUserMedia` and `BarcodeDetector` to identify a medication container. Optionally send a consented image to Gemini Vision within its free-tier quota to extract label text.
- Create a short-lived `capture_sessions` record with an extracted label, candidate medication IDs, confirmation result, expiry, and optional evidence hash. Avoid retaining raw images by default.
- Match candidates only against caregiver-maintained medication records. A scan or voice transcript must never automatically log a dose.
- Provide a large, high-contrast manual fallback when browser APIs are unsupported.

#### Demonstration and presentation impact

“Say it, see it, confirm it” is a concrete accessibility improvement that is easy to demonstrate without any purchased hardware.

### 5. Offline-Safe Care Workflow

**Clinical impact:** High  
**Implementation effort:** Medium

#### Clinical problem and real-world use case

Connectivity loss must not convert a tentative tap into a false clinical fact.

#### Technical architecture and data flow

- Generate a UUID idempotency key for each client intent.
- Store the intent, dose-instance version, timestamp, and UI state in IndexedDB.
- Distinguish `pending sync`, `confirmed`, `rejected—review needed`, and `conflict`; do not show a queued action as definitively taken.
- On reconnect, return canonical dose state from the server. Retain conflicts for review instead of silently deleting an invalid action.
- Use Background Sync when available, plus the current foreground flush fallback. Cache immutable assets only; do not cache authenticated Blade pages.

#### Demonstration and presentation impact

Seniors see an honest amber “Waiting to sync” badge, while caregivers can distinguish a real missed dose from a disconnected phone.

### 6. Daily “I’m Okay” Check-in and Non-response Escalation

**Clinical impact:** High  
**Implementation effort:** Small–Medium

#### Clinical problem and real-world use case

Emergencies are often first detected by absence of response, not a deliberate SOS tap.

#### Technical architecture and data flow

- Add `care_checkins` and schedule one or two configurable prompts each day.
- A large one-tap or voice-assisted “I’m okay” response records a check-in with timestamp and source.
- A missed check-in creates a caregiver alert and follows the normal acknowledgement/escalation workflow.
- Do not automatically declare an emergency; absence is a signal for caregiver follow-up, not a diagnosis.

#### Demonstration and presentation impact

A friendly, low-cognitive-load daily ritual gives caregivers a meaningful safety signal between vital readings.

## Phased Implementation Roadmap

### Phase 1 — Immediate Safety and Critical Wins

#### Sprint 1: Medication correctness

1. Remove public debug routes and stop service-worker caching of authenticated pages.
2. Correct the missed-dose command to use normalized medication schedules, start/end dates, weekly/specific-date applicability, and 24-hour execution.
3. Add dose instances, database uniqueness, idempotency keys, timezone fields, and PostgreSQL concurrency tests.
4. Build `DoseAdministrationService`; route the senior UI, AI, offline replay, inventory, and notification paths through it.
5. Disable direct AI medication writes until the shared service is in place.

#### Sprint 2: Critical alert path

1. Add database support for `critical` severity and a dedicated alert/delivery model.
2. Trigger deterministic vital threshold alerts, SOS alerts, missed-dose escalation, and AI emergency-intent alerts.
3. Add caregiver recipients, acknowledgement, queued SMTP, browser VAPID push, and a visible emergency-call fallback.
4. Add deployment gates for scheduler heartbeat, failed delivery count, and a fully passing test suite.
5. Add tests for duplicate submissions, first-insert races, after-end-date doses, schedule edits, timezone boundaries, AI actions, and unacknowledged alerts.

### Phase 2 — Intelligence and Multimodal Support

1. Deliver deterministic caregiver risk briefings, data freshness, medication run-out, and consecutive-miss insights.
2. Add patient-specific thresholds and documented escalation policies.
3. Repair and register voice vital capture; add confirmation-first medication camera/barcode workflows.
4. Deliver daily check-ins and non-response alerts.
5. Enrich PDF handoffs with source, timestamp, dose lateness, alert history, and “data current as of” metadata.

### Phase 3 — Telemetry and Polish

1. Use Reverb for real private caregiver alert events and PWA browser push for unattended caregivers.
2. Implement full offline reconciliation and a caregiver-visible sync/conflict queue.
3. Add operational telemetry: scheduler heartbeat, last successful Google Fit sync, alert delivery/acknowledgement latency, and failed mail/push count.
4. Conduct senior accessibility QA at 200% zoom, keyboard-only use, reduced motion, large touch targets, color-independent states, and shared-device logout behavior.

## Non-negotiable design rule

Gemini should remain a supportive summarizer and conversational interface. All clinical-state changes, thresholds, medication administration decisions, and alert escalations must be deterministic Laravel/PostgreSQL workflows with a durable audit trail.

SilverCare should be presented as a care-coordination and decision-support tool, not an emergency response service or autonomous clinical device.

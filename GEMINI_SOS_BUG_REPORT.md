# SOS Feature Bug Report - Need Expert Help 🚨

## Context
I'm building a Flutter app called SilverCare for elderly care. The SOS feature mostly works but has 2 critical bugs that are blocking deployment.

## Bug #1: Elder Stuck on Loading Screen After SOS Trigger ❌

### What Should Happen:
1. Elder taps "Activate SOS" button
2. Loading dialog appears
3. SOS alert created in Firestore
4. Loading dialog closes
5. Success message shown: "🚨 Emergency SOS sent to your caregiver!"

### What Actually Happens:
- Loading dialog appears but NEVER closes
- Elder stuck looking at purple spinning circle forever
- No success message shown
- BUT the SOS alert IS created successfully in Firestore (checked database)

### Code Location:
`lib/screens/home_screen.dart` - Method `_showEmergencySosDialog()` around line 1540

### What I Tried:
- Added `rootNavigator: true` to `Navigator.pop()` in success handler ✅
- Added `rootNavigator: true` to error handler ✅
- Added `context.mounted` checks ✅
- Still doesn't work!

### Suspect:
The loading dialog might be in a different context tree. Maybe `showDialog()` needs different parameters?

---

## Bug #2: Vitals Not Displaying on SOS Alert Screen ❌

### What Should Happen:
When caregiver receives SOS alert, they should see:
- Elder's location on map ✅ WORKS
- Elder's name and timestamp ✅ WORKS  
- **Elder's vital signs** (heart rate, blood pressure, sugar, temperature) ❌ BROKEN

### What Actually Happens:
- The vitals card doesn't appear at all
- Code shows: `if (_alert!.vitalsSummary?.hasData ?? false) _buildVitalsCard()`
- This condition is FALSE even when vitals exist in database

### Code Flow:
1. `lib/services/sos_service.dart` - `_getLatestVitals()` fetches from Firestore
2. Creates `VitalsSummary` object
3. Saved to Firestore in SOS alert document
4. `lib/screens/caregiver_screens/sos_alert_screen.dart` - Should display vitals

### Database Structure (Firestore):

**health_data collection:**
```json
{
  "elderlyId": "user123",
  "type": "blood_pressure",
  "value": 120.0,  // systolic
  "systolic": 120.0,
  "diastolic": 80.0,
  "measuredAt": "2025-11-22T10:30:00Z",
  "createdAt": "2025-11-22T10:30:00Z",
  "source": "manual"
}
```

**Note:** Blood pressure stores BOTH:
- `value` field = systolic
- Separate `systolic` and `diastolic` fields

### What I Tried:
1. Updated `HealthDataModel.fromDoc()` to read `systolic`/`diastolic` fields ✅
2. Added metadata building for blood pressure ✅
3. Changed query to use `userId` instead of `elderlyId` ✅
4. Added debug logging ⏳ (need to test)

### Suspect:
Either:
- The query isn't finding data (wrong field name?)
- The data is found but `VitalsSummary.hasData` returns false
- The data isn't being saved to SOS alert document correctly

---

## Key Files to Review:

### 1. Health Data Model
**File:** `lib/models/health_data_model.dart`
- Has `metadata` field
- `fromDoc()` should read `systolic`/`diastolic` for blood pressure

### 2. SOS Service
**File:** `lib/services/sos_service.dart`
- `triggerSOSAlert()` - Main function that creates alert
- `_getLatestVitals()` - Queries Firestore for health data
- Uses: `where('elderlyId', isEqualTo: userId)` 

### 3. Home Screen (Elder Side)
**File:** `lib/screens/home_screen.dart`
- `_showEmergencySosDialog()` - Shows dialog and calls SOS service
- Loading dialog that won't close

### 4. SOS Alert Screen (Caregiver Side)
**File:** `lib/screens/caregiver_screens/sos_alert_screen.dart`
- Displays alert details
- Line 132: `if (_alert!.vitalsSummary?.hasData ?? false) _buildVitalsCard()`

### 5. SOS Alert Model
**File:** `lib/models/sos_alert_model.dart`
- `VitalsSummary` class with `hasData` getter
- `toMap()` and `fromMap()` for Firestore

---

## Test Data Available:
- Real elderly account with recent vitals (last 24 hours):
  - Blood pressure: 120/80 mmHg
  - Heart rate: 75 bpm
  - Sugar level: 95 mg/dL
  - Temperature: 36.8°C
- All stored in Firestore `health_data` collection
- Confirmed via Firestore console

---

## What I Need:

### For Bug #1 (Loading Dialog):
Please review the dialog navigation code and tell me:
1. Why won't the loading dialog close?
2. Should I use `Navigator.of(context, rootNavigator: true).pop()` or something else?
3. Is there a better pattern for showing loading during async operations?

### For Bug #2 (Vitals Display):
Please review the data flow and tell me:
1. Is the Firestore query correct? Should it be `userId` or `elderlyId`?
2. Is `HealthDataModel.fromDoc()` reading blood pressure data correctly?
3. Why would `VitalsSummary.hasData` return false when data exists?
4. Add console.log/print statements to trace where data is lost

---

## Expected Output:

Please provide:
1. ✅ Explanation of root cause for each bug
2. ✅ Code fixes with exact file locations
3. ✅ Any Firestore index requirements
4. ✅ Testing steps to verify fixes

---

## Additional Context:

- Flutter version: Latest stable
- Firebase: Cloud Firestore
- The SOS alert creation DOES work (alert appears in Firestore)
- The caregiver DOES receive the alert (alarm plays, screen opens)
- Just these 2 display issues remain

Thank you! This is blocking our student project deployment.

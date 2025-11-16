# 🐛 SOS Feature - Troubleshooting & Setup

## ✅ Cloud Functions Approach (Works Even When App Closed!)

**This SOS system uses Cloud Functions + FCM** for TRUE background notifications:
- Elder presses SOS → Creates alert in Firestore
- Cloud Function detects new alert → Sends FCM push notification
- Caregiver receives notification **EVEN IF APP IS COMPLETELY CLOSED**
- Tapping notification opens app → Shows SOSAlertScreen with alarm

**NOTE:** Requires Firebase Blaze (pay-as-you-go) plan, but you'll likely stay in the free tier!

---

## Why the Loading Circle Gets Stuck

The loading indicator gets stuck because of one or more of these issues:

### 1. ❌ No Caregiver Assigned (Most Common)
**Problem:** The elderly user doesn't have a `caregiverId` in Firestore.

**How to Fix:**
1. Go to Firebase Console > Firestore
2. Navigate to `elderly` collection
3. Find your elderly user's document
4. Make sure the `caregiverId` field contains the caregiver's document ID (from `caregivers` collection)

**Manual Assignment in Firestore:**
```javascript
// In elderly document:
{
  "userId": "elderly_user_uid",
  "username": "John Doe",
  "caregiverId": "caregiver_document_id",  // ← This must be set!
  // ... other fields
}

// The caregiverId should match a document ID in the caregivers collection
```

### 2. 📍 Location Permissions Not Granted
**Problem:** App trying to get location but permission denied.

**How to Fix on Android:**
1. Open Settings > Apps > SilverCare
2. Permissions > Location
3. Select **"Allow all the time"** (not just "While using the app")

**Why "All the time"?** For emergency SOS, we need location even when app is in background.

### 3. 🌐 Testing on Web/Chrome
**Problem:** Many features don't work on web:
- ❌ FCM background notifications
- ❌ Location services (may work but limited)
- ❌ Alarm sound (flutter_ringtone_player is mobile-only)

**Solution:** **Always test SOS on real Android/iOS devices**, not web/emulator.

### 4. 🔥 Cloud Functions Not Deployed 
~~**Problem:** Notification won't send even after alert is created.~~

---

## ✅ Pre-Flight Checklist

Before testing SOS, verify:

### Firestore Setup:
- [ ] `elderly` document has `caregiverId` field populated
- [ ] `caregivers` document exists with matching ID
- [ ] ~~Both users have `fcmToken` in their `users` document~~ (NOT needed with SOSListenerService!)

### Permissions:
- [ ] Location permission: "Allow all the time"
- [ ] Notification permission: Enabled
- [ ] Battery optimization: Disabled for SilverCare (Android)

### Backend:
- [ ] ~~Firebase Cloud Functions deployed~~ (NOT needed anymore!)
- [ ] Firestore rules allow reading `sos_alerts` collection (for caregiver)
- [ ] Firestore rules allow creating `sos_alerts` collection (for elderly)

### Device:
- [ ] Testing on **real Android/iOS device** (not web/emulator)
- [ ] Internet connection active
- [ ] GPS/Location Services enabled

## 🧪 Testing Steps

### Step 1: Set Up Data in Firestore

1. **Create/Verify Elderly Document:**
```javascript
// Path: elderly/{elderlyId}
{
  "userId": "elderly_firebase_auth_uid",
  "username": "Elder Name",
  "phoneNumber": "+1234567890",
  "sex": "Male",
  "caregiverId": "caregiver_doc_id",  // ← CRITICAL!
  "profileCompleted": true,
  "createdAt": <timestamp>
}
```

2. **Create/Verify Caregiver Document:**
```javascript
// Path: caregivers/{caregiverId}
{
  "userId": "caregiver_firebase_auth_uid",
  "email": "caregiver@email.com",
  "fullName": "Caregiver Name",
  "elderlyId": "elderly_doc_id",  // Should match elderly's ID
  "relationship": "Child",
  "createdAt": <timestamp>
}
```

3. **Verify Users Have FCM Tokens:**
```javascript
// Path: users/{userId}
{
  "email": "user@email.com",
  "fullName": "User Name",
  "userType": "elderly", // or "caregiver"
  "fcmToken": "FCM_TOKEN_STRING",  // Auto-set on login
  "fcmTokenUpdatedAt": <timestamp>
}
```

### Step 2: Test on Elder Device (Android Tablet)

1. Login as **elderly user**
2. Go to Home screen
3. Check Flutter logs for initialization:
```
🔔 Initializing FCM Handler Service...
✅ FCM Handler initialized
✅ FCM token saved for user: {userId}
```

4. Press SOS button
5. Confirm the alert
6. Watch the logs:
```
🚨 Triggering SOS alert for user: {userId}
📋 Fetching elderly profile...
✅ Elderly profile found: {elderlyId}
✅ Caregiver assigned: {caregiverId}
📋 Fetching user name...
✅ User name: {name}
📍 Getting current location...
✅ Location obtained: lat, lng
📊 Fetching latest vitals...
💾 Creating SOS alert in Firestore...
✅ SOS alert created: {alertId}
📨 Sending notification to caregiver...
✅ FCM notification queued for caregiver
```

7. If successful, you'll see green success message

### Step 3: Check Firestore

1. Go to Firebase Console
2. Check `sos_alerts` collection - should have new document
3. ~~Check `fcm_notifications` collection~~ (Not used with SOSListenerService!)

### Step 4: Test on Caregiver Device

**NOTE:** The caregiver MUST be logged in and have the app open (foreground or background)!

1. Login as **caregiver user**
2. Keep app open or in background (don't force-close!)
3. Check logs for:
```
✅ Starting SOS listener for elderly: {elderlyId}
✅ SOS Listener active - will trigger on new alerts
```

4. When elder triggers SOS (from Step 2):
   - Caregiver's listener detects new alert within 1-2 seconds
   - Alarm sound plays automatically
   - Local notification appears
   - You'll see in logs:
```
🚨 NEW SOS ALERT DETECTED: {alertId}
🔔 Playing SOS alarm...
✅ Local notification shown for alert: {alertId}
```

## 🔍 Debugging Common Errors

### Error: "Elderly profile not found"
**Cause:** No document in `elderly` collection with matching `userId`

**Fix:**
1. Check if elderly document exists
2. Verify `userId` field matches Firebase Auth UID
3. Create elderly profile if missing

### Error: "No caregiver assigned"
**Cause:** `caregiverId` is null or empty in elderly document

**Fix:**
1. Update elderly document in Firestore
2. Set `caregiverId` to the caregiver's document ID from `caregivers` collection

### Error: "Location timeout"
**Cause:** GPS taking too long or permission denied

**Fix:**
1. Grant location permission ("Allow all the time")
2. Enable GPS in device settings
3. Test outdoors (GPS works better outside)
4. **Note:** SOS will still work without location!

### Error: "Caregiver has no FCM token"
**Cause:** Caregiver hasn't logged in yet, or FCM not initialized

**Fix:**
1. Have caregiver login at least once
2. Check `users/{caregiverUserId}/fcmToken` exists in Firestore
3. If missing, caregiver should logout and login again

### Notification not received on caregiver device:
**Possible Causes:**
1. Cloud Functions not deployed → Deploy with `firebase deploy --only functions`
2. FCM token missing → Have caregiver re-login
3. Notification permission denied → Enable in Settings
4. Battery saver killing app → Disable battery optimization for SilverCare
5. App force-stopped → Reopen app

## 📊 How to View Logs

### Flutter App Logs:
```bash
# If connected via USB
flutter logs

# Or in VS Code
# Debug Console tab when running app
```

### Firebase Functions Logs:
```bash
firebase functions:log

# Or in Firebase Console:
# Functions > Dashboard > View logs
```

### Firestore Data:
```
Firebase Console > Firestore Database > Collections
```

## 🎯 Quick Test Without Full Setup

If you just want to test that the button works:

1. **Comment out the caregiver check temporarily:**

In `lib/services/sos_service.dart`, find:
```dart
if (caregiverId == null || caregiverId.isEmpty) {
  throw Exception('No caregiver assigned...');
}
```

Change to:
```dart
// TEMPORARY: Skip caregiver check for testing
caregiverId = caregiverId ?? 'test_caregiver';
```

2. This will let you create the alert even without a real caregiver
3. You can verify the alert is created in Firestore
4. **Remember to remove this after testing!**

## ✨ Next Steps

Once you've verified:
1. ✅ Alert creates successfully in Firestore
2. ✅ No errors in Flutter logs
3. ✅ FCM notification document created

Then you know the elder side works! The caregiver notification depends on:
- Cloud Functions being deployed
- Caregiver having valid FCM token
- Caregiver device having notifications enabled

**Both accounts do NOT need to be logged in simultaneously!** The notification will be delivered even if the caregiver app is closed, thanks to FCM.

---

**Pro Tip:** Start simple - just verify the SOS alert creates in Firestore first. Then layer on the notification functionality.

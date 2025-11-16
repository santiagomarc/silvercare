# 🚨 SOS Emergency Alert System - Setup Guide

## Overview
The SOS Emergency Alert System allows elderly users to trigger emergency alerts that notify their assigned caregiver with:
- Real-time location (GPS coordinates + address)
- Latest vital signs summary
- High-priority push notifications with alarm sound
- Interactive map display for caregiver

## 🏗️ Architecture

### Elder Flow:
1. Elder presses SOS button in home screen
2. App collects: location, vitals, user info
3. Creates SOS alert in Firestore (`sos_alerts` collection)
4. Queues FCM notification in Firestore (`fcm_notifications` collection)
5. Firebase Cloud Function processes queue and sends FCM to caregiver

### Caregiver Flow:
1. Receives high-priority FCM notification
2. Device plays loud alarm sound (loops until stopped)
3. Notification shows elder name, location, and SOS details
4. Tapping notification opens SOS Alert Screen with:
   - Google Map showing elder's location
   - Elder's vitals summary
   - Options to acknowledge/resolve alert

## 📦 Components Created

### Models
- `lib/models/sos_alert_model.dart` - SOS alert data structure with location and vitals

### Services
- `lib/services/sos_service.dart` - Core SOS logic (trigger alerts, FCM tokens, Firestore ops)
- `lib/services/fcm_handler_service.dart` - FCM message handling, alarm playback

### Screens
- `lib/screens/sos_alert_screen.dart` - Caregiver view of SOS alert with map

### Backend
- `functions/index.js` - Firebase Cloud Functions for sending FCM notifications

## 🔧 Setup Instructions

### 1. Install Flutter Dependencies

```bash
cd silvercare
flutter pub get
```

### 2. Google Maps API Setup

#### Android:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Enable **Maps SDK for Android**
3. Create API key (restrict to Android apps)
4. Add to `android/app/src/main/AndroidManifest.xml`:

```xml
<application>
  <meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="YOUR_ANDROID_API_KEY_HERE"/>
</application>
```

#### iOS:
1. Enable **Maps SDK for iOS**
2. Create API key (restrict to iOS apps)
3. Add to `ios/Runner/AppDelegate.swift`:

```swift
import GoogleMaps

GMSServices.provideAPIKey("YOUR_IOS_API_KEY_HERE")
```

### 3. Deploy Firebase Cloud Functions

```bash
cd functions
npm install
firebase deploy --only functions
```

**Required Functions:**
- `sendSOSNotification` - Sends FCM to caregivers
- `cleanupOldAlerts` - Daily cleanup of old alerts
- `cleanupProcessedNotifications` - Daily cleanup of notification queue

### 4. Firebase Firestore Security Rules

Add these rules to protect SOS data:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    
    // SOS Alerts - elderly can create, caregivers can read/update
    match /sos_alerts/{alertId} {
      allow create: if request.auth != null && 
        request.resource.data.elderId != null;
      allow read: if request.auth != null;
      allow update: if request.auth != null && 
        (request.resource.data.status == 'acknowledged' || 
         request.resource.data.status == 'resolved');
      allow delete: if false; // Only Cloud Functions can delete
    }
    
    // FCM Notifications - only app and Cloud Functions
    match /fcm_notifications/{notificationId} {
      allow create: if request.auth != null;
      allow read, update, delete: if false; // Only Cloud Functions
    }
  }
}
```

### 5. Firestore Indexes

Create these indexes for better performance:

**Index 1 - Active Alerts:**
- Collection: `sos_alerts`
- Fields: `status` (Ascending), `timestamp` (Descending)

**Index 2 - Cleanup Old Alerts:**
- Collection: `sos_alerts`
- Fields: `status` (Ascending), `resolvedAt` (Ascending)

**Index 3 - Cleanup Notifications:**
- Collection: `fcm_notifications`
- Fields: `processed` (Ascending), `processedAt` (Ascending)

### 6. Test Location Permissions

Before testing, ensure location permissions are granted:

**Android:**
- Settings > Apps > SilverCare > Permissions > Location > Allow all the time

**iOS:**
- Settings > SilverCare > Location > Always

## 🧪 Testing the SOS Feature

### Test as Elder:
1. Login as elderly user
2. Ensure you have a caregiver assigned in your profile
3. Go to Home screen
4. Tap the red SOS button
5. Confirm the alert
6. Check that:
   - Loading indicator shows
   - Success message appears
   - Alert is created in Firestore

### Test as Caregiver:
1. Login as caregiver on a different device
2. When elder triggers SOS:
   - You should receive notification immediately
   - Alarm sound plays automatically
   - Notification shows elder's name and location
3. Tap notification to open SOS Alert Screen
4. Verify:
   - Map shows elder's location with red marker
   - Elder's name and timestamp display
   - Vitals summary shows (if available)
   - Buttons work: Stop Alarm, Acknowledge, Resolve

## 📱 FCM Token Management

FCM tokens are automatically:
- Generated on app launch
- Saved to Firestore (`users.fcmToken`)
- Refreshed when expired
- Updated on each login

## 🔔 Notification Channels (Android)

The app creates a high-priority notification channel:
- **Channel ID:** `sos_alert_channel`
- **Name:** SOS Emergency Alerts
- **Importance:** MAX
- **Sound:** Default alarm sound
- **Vibration:** Custom pattern
- **LED:** Red color
- **Full Screen Intent:** Yes (wakes screen)

## 🗺️ Location Accuracy

The SOS system uses:
- `LocationAccuracy.high` for precise coordinates
- Reverse geocoding to get human-readable address
- Accuracy metadata stored with each alert

## 📊 Firestore Collections

### `sos_alerts`
```javascript
{
  elderId: string,
  elderName: string,
  timestamp: timestamp,
  location: {
    latitude: number,
    longitude: number,
    address: string,
    accuracy: number
  },
  vitalsSummary: {
    heartRate: number,
    bloodPressureSystolic: number,
    bloodPressureDiastolic: number,
    sugarLevel: number,
    temperature: number,
    lastUpdated: timestamp
  },
  alertType: 'sos_alert',
  status: 'active' | 'acknowledged' | 'resolved',
  caregiverId: string,
  acknowledgedAt: timestamp,
  resolvedAt: timestamp
}
```

### `fcm_notifications`
```javascript
{
  to: string, // FCM token
  notification: {
    title: string,
    body: string
  },
  data: {
    alertId: string,
    alertType: 'sos_alert',
    elderName: string,
    latitude: string,
    longitude: string,
    // ... other data
  },
  processed: boolean,
  processedAt: timestamp,
  createdAt: timestamp
}
```

## 🐛 Troubleshooting

### Notification not received:
- Check if FCM token is saved in Firestore
- Verify Firebase Cloud Functions are deployed
- Check caregiver has notification permissions enabled
- Review Firebase Functions logs

### Location not working:
- Grant location permissions in device settings
- Enable GPS/Location Services
- Test on real device (emulator GPS may be unreliable)

### Alarm not playing:
- Check device is not in silent/DND mode
- Verify notification permissions granted
- Test on real device (emulator audio may not work)

### Map not showing:
- Verify Google Maps API key is configured
- Check API key restrictions
- Ensure Maps SDK is enabled in Google Cloud Console

## 🚀 Next Steps

### Enhancements:
1. **Multiple Caregivers:** Extend to support multiple caregivers per elder
2. **SOS History:** Add screen to view past SOS alerts
3. **Call Feature:** Add direct call button in SOS screen
4. **Custom Sounds:** Allow caregivers to set custom alarm sounds
5. **Geofencing:** Alert if elder leaves safe zone
6. **Fall Detection:** Integrate with device sensors

### Production Checklist:
- [ ] Replace notification icon with custom drawable
- [ ] Test on multiple Android versions (10+)
- [ ] Test on iOS (13+)
- [ ] Set up Firebase App Distribution for testing
- [ ] Configure production Firebase project
- [ ] Set up error monitoring (Crashlytics)
- [ ] Add analytics for SOS usage

## 📞 Support

For issues or questions, check:
- Firebase Functions logs: `firebase functions:log`
- Flutter logs: `flutter logs`
- Firestore console for data validation

## 🎉 Success!

Your SOS Emergency Alert System is now ready. Test thoroughly before production deployment!

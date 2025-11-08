# Notification System Debug Guide

## 🔍 Testing Steps

### 1. Check Console Logs

When you tick a medication, watch for these console messages:

```
📝 Creating notification for Aspirin...
   elderlyId: abc123xyz
   isTakenLate: false
📬 Notification created: ✓ Medication Taken (ID: doc123)
✅ Notification created successfully
```

If you see **error messages** instead, note them down.

### 2. Check Firebase Console

1. Open Firebase Console: https://console.firebase.google.com
2. Go to your project
3. Click on **Firestore Database**
4. Look for a collection called **`notifications`**
5. Check if documents are being created when you tick medications

### 3. Test Notification Service Directly

You can add this temporary button to your home screen to test:

```dart
// Add this to home_screen.dart after the medications section:

ElevatedButton(
  onPressed: () async {
    final notifService = PersistentNotificationService();
    await notifService.testNotificationCreation();
    
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Test notification sent! Check console')),
    );
  },
  child: const Text('TEST NOTIFICATIONS'),
),
```

### 4. Common Issues & Solutions

#### Issue: "Permission denied" error
**Solution**: Check Firestore Security Rules. They should allow authenticated users to write:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /notifications/{notificationId} {
      allow read, write: if request.auth != null && 
                         request.auth.uid == resource.data.elderlyId;
      allow create: if request.auth != null && 
                       request.auth.uid == request.resource.data.elderlyId;
    }
  }
}
```

#### Issue: No error but no notifications appearing
**Solution**: 
1. Make sure user is logged in
2. Check that `elderlyId` matches current user's UID
3. Verify Firestore indexes are built (check Firebase Console > Indexes tab)

#### Issue: "Undefined name '_currentElderlyId'"
**Solution**: The `PersistentNotificationService` uses `FirebaseAuth.currentUser?.uid`. Make sure user is authenticated.

### 5. Quick Console Debug Commands

Run these in your Flutter app and check the output:

```dart
// Check if user is logged in
print('Current user: ${FirebaseAuth.instance.currentUser?.uid}');

// Test notification creation
final notifService = PersistentNotificationService();
await notifService.testNotificationCreation();

// Check notifications stream
notifService.getNotifications().listen((notifs) {
  print('📬 Loaded ${notifs.length} notifications from Firestore');
  for (var notif in notifs) {
    print('  - ${notif.title}: ${notif.message}');
  }
});
```

### 6. Firestore Rules Template

If your Firestore rules are blocking writes, update them to:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Allow authenticated users to read/write their own data
    match /{document=**} {
      allow read, write: if request.auth != null;
    }
    
    // More specific rules for notifications
    match /notifications/{notificationId} {
      allow read: if request.auth != null && 
                     (request.auth.uid == resource.data.elderlyId || 
                      request.auth.uid == resource.data.caregiverId);
      allow create: if request.auth != null;
      allow update: if request.auth != null && 
                       request.auth.uid == resource.data.elderlyId;
    }
  }
}
```

### 7. Expected Notification Structure in Firestore

When a medication is taken, this document should be created in `notifications` collection:

```javascript
{
  elderlyId: "user123abc",
  type: "medication_taken",
  title: "✓ Medication Taken",
  message: "Aspirin taken on time at 09:15 AM",
  timestamp: Timestamp(2025, 1, 31, 9, 15, 0),
  severity: "positive",
  isRead: false,
  metadata: {
    medicationId: "med123",
    scheduledTime: "2025-01-31T09:00:00.000",
    takenAt: "2025-01-31T09:15:00.000",
    medicationName: "Aspirin",
    isLate: false,
    minutesLate: 0
  }
}
```

---

## 🐛 Debugging Checklist

- [ ] User is logged in (`FirebaseAuth.instance.currentUser != null`)
- [ ] Console shows "Creating notification" message when ticking medication
- [ ] Console shows "Notification created" success message
- [ ] Firestore `notifications` collection exists
- [ ] Documents are appearing in Firestore console
- [ ] Firestore security rules allow writes
- [ ] No permission errors in console
- [ ] `NotificationsScreen` is querying the right collection
- [ ] Stream is returning data in notifications screen

---

## 📞 Need Help?

If notifications still aren't working after these checks:

1. Share the **console logs** when you tick a medication
2. Share a **screenshot** of your Firestore collections
3. Share your **Firestore security rules**
4. Note any **error messages** you see

This will help diagnose the exact issue!

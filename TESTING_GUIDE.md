# Quick Testing Guide - Medication Features

## 🧪 How to Test Each Feature

### 1. Test Medication Time Zones

#### Setup:
1. Add a medication with times for today
2. Set one time 20 minutes from now (for SOON)
3. Set one time 10 minutes ago (for TAKE NOW)
4. Set one time 2 hours ago (for LATE)

#### Expected Results:
```
📍 20 minutes from now:
   - Blue "SOON" badge
   - Lock icon (not tickable)
   
📍 10 minutes ago:
   - Green "TAKE NOW" badge
   - Checkbox (tickable)
   
📍 2 hours ago:
   - Orange "LATE" badge
   - Checkbox (tickable)
```

#### Actions:
- Try clicking SOON medication → Should NOT tick
- Tick TAKE NOW medication → Should mark as taken
- Tick LATE medication → Should show "TAKEN LATE" in red

---

### 2. Test Persistent Notifications

#### Setup:
1. Take a medication within 1 hour (on-time)
2. Take a medication after 1 hour (late)
3. Complete a checklist item

#### Expected Results:
Navigate to Notifications screen and verify:
```
✅ "You took [Med Name] ([Dosage]) on time" (Green)
⚠️ "You took [Med Name] ([Dosage]) late" (Orange)
✅ "You completed: [Task Name]" (Green)
```

#### Actions:
- Close app and reopen → Notifications should persist
- Tap notification → Should mark as read
- Wait 30+ days → Old notifications auto-delete

---

### 3. Test Enhanced Checklist Display

#### Setup:
1. Create checklist items in different categories:
   - Morning task with 9:00 AM due time
   - Health task with 2:30 PM due time
   - Exercise task with yesterday's date
   - Personal Care task with tomorrow's date

#### Expected Results:
```
🌅 Morning task:
   - Sun icon
   - "Before 9:00 AM"
   - No OVERDUE badge
   
❤️ Health task:
   - Heart icon
   - "Before 2:30 PM"
   - No OVERDUE badge
   
🏃 Exercise task (yesterday):
   - Running icon
   - Red "OVERDUE" badge
   - "Before X:XX PM" in red
   
🧼 Personal Care task (tomorrow):
   - Should NOT appear in today's list
   - Should appear in "Due in Future" section
```

---

### 4. Test "Due in Future" Sections

#### Setup Medications:
1. Create medication scheduled for tomorrow at 9:00 AM
2. Create medication scheduled for next Monday at 8:00 PM
3. Create medication scheduled for Friday (within 7 days)

#### Setup Checklists:
1. Create task due tomorrow at 3:00 PM
2. Create task due in 5 days at 6:00 PM
3. Create task due in 10 days (should NOT show)

#### Expected Results - Medications:
```
📘 Due in Future (Medications)
   [Collapsed by default - tap to expand]
   
   Expanded view:
   📅 Tomorrow - Friday, Jan 31
      • Medication A (500mg) - 9:00 AM
      
   📅 Monday - February 3  
      • Medication B (250mg) - 8:00 PM
      
   📅 Friday - February 7
      • Medication C (100mg) - 12:00 PM
```

#### Expected Results - Checklists:
```
💜 Due in Future (Checklist)
   [Collapsed by default - tap to expand]
   
   Expanded view:
   📅 Tomorrow - Friday, Jan 31
      🔹 Task A - Before 3:00 PM
      
   📅 Wednesday - February 5
      ❤️ Task B - Before 6:00 PM
      
   [Task due in 10 days should NOT appear]
```

---

### 5. Test Push Notifications

#### Setup:
1. Create medication scheduled 30 minutes from now
2. Keep app in foreground

#### Expected Behavior:
```
⏰ 15 minutes from now:
   Notification: "Medication Reminder - [Med] is due in 15 minutes"
   
⏰ At scheduled time:
   Notification: "Time to Take Medication - [Med] - Take now"
   
⏰ 15 minutes after:
   Notification: "Medication Overdue - [Med] - Please take ASAP"
```

#### Verification:
1. Check console log: Should print "Scheduled X push notifications"
2. Close and reopen app → Notifications should still trigger
3. Mark medication as taken → Should reschedule remaining doses

---

## 🐛 Common Issues & Solutions

### Issue: SOON badge not showing
**Solution**: Verify system time is correct. SOON appears 30 min before.

### Issue: Notifications not persisting
**Solution**: Check Firestore `notifications` collection has data.

### Issue: Push notifications not appearing
**Solution**: 
- iOS: Check Settings → Notifications → SilverCare (allow notifications)
- Android: Should work automatically
- Simulator: May not support notifications, test on real device

### Issue: Future sections empty
**Solution**: Verify medications/tasks have dates within next 7 days.

### Issue: Category icons not showing
**Solution**: Check task has valid category field in Firestore.

---

## 📊 Debug Console Messages

When testing, watch for these console outputs:

```dart
// Push notification scheduling
Scheduled 42 push notifications for next 7 days

// Notification tap
Notification tapped: medication_abc123_before

// Persistent notification creation
Created notification: medication_taken
Created notification: task_completed
```

---

## 🎯 Success Criteria

✅ **All time zones display correctly** (SOON, TAKE NOW, LATE)  
✅ **SOON medications cannot be ticked**  
✅ **Notifications persist across app restarts**  
✅ **All 10 category icons display properly**  
✅ **Overdue badge appears for past-due tasks**  
✅ **Future sections show next 7 days**  
✅ **Push notifications trigger at correct times**  
✅ **Daily refresh reschedules notifications**

---

## 📱 Testing on Physical Device

### iOS:
```bash
# Connect iPhone
flutter run -d <device-id>

# Check device logs
flutter logs
```

### Android:
```bash
# Connect Android device
flutter run -d <device-id>

# Check notification status
adb shell dumpsys notification
```

---

## 🔍 Firestore Collections to Monitor

### `notifications` Collection:
```javascript
{
  elderlyId: "user123",
  type: "medication_taken",
  title: "Medication Taken",
  message: "You took Aspirin (500mg) on time",
  timestamp: Timestamp,
  severity: "success",
  isRead: false,
  metadata: {
    medicationId: "med123",
    doseTime: "09:00",
    takenLate: false
  }
}
```

### `medications` Collection:
```javascript
{
  elderlyId: "user123",
  name: "Aspirin",
  dosage: "500mg",
  daysOfWeek: ["Monday", "Wednesday", "Friday"],
  timesOfDay: ["09:00", "21:00"],
  startDate: Timestamp,
  endDate: null
}
```

### `checklistItems` Collection:
```javascript
{
  elderlyId: "user123",
  task: "Morning Exercise",
  category: "Exercise",
  dueDate: Timestamp,
  dueTime: "08:00",
  completed: false
}
```

---

**Happy Testing! 🎉**

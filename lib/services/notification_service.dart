import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/data/latest_all.dart' as tz;
import 'package:timezone/timezone.dart' as tz;
import 'package:flutter/material.dart';

// IMPORTANT: This class uses flutter_local_notifications for scheduling local, 
// time-based reminders (like medication and checklist tasks).
// For critical, instant alerts (SOS, Caregiver updates), you should integrate 
// Firebase Cloud Messaging (FCM) separately.

class NotificationService {
  final FlutterLocalNotificationsPlugin _flutterLocalNotificationsPlugin = 
      FlutterLocalNotificationsPlugin();
  
  static final NotificationService _instance = NotificationService._internal();
  
  // Global navigator key for routing (set this in main.dart)
  static GlobalKey<NavigatorState>? navigatorKey;

  factory NotificationService() {
    return _instance;
  }

  NotificationService._internal();

  /// Call this once in main.dart to set up the notification channels and timezone.
  Future<void> initialize() async {
    // 1. Initialize Timezone for scheduling
    tz.initializeTimeZones();
    // Use the device's current location (or a specific location if needed)
    final location = tz.getLocation(tz.local.name);
    tz.setLocalLocation(location);

    // 2. Platform-specific settings
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher'); // Use your app icon
    
    const DarwinInitializationSettings initializationSettingsIOS =
        DarwinInitializationSettings(
          requestAlertPermission: true,
          requestBadgePermission: true,
          requestSoundPermission: true,
          // Optional: handle notifications received while app is running
          onDidReceiveLocalNotification: onDidReceiveLocalNotification,
        );

    const InitializationSettings initializationSettings = InitializationSettings(
        android: initializationSettingsAndroid,
        iOS: initializationSettingsIOS);

    // 3. Initialize the plugin
    await _flutterLocalNotificationsPlugin.initialize(
        initializationSettings,
        // Handler for when a notification is tapped
        onDidReceiveNotificationResponse: onDidReceiveNotificationResponse);
  }

  /// Handler for when a notification is tapped (opens app from background/terminated)
  static void onDidReceiveNotificationResponse(NotificationResponse response) {
    if (response.payload != null) {
      print('Notification payload: ${response.payload}');
      
      // Handle routing based on payload
      final payload = response.payload!;
      
      if (navigatorKey?.currentContext != null) {
        if (payload.startsWith('medication_')) {
          // Navigate to home screen (where medications are shown)
          navigatorKey!.currentState?.pushNamed('/main');
        } else if (payload.startsWith('checklist_')) {
          // Navigate to home screen (where checklist is shown)
          navigatorKey!.currentState?.pushNamed('/main');
        } else {
          // Default: go to notifications screen
          navigatorKey!.currentState?.pushNamed('/notifications');
        }
      }
    }
  }
  
  // (iOS only) Handler for when a notification is received while app is in foreground
  static void onDidReceiveLocalNotification(
      int id, String? title, String? body, String? payload) async {
    // This is for older iOS versions, mainly to show an in-app alert/modal
    print('iOS Foreground Notification received: $title, $body');
    // You might show a Flutter dialog here.
  }
  
  // --- Core Scheduling Function ---

  /// Schedules a one-time notification.
  Future<void> scheduleNotification({
    required int id,
    required String title,
    required String body,
    required DateTime scheduledDate,
    String? payload,
    // Add a flag for red/green/blue alert color/sound if needed later
  }) async {
    
    // Check if the scheduled time is in the future
    final tz.TZDateTime tzScheduledTime = tz.TZDateTime.from(scheduledDate, tz.local);
    if (tzScheduledTime.isBefore(tz.TZDateTime.now(tz.local))) {
      print('⚠️ Cannot schedule notification: Time is in the past.');
      return;
    }

    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
        'medication_reminder_channel_id', // Channel ID
        'Medication Reminders', // Channel Name
        channelDescription: 'Reminders for daily medication dosage.',
        importance: Importance.max,
        priority: Priority.high,
        ticker: 'ticker_text');

    const DarwinNotificationDetails iOSDetails = DarwinNotificationDetails();

    const NotificationDetails platformDetails = 
        NotificationDetails(android: androidDetails, iOS: iOSDetails);

    await _flutterLocalNotificationsPlugin.zonedSchedule(
        id,
        title,
        body,
        tzScheduledTime,
        platformDetails,
        uiLocalNotificationDateInterpretation:
            UILocalNotificationDateInterpretation.absoluteTime,
        androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
        matchDateTimeComponents: DateTimeComponents.time, // Optional: useful for recurring daily reminders
        payload: payload);
    
    print('✅ Notification scheduled for ID $id at $scheduledDate');
  }
  
  /// Cancels a scheduled notification by its ID.
  Future<void> cancelNotification(int id) async {
    await _flutterLocalNotificationsPlugin.cancel(id);
    print('❌ Notification cancelled for ID $id');
  }

  /// Utility to get pending notifications (for debugging/verification)
  Future<List<PendingNotificationRequest>> getPendingNotifications() async {
    return _flutterLocalNotificationsPlugin.pendingNotificationRequests();
  }
}

// Don't forget to call NotificationService().initialize() in lib/main.dart!
// ... right after Firebase.initializeApp()
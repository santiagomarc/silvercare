import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/timezone.dart' as tz;
import 'package:timezone/data/latest.dart' as tz;
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:intl/intl.dart';
import '../models/models.dart';

class PushNotificationService {
  static final PushNotificationService _instance = PushNotificationService._internal();
  factory PushNotificationService() => _instance;
  PushNotificationService._internal();

  final FlutterLocalNotificationsPlugin _notifications = FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  /// Initialize the notification service
  Future<void> initialize() async {
    if (_initialized) return;

    // Initialize timezone
    tz.initializeTimeZones();
    
    // Android initialization
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    
    // iOS initialization
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    
    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _notifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: _onNotificationTap,
    );

    _initialized = true;
  }

  /// Request notification permissions (iOS)
  Future<bool> requestPermissions() async {
    if (!_initialized) await initialize();
    
    final result = await _notifications
        .resolvePlatformSpecificImplementation<IOSFlutterLocalNotificationsPlugin>()
        ?.requestPermissions(alert: true, badge: true, sound: true);
    
    return result ?? true; // Android doesn't need runtime permission
  }

  /// Handle notification tap
  void _onNotificationTap(NotificationResponse response) {
    // Handle navigation based on payload
    // You can implement navigation logic here if needed
    print('Notification tapped: ${response.payload}');
  }

  /// Schedule medication notifications for next 7 days
  Future<void> scheduleMedicationNotifications(String elderlyId) async {
    if (!_initialized) await initialize();

    // Cancel all existing notifications for this elderly
    await cancelAllNotifications();

    // Fetch active medication schedules
    final medicationsSnapshot = await FirebaseFirestore.instance
        .collection('medications')
        .where('elderlyId', isEqualTo: elderlyId)
        .get();

    final now = DateTime.now();
    final medications = medicationsSnapshot.docs
        .map((doc) => MedicationModel.fromDoc(doc))
        .where((med) => 
          med.endDate == null || med.endDate!.isAfter(now)
        )
        .toList();

    int notificationId = 1000; // Start from 1000 to avoid conflicts

    // Schedule for next 7 days
    for (int dayOffset = 0; dayOffset < 7; dayOffset++) {
      final targetDate = now.add(Duration(days: dayOffset));
      final dayName = DateFormat('EEEE').format(targetDate);

      for (final medication in medications) {
        // Check if medication is scheduled for this day
        if (medication.daysOfWeek.isNotEmpty && 
            !medication.daysOfWeek.contains(dayName)) continue;

        for (final time in medication.timesOfDay) {
          final scheduledTime = _parseTime(time);
          final notificationTime = DateTime(
            targetDate.year,
            targetDate.month,
            targetDate.day,
            scheduledTime.hour,
            scheduledTime.minute,
          );

          // Skip if the time has already passed
          if (notificationTime.isBefore(now)) continue;

          // Schedule 3 notifications: 15 min before, at time, 15 min after
          await _scheduleNotification(
            id: notificationId++,
            title: 'Medication Reminder',
            body: '${medication.name} (${medication.dosage}) is due in 15 minutes',
            scheduledTime: notificationTime.subtract(Duration(minutes: 15)),
            payload: 'medication_${medication.id}_before',
          );

          await _scheduleNotification(
            id: notificationId++,
            title: 'Time to Take Medication',
            body: '${medication.name} (${medication.dosage}) - Take now',
            scheduledTime: notificationTime,
            payload: 'medication_${medication.id}_now',
          );

          await _scheduleNotification(
            id: notificationId++,
            title: 'Medication Overdue',
            body: '${medication.name} (${medication.dosage}) - Please take as soon as possible',
            scheduledTime: notificationTime.add(Duration(minutes: 15)),
            payload: 'medication_${medication.id}_after',
          );
        }
      }
    }

    print('Scheduled ${(notificationId - 1000)} medication notifications for next 7 days');
  }

  /// Schedule a single notification
  Future<void> _scheduleNotification({
    required int id,
    required String title,
    required String body,
    required DateTime scheduledTime,
    String? payload,
  }) async {
    final tzTime = tz.TZDateTime.from(scheduledTime, tz.local);

    const androidDetails = AndroidNotificationDetails(
      'medication_reminders',
      'Medication Reminders',
      channelDescription: 'Notifications for medication schedules',
      importance: Importance.high,
      priority: Priority.high,
      playSound: true,
      enableVibration: true,
    );

    const iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _notifications.zonedSchedule(
      id,
      title,
      body,
      tzTime,
      notificationDetails,
      androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
      uiLocalNotificationDateInterpretation:
          UILocalNotificationDateInterpretation.absoluteTime,
      payload: payload,
    );
  }

  /// Show an immediate notification
  Future<void> showNotification({
    required int id,
    required String title,
    required String body,
    String? payload,
  }) async {
    if (!_initialized) await initialize();

    const androidDetails = AndroidNotificationDetails(
      'medication_reminders',
      'Medication Reminders',
      channelDescription: 'Notifications for medication schedules',
      importance: Importance.high,
      priority: Priority.high,
      playSound: true,
      enableVibration: true,
    );

    const iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _notifications.show(id, title, body, notificationDetails, payload: payload);
  }

  /// Send a notification immediately (alias for showNotification with auto-generated ID)
  Future<void> sendNotification({
    required String title,
    required String body,
    String? payload,
  }) async {
    final id = DateTime.now().millisecondsSinceEpoch % 100000; // Generate unique ID
    await showNotification(
      id: id,
      title: title,
      body: body,
      payload: payload,
    );
  }

  /// Cancel all scheduled notifications
  Future<void> cancelAllNotifications() async {
    await _notifications.cancelAll();
  }

  /// Cancel a specific notification
  Future<void> cancelNotification(int id) async {
    await _notifications.cancel(id);
  }

  /// Get pending notifications count
  Future<int> getPendingNotificationsCount() async {
    final pending = await _notifications.pendingNotificationRequests();
    return pending.length;
  }

  /// Parse time string (HH:mm format) to DateTime
  TimeOfDay _parseTime(String timeStr) {
    final parts = timeStr.split(':');
    return TimeOfDay(
      hour: int.parse(parts[0]),
      minute: int.parse(parts[1]),
    );
  }

  /// Schedule daily refresh at midnight (to reschedule for next 7 days)
  Future<void> scheduleDailyRefresh(String elderlyId) async {
    if (!_initialized) await initialize();

    final now = DateTime.now();
    final tomorrow = DateTime(now.year, now.month, now.day + 1, 0, 0, 0);
    final tzTime = tz.TZDateTime.from(tomorrow, tz.local);

    const androidDetails = AndroidNotificationDetails(
      'system_refresh',
      'System Refresh',
      channelDescription: 'Internal system notifications',
      importance: Importance.low,
      priority: Priority.low,
      playSound: false,
      enableVibration: false,
    );

    const iosDetails = DarwinNotificationDetails(
      presentAlert: false,
      presentBadge: false,
      presentSound: false,
    );

    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    // Schedule a daily repeating notification that triggers rescheduling
    await _notifications.zonedSchedule(
      999, // Reserved ID for daily refresh
      'System Refresh',
      'Rescheduling notifications',
      tzTime,
      notificationDetails,
      androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
      uiLocalNotificationDateInterpretation:
          UILocalNotificationDateInterpretation.absoluteTime,
      payload: 'refresh_$elderlyId',
      matchDateTimeComponents: DateTimeComponents.time, // Daily repeat
    );
  }
}

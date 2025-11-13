import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/calendar_model.dart';
import '../services/calendar_service.dart';
import '../services/persistent_notification_service.dart';
import '../services/push_notification_service.dart';

/// Service to schedule notifications for upcoming calendar events
class CalendarNotificationService {
  static final FirebaseAuth _auth = FirebaseAuth.instance;
  static final PersistentNotificationService _persistentNotificationService =
      PersistentNotificationService();
  static final PushNotificationService _pushNotificationService =
      PushNotificationService();

  /// Check and create notifications for upcoming events
  /// Notifies: 24 hours before, 1 hour before, and 15 minutes before
  static Future<void> checkAndScheduleNotifications() async {
    final user = _auth.currentUser;
    if (user == null) return;

    try {
      // Get all calendar events
      final allEvents = await CalendarService.loadAllEvents();
      final now = DateTime.now();

      // Flatten all events
      List<CalendarEvent> allEventsList = [];
      allEvents.forEach((date, events) {
        allEventsList.addAll(events);
      });

      for (var event in allEventsList) {
        final timeUntilEvent = event.eventDate.difference(now);

        // Skip past events
        if (timeUntilEvent.isNegative) continue;

        // 24 hours before (notify if within 24-23 hours window)
        if (timeUntilEvent.inHours >= 23 && timeUntilEvent.inHours < 24) {
          await _createEventNotification(
            event: event,
            timeUntil: '24 hours',
            notifId: '${event.id}_24h',
          );
        }

        // 1 hour before (notify if within 1-0.5 hours window)
        if (timeUntilEvent.inMinutes >= 30 && timeUntilEvent.inMinutes < 60) {
          await _createEventNotification(
            event: event,
            timeUntil: '1 hour',
            notifId: '${event.id}_1h',
          );
        }

        // 15 minutes before (notify if within 15-10 minutes window)
        if (timeUntilEvent.inMinutes >= 10 && timeUntilEvent.inMinutes < 15) {
          await _createEventNotification(
            event: event,
            timeUntil: '15 minutes',
            notifId: '${event.id}_15m',
          );
        }
      }
    } catch (e) {
      print('Error scheduling calendar notifications: $e');
    }
  }

  /// Create a notification for an upcoming event
  static Future<void> _createEventNotification({
    required CalendarEvent event,
    required String timeUntil,
    required String notifId,
  }) async {
    final user = _auth.currentUser;
    if (user == null) return;

    try {
      // Check if this notification was already created
      final existingNotif = await FirebaseFirestore.instance
          .collection('elderly')
          .doc(user.uid)
          .collection('notifications')
          .where('customId', isEqualTo: notifId)
          .limit(1)
          .get();

      if (existingNotif.docs.isNotEmpty) {
        // Notification already exists, skip
        return;
      }

      // Create notification
      final title = '📅 Upcoming: ${event.title}';
      final message = '${event.eventType} in $timeUntil';

      // Create persistent notification
      await _persistentNotificationService.createNotification(
        title: title,
        message: message,
        severity: 'reminder',
        customId: notifId, // Use custom ID to prevent duplicates
      );

      // Send push notification
      await _pushNotificationService.sendNotification(
        title: title,
        body: message,
      );

      print(
        '✅ Created calendar notification for ${event.title} ($timeUntil before)',
      );
    } catch (e) {
      print('Error creating event notification: $e');
    }
  }

  /// Initialize periodic checks (call this from main.dart or home_screen)
  static void initializePeriodicChecks() {
    // Check every 5 minutes
    Future.delayed(const Duration(minutes: 5), () {
      checkAndScheduleNotifications();
      initializePeriodicChecks(); // Recursive call
    });

    // Initial check
    checkAndScheduleNotifications();
  }
}

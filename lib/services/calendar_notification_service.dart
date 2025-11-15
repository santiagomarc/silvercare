import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/calendar_model.dart';
import '../services/calendar_service.dart';
import '../services/persistent_notification_service.dart';
import '../services/push_notification_service.dart';

/// Service to create smart notifications for calendar events
/// - Daily summary on first app open
/// - Time-based reminders (1h, 15min before) for imminent events
class CalendarNotificationService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;
  static final PersistentNotificationService _persistentNotificationService =
      PersistentNotificationService();
  static final PushNotificationService _pushNotificationService =
      PushNotificationService();

  /// Check if we should send daily summary (once per day on first app open)
  static Future<void> checkDailySummary() async {
    final prefs = await SharedPreferences.getInstance();
    final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
    final lastCheckDate = prefs.getString('last_calendar_check') ?? '';

    if (lastCheckDate == today) {
      print('📅 Daily calendar summary already sent today');
      return;
    }

    await _sendDailySummary();
    await prefs.setString('last_calendar_check', today);
  }

  /// Send daily summary of upcoming events
  static Future<void> _sendDailySummary() async {
    final user = _auth.currentUser;
    if (user == null) return;

    try {
      final allEvents = await CalendarService.loadAllEvents();
      final now = DateTime.now();

      // Flatten and filter upcoming events (next 7 days)
      List<CalendarEvent> upcomingEvents = [];
      allEvents.forEach((date, events) {
        for (var event in events) {
          final daysUntil = event.eventDate.difference(now).inDays;
          if (daysUntil >= 0 && daysUntil <= 7) {
            upcomingEvents.add(event);
          }
        }
      });

      if (upcomingEvents.isEmpty) {
        print('📭 No upcoming events in next 7 days');
        return;
      }

      // Sort by date
      upcomingEvents.sort((a, b) => a.eventDate.compareTo(b.eventDate));

      // Categorize events
      final todayEvents = upcomingEvents.where((e) => 
        _isSameDay(e.eventDate, now)).toList();
      final tomorrowEvents = upcomingEvents.where((e) => 
        _isSameDay(e.eventDate, now.add(const Duration(days: 1)))).toList();
      final thisWeekEvents = upcomingEvents.where((e) {
        final daysUntil = e.eventDate.difference(now).inDays;
        return daysUntil >= 2 && daysUntil <= 7;
      }).toList();

      // Create summary notification
      final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
      String message = '';
      if (todayEvents.isNotEmpty) {
        message += '${todayEvents.length} event${todayEvents.length > 1 ? 's' : ''} today';
      }
      if (tomorrowEvents.isNotEmpty) {
        if (message.isNotEmpty) message += ', ';
        message += '${tomorrowEvents.length} tomorrow';
      }
      if (thisWeekEvents.isNotEmpty) {
        if (message.isNotEmpty) message += ', ';
        message += '${thisWeekEvents.length} this week';
      }

      final customId = 'daily_summary_$today';
      
      // Check if already sent
      final existing = await _firestore
          .collection('notifications')
          .where('elderlyId', isEqualTo: user.uid)
          .where('customId', isEqualTo: customId)
          .limit(1)
          .get();

      if (existing.docs.isNotEmpty) return;

      await _persistentNotificationService.createNotification(
        title: '📅 Your Calendar Summary',
        message: message,
        severity: 'reminder',
        type: 'calendar_summary',
        customId: customId,
        metadata: {
          'todayCount': todayEvents.length,
          'tomorrowCount': tomorrowEvents.length,
          'weekCount': thisWeekEvents.length,
        },
      );

      await _pushNotificationService.sendNotification(
        title: '📅 Your Calendar Summary',
        body: message,
      );

      print('✅ Daily calendar summary sent: $message');
    } catch (e) {
      print('❌ Error sending daily summary: $e');
    }
  }

  /// Check and send time-based reminders for imminent events (1h, 15min)
  static Future<void> checkImminentReminders() async {
    final user = _auth.currentUser;
    if (user == null) return;

    try {
      final allEvents = await CalendarService.loadAllEvents();
      final now = DateTime.now();

      List<CalendarEvent> allEventsList = [];
      allEvents.forEach((date, events) {
        allEventsList.addAll(events);
      });

      for (var event in allEventsList) {
        final minutesUntil = event.eventDate.difference(now).inMinutes;

        // Skip past events
        if (minutesUntil < 0) continue;

        // 1 hour reminder (58-62 min window)
        if (minutesUntil >= 58 && minutesUntil <= 62) {
          await _createTimedReminder(
            event: event,
            timeLabel: '1 hour',
            customId: '${event.id}_1h',
          );
        }
        // 15 minute reminder (13-17 min window)
        else if (minutesUntil >= 13 && minutesUntil <= 17) {
          await _createTimedReminder(
            event: event,
            timeLabel: '15 minutes',
            customId: '${event.id}_15m',
          );
        }
      }
    } catch (e) {
      print('❌ Error checking imminent reminders: $e');
    }
  }

  /// Create a time-based reminder notification
  static Future<void> _createTimedReminder({
    required CalendarEvent event,
    required String timeLabel,
    required String customId,
  }) async {
    final user = _auth.currentUser;
    if (user == null) return;

    try {
      // Check if already sent
      final existing = await _firestore
          .collection('notifications')
          .where('elderlyId', isEqualTo: user.uid)
          .where('customId', isEqualTo: customId)
          .limit(1)
          .get();

      if (existing.docs.isNotEmpty) {
        print('⏭️  Reminder already sent: $customId');
        return;
      }

      final formattedTime = DateFormat('h:mm a').format(event.eventDate);
      final title = '⏰ Reminder: ${event.title}';
      final message = 'Starts in $timeLabel at $formattedTime';

      await _persistentNotificationService.createNotification(
        title: title,
        message: message,
        severity: 'reminder',
        type: 'calendar_reminder',
        customId: customId,
        metadata: {
          'eventId': event.id,
          'eventDate': event.eventDate.toIso8601String(),
          'timeLabel': timeLabel,
        },
      );

      await _pushNotificationService.sendNotification(
        title: title,
        body: message,
      );

      print('✅ Sent $timeLabel reminder for: ${event.title}');
    } catch (e) {
      print('❌ Error creating timed reminder: $e');
    }
  }

  /// Helper to check if two dates are the same day
  static bool _isSameDay(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }

}

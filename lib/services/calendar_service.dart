import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/calendar_model.dart';

class CalendarService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Load all calendar events for the current user
  static Future<Map<DateTime, List<CalendarEvent>>> loadAllEvents() async {
    final user = _auth.currentUser;
    if (user == null) return {};

    try {
      final snapshot = await _firestore
          .collection('elderly')
          .doc(user.uid)
          .collection('calendarEvents')
          .get();

      Map<DateTime, List<CalendarEvent>> events = {};

      for (var doc in snapshot.docs) {
        final event = CalendarEvent.fromDoc(doc);
        final day = _normalizeDay(event.eventDate);

        if (events[day] == null) {
          events[day] = [];
        }
        events[day]!.add(event);
      }

      // Sort events by date within each day
      events.forEach((key, value) {
        value.sort((a, b) => a.eventDate.compareTo(b.eventDate));
      });

      return events;
    } catch (e) {
      print('Error loading calendar events: $e');
      return {};
    }
  }

  /// Add a new calendar event
  static Future<bool> addEvent({
    required String title,
    required String description,
    required DateTime eventDate,
    required String eventType,
  }) async {
    final user = _auth.currentUser;
    if (user == null) return false;

    if (title.trim().isEmpty) return false;

    final newEvent = CalendarEvent(
      id: '',
      title: title.trim(),
      description: description.trim(),
      eventDate: eventDate,
      eventType: eventType,
    );

    try {
      await _firestore
          .collection('elderly')
          .doc(user.uid)
          .collection('calendarEvents')
          .add(newEvent.toMap());

      return true;
    } catch (e) {
      print('Error adding calendar event: $e');
      return false;
    }
  }

  /// Update an existing calendar event
  static Future<bool> updateEvent({
    required String eventId,
    required String title,
    required String description,
    required DateTime eventDate,
    required String eventType,
  }) async {
    final user = _auth.currentUser;
    if (user == null) return false;

    if (title.trim().isEmpty) return false;

    try {
      await _firestore
          .collection('elderly')
          .doc(user.uid)
          .collection('calendarEvents')
          .doc(eventId)
          .update({
        'title': title.trim(),
        'description': description.trim(),
        'eventDate': Timestamp.fromDate(eventDate),
        'eventType': eventType,
      });

      return true;
    } catch (e) {
      print('Error updating calendar event: $e');
      return false;
    }
  }

  /// Delete a calendar event
  static Future<bool> deleteEvent(String eventId) async {
    final user = _auth.currentUser;
    if (user == null) return false;

    try {
      await _firestore
          .collection('elderly')
          .doc(user.uid)
          .collection('calendarEvents')
          .doc(eventId)
          .delete();

      return true;
    } catch (e) {
      print('Error deleting calendar event: $e');
      return false;
    }
  }

  /// Get events for a specific day
  static List<CalendarEvent> getEventsForDay(
    DateTime day,
    Map<DateTime, List<CalendarEvent>> allEvents,
  ) {
    return allEvents[_normalizeDay(day)] ?? [];
  }

  /// Normalize a DateTime to midnight UTC for consistent day comparison
  static DateTime _normalizeDay(DateTime date) {
    return DateTime.utc(date.year, date.month, date.day);
  }
}

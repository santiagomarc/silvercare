import 'package:cloud_firestore/cloud_firestore.dart';

class CalendarEvent {
  final String id;
  final String title;
  final String description;
  final DateTime eventDate;
  final String eventType;

  CalendarEvent({
    required this.id,
    required this.title,
    required this.description,
    required this.eventDate,
    required this.eventType,
  });

  factory CalendarEvent.fromDoc(DocumentSnapshot doc) {
    Map<String, dynamic> data = doc.data() as Map<String, dynamic>;
    return CalendarEvent(
      id: doc.id,
      title: data['title'] ?? '',
      description: data['description'] ?? '',
      eventDate: data['eventDate'] != null
          ? (data['eventDate'] as Timestamp).toDate()
          : DateTime.now(),
      eventType: data['eventType'] ?? 'Reminder',
    );
  }

  factory CalendarEvent.fromMap(Map<String, dynamic> map, String id) {
    return CalendarEvent(
      id: id,
      title: map['title'] ?? '',
      description: map['description'] ?? '',
      eventDate: map['eventDate'] != null
          ? (map['eventDate'] as Timestamp).toDate()
          : DateTime.now(),
      eventType: map['eventType'] ?? 'Reminder',
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'title': title,
      'description': description,
      'eventDate': Timestamp.fromDate(eventDate),
      'eventType': eventType,
    };
  }

  CalendarEvent copyWith({
    String? id,
    String? title,
    String? description,
    DateTime? eventDate,
    String? eventType,
  }) {
    return CalendarEvent(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      eventDate: eventDate ?? this.eventDate,
      eventType: eventType ?? this.eventType,
    );
  }
}

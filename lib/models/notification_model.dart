import 'package:cloud_firestore/cloud_firestore.dart';

class NotificationModel {
  final String id;
  final String elderlyId;
  final String type; // 'medication_reminder', 'medication_taken', 'medication_missed', 'task_completed', 'task_overdue'
  final String title;
  final String message;
  final DateTime timestamp;
  final String severity; // 'positive' (green), 'negative' (red), 'reminder' (blue), 'warning' (orange)
  final Map<String, dynamic> metadata; // Store medicationId, taskId, scheduledTime, etc.
  final bool isRead;

  NotificationModel({
    required this.id,
    required this.elderlyId,
    required this.type,
    required this.title,
    required this.message,
    required this.timestamp,
    required this.severity,
    this.metadata = const {},
    this.isRead = false,
  });

  Map<String, dynamic> toMap() {
    return {
      'elderlyId': elderlyId,
      'type': type,
      'title': title,
      'message': message,
      'timestamp': Timestamp.fromDate(timestamp),
      'severity': severity,
      'metadata': metadata,
      'isRead': isRead,
    };
  }

  factory NotificationModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return NotificationModel(
      id: doc.id,
      elderlyId: data['elderlyId'] ?? '',
      type: data['type'] ?? '',
      title: data['title'] ?? '',
      message: data['message'] ?? '',
      timestamp: (data['timestamp'] as Timestamp).toDate(),
      severity: data['severity'] ?? 'reminder',
      metadata: Map<String, dynamic>.from(data['metadata'] ?? {}),
      isRead: data['isRead'] ?? false,
    );
  }

  NotificationModel copyWith({
    String? id,
    String? elderlyId,
    String? type,
    String? title,
    String? message,
    DateTime? timestamp,
    String? severity,
    Map<String, dynamic>? metadata,
    bool? isRead,
  }) {
    return NotificationModel(
      id: id ?? this.id,
      elderlyId: elderlyId ?? this.elderlyId,
      type: type ?? this.type,
      title: title ?? this.title,
      message: message ?? this.message,
      timestamp: timestamp ?? this.timestamp,
      severity: severity ?? this.severity,
      metadata: metadata ?? this.metadata,
      isRead: isRead ?? this.isRead,
    );
  }
}

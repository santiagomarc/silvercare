import 'package:cloud_firestore/cloud_firestore.dart';

/// Represents a single dose instance completion record.
/// Each medication schedule can have multiple dose completions (one per scheduled time per day).
class DoseCompletionModel {
  final String id; // Document ID (format: scheduleId_YYYY-MM-DD_HHMM)
  final String elderlyId; // Who this dose is for
  final String scheduleId; // Reference to the medication_schedules document
  
  final DateTime scheduledTime; // When this dose was supposed to be taken
  final bool isTaken; // Whether the dose was taken
  final DateTime? takenAt; // When the dose was actually taken (if isTaken is true)
  
  DoseCompletionModel({
    required this.id,
    required this.elderlyId,
    required this.scheduleId,
    required this.scheduledTime,
    required this.isTaken,
    this.takenAt,
  });

  Map<String, dynamic> toMap() {
    return {
      'elderlyId': elderlyId,
      'scheduleId': scheduleId,
      'scheduledTime': Timestamp.fromDate(scheduledTime),
      'isTaken': isTaken,
      'takenAt': takenAt != null ? Timestamp.fromDate(takenAt!) : null,
    };
  }

  factory DoseCompletionModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return DoseCompletionModel(
      id: doc.id,
      elderlyId: data['elderlyId'] ?? '',
      scheduleId: data['scheduleId'] ?? '',
      scheduledTime: (data['scheduledTime'] as Timestamp).toDate(),
      isTaken: data['isTaken'] ?? false,
      takenAt: (data['takenAt'] as Timestamp?)?.toDate(),
    );
  }
  
  /// Helper to check if this dose was taken late (after scheduled time + grace period)
  bool wasTakenLate({int graceMinutes = 15}) {
    if (!isTaken || takenAt == null) return false;
    final graceDeadline = scheduledTime.add(Duration(minutes: graceMinutes));
    return takenAt!.isAfter(graceDeadline);
  }
  
  /// Helper to check if this dose is currently missed
  bool isMissed({int graceMinutes = 15}) {
    if (isTaken) return false;
    final graceDeadline = scheduledTime.add(Duration(minutes: graceMinutes));
    return DateTime.now().isAfter(graceDeadline);
  }
}

import 'package:cloud_firestore/cloud_firestore.dart';

class MedicationModel {
  final String id; // Firestore document ID
  final String elderlyId; // Who this medication is for
  final String caregiverId; // Who prescribed/manages this
  
  final String name; // e.g., "Paracetamol"
  final String dosage; // e.g., "500mg"
  final String instructions; // e.g., "Take with food"

  // Scheduling
  final List<String> daysOfWeek; // e.g., ["Monday", "Wednesday", "Friday"]
  final List<Timestamp> specificDates; // For one-time meds
  final List<String> timesOfDay; // e.g., ["09:00", "21:00"]
  
  final DateTime startDate;
  final DateTime? endDate; // Nullable for ongoing medication

  MedicationModel({
    required this.id,
    required this.elderlyId,
    required this.caregiverId,
    required this.name,
    required this.dosage,
    this.instructions = '',
    this.daysOfWeek = const [],
    this.specificDates = const [],
    this.timesOfDay = const [],
    required this.startDate,
    this.endDate,
  });

  Map<String, dynamic> toMap() {
    return {
      'elderlyId': elderlyId,
      'caregiverId': caregiverId,
      'name': name,
      'dosage': dosage,
      'instructions': instructions,
      'daysOfWeek': daysOfWeek,
      'specificDates': specificDates,
      'timesOfDay': timesOfDay,
      'startDate': Timestamp.fromDate(startDate),
      'endDate': endDate != null ? Timestamp.fromDate(endDate!) : null,
    };
  }

  factory MedicationModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return MedicationModel(
      id: doc.id,
      elderlyId: data['elderlyId'] ?? '',
      caregiverId: data['caregiverId'] ?? '',
      name: data['name'] ?? '',
      dosage: data['dosage'] ?? '',
      instructions: data['instructions'] ?? '',
      daysOfWeek: List<String>.from(data['daysOfWeek'] ?? []),
      specificDates: List<Timestamp>.from(data['specificDates'] ?? []),
      timesOfDay: List<String>.from(data['timesOfDay'] ?? []),
      startDate: (data['startDate'] as Timestamp).toDate(),
      endDate: (data['endDate'] as Timestamp?)?.toDate(),
    );
  }
}
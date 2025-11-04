import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
// Assuming your models are structured as defined previously
import 'package:silvercare/models/medication_model.dart';

class MedicationService {
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;

  // Collection for the recurring medication schedules (Caregiver management)
  static const String _scheduleCollection = 'medication_schedules';
  // Collection for daily compliance records (Elder interaction)
  static const String completionCollection = 'dose_completions';
  
  // Helper to get the current elderly user's ID
  String get _elderlyId => _auth.currentUser?.uid ?? '';

  // --- Caregiver CRUD Operations (Managing the Schedule) ---

  /// Adds a new recurring medication schedule.
  Future<String?> addMedicationSchedule(MedicationModel model) async {
    try {
      final docRef = await _firestore
          .collection(_scheduleCollection)
          .add(model.toMap());
      return docRef.id;
    } catch (e) {
      print('Error adding medication schedule: $e');
      return null;
    }
  }

  /// Updates an existing medication schedule.
  Future<void> updateMedicationSchedule(MedicationModel model) async {
    if (_elderlyId.isEmpty) return;
    try {
      await _firestore
          .collection(_scheduleCollection)
          .doc(model.id)
          .update(model.toMap());
    } catch (e) {
      print('Error updating medication schedule: $e');
    }
  }

  /// Deletes a medication schedule.
  Future<void> deleteMedicationSchedule(String scheduleId) async {
    try {
      await _firestore.collection(_scheduleCollection).doc(scheduleId).delete();
      // Note: You may want to delete related future dose completions as well.
    } catch (e) {
      print('Error deleting medication schedule: $e');
    }
  }

  // --- Elder/Home Screen Streams (Viewing Today's Doses) ---

  /// Stream of all active medication schedules for the Elder's home screen.
  Stream<List<MedicationModel>> getActiveMedicationSchedules() {
    if (_elderlyId.isEmpty) return Stream.value([]);
    
    // This fetches the *schedule*. UI must filter to show only today's due times.
    return _firestore
        .collection(_scheduleCollection)
        .where('elderlyId', isEqualTo: _elderlyId)
        // Add logic to check for active date range if implemented
        .snapshots()
        .map((snapshot) => snapshot.docs
            .map((doc) => MedicationModel.fromDoc(doc))
            .toList());
  }

  // --- Compliance Tracking (Elder's Action) ---

  /// Elder marks a specific dose as taken.
  /// This dose is identified by the schedule ID and the exact scheduled time.
  Future<void> markDoseAsTaken({
    required String scheduleId,
    required String doseTime, // e.g., "09:00"
    required DateTime scheduledDate, // Date the dose was due
  }) async {
    if (_elderlyId.isEmpty) return;
    
    // Create a unique ID for this specific dose instance (e.g., scheduleId_2025-11-03_09:00)
    final String doseInstanceId = 
        '${scheduleId}_${scheduledDate.toIso8601String().substring(0, 10)}_${doseTime.replaceAll(':', '')}';

    try {
      await _firestore.collection(completionCollection).doc(doseInstanceId).set({
        'elderlyId': _elderlyId,
        'scheduleId': scheduleId,
        'scheduledTime': Timestamp.fromDate(scheduledDate.copyWith(
            hour: int.parse(doseTime.split(':')[0]),
            minute: int.parse(doseTime.split(':')[1]),
            second: 0,
            millisecond: 0,
            microsecond: 0,
        )),
        'isTaken': true,
        'takenAt': Timestamp.fromDate(DateTime.now()),
      }, SetOptions(merge: true));

      print('✅ Dose instance $doseInstanceId marked as taken.');
    } catch (e) {
      print('Error marking dose as taken: $e');
    }
  }
}
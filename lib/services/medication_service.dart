import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:silvercare/models/medication_model.dart';
import 'package:silvercare/services/persistent_notification_service.dart';

class MedicationService {
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final PersistentNotificationService _notificationService = PersistentNotificationService();

  static const String _scheduleCollection = 'medication_schedules';
  static const String completionCollection = 'dose_completions';
  
  String get _elderlyId => _auth.currentUser?.uid ?? '';

  // --- Caregiver CRUD Operations (Managing the Schedule) ---

  /// Adds a new recurring medication schedule.
  Future<String?> addMedicationSchedule(MedicationModel model) async {
    try {
      print('💊 Adding medication schedule for elderlyId: ${model.elderlyId}');
      print('💊 Medication details: ${model.name}, Days: ${model.daysOfWeek}, Times: ${model.timesOfDay}');
      final docRef = await _firestore
          .collection(_scheduleCollection)
          .add(model.toMap());
      print('✅ Medication added with ID: ${docRef.id}');
      return docRef.id;
    } catch (e) {
      print('❌ Error adding medication schedule: $e');
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
    if (_elderlyId.isEmpty) {
      print('⚠️ Cannot fetch medications: No elderlyId (user not logged in)');
      return Stream.value([]);
    }
    
    print('🔍 Fetching medications for elderlyId: $_elderlyId');
    // This fetches the *schedule*. UI must filter to show only today's due times.
    return _firestore
        .collection(_scheduleCollection)
        .where('elderlyId', isEqualTo: _elderlyId)
        // Add logic to check for active date range if implemented
        .snapshots()
        .map((snapshot) {
          print('📦 Received ${snapshot.docs.length} medication schedules from Firestore');
          return snapshot.docs
              .map((doc) => MedicationModel.fromDoc(doc))
              .toList();
        });
  }

  /// Stream of all active medication schedules for a specific elderly (for caregiver use).
  /// This allows caregivers to view all medications for their patient.
  Stream<List<MedicationModel>> getMedicationSchedulesForElderly(String elderlyId) {
    if (elderlyId.isEmpty) {
      print('⚠️ Cannot fetch medications: elderlyId is empty');
      return Stream.value([]);
    }
    
    print('🔍 Caregiver fetching medications for elderlyId: $elderlyId');
    return _firestore
        .collection(_scheduleCollection)
        .where('elderlyId', isEqualTo: elderlyId)
        .snapshots()
        .map((snapshot) {
          print('📦 Found ${snapshot.docs.length} medication schedules for elderly $elderlyId');
          return snapshot.docs
              .map((doc) => MedicationModel.fromDoc(doc))
              .toList();
        });
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
      final takenAt = DateTime.now();
      final scheduledDateTime = scheduledDate.copyWith(
        hour: int.parse(doseTime.split(':')[0]),
        minute: int.parse(doseTime.split(':')[1]),
        second: 0,
        millisecond: 0,
        microsecond: 0,
      );
      
      // Get medication details for notification
      final medDoc = await _firestore.collection(_scheduleCollection).doc(scheduleId).get();
      if (!medDoc.exists) {
        print('⚠️ Medication document not found: $scheduleId');
        return;
      }
      
      final med = MedicationModel.fromDoc(medDoc);
      
      // Determine if taken late (after 1-hour grace period)
      final graceDeadline = scheduledDateTime.add(const Duration(hours: 1));
      final isTakenLate = takenAt.isAfter(graceDeadline);
      
      print('📝 Creating notification for ${med.name}...');
      print('   elderlyId: $_elderlyId');
      print('   isTakenLate: $isTakenLate');
      
      // Create persistent notification and get its ID
      final notificationId = await _notificationService.createMedicationTaken(
        elderlyId: _elderlyId,
        medicationName: med.name,
        scheduledTime: scheduledDateTime,
        takenAt: takenAt,
        isTakenLate: isTakenLate,
        medicationId: scheduleId,
      );
      
      print('✅ Notification created successfully');
      
      // Mark dose as taken in Firestore, storing the notification ID
      await _firestore.collection(completionCollection).doc(doseInstanceId).set({
        'elderlyId': _elderlyId,
        'scheduleId': scheduleId,
        'scheduledTime': Timestamp.fromDate(scheduledDateTime),
        'isTaken': true,
        'takenAt': Timestamp.fromDate(takenAt),
        'notificationId': notificationId, // Store for undo functionality
      }, SetOptions(merge: true));

      print('✅ Dose instance $doseInstanceId marked as taken.');
    } catch (e) {
      print('❌ Error marking dose as taken: $e');
      print('Stack trace: ${StackTrace.current}');
    }
  }
  
  /// Elder undoes marking a dose as taken (removes the completion record and deletes the notification)
  Future<void> undoDoseTaken({
    required String scheduleId,
    required String doseTime,
    required DateTime scheduledDate,
  }) async {
    if (_elderlyId.isEmpty) return;
    
    final String doseInstanceId = 
        '${scheduleId}_${scheduledDate.toIso8601String().substring(0, 10)}_${doseTime.replaceAll(':', '')}';

    try {
      // Get the dose completion record to find the notification ID
      final doseDoc = await _firestore.collection(completionCollection).doc(doseInstanceId).get();
      
      if (doseDoc.exists) {
        final data = doseDoc.data();
        final notificationId = data?['notificationId'] as String?;
        
        // Delete the notification if it exists
        if (notificationId != null) {
          await _notificationService.deleteNotification(notificationId);
          print('🗑️ Deleted notification for undone dose');
        }
      }
      
      // Delete the dose completion record
      await _firestore.collection(completionCollection).doc(doseInstanceId).delete();
      print('✅ Dose instance $doseInstanceId unmarked (undone).');
    } catch (e) {
      print('❌ Error undoing dose: $e');
      print('Stack trace: ${StackTrace.current}');
    }
  }
}
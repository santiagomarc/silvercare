import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/health_data_model.dart';

class BloodPressureService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Save blood pressure data to Firestore
  static Future<void> saveBloodPressureData({
    required double systolic,
    required double diastolic,
    required DateTime measuredAt,
  }) async {
    try {
      final user = _auth.currentUser;
      if (user == null) throw Exception('User not authenticated');

      final docRef = await _firestore.collection('health_data').add({
        'elderlyId': user.uid,
        'type': 'blood_pressure',
        'value': systolic, // Primary value (systolic)
        'systolic': systolic,
        'diastolic': diastolic,
        'measuredAt': Timestamp.fromDate(measuredAt),
        'createdAt': Timestamp.fromDate(DateTime.now()),
        'source': 'manual',
      });

      print('✅ Blood pressure data saved with ID: ${docRef.id}');
    } catch (e) {
      print('❌ Error saving blood pressure data: $e');
      rethrow;
    }
  }

  /// Get blood pressure data from Firestore
  static Future<List<HealthDataModel>> getBloodPressureData({int days = 7}) async {
    try {
      final user = _auth.currentUser;
      if (user == null) throw Exception('User not authenticated');

      final DateTime cutoffDate = DateTime.now().subtract(Duration(days: days));

      final querySnapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: user.uid)
          .where('type', isEqualTo: 'blood_pressure')
          .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(cutoffDate))
          .orderBy('measuredAt', descending: true)
          .get();

      return querySnapshot.docs.map((doc) {
        final data = doc.data();
        return HealthDataModel(
          id: doc.id,
          elderlyId: data['elderlyId'] ?? '',
          type: data['type'] ?? 'blood_pressure',
          value: (data['value'] ?? 0).toDouble(), // Store systolic as primary value
          measuredAt: (data['measuredAt'] as Timestamp).toDate(),
          createdAt: (data['createdAt'] as Timestamp).toDate(),
          source: data['source'] ?? 'manual',
        );
      }).toList();
    } catch (e) {
      print('❌ Error fetching blood pressure data: $e');
      return [];
    }
  }
}
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
  static Future<List<HealthDataModel>> getBloodPressureData({int days = 30}) async {
    try {
      final user = _auth.currentUser;
      if (user == null) {
        print('⚠️ User not authenticated, returning empty list');
        return [];
      }

      final DateTime cutoffDate = DateTime.now().subtract(Duration(days: days));

      // Use simple query to avoid index requirements like heart rate service
      final querySnapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: user.uid)
          .where('type', isEqualTo: 'blood_pressure')
          .get(); // No orderBy to avoid compound index requirement

      final List<HealthDataModel> allData = querySnapshot.docs.map((doc) {
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

      // Filter by date and sort in memory
      final filteredData = allData
          .where((data) => data.measuredAt.isAfter(cutoffDate))
          .toList();
      
      // Sort by measuredAt (newest first)
      filteredData.sort((a, b) => b.measuredAt.compareTo(a.measuredAt));

      print('✅ Found ${filteredData.length} blood pressure readings (last $days days)');
      return filteredData;
    } on FirebaseException catch (e) {
      print('❌ Firebase error fetching blood pressure data: ${e.code} - ${e.message}');
      return [];
    } catch (e) {
      print('❌ Error fetching blood pressure data: $e');
      return [];
    }
  }
}
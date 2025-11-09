import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';

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
  /// Returns a list of Maps containing both systolic and diastolic values
  static Future<List<Map<String, dynamic>>> getBloodPressureData({int days = 30}) async {
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

      // Create a custom list that includes both systolic and diastolic data
      final List<Map<String, dynamic>> allDataWithBP = querySnapshot.docs.map((doc) {
        final data = doc.data();
        return {
          'id': doc.id,
          'elderlyId': data['elderlyId'] ?? '',
          'type': data['type'] ?? 'blood_pressure',
          'systolic': (data['systolic'] ?? data['value'] ?? 0).toDouble(),
          'diastolic': (data['diastolic'] ?? 0).toDouble(), // Read actual diastolic from Firestore
          'measuredAt': (data['measuredAt'] as Timestamp).toDate(),
          'createdAt': (data['createdAt'] as Timestamp).toDate(),
          'source': data['source'] ?? 'manual',
        };
      }).toList();

      // Filter by date
      final filteredData = allDataWithBP
          .where((data) => (data['measuredAt'] as DateTime).isAfter(cutoffDate))
          .toList();
      
      // Sort by measuredAt (newest first)
      filteredData.sort((a, b) => 
        (b['measuredAt'] as DateTime).compareTo(a['measuredAt'] as DateTime));

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
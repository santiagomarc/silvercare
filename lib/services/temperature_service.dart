import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/health_data_model.dart';

class TemperatureService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Save temperature data to Firestore
  static Future<String> saveTemperatureData({
    required double value,
    required DateTime measuredAt,
    String? unit,
    String source = 'manual',
  }) async {
    try {
      final User? user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated. Please sign in first.');
      }

      // Get elderly ID (assuming current user is elderly)
      final String elderlyId = user.uid;

      final temperatureData = HealthDataModel(
        id: '', // Will be set by Firestore
        elderlyId: elderlyId,
        type: 'temperature',
        measuredAt: measuredAt,
        createdAt: DateTime.now(),
        value: value,
        source: source,
      );

      // Save to Firestore
      final DocumentReference docRef = await _firestore
          .collection('health_data')
          .add(temperatureData.toMap());

      print('✅ Temperature saved: ${value.toStringAsFixed(1)}°C (source: $source)');
      return docRef.id;
    } on FirebaseException catch (e) {
      print('❌ Firebase error saving temperature data: ${e.code} - ${e.message}');
      throw Exception('Failed to save temperature: ${e.message}');
    } catch (error) {
      print('❌ Error saving temperature data: $error');
      throw Exception('Failed to save temperature: $error');
    }
  }

  /// Get temperature data from Firestore
  static Future<List<HealthDataModel>> getTemperatureData({int days = 7}) async {
    try {
      final User? user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated. Please sign in first.');
      }

      final String elderlyId = user.uid;
      final DateTime cutoffDate = DateTime.now().subtract(Duration(days: days));

      // Use simple query to avoid index requirements
      final querySnapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: elderlyId)
          .where('type', isEqualTo: 'temperature')
          .get(); // No orderBy to avoid compound index requirement

      // Filter by date and sort in memory
      final List<HealthDataModel> allData = querySnapshot.docs
          .map((doc) => HealthDataModel.fromDoc(doc))
          .toList();

      final filteredData = allData
          .where((data) => data.measuredAt.isAfter(cutoffDate))
          .toList();
      
      // Sort by measuredAt (newest first)
      filteredData.sort((a, b) => b.measuredAt.compareTo(a.measuredAt));

      print('✅ Retrieved ${filteredData.length} temperature records');
      return filteredData;
    } on FirebaseException catch (e) {
      print('❌ Firebase error getting temperature data: ${e.code} - ${e.message}');
      throw Exception('Failed to get temperature data: ${e.message}');
    } catch (error) {
      print('❌ Error getting temperature data: $error');
      throw Exception('Failed to get temperature data: $error');
    }
  }
}

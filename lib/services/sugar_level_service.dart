import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/health_data_model.dart';

class SugarLevelService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Save sugar level data to Firestore
  static Future<String> saveSugarLevelData({
    required double value,
    required DateTime measuredAt,
    String source = 'manual',
  }) async {
    try {
      final User? user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated. Please sign in first.');
      }

      // Get elderly ID (assuming current user is elderly)
      final String elderlyId = user.uid;

      final sugarData = HealthDataModel(
        id: '', // Will be set by Firestore
        elderlyId: elderlyId,
        type: 'sugar_level',
        measuredAt: measuredAt,
        createdAt: DateTime.now(),
        value: value,
        source: source,
      );

      // Save to Firestore
      final DocumentReference docRef = await _firestore
          .collection('health_data')
          .add(sugarData.toMap());

      print('✅ Sugar level saved: ${value.toInt()} mg/dL (source: $source)');
      return docRef.id;
    } on FirebaseException catch (e) {
      print('❌ Firebase error saving sugar level data: ${e.code} - ${e.message}');
      throw Exception('Failed to save sugar level: ${e.message}');
    } catch (error) {
      print('❌ Error saving sugar level data: $error');
      throw Exception('Failed to save sugar level: $error');
    }
  }

  /// Get sugar level data from Firestore
  static Future<List<HealthDataModel>> getSugarLevelData({int days = 7}) async {
    try {
      final User? user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated. Please sign in first.');
      }

      final String elderlyId = user.uid;
      final DateTime cutoffDate = DateTime.now().subtract(Duration(days: days));

      // Use simple query without compound indexes like blood pressure service
      final querySnapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: elderlyId)
          .where('type', isEqualTo: 'sugar_level')
          .get(); // No orderBy, no additional where clauses

      final List<HealthDataModel> allData = querySnapshot.docs
          .map((doc) => HealthDataModel.fromDoc(doc))
          .toList();

      // Filter by date and sort in memory
      final filteredData = allData
          .where((data) => data.measuredAt.isAfter(cutoffDate))
          .toList();
      
      // Sort by measuredAt (newest first)
      filteredData.sort((a, b) => b.measuredAt.compareTo(a.measuredAt));

      print('✅ Retrieved ${filteredData.length} sugar level records');
      return filteredData;
    } on FirebaseException catch (e) {
      print('❌ Firebase error getting sugar level data: ${e.code} - ${e.message}');
      return [];
    } catch (error) {
      print('❌ Error getting sugar level data: $error');
      return [];
    }
  }
}

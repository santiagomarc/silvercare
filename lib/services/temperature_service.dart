import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/health_data_model.dart';
import 'google_fit_service.dart';

class TemperatureService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Check if two timestamps are within 10 seconds of each other
  /// This allows multiple readings in the same minute while avoiding exact duplicates
  static bool _areSimilarTimestamps(DateTime t1, DateTime t2) {
    final diff = t1.difference(t2).abs();
    return diff.inSeconds < 10;
  }

  /// Sync temperature data from Google Fit
  static Future<int> syncFromGoogleFit({int days = 7}) async {
    try {
      print('🔄 Starting temperature sync from Google Fit...');
      print('📅 Syncing data from last $days days');

      // Fetch existing data to avoid duplicates
      final existingData = await getTemperatureData(days: days);
      print('📊 Found ${existingData.length} existing temperature records in Firestore');
      
      final existingTimestamps = existingData
          .map((temp) => temp.measuredAt)
          .toList();

      // Fetch from Google Fit
      final fitData = await GoogleFitService.fetchTemperatureData(days: days);
      print('📊 Retrieved ${fitData.length} temperature readings from Google Fit');

      int newCount = 0;
      final List<DateTime> processedInThisSync = []; // Track what we've saved in this sync
      
      for (final temp in fitData) {
        print('🔍 Checking temperature ${temp.celsius}°C at ${temp.timestamp}');
        
        // Check against existing Firestore data (within 10 seconds)
        bool isDuplicateInFirestore = existingTimestamps.any((existing) => 
          _areSimilarTimestamps(existing, temp.timestamp)
        );
        
        if (isDuplicateInFirestore) {
          print('⏭️ Skipping duplicate temperature at ${temp.timestamp} (already in Firestore)');
          continue;
        }
        
        // Check against what we've already processed in THIS sync session (within 10 seconds)
        bool isDuplicateInThisSync = processedInThisSync.any((processed) => 
          _areSimilarTimestamps(processed, temp.timestamp)
        );
        
        if (isDuplicateInThisSync) {
          print('⏭️ Skipping duplicate temperature at ${temp.timestamp} (already processed in this sync)');
          continue;
        }
        
        print('✅ NEW temperature data found! Saving ${temp.celsius}°C at ${temp.timestamp}');

        // Save new reading
        await saveTemperatureData(
          value: temp.celsius,
          measuredAt: temp.timestamp,
          source: 'google_fit',
        );
        
        // Track that we've processed this timestamp
        processedInThisSync.add(temp.timestamp);
        newCount++;
      }

      print('✅ Temperature sync complete: $newCount new readings');
      return newCount;
    } catch (e) {
      print('❌ Error syncing temperature from Google Fit: $e');
      return 0;
    }
  }

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

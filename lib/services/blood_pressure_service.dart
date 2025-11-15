import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'google_fit_service.dart';

class BloodPressureService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Check if two timestamps are within 10 seconds of each other
  /// This allows multiple readings in the same minute while avoiding exact duplicates
  static bool _areSimilarTimestamps(DateTime t1, DateTime t2) {
    final diff = t1.difference(t2).abs();
    return diff.inSeconds < 10;
  }

  /// Sync blood pressure data from Google Fit
  static Future<int> syncFromGoogleFit({int days = 7}) async {
    try {
      print('🔄 Starting blood pressure sync from Google Fit...');
      print('📅 Syncing data from last $days days');

      // Fetch existing data to avoid duplicates
      final existingData = await getBloodPressureData(days: days);
      print('📊 Found ${existingData.length} existing BP records in Firestore');
      
      final existingTimestamps = existingData
          .map((bp) => bp['measuredAt'] as DateTime)
          .toList();
      
      print('🕐 First 5 existing timestamps:');
      for (final ts in existingTimestamps.take(5)) {
        print('   - $ts');
      }

      // Fetch from Google Fit
      final fitData = await GoogleFitService.fetchBloodPressureData(days: days);
      print('📊 Retrieved ${fitData.length} BP readings from Google Fit');
      
      if (fitData.isEmpty) {
        print('⚠️ No data returned from Google Fit API');
        return 0;
      }

      int newCount = 0;
      final List<DateTime> processedInThisSync = []; // Track what we've saved in this sync
      
      for (final bp in fitData) {
        print('🔍 Checking BP ${bp.systolic}/${bp.diastolic} at ${bp.timestamp}');
        
        // Check against existing Firestore data (within 10 seconds)
        bool isDuplicateInFirestore = existingTimestamps.any((existing) => 
          _areSimilarTimestamps(existing, bp.timestamp)
        );
        
        if (isDuplicateInFirestore) {
          print('⏭️ Skipping duplicate BP at ${bp.timestamp} (already in Firestore)');
          continue;
        }
        
        // Check against what we've already processed in THIS sync session (within 10 seconds)
        bool isDuplicateInThisSync = processedInThisSync.any((processed) => 
          _areSimilarTimestamps(processed, bp.timestamp)
        );
        
        if (isDuplicateInThisSync) {
          print('⏭️ Skipping duplicate BP at ${bp.timestamp} (already processed in this sync)');
          continue;
        }

        print('✅ NEW BP data found! Saving ${bp.systolic}/${bp.diastolic} at ${bp.timestamp}');
        
        // Save new reading
        await saveBloodPressureData(
          systolic: bp.systolic,
          diastolic: bp.diastolic,
          measuredAt: bp.timestamp,
          source: 'google_fit',
        );
        
        // Track that we've processed this timestamp
        processedInThisSync.add(bp.timestamp);
        newCount++;
      }

      print('✅ Blood pressure sync complete: $newCount new readings');
      return newCount;
    } catch (e) {
      print('❌ Error syncing blood pressure from Google Fit: $e');
      return 0;
    }
  }

  /// Save blood pressure data to Firestore
  static Future<void> saveBloodPressureData({
    required double systolic,
    required double diastolic,
    required DateTime measuredAt,
    String source = 'manual',
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
        'source': source,
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
      print('📅 Fetching BP data from ${cutoffDate.toString()} to now');

      // Use simple query to avoid index requirements like heart rate service
      final querySnapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: user.uid)
          .where('type', isEqualTo: 'blood_pressure')
          .get(); // No orderBy to avoid compound index requirement

      print('📊 Query returned ${querySnapshot.docs.length} total BP documents');

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
      
      print('📊 After date filter: ${filteredData.length} BP records (last $days days)');
      
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
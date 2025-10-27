import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/health_data_model.dart';
import 'google_fit_service.dart';

class HeartRateService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Save heart rate data to Firestore
  static Future<String> saveHeartRateData({
    required double bpm,
    required DateTime measuredAt,
    String source = 'manual',
  }) async {
    try {
      final User? user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated');
      }

      // Get elderly ID (assuming current user is elderly, or you might need to get it differently)
      final String elderlyId = user.uid;

      final HealthDataModel heartRateData = HealthDataModel(
        id: '', // Will be set by Firestore
        elderlyId: elderlyId,
        type: 'heart_rate',
        value: bpm,
        measuredAt: measuredAt,
        createdAt: DateTime.now(),
      );

      // Save to Firestore
      final DocumentReference docRef = await _firestore
          .collection('health_data')
          .add(heartRateData.toMap());

      print('✅ Heart rate saved: ${bpm.toInt()} bpm (source: $source)');
      return docRef.id;
    } catch (error) {
      print('❌ Error saving heart rate data: $error');
      rethrow;
    }
  }

  /// Fetch heart rate data from Google Fit and save to Firestore
  static Future<List<HealthDataModel>> syncFromGoogleFit({int days = 7}) async {
    try {
      // Check if user is signed in to Google
      if (!GoogleFitService.isSignedIn) {
        throw Exception('Not signed in to Google Fit');
      }

      // Fetch data from Google Fit
      final List<HeartRateData> googleFitData = await GoogleFitService.fetchHeartRateData(days: days);
      
      if (googleFitData.isEmpty) {
        print('📊 No heart rate data found in Google Fit');
        return [];
      }

      // Get existing heart rate data to avoid duplicates
      final List<HealthDataModel> existingData = await getHeartRateData(days: days);
      final Set<DateTime> existingTimestamps = existingData
          .map((data) => _roundToMinute(data.measuredAt))
          .toSet();

      final List<HealthDataModel> savedData = [];

      // Save new data points
      for (final heartRate in googleFitData) {
        final DateTime roundedTime = _roundToMinute(heartRate.timestamp);
        
        // Skip if we already have data for this time
        if (existingTimestamps.contains(roundedTime)) {
          continue;
        }

        try {
          final String docId = await saveHeartRateData(
            bpm: heartRate.bpm,
            measuredAt: heartRate.timestamp,
            source: 'google_fit',
          );

          savedData.add(HealthDataModel(
            id: docId,
            elderlyId: _auth.currentUser!.uid,
            type: 'heart_rate',
            value: heartRate.bpm,
            measuredAt: heartRate.timestamp,
            createdAt: DateTime.now(),
          ));
        } catch (error) {
          print('⚠️ Failed to save heart rate reading: $error');
        }
      }

      print('✅ Synced ${savedData.length} new heart rate readings from Google Fit');
      return savedData;
    } catch (error) {
      print('❌ Error syncing from Google Fit: $error');
      rethrow;
    }
  }

  /// Get heart rate data from Firestore
  static Future<List<HealthDataModel>> getHeartRateData({int days = 30}) async {
    try {
      final User? user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated');
      }

      final String elderlyId = user.uid;
      final DateTime startDate = DateTime.now().subtract(Duration(days: days));

      // Use the simplest possible query to avoid ANY index requirements
      final QuerySnapshot snapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: elderlyId)
          .where('type', isEqualTo: 'heart_rate')
          .get(); // No orderBy, no additional where clauses

      final List<HealthDataModel> allData = snapshot.docs
          .map((doc) => HealthDataModel.fromDoc(doc))
          .toList();

      // Filter by date and sort in memory
      final filteredData = allData
          .where((data) => data.measuredAt.isAfter(startDate))
          .toList();
      
      // Sort by measuredAt (newest first)
      filteredData.sort((a, b) => b.measuredAt.compareTo(a.measuredAt));
      
      print('✅ Found ${filteredData.length} heart rate readings (last $days days)');
      return filteredData;
    } catch (error) {
      print('❌ Error fetching heart rate data: $error');
      
      // If even the basic query fails, return empty list
      print('🔄 Returning empty list to prevent app crash');
      return [];
    }
  }

  /// Get latest heart rate reading
  static Future<HealthDataModel?> getLatestHeartRate() async {
    try {
      final List<HealthDataModel> data = await getHeartRateData(days: 1);
      return data.isNotEmpty ? data.first : null;
    } catch (error) {
      print('❌ Error fetching latest heart rate: $error');
      return null;
    }
  }

  /// Delete heart rate data
  static Future<void> deleteHeartRateData(String documentId) async {
    try {
      await _firestore.collection('health_data').doc(documentId).delete();
      print('✅ Heart rate data deleted');
    } catch (error) {
      print('❌ Error deleting heart rate data: $error');
      rethrow;
    }
  }

  /// Get heart rate statistics for a period
  static Future<HeartRateStats> getHeartRateStats({int days = 30}) async {
    try {
      final List<HealthDataModel> data = await getHeartRateData(days: days);
      
      if (data.isEmpty) {
        return HeartRateStats(
          average: 0,
          min: 0,
          max: 0,
          count: 0,
          latestReading: null,
        );
      }

      final List<double> values = data.map((d) => d.value).toList();
      final double average = values.reduce((a, b) => a + b) / values.length;
      final double min = values.reduce((a, b) => a < b ? a : b);
      final double max = values.reduce((a, b) => a > b ? a : b);

      return HeartRateStats(
        average: average,
        min: min,
        max: max,
        count: data.length,
        latestReading: data.first,
      );
    } catch (error) {
      print('❌ Error calculating heart rate stats: $error');
      return HeartRateStats(
        average: 0,
        min: 0,
        max: 0,
        count: 0,
        latestReading: null,
      );
    }
  }

  /// Round timestamp to nearest minute to avoid duplicate detection issues
  static DateTime _roundToMinute(DateTime dateTime) {
    return DateTime(
      dateTime.year,
      dateTime.month,
      dateTime.day,
      dateTime.hour,
      dateTime.minute,
    );
  }
}

/// Heart rate statistics model
class HeartRateStats {
  final double average;
  final double min;
  final double max;
  final int count;
  final HealthDataModel? latestReading;

  HeartRateStats({
    required this.average,
    required this.min,
    required this.max,
    required this.count,
    this.latestReading,
  });

  String get averageDisplay => '${average.toInt()} bpm';
  String get minDisplay => '${min.toInt()} bpm';
  String get maxDisplay => '${max.toInt()} bpm';
  String get rangeDisplay => '${min.toInt()}-${max.toInt()} bpm';

  bool get hasData => count > 0;

  @override
  String toString() {
    return 'HeartRateStats(avg: $averageDisplay, range: $rangeDisplay, count: $count)';
  }
}
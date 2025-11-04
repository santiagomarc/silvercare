import 'dart:convert';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;

class GoogleFitService {
  static const String _fitnessReadScope = 'https://www.googleapis.com/auth/fitness.heart_rate.read';
  static const String _fitnessApiUrl = 'https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate';
  
  // Google Sign In instance with fitness scope
  static final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: [_fitnessReadScope],
    forceCodeForRefreshToken: true,
    // Web client ID for testing in Chrome
    clientId: '288695034445-1apprq1ifhkvir41tepjj7l8g0hlh2rv.apps.googleusercontent.com',
  );

  /// Check if user is signed in to Google
  static bool get isSignedIn => _googleSignIn.currentUser != null;

  /// Get current Google user
  static GoogleSignInAccount? get currentUser => _googleSignIn.currentUser;

  /// Sign in to Google with fitness permissions
  /// This is separate from Firebase authentication
  static Future<GoogleSignInAccount?> signInWithGoogle() async {
    try {
      // First, try silent sign in to see if user was previously signed in
      GoogleSignInAccount? account = await _googleSignIn.signInSilently();
      
      if (account == null) {
        // No previous sign in, show account picker
        print('🔄 Showing Google account picker...');
        account = await _googleSignIn.signIn();
      }
      
      if (account != null) {
        // Ensure we have the required scope
        final GoogleSignInAuthentication auth = await account.authentication;
        print('✅ Google Sign In successful: ${account.email}');
        print('🔑 Access token available: ${auth.accessToken != null}');
        print('🏥 Ready to fetch Google Fit data');
        
        // Test if we can access the fitness API
        final bool hasAccess = await _testFitnessAccess(auth.accessToken!);
        if (!hasAccess) {
          print('⚠️ Google Fit access denied or no data available');
          throw Exception('Google Fit access denied. Please ensure your Google account has Google Fit data and permissions are granted.');
        }
      } else {
        print('❌ Google Sign In was cancelled by user');
      }
      
      return account;
    } catch (error) {
      print('❌ Google Sign In failed: $error');
      rethrow;
    }
  }

  /// Test if we have access to Google Fit API
  static Future<bool> _testFitnessAccess(String accessToken) async {
    try {
      final response = await http.get(
        Uri.parse('https://www.googleapis.com/fitness/v1/users/me/dataSources'),
        headers: {
          'Authorization': 'Bearer $accessToken',
          'Content-Type': 'application/json',
        },
      );
      
      print('🧪 Google Fit API test response: ${response.statusCode}');
      return response.statusCode == 200;
    } catch (e) {
      print('🧪 Google Fit API test failed: $e');
      return false;
    }
  }

  /// Sign out from Google (but keep Firebase auth)
  static Future<void> signOut() async {
    try {
      await _googleSignIn.signOut();
      print('👋 Signed out from Google Fit (Firebase auth preserved)');
    } catch (e) {
      print('⚠️ Error signing out from Google: $e');
    }
  }

  /// Fetch heart rate data from Google Fit for the last N days
  static Future<List<HeartRateData>> fetchHeartRateData({int days = 7}) async {
    try {
      final GoogleSignInAccount? account = _googleSignIn.currentUser;
      if (account == null) {
        throw Exception('User not signed in to Google');
      }

      final GoogleSignInAuthentication auth = await account.authentication;
      final String? accessToken = auth.accessToken;
      
      if (accessToken == null) {
        throw Exception('No access token available');
      }

      // Calculate time range (last N days)
      final DateTime endTime = DateTime.now();
      final DateTime startTime = endTime.subtract(Duration(days: days));
      
      print('🔄 Fetching raw heart rate data from Google Fit...');
      print('📅 Time range: ${startTime.toString()} to ${endTime.toString()}');

      // Try both aggregated approach and direct dataset approach
      List<HeartRateData> allData = [];
      
      // Method 1: Try aggregated data first
      try {
        final aggregatedData = await _fetchAggregatedHeartRateData(accessToken, startTime, endTime);
        allData.addAll(aggregatedData);
        print('📊 Got ${aggregatedData.length} readings from aggregated data');
      } catch (e) {
        print('⚠️ Aggregated data fetch failed: $e');
      }
      
      // Method 2: Try direct dataset query for more recent data
      try {
        final directData = await _fetchDirectHeartRateData(accessToken, startTime, endTime);
        allData.addAll(directData);
        print('� Got ${directData.length} readings from direct data sources');
      } catch (e) {
        print('⚠️ Direct data fetch failed: $e');
      }

      // Remove duplicates based on timestamp (within 1 minute)
      final Set<DateTime> seenTimestamps = <DateTime>{};
      final List<HeartRateData> uniqueData = [];
      
      for (final data in allData) {
        final roundedTime = DateTime(
          data.timestamp.year,
          data.timestamp.month,
          data.timestamp.day,
          data.timestamp.hour,
          data.timestamp.minute,
        );
        
        if (!seenTimestamps.contains(roundedTime)) {
          seenTimestamps.add(roundedTime);
          uniqueData.add(data);
        }
      }
      
      // Sort by timestamp (newest first)
      uniqueData.sort((a, b) => b.timestamp.compareTo(a.timestamp));
      
      print('✅ Total unique heart rate readings: ${uniqueData.length}');
      return uniqueData;
    } catch (error) {
      print('❌ Error fetching heart rate data: $error');
      rethrow;
    }
  }

  /// Fetch aggregated heart rate data
  static Future<List<HeartRateData>> _fetchAggregatedHeartRateData(
    String accessToken, 
    DateTime startTime, 
    DateTime endTime
  ) async {
    final Map<String, dynamic> requestBody = {
      'aggregateBy': [
        {
          'dataTypeName': 'com.google.heart_rate.bpm'
        }
      ],
      'bucketByTime': {
        'durationMillis': 3600000, // 1 hour buckets
      },
      'startTimeMillis': startTime.millisecondsSinceEpoch,
      'endTimeMillis': endTime.millisecondsSinceEpoch,
    };

    final response = await http.post(
      Uri.parse('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: json.encode(requestBody),
    );

    if (response.statusCode == 200) {
      final Map<String, dynamic> data = json.decode(response.body);
      return _parseHeartRateResponse(data);
    } else {
      throw Exception('Aggregated data API error: ${response.statusCode} - ${response.body}');
    }
  }

  /// Fetch direct heart rate data from data sources
  static Future<List<HeartRateData>> _fetchDirectHeartRateData(
    String accessToken, 
    DateTime startTime, 
    DateTime endTime
  ) async {
    try {
      // First, get available data sources
      final sourcesResponse = await http.get(
        Uri.parse('https://www.googleapis.com/fitness/v1/users/me/dataSources'),
        headers: {
          'Authorization': 'Bearer $accessToken',
          'Content-Type': 'application/json',
        },
      );

      if (sourcesResponse.statusCode != 200) {
        throw Exception('Failed to get data sources: ${sourcesResponse.statusCode}');
      }

      final sourcesData = json.decode(sourcesResponse.body);
      final dataSources = sourcesData['dataSource'] as List<dynamic>? ?? [];
      
      List<HeartRateData> allData = [];

      // Look for heart rate data sources
      for (final source in dataSources) {
        final dataType = source['dataType'];
        if (dataType != null && dataType['name'] == 'com.google.heart_rate.bpm') {
          final dataSourceId = source['dataStreamId'];
          if (dataSourceId != null) {
            print('📡 Fetching from data source: $dataSourceId');
            
            // Fetch data from this specific source
            final datasetId = '${startTime.millisecondsSinceEpoch * 1000000}-${endTime.millisecondsSinceEpoch * 1000000}';
            final dataUrl = 'https://www.googleapis.com/fitness/v1/users/me/dataSources/$dataSourceId/datasets/$datasetId';
            
            final dataResponse = await http.get(
              Uri.parse(dataUrl),
              headers: {
                'Authorization': 'Bearer $accessToken',
                'Content-Type': 'application/json',
              },
            );

            if (dataResponse.statusCode == 200) {
              final datasetData = json.decode(dataResponse.body);
              final points = datasetData['point'] as List<dynamic>? ?? [];
              
              for (final point in points) {
                try {
                  final String startTimeNanos = point['startTimeNanos']?.toString() ?? '0';
                  final List<dynamic> values = point['value'] ?? [];
                  
                  if (values.isNotEmpty && values[0] != null) {
                    double heartRate = 0.0;
                    final valueData = values[0];
                    
                    if (valueData['fpVal'] != null) {
                      heartRate = (valueData['fpVal']).toDouble();
                    } else if (valueData['intVal'] != null) {
                      heartRate = (valueData['intVal']).toDouble();
                    }
                    
                    if (heartRate > 0 && heartRate < 300) {
                      final DateTime timestamp = DateTime.fromMillisecondsSinceEpoch(
                        int.parse(startTimeNanos) ~/ 1000000
                      );
                      
                      allData.add(HeartRateData(
                        bpm: heartRate,
                        timestamp: timestamp,
                        source: 'google_fit',
                      ));
                      print('💓 Direct data: ${heartRate.toInt()} bpm at $timestamp');
                    }
                  }
                } catch (e) {
                  print('⚠️ Error parsing direct data point: $e');
                  continue;
                }
              }
            }
          }
        }
      }

      return allData;
    } catch (e) {
      print('⚠️ Error fetching direct data: $e');
      return [];
    }
  }

  /// Parse Google Fit API response to extract heart rate data
  static List<HeartRateData> _parseHeartRateResponse(Map<String, dynamic> data) {
    final List<HeartRateData> heartRateList = [];
    
    try {
      final List<dynamic> buckets = data['bucket'] ?? [];
      print('📦 Found ${buckets.length} data buckets');
      
      for (final bucket in buckets) {
        final List<dynamic> datasets = bucket['dataset'] ?? [];
        print('📂 Processing ${datasets.length} datasets in bucket');
        
        for (final dataset in datasets) {
          final List<dynamic> points = dataset['point'] ?? [];
          print('📍 Processing ${points.length} data points in dataset');
          
          for (final point in points) {
            try {
              final String startTimeNanos = point['startTimeNanos']?.toString() ?? '0';
              final List<dynamic> values = point['value'] ?? [];
              
              if (values.isNotEmpty && values[0] != null) {
                // Handle different value formats
                double heartRate = 0.0;
                final valueData = values[0];
                
                if (valueData['fpVal'] != null) {
                  heartRate = (valueData['fpVal']).toDouble();
                } else if (valueData['intVal'] != null) {
                  heartRate = (valueData['intVal']).toDouble();
                } else {
                  print('⚠️ Unknown value format: $valueData');
                  continue;
                }
                
                final DateTime timestamp = DateTime.fromMillisecondsSinceEpoch(
                  int.parse(startTimeNanos) ~/ 1000000
                );
                
                if (heartRate > 0 && heartRate < 300) { // Reasonable heart rate range
                  heartRateList.add(HeartRateData(
                    bpm: heartRate,
                    timestamp: timestamp,
                    source: 'google_fit',
                  ));
                  print('💓 Found heart rate: ${heartRate.toInt()} bpm at $timestamp');
                }
              }
            } catch (pointError) {
              print('⚠️ Error parsing point: $pointError');
              continue; // Skip this point, continue with others
            }
          }
        }
      }
      
      // Sort by timestamp (newest first)
      heartRateList.sort((a, b) => b.timestamp.compareTo(a.timestamp));
      
      print('✅ Successfully parsed ${heartRateList.length} heart rate readings from Google Fit');
      return heartRateList;
      
    } catch (error) {
      print('❌ Error parsing heart rate response: $error');
      print('📄 Raw response data: $data');
      return [];
    }
  }

  /// Test connection to Google Fit API
  static Future<bool> testConnection() async {
    try {
      final data = await fetchHeartRateData(days: 1);
      print('🧪 Google Fit connection test: ${data.isNotEmpty ? "SUCCESS" : "NO DATA"}');
      return true;
    } catch (error) {
      print('🧪 Google Fit connection test: FAILED - $error');
      return false;
    }
  }
}

/// Heart rate data model for Google Fit
class HeartRateData {
  final double bpm;
  final DateTime timestamp;
  final String source;

  HeartRateData({
    required this.bpm,
    required this.timestamp,
    required this.source,
  });

  @override
  String toString() {
    return 'HeartRateData(bpm: $bpm, timestamp: $timestamp, source: $source)';
  }
}
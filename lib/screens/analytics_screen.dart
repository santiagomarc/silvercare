import 'dart:async';

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:silvercare/services/user_service.dart';
import 'package:silvercare/widgets/blood_pressure_analytics_card.dart';
import 'package:silvercare/widgets/sugar_level_analytics_card.dart';
import 'package:silvercare/widgets/temperature_analytics_card.dart';
import 'package:silvercare/widgets/heart_rate_analytics_card.dart';
import 'package:silvercare/widgets/health_score_card.dart';
import 'package:silvercare/widgets/bmi_wellness_card.dart';
import 'package:silvercare/widgets/health_trends_card.dart';

class AnalyticsScreen extends StatefulWidget {
  const AnalyticsScreen({super.key});

  @override
  State<AnalyticsScreen> createState() => _AnalyticsScreenState();
}

class _AnalyticsScreenState extends State<AnalyticsScreen> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  String? _managingElderlyId;
  bool _isLoading = true;

  // Firestore listener subscription
  StreamSubscription<QuerySnapshot>? _healthDataSubscription;
  // Last snapshot cache for local recomputation on filter change
  QuerySnapshot? _lastHealthSnapshot;
  
  // Timer to update the "time since" text
  Timer? _updateTimer;

  // Analytics data
  Map<String, dynamic> _bloodPressureData = {
    'average': '0/0',
    'status': 'No Data',
    'statusColor': Colors.grey,
    'count': 0,
  };
  Map<String, dynamic> _sugarLevelData = {
    'average': 0.0,
    'status': 'No Data',
    'statusColor': Colors.grey,
    'count': 0,
  };
  Map<String, dynamic> _temperatureData = {
    'average': 0.0,
    'status': 'No Data',
    'statusColor': Colors.grey,
    'count': 0,
  };
  Map<String, dynamic> _heartRateData = {
    'average': 0.0,
    'status': 'No Data',
    'statusColor': Colors.grey,
    'count': 0,
  };
  String _trendInsight = '';
  List<String> _insights = [];
  DateTime? _lastUpdated;
  
  // Stores ALL BP records for chart (not filtered by date)
  List<Map<String, dynamic>> _bpChartRecords = [];

  // Stores ALL temperature records for chart
  List<Map<String, dynamic>> _tempChartRecords = [];

  // Stores ALL heart rate records for chart
  List<Map<String, dynamic>> _hrChartRecords = [];

  // Stores ALL sugar records for chart
  List<Map<String, dynamic>> _sugarChartRecords = [];

  // Returns a list of blood pressure records for the chart
  List<Map<String, dynamic>> _getBloodPressureChartData() {
    return _bpChartRecords;
  }

  // Returns a list of temperature records for the chart
  List<Map<String, dynamic>> _getTemperatureChartData() {
    return _tempChartRecords;
  }

  // Returns a list of heart rate records for the chart
  List<Map<String, dynamic>> _getHeartRateChartData() {
    return _hrChartRecords;
  }

  // Returns a list of sugar records for the chart
  List<Map<String, dynamic>> _getSugarChartData() {
    return _sugarChartRecords;
  }

  @override
  void initState() {
    super.initState();
    _fetchAnalyticsData();
    
    // Start timer to update "time since" text every minute
    _updateTimer = Timer.periodic(Duration(minutes: 1), (timer) {
      if (mounted) {
        setState(() {
          // Just trigger rebuild to update the time text
        });
      }
    });
  }

  @override
  void dispose() {
    _healthDataSubscription?.cancel();
    _updateTimer?.cancel();
    super.dispose();
  }

  // Start (or restart) a realtime listener for health_data for elderlyId + current date filter.
  void _startHealthDataListener(String elderlyId) {
    // Cancel existing first
    _healthDataSubscription?.cancel();

    // Listen only by elderlyId to avoid index/missing measuredAt issues; filter by date client-side.
    final query = FirebaseFirestore.instance
        .collection('health_data')
        .where('elderlyId', isEqualTo: elderlyId);

    _healthDataSubscription = query
        .snapshots(includeMetadataChanges: true)
        .listen((snapshot) {
      if (!mounted) return;
      _lastHealthSnapshot = snapshot;
      // Recompute directly from snapshot to avoid an extra round-trip
      _recomputeFromSnapshot(snapshot);
    }, onError: (error) {
      print('Health data listener error: $error');
    });
  }

  // Recomputes all statistics from a health_data snapshot - now showing only latest same-day record
  void _recomputeFromSnapshot(QuerySnapshot snapshot) {
    final todayStart = _getTodayStart();

    // Separate docs by type - collect ALL for charts, filter for today for overview
    final allBpDocs = <QueryDocumentSnapshot>[];
    final allSugarDocs = <QueryDocumentSnapshot>[];
    final allTempDocs = <QueryDocumentSnapshot>[];
    final allHrDocs = <QueryDocumentSnapshot>[];
    
    final todayBpDocs = <QueryDocumentSnapshot>[];
    final todaySugarDocs = <QueryDocumentSnapshot>[];
    final todayTempDocs = <QueryDocumentSnapshot>[];
    final todayHrDocs = <QueryDocumentSnapshot>[];

    for (final doc in snapshot.docs) {
      final data = doc.data() as Map<String, dynamic>;
      DateTime? measured;
      if (data['measuredAt'] is Timestamp) {
        measured = (data['measuredAt'] as Timestamp).toDate();
      } else if (data['createdAt'] is Timestamp) {
        measured = (data['createdAt'] as Timestamp).toDate();
      }
      
      final isToday = measured != null && measured.isAfter(todayStart);

      switch (data['type']) {
        case 'blood_pressure':
          allBpDocs.add(doc);
          if (isToday) todayBpDocs.add(doc);
          break;
        case 'sugar_level':
          allSugarDocs.add(doc);
          if (isToday) todaySugarDocs.add(doc);
          break;
        case 'temperature':
          allTempDocs.add(doc);
          if (isToday) todayTempDocs.add(doc);
          break;
        case 'heart_rate':
          allHrDocs.add(doc);
          if (isToday) todayHrDocs.add(doc);
          break;
      }
    }

    // Populate chart data with ALL records
    _bpChartRecords = allBpDocs.map((doc) {
      final data = doc.data() as Map<String, dynamic>;
      DateTime measured = DateTime.now();
      if (data['measuredAt'] is Timestamp) {
        measured = (data['measuredAt'] as Timestamp).toDate();
      }
      return {
        'systolic': (data['systolic'] ?? 0).toDouble(),
        'diastolic': (data['diastolic'] ?? 0).toDouble(),
        'measuredAt': measured,
      };
    }).toList();

    _sugarChartRecords = allSugarDocs.map((doc) {
      final data = doc.data() as Map<String, dynamic>;
      DateTime measured = DateTime.now();
      if (data['measuredAt'] is Timestamp) {
        measured = (data['measuredAt'] as Timestamp).toDate();
      }
      return {
        'value': (data['value'] ?? 0).toDouble(),
        'measuredAt': measured,
      };
    }).toList();

    _tempChartRecords = allTempDocs.map((doc) {
      final data = doc.data() as Map<String, dynamic>;
      DateTime measured = DateTime.now();
      if (data['measuredAt'] is Timestamp) {
        measured = (data['measuredAt'] as Timestamp).toDate();
      }
      return {
        'value': (data['value'] ?? 0).toDouble(),
        'measuredAt': measured,
      };
    }).toList();

    _hrChartRecords = allHrDocs.map((doc) {
      final data = doc.data() as Map<String, dynamic>;
      DateTime measured = DateTime.now();
      if (data['measuredAt'] is Timestamp) {
        measured = (data['measuredAt'] as Timestamp).toDate();
      }
      return {
        'value': (data['value'] ?? 0).toDouble(),
        'measuredAt': measured,
      };
    }).toList();

    // Blood pressure - get latest TODAY record only for overview
    if (todayBpDocs.isNotEmpty) {
      todayBpDocs.sort((a, b) {
        final aData = a.data() as Map<String, dynamic>;
        final bData = b.data() as Map<String, dynamic>;
        DateTime aTime = DateTime.now();
        DateTime bTime = DateTime.now();
        if (aData['measuredAt'] is Timestamp) {
          aTime = (aData['measuredAt'] as Timestamp).toDate();
        }
        if (bData['measuredAt'] is Timestamp) {
          bTime = (bData['measuredAt'] as Timestamp).toDate();
        }
        return bTime.compareTo(aTime); // Descending
      });
      
      final latestDoc = todayBpDocs.first;
      final data = latestDoc.data() as Map<String, dynamic>;
      final sys = (data['systolic'] ?? 0).toDouble().round();
      final dia = (data['diastolic'] ?? 0).toDouble().round();
      
      _bloodPressureData = {
        'average': '$sys/$dia',
        'status': _getBloodPressureStatus(sys, dia),
        'statusColor': _getBloodPressureStatusColor(sys, dia),
        'count': 1,
      };
    } else {
      _bloodPressureData = {
        'average': '0/0',
        'status': 'No Data',
        'statusColor': Colors.grey,
        'count': 0,
      };
    }

    // Sugar level - get latest TODAY record only for overview
    if (todaySugarDocs.isNotEmpty) {
      todaySugarDocs.sort((a, b) {
        final aData = a.data() as Map<String, dynamic>;
        final bData = b.data() as Map<String, dynamic>;
        DateTime aTime = DateTime.now();
        DateTime bTime = DateTime.now();
        if (aData['measuredAt'] is Timestamp) {
          aTime = (aData['measuredAt'] as Timestamp).toDate();
        }
        if (bData['measuredAt'] is Timestamp) {
          bTime = (bData['measuredAt'] as Timestamp).toDate();
        }
        return bTime.compareTo(aTime);
      });
      
      final latestDoc = todaySugarDocs.first;
      final data = latestDoc.data() as Map<String, dynamic>;
      final val = (data['value'] ?? 0).toDouble();
      
      _sugarLevelData = {
        'average': val,
        'status': _getSugarLevelStatus(val),
        'statusColor': _getSugarLevelStatusColor(val),
        'count': 1,
      };
    } else {
      _sugarLevelData = {
        'average': 0.0,
        'status': 'No Data',
        'statusColor': Colors.grey,
        'count': 0,
      };
    }

    // Temperature - get latest TODAY record only for overview
    if (todayTempDocs.isNotEmpty) {
      todayTempDocs.sort((a, b) {
        final aData = a.data() as Map<String, dynamic>;
        final bData = b.data() as Map<String, dynamic>;
        DateTime aTime = DateTime.now();
        DateTime bTime = DateTime.now();
        if (aData['measuredAt'] is Timestamp) {
          aTime = (aData['measuredAt'] as Timestamp).toDate();
        }
        if (bData['measuredAt'] is Timestamp) {
          bTime = (bData['measuredAt'] as Timestamp).toDate();
        }
        return bTime.compareTo(aTime);
      });
      
      final latestDoc = todayTempDocs.first;
      final data = latestDoc.data() as Map<String, dynamic>;
      final val = (data['value'] ?? 0).toDouble();
      
      _temperatureData = {
        'average': val,
        'status': _getTemperatureStatus(val),
        'statusColor': _getTemperatureStatusColor(val),
        'count': 1,
      };
    } else {
      _temperatureData = {
        'average': 0.0,
        'status': 'No Data',
        'statusColor': Colors.grey,
        'count': 0,
      };
    }

    // Heart rate - get latest TODAY record only for overview
    if (todayHrDocs.isNotEmpty) {
      todayHrDocs.sort((a, b) {
        final aData = a.data() as Map<String, dynamic>;
        final bData = b.data() as Map<String, dynamic>;
        DateTime aTime = DateTime.now();
        DateTime bTime = DateTime.now();
        if (aData['measuredAt'] is Timestamp) {
          aTime = (aData['measuredAt'] as Timestamp).toDate();
        }
        if (bData['measuredAt'] is Timestamp) {
          bTime = (bData['measuredAt'] as Timestamp).toDate();
        }
        return bTime.compareTo(aTime);
      });
      
      final latestDoc = todayHrDocs.first;
      final data = latestDoc.data() as Map<String, dynamic>;
      final val = (data['value'] ?? 0).toDouble();
      
      _heartRateData = {
        'average': val,
        'status': _getHeartRateStatus(val),
        'statusColor': _getHeartRateStatusColor(val),
        'count': 1,
      };
    } else {
      _heartRateData = {
        'average': 0.0,
        'status': 'No Data',
        'statusColor': Colors.grey,
        'count': 0,
      };
    }

    // Analyze trends (using today's docs for insights)
    _analyzeTrends(todayBpDocs, todaySugarDocs, todayTempDocs, todayHrDocs);

    setState(() {
      _lastUpdated = DateTime.now();
    });
  }

  Future<void> _fetchAnalyticsData() async {
    try {
      // Get user profile
      final profile = await UserService.getUserProfile(_auth.currentUser?.uid);
      if (!mounted) return;

      print('=== ANALYTICS DATA FETCH ===');
      print('Current user ID: ${_auth.currentUser?.uid}');
      print('Profile data: $profile');

      if (profile != null) {
        // Determine the ID to use based on user type
        final userType = profile['userType'] as String?;
        String? targetId;

        if (userType == 'elderly') {
          // If elderly user, use their own ID
          targetId = profile['userId'] as String? ?? _auth.currentUser?.uid;
          print('✅ User is ELDERLY - using their own ID: $targetId');
        } else if (userType == 'caregiver') {
          // If caregiver, look for linked elderly ID
          targetId = profile['elderlyId'] as String?;
          print('User is CAREGIVER - looking for linked elderly ID: $targetId');
        } else {
          // Unknown user type, use current user ID as fallback
          targetId = _auth.currentUser?.uid;
          print('⚠️ Unknown user type - using current user ID: $targetId');
        }

        print('Target ID for health data query: $targetId');

        if (targetId != null && targetId.isNotEmpty) {
          setState(() {
            _managingElderlyId = targetId;
          });

          // Start realtime listener (this will trigger updates when data changes)
          _startHealthDataListener(targetId);

          // Do an initial fetch immediately for UI responsiveness
          await _fetchHealthStatistics(targetId);
        } else {
          print('⚠️ No valid ID found to fetch health data');
        }
      } else {
        print('⚠️ Profile is null');
      }
    } catch (e) {
      print("❌ Error fetching analytics data: $e");
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  String _getTimeSinceUpdate() {
    if (_lastUpdated == null) return 'just now';
    
    final difference = DateTime.now().difference(_lastUpdated!);
    
    if (difference.inSeconds < 60) {
      return 'just now';
    } else if (difference.inMinutes < 60) {
      final mins = difference.inMinutes;
      return '$mins min${mins != 1 ? 's' : ''} ago';
    } else if (difference.inHours < 24) {
      final hours = difference.inHours;
      return '$hours hr${hours != 1 ? 's' : ''} ago';
    } else {
      final days = difference.inDays;
      return '$days day${days != 1 ? 's' : ''} ago';
    }
  }

  DateTime _getTodayStart() {
    final now = DateTime.now();
    return DateTime(now.year, now.month, now.day);
  }

  Future<void> _fetchHealthStatistics(String elderlyId) async {
    try {
      final todayStart = _getTodayStart();

      print('=== FETCHING HEALTH STATISTICS ===');
      print('Fetching health data for elderly: $elderlyId');
      print('Today start: $todayStart');

      // Fetch all data for this elderlyId (will filter client-side for today)
      final snapshot = await FirebaseFirestore.instance
          .collection('health_data')
          .where('elderlyId', isEqualTo: elderlyId)
          .get();

      print('Found ${snapshot.docs.length} total document(s)');
      
      // Recompute using existing snapshot logic
      _recomputeFromSnapshot(snapshot);

    } catch (e) {
      print("❌ Error fetching health statistics: $e");
    }
  }

  void _analyzeTrends(
    List<QueryDocumentSnapshot> bpDocs,
    List<QueryDocumentSnapshot> sugarDocs,
    List<QueryDocumentSnapshot> tempDocs,
    List<QueryDocumentSnapshot> hrDocs,
  ) {
    List<String> insights = [];
    int totalRecords = bpDocs.length + sugarDocs.length + tempDocs.length + hrDocs.length;

    // Since we're only showing today's data, insights should reflect that
    if (bpDocs.isNotEmpty) {
      if (_bloodPressureData['status'] == 'High') {
        insights.add('🩸 Blood Pressure: Elevated today. Consider reducing salt intake and increasing physical activity.');
      } else if (_bloodPressureData['status'] == 'Low') {
        insights.add('🩸 Blood Pressure: Low reading today. Ensure adequate hydration and avoid sudden position changes.');
      } else {
        insights.add('🩸 Blood Pressure: Within normal range today.');
      }
    }

    if (sugarDocs.isNotEmpty) {
      if (_sugarLevelData['status'] == 'High') {
        insights.add('🍬 Sugar Level: High reading today. Monitor carbohydrate intake and consider consulting your healthcare provider.');
      } else if (_sugarLevelData['status'] == 'Low') {
        insights.add('🍬 Sugar Level: Low reading today. Have a balanced snack if you feel symptoms.');
      } else {
        insights.add('🍬 Sugar Level: Within normal range today.');
      }
    }

    if (tempDocs.isNotEmpty) {
      if (_temperatureData['status'] == 'Fever') {
        insights.add('🌡️ Temperature: Elevated temperature detected today. Monitor closely and stay hydrated.');
      } else if (_temperatureData['status'] == 'Low') {
        insights.add('🌡️ Temperature: Below normal today. Ensure you\'re warm and comfortable.');
      } else {
        insights.add('🌡️ Temperature: Normal today.');
      }
    }

    if (hrDocs.isNotEmpty) {
      if (_heartRateData['status'] == 'High') {
        insights.add('❤️ Heart Rate: Elevated today. Consider rest and relaxation.');
      } else if (_heartRateData['status'] == 'Low') {
        insights.add('❤️ Heart Rate: Lower than usual today. If you feel unwell, consult a healthcare provider.');
      } else {
        insights.add('❤️ Heart Rate: Normal today.');
      }
    }

    if (totalRecords == 0) {
      insights.add('No health data recorded today. Consider tracking your vitals.');
    } else if (totalRecords >= 3) {
      insights.add('✅ Great job tracking your health today with $totalRecords reading(s)!');
    }

    setState(() {
      _trendInsight = totalRecords > 0 
          ? 'Today\'s overview ($totalRecords reading${totalRecords != 1 ? 's' : ''})' 
          : 'No readings recorded today';
      _insights = insights;
    });
  }

  // Status helpers
  String _getBloodPressureStatus(int systolic, int diastolic) {
    if (systolic == 0 && diastolic == 0) return "No Data";
    if (systolic < 90 || diastolic < 60) return "Low";
    if (systolic > 140 || diastolic > 90) return "High";
    return "Normal";
  }

  Color _getBloodPressureStatusColor(int systolic, int diastolic) {
    if (systolic == 0 && diastolic == 0) return Colors.grey;
    if (systolic < 90 || diastolic < 60) return Colors.blue;
    if (systolic > 140 || diastolic > 90) return Colors.red;
    return Colors.green;
  }

  String _getSugarLevelStatus(double sugar) {
    if (sugar == 0) return "No Data";
    if (sugar < 70) return "Low";
    if (sugar >= 140) return "High";
    return "Normal";
  }

  Color _getSugarLevelStatusColor(double sugar) {
    if (sugar == 0) return Colors.grey;
    if (sugar < 70) return Colors.blue;
    if (sugar > 140) return Colors.red;
    return Colors.green;
  }

  String _getTemperatureStatus(double temp) {
    if (temp == 0) return "No Data";
    if (temp < 36.1) return "Low";
    if (temp > 37.5) return "Fever";
    return "Normal";
  }

  Color _getTemperatureStatusColor(double temp) {
    if (temp == 0) return Colors.grey;
    if (temp < 36.1) return Colors.blue;
    if (temp > 37.5) return Colors.red;
    return Colors.green;
  }

  String _getHeartRateStatus(double hr) {
    if (hr == 0) return "No Data";
    if (hr < 60) return "Low";
    if (hr > 100) return "High";
    return "Normal";
  }

  Color _getHeartRateStatusColor(double hr) {
    if (hr == 0) return Colors.grey;
    if (hr < 60) return Colors.blue;
    if (hr > 100) return Colors.red;
    return Colors.green;
  }

  // Responsive font size helper to match other screens
  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    if (screenWidth < 360) {
      return baseSize * 0.9;
    } else if (screenWidth > 600) {
      return baseSize * 1.2;
    }
    return baseSize;
  }

   Widget _ScreenHeaderButton(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 10, 20, 20),
      height: 80,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(40),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.15),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
        border: Border.all(color: Colors.orangeAccent.withOpacity(0.2), width: 2),
      ),
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.analytics, size: 28, color: Colors.orangeAccent),
            const SizedBox(width: 12),
            Text(
              'ANALYTICS',
              style: TextStyle(
                color: Color(0xFF2D3748),
                fontSize: _getResponsiveFontSize(context, 28),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                letterSpacing: 1.2,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryAndInsights() {
    return Card(
      elevation: 3,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              children: [
                Icon(Icons.medical_services, size: 24, color: Colors.blue.shade700),
                const SizedBox(width: 12),
                Text(
                  'Latest Health Records',
                  style: TextStyle(
                    fontSize: _getResponsiveFontSize(context, 18),
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            
            // Auto-update indicator and last updated time
            Row(
              children: [
                Icon(Icons.autorenew, size: 14, color: Colors.green.shade600),
                const SizedBox(width: 4),
                Text(
                  'Auto-updates',
                  style: TextStyle(
                    fontSize: _getResponsiveFontSize(context, 11),
                    color: Colors.green.shade600,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(width: 8),
                if (_lastUpdated != null) ...[
                  Text(
                    '• Updated ${_getTimeSinceUpdate()}',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 10),
                      color: Colors.grey.shade500,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 8),

            // Latest record label
            Padding(
              padding: const EdgeInsets.only(left: 4),
              child: Text(
                'Latest readings (today only)',
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 12),
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(height: 16),

            // 4 Health Vitals in a Row
            Row(
              children: [
                Expanded(
                  child: _buildVitalCard(
                    title: 'Blood Pressure',
                    value: _bloodPressureData['average'],
                    unit: 'mmHg',
                    status: _bloodPressureData['status'],
                    statusColor: _bloodPressureData['statusColor'],
                    icon: Icons.bloodtype,
                    iconColor: Colors.orange.shade700,
                    count: _bloodPressureData['count'],
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildVitalCard(
                    title: 'Sugar Level',
                    value: _sugarLevelData['average'] == 0.0
                        ? '0'
                        : _sugarLevelData['average'].toStringAsFixed(1),
                    unit: 'mg/dL',
                    status: _sugarLevelData['status'],
                    statusColor: _sugarLevelData['statusColor'],
                    icon: Icons.water_drop,
                    iconColor: Colors.green.shade700,
                    count: _sugarLevelData['count'],
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildVitalCard(
                    title: 'Temperature',
                    value: _temperatureData['average'] == 0.0
                        ? '0'
                        : _temperatureData['average'].toStringAsFixed(1),
                    unit: '°C',
                    status: _temperatureData['status'],
                    statusColor: _temperatureData['statusColor'],
                    icon: Icons.thermostat,
                    iconColor: Colors.blue.shade700,
                    count: _temperatureData['count'],
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildVitalCard(
                    title: 'Heart Rate',
                    value: _heartRateData['average'] == 0.0
                        ? '0'
                        : _heartRateData['average'].toStringAsFixed(0),
                    unit: 'bpm',
                    status: _heartRateData['status'],
                    statusColor: _heartRateData['statusColor'],
                    icon: Icons.favorite,
                    iconColor: Colors.pink.shade700,
                    count: _heartRateData['count'],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInsightsSection() {
    return Card(
      elevation: 3,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Insights Section Header
            Row(
              children: [
                Icon(Icons.insights, size: 20, color: Colors.purple.shade700),
                const SizedBox(width: 8),
                Text(
                  'Health Insights',
                  style: TextStyle(
                    fontSize: _getResponsiveFontSize(context, 16),
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Trend Summary
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blue.shade200),
              ),
              child: Row(
                children: [
                  Icon(Icons.trending_up, size: 20, color: Colors.blue.shade700),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      _trendInsight.isEmpty ? 'Analyzing trends...' : _trendInsight,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 14),
                        fontWeight: FontWeight.w600,
                        color: Colors.black87,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // Detailed Insights
            ..._insights.map((insight) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Icon(Icons.fiber_manual_record, size: 8, color: Colors.grey.shade600),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      insight,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 13),
                        color: Colors.grey.shade700,
                        height: 1.4,
                      ),
                    ),
                  ),
                ],
              ),
            )).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildVitalCard({
    required String title,
    required String value,
    required String unit,
    required String status,
    required Color statusColor,
    required IconData icon,
    required Color iconColor,
    required int count,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: iconColor.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: iconColor.withOpacity(0.2), width: 1.5),
      ),
      child: Column(
        children: [
          Icon(icon, size: 24, color: iconColor),
          const SizedBox(height: 8),
          Text(
            title,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 10),
              fontWeight: FontWeight.w600,
              color: Colors.grey.shade700,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 18),
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          Text(
            unit,
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 9),
              color: Colors.grey.shade600,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
            decoration: BoxDecoration(
              color: statusColor.withOpacity(0.2),
              borderRadius: BorderRadius.circular(6),
              border: Border.all(color: statusColor, width: 1),
            ),
            child: Text(
              status,
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 9),
                fontWeight: FontWeight.bold,
                color: statusColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    return Scaffold(
      backgroundColor: Color(0xFFF8F9FA),
      body: SingleChildScrollView(
      
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
          // Title Header
          _ScreenHeaderButton(context),
          const SizedBox(height: 5),
    
          // Health Score Card (New - High Impact)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: HealthScoreCard(
              bpData: _bloodPressureData,
              sugarData: _sugarLevelData,
              tempData: _temperatureData,
              hrData: _heartRateData,
            ),
          ),
          const SizedBox(height: 16),

          // BMI & Wellness Card (New)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: BMIWellnessCard(),
          ),
          const SizedBox(height: 16),
    
          // Combined Summary and Insights
          _buildSummaryAndInsights(),
          const SizedBox(height: 16),
    
          // Insights Section (separate card)
          _buildInsightsSection(),
          const SizedBox(height: 24),            // Blood Pressure Card
            BloodPressureAnalyticsCard(
              bpData: _getBloodPressureChartData(),
              onTapDetails: () {
              },
            ),
            const SizedBox(height: 16),
      
            // Sugar Level Card - pass sugar chart records
            SugarLevelAnalyticsCard(
              sugarData: _getSugarChartData(),
            ),
            const SizedBox(height: 16),
      
            // Temperature Card - pass the prepared temp chart records
            TemperatureAnalyticsCard(
              tempData: _getTemperatureChartData(),
            ),
            const SizedBox(height: 16),
      
            // Heart Rate Card - pass the prepared hr chart records
            HeartRateAnalyticsCard(
              hrData: _getHeartRateChartData(),
            ),
            const SizedBox(height: 24),

            // Weekly Trends Comparison (Moved to bottom for better context)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: HealthTrendsCard(),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}
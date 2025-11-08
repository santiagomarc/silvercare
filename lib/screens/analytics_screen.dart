import 'dart:async';

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:silvercare/services/user_service.dart';
import 'package:silvercare/widgets/blood_pressure_analytics_card.dart';
import 'package:silvercare/widgets/sugar_level_analytics_card.dart';
import 'package:silvercare/widgets/temperature_analytics_card.dart';
import 'package:silvercare/widgets/heart_rate_analytics_card.dart';
// ...existing imports...

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

  // Filter
  String _selectedFilter = 'Week'; // Day, Week, Month

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
  // Stores filtered BP records for chart
  List<Map<String, dynamic>> _bpChartRecords = [];

  // Stores filtered temperature records for chart
  List<Map<String, dynamic>> _tempChartRecords = [];

  // Stores filtered heart rate records for chart
  List<Map<String, dynamic>> _hrChartRecords = [];

  // Stores filtered sugar records for chart
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
  }

  @override
  void dispose() {
    _healthDataSubscription?.cancel();
    super.dispose();
  }

  // Start (or restart) a realtime listener for health_data for elderlyId + current date filter.
  void _startHealthDataListener(String elderlyId) {
    // Cancel existing first
    _healthDataSubscription?.cancel();

    final startDate = _getFilterStartDate();

    Query query = FirebaseFirestore.instance
        .collection('health_data')
        .where('elderlyId', isEqualTo: elderlyId);

    // Try to apply date filter; if it fails runtime (index), the listener will fallback to unfiltered if needed.
    try {
      query = query.where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate));
    } catch (e) {
      // If Firestore throws about indexing or inability to use inequality, we keep query without date filter.
      print('Could not apply measuredAt inequality to listener: $e');
    }

    _healthDataSubscription = query.snapshots().listen((snapshot) {
      // On any snapshot change, re-run the statistics fetch to re-calculate and update charts.
      // We call the same function which performs queries and updates state.
      if (mounted) {
        _fetchHealthStatistics(elderlyId);
      }
    }, onError: (error) {
      print('Health data listener error: $error');
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

  DateTime _getFilterStartDate() {
    final now = DateTime.now();
    switch (_selectedFilter) {
      case 'Day':
        return now.subtract(const Duration(days: 1));
      case 'Week':
        return now.subtract(const Duration(days: 7));
      case 'Month':
        return now.subtract(const Duration(days: 30));
      default:
        return now.subtract(const Duration(days: 7));
    }
  }

  Future<void> _fetchHealthStatistics(String elderlyId) async {
    try {
      final startDate = _getFilterStartDate();

      print('=== FETCHING HEALTH STATISTICS ===');
      print('Fetching health data for elderly: $elderlyId');
      print('Start date: $startDate');
      print('Selected filter: $_selectedFilter');

      // First, let's check if there's ANY data for this elderlyId
      final testQuery = await FirebaseFirestore.instance
          .collection('health_data')
          .where('elderlyId', isEqualTo: elderlyId)
          .limit(1)
          .get();

      print('Test query found ${testQuery.docs.length} document(s)');
      if (testQuery.docs.isNotEmpty) {
        print('✅ Sample document found: ${testQuery.docs.first.data()}');
        print('Document ID: ${testQuery.docs.first.id}');
      } else {
        print('⚠️ NO DOCUMENTS FOUND for elderlyId: $elderlyId');
      }

      // Fetch each type (try with date filter; fallback to without)
      QuerySnapshot bpSnapshot;
      try {
        bpSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'blood_pressure')
            .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
            .get();
      } catch (e) {
        print('Error fetching BP with date filter (falling back): $e');
        bpSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'blood_pressure')
            .get();
      }

      QuerySnapshot sugarSnapshot;
      try {
        sugarSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'sugar_level')
            .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
            .get();
      } catch (e) {
        print('Error fetching Sugar with date filter (falling back): $e');
        sugarSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'sugar_level')
            .get();
      }

      QuerySnapshot tempSnapshot;
      try {
        tempSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'temperature')
            .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
            .get();
      } catch (e) {
        print('Error fetching Temp with date filter (falling back): $e');
        tempSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'temperature')
            .get();
      }

      QuerySnapshot hrSnapshot;
      try {
        hrSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'heart_rate')
            .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
            .get();
      } catch (e) {
        print('Error fetching HR with date filter (falling back): $e');
        hrSnapshot = await FirebaseFirestore.instance
            .collection('health_data')
            .where('elderlyId', isEqualTo: elderlyId)
            .where('type', isEqualTo: 'heart_rate')
            .get();
      }

      // In-memory date filtering (if we had to fall back)
      List<QueryDocumentSnapshot> filterByDate(QuerySnapshot snapshot) {
        final filtered = snapshot.docs.where((doc) {
          final data = doc.data() as Map<String, dynamic>;
          if (data['measuredAt'] is Timestamp) {
            final measuredAt = (data['measuredAt'] as Timestamp).toDate();
            return measuredAt.isAfter(startDate);
          }
          return true;
        }).toList();
        return filtered;
      }

      final filteredBpDocs = filterByDate(bpSnapshot);
      final filteredSugarDocs = filterByDate(sugarSnapshot);
      final filteredTempDocs = filterByDate(tempSnapshot);
      final filteredHrDocs = filterByDate(hrSnapshot);

      print('FINAL COUNTS - BP: ${filteredBpDocs.length}, Sugar: ${filteredSugarDocs.length}, Temp: ${filteredTempDocs.length}, HR: ${filteredHrDocs.length}');

      // --- Blood Pressure ---
      if (filteredBpDocs.isNotEmpty) {
        double totalSystolic = 0;
        double totalDiastolic = 0;
        List<Map<String, dynamic>> chartRecords = [];

        for (var doc in filteredBpDocs) {
          final data = doc.data() as Map<String, dynamic>;
          totalSystolic += (data['systolic'] ?? 0).toDouble();
          totalDiastolic += (data['diastolic'] ?? 0).toDouble();

          chartRecords.add({
            'systolic': (data['systolic'] ?? 0).toDouble(),
            'diastolic': (data['diastolic'] ?? 0).toDouble(),
            'measuredAt': data['measuredAt'] is Timestamp ? (data['measuredAt'] as Timestamp).toDate() : DateTime.now(),
          });
        }

        final avgSystolic = (totalSystolic / filteredBpDocs.length).round();
        final avgDiastolic = (totalDiastolic / filteredBpDocs.length).round();

        setState(() {
          _bloodPressureData = {
            'average': '$avgSystolic/$avgDiastolic',
            'status': _getBloodPressureStatus(avgSystolic, avgDiastolic),
            'statusColor': _getBloodPressureStatusColor(avgSystolic, avgDiastolic),
            'count': filteredBpDocs.length,
          };
          _bpChartRecords = chartRecords;
        });
      } else {
        setState(() {
          _bloodPressureData = {
            'average': '0/0',
            'status': 'No Data',
            'statusColor': Colors.grey,
            'count': 0,
          };
          _bpChartRecords = [];
        });
      }

      // --- Sugar Level ---
      if (filteredSugarDocs.isNotEmpty) {
        double total = 0;
        List<Map<String, dynamic>> sugarChart = [];
        for (var doc in filteredSugarDocs) {
          final data = doc.data() as Map<String, dynamic>;
          final value = (data['value'] ?? 0).toDouble();
          total += value;

          DateTime measuredAt;
          if (data['measuredAt'] is Timestamp) {
            measuredAt = (data['measuredAt'] as Timestamp).toDate();
          } else if (data['measuredAt'] is DateTime) {
            measuredAt = data['measuredAt'] as DateTime;
          } else {
            measuredAt = DateTime.now();
          }

          sugarChart.add({
            'value': value,
            'measuredAt': measuredAt,
          });
        }
        final avg = total / filteredSugarDocs.length;

        setState(() {
          _sugarLevelData = {
            'average': avg,
            'status': _getSugarLevelStatus(avg),
            'statusColor': _getSugarLevelStatusColor(avg),
            'count': filteredSugarDocs.length,
          };
          _sugarChartRecords = sugarChart;
        });
      } else {
        setState(() {
          _sugarLevelData = {
            'average': 0.0,
            'status': 'No Data',
            'statusColor': Colors.grey,
            'count': 0,
          };
          _sugarChartRecords = [];
        });
      }

      // --- Temperature ---
      if (filteredTempDocs.isNotEmpty) {
        double total = 0;
        List<Map<String, dynamic>> tempChart = [];
        for (var doc in filteredTempDocs) {
          final data = doc.data() as Map<String, dynamic>;
          final value = (data['value'] ?? 0).toDouble();
          total += value;

          DateTime measuredAt;
          if (data['measuredAt'] is Timestamp) {
            measuredAt = (data['measuredAt'] as Timestamp).toDate();
          } else if (data['measuredAt'] is DateTime) {
            measuredAt = data['measuredAt'] as DateTime;
          } else {
            measuredAt = DateTime.now();
          }

          tempChart.add({
            'value': value,
            'measuredAt': measuredAt,
          });
        }
        final avg = total / filteredTempDocs.length;

        setState(() {
          _temperatureData = {
            'average': avg,
            'status': _getTemperatureStatus(avg),
            'statusColor': _getTemperatureStatusColor(avg),
            'count': filteredTempDocs.length,
          };
          _tempChartRecords = tempChart;
        });
      } else {
        setState(() {
          _temperatureData = {
            'average': 0.0,
            'status': 'No Data',
            'statusColor': Colors.grey,
            'count': 0,
          };
          _tempChartRecords = [];
        });
      }

      // --- Heart Rate ---
      if (filteredHrDocs.isNotEmpty) {
        double total = 0;
        List<Map<String, dynamic>> hrChart = [];
        for (var doc in filteredHrDocs) {
          final data = doc.data() as Map<String, dynamic>;
          final value = (data['value'] ?? 0).toDouble();
          total += value;

          DateTime measuredAt;
          if (data['measuredAt'] is Timestamp) {
            measuredAt = (data['measuredAt'] as Timestamp).toDate();
          } else if (data['measuredAt'] is DateTime) {
            measuredAt = data['measuredAt'] as DateTime;
          } else {
            measuredAt = DateTime.now();
          }

          hrChart.add({
            'value': value,
            'measuredAt': measuredAt,
          });
        }
        final avg = total / filteredHrDocs.length;

        setState(() {
          _heartRateData = {
            'average': avg,
            'status': _getHeartRateStatus(avg),
            'statusColor': _getHeartRateStatusColor(avg),
            'count': filteredHrDocs.length,
          };
          _hrChartRecords = hrChart;
        });
      } else {
        setState(() {
          _heartRateData = {
            'average': 0.0,
            'status': 'No Data',
            'statusColor': Colors.grey,
            'count': 0,
          };
          _hrChartRecords = [];
        });
      }

      // Analyze trends with FILTERED data
      _analyzeTrends(filteredBpDocs, filteredSugarDocs, filteredTempDocs, filteredHrDocs);
    } catch (e) {
      print("Error fetching health statistics: $e");
    }
  }

  void _analyzeTrends(
    List<QueryDocumentSnapshot> bpDocs,
    List<QueryDocumentSnapshot> sugarDocs,
    List<QueryDocumentSnapshot> tempDocs,
    List<QueryDocumentSnapshot> hrDocs,
  ) {
    List<String> insights = [];

    // Total records (now using filtered lists)
    final totalRecords = bpDocs.length + sugarDocs.length + tempDocs.length + hrDocs.length;

    // Get filter period text for insights
    String periodText = _selectedFilter == 'Day' ? 'today' : _selectedFilter == 'Week' ? 'this week' : 'this month';

    if (totalRecords == 0) {
      setState(() {
        _trendInsight = 'No data recorded in the selected period.';
        _insights = ['No health data recorded $periodText. Start tracking your vitals to see insights.'];
      });
      return;
    }

    // Count alerts
    int alerts = 0;
    if (_bloodPressureData['status'] != 'Normal' && _bloodPressureData['status'] != 'No Data') alerts++;
    if (_sugarLevelData['status'] != 'Normal' && _sugarLevelData['status'] != 'No Data') alerts++;
    if (_temperatureData['status'] != 'Normal' && _temperatureData['status'] != 'No Data') alerts++;
    if (_heartRateData['status'] != 'Normal' && _heartRateData['status'] != 'No Data') alerts++;

    // Generate trend insight
    String trendText = '';
    if (alerts == 0) {
      trendText = '✓ All vitals are within normal range';
    } else if (alerts == 1) {
      trendText = '⚠ 1 vital requires attention';
    } else {
      trendText = '⚠ $alerts vitals require attention';
    }

    // Generate specific insights for each vital

    // Blood Pressure insights
    if (_bloodPressureData['count'] > 0) {
      if (_bloodPressureData['status'] == 'High') {
        insights.add('🩸 Blood Pressure: Elevated. Consider reducing salt intake and increasing physical activity.');
      } else if (_bloodPressureData['status'] == 'Low') {
        insights.add('🩸 Blood Pressure: Low. Ensure adequate hydration and avoid sudden position changes.');
      } else {
        insights.add('🩸 Blood Pressure: Stable and within normal range (${_bloodPressureData['count']} readings).');
      }
    } else {
      insights.add('🩸 Blood Pressure: No data recorded $periodText.');
    }

    // Sugar Level insights
    if (_sugarLevelData['count'] > 0) {
      if (_sugarLevelData['status'] == 'High') {
        insights.add('🍬 Sugar Level: Elevated. Monitor carbohydrate intake and consult with healthcare provider.');
      } else if (_sugarLevelData['status'] == 'Low') {
        insights.add('🍬 Sugar Level: Low. Consider regular meal timing and balanced nutrition.');
      } else {
        insights.add('🍬 Sugar Level: Normal range maintained (${_sugarLevelData['count']} readings).');
      }
    } else {
      insights.add('🍬 Sugar Level: No data recorded $periodText.');
    }

    // Temperature insights
    if (_temperatureData['count'] > 0) {
      if (_temperatureData['status'] == 'Fever') {
        insights.add('🌡️ Temperature: Elevated. Monitor for other symptoms and ensure adequate rest.');
      } else if (_temperatureData['status'] == 'Low') {
        insights.add('🌡️ Temperature: Below normal. Keep warm and monitor how you feel.');
      } else {
        insights.add('🌡️ Temperature: Normal and stable (${_temperatureData['count']} readings).');
      }
    } else {
      insights.add('🌡️ Temperature: No data recorded $periodText.');
    }

    // Heart Rate insights
    if (_heartRateData['count'] > 0) {
      if (_heartRateData['status'] == 'High') {
        insights.add('❤️ Heart Rate: Elevated. Ensure adequate rest and monitor stress levels.');
      } else if (_heartRateData['status'] == 'Low') {
        insights.add('❤️ Heart Rate: Below normal. Consult healthcare provider if symptoms persist.');
      } else {
        insights.add('❤️ Heart Rate: Within normal range (${_heartRateData['count']} readings).');
      }
    } else {
      insights.add('❤️ Heart Rate: No data recorded $periodText.');
    }

    // Monitoring frequency insight based on selected filter
    if (_selectedFilter == 'Day') {
      if (totalRecords >= 4) {
        insights.add('Great job! You\'ve tracked multiple vitals today.');
      } else if (totalRecords > 0) {
        insights.add('You have $totalRecords reading(s) today. Consider tracking more vitals.');
      }
    } else if (_selectedFilter == 'Week') {
      if (totalRecords < 5) {
        insights.add('Consider more frequent monitoring this week for better health tracking.');
      } else if (totalRecords >= 10) {
        insights.add('Excellent monitoring consistency this week! Keep it up.');
      } else {
        insights.add('You have $totalRecords readings this week.');
      }
    } else { // Month
      if (totalRecords < 10) {
        insights.add('Try to monitor your vitals more regularly this month.');
      } else if (totalRecords >= 30) {
        insights.add('Outstanding! You\'ve been consistently tracking your health this month.');
      } else {
        insights.add('You have $totalRecords readings this month. Regular tracking helps identify trends.');
      }
    }

    setState(() {
      _trendInsight = '$trendText ($totalRecords readings $periodText)';
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

  Widget _buildSectionTitle(BuildContext context) {
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
        border: Border.all(color: const Color.fromRGBO(108, 99, 255, 0.2), width: 2),
      ),
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.analytics, size: 28, color: Color(0xFFFFB300)),
            const SizedBox(width: 12),
            Text(
              'Analytics',
              style: TextStyle(
                color: Color(0xFF2D3748),
                fontSize: _getResponsiveFontSize(context, 24),
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
            // Header with Filter Buttons
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Icon(Icons.medical_services, size: 24, color: Colors.blue.shade700),
                    const SizedBox(width: 12),
                    Text(
                      'Health Overview',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 18),
                        fontWeight: FontWeight.bold,
                        color: Colors.black87,
                      ),
                    ),
                  ],
                ),
                // Filter Dropdown
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade700,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.blue.shade700, width: 1.5),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: _selectedFilter,
                      icon: Icon(Icons.arrow_drop_down, color: Colors.white),
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 12),
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                      dropdownColor: Colors.blue.shade700,
                      items: ['Day', 'Week', 'Month'].map((String value) {
                        return DropdownMenuItem<String>(
                          value: value,
                          child: Text(
                            value,
                            style: TextStyle(color: Colors.white),
                          ),
                        );
                      }).toList(),
                      onChanged: (String? newValue) async {
                        if (newValue != null && _managingElderlyId != null) {
                          setState(() {
                            _selectedFilter = newValue;
                          });
                          // restart listener so it uses the new date filter
                          _startHealthDataListener(_managingElderlyId!);
                          await _fetchHealthStatistics(_managingElderlyId!);
                        }
                      },
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Average Label
            Padding(
              padding: const EdgeInsets.only(left: 4),
              child: Text(
                'Average values for selected period',
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

            const SizedBox(height: 20),
            Divider(color: Colors.grey.shade300),
            const SizedBox(height: 16),

            // Insights Section
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
          const SizedBox(height: 6),
          Text(
            '$count record${count != 1 ? 's' : ''}',
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 9),
              color: Colors.grey.shade600,
              fontStyle: FontStyle.italic,
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

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Title Header
          _buildSectionTitle(context),
          const SizedBox(height: 5),

          // Combined Summary and Insights
          _buildSummaryAndInsights(),
          const SizedBox(height: 24),

          // Blood Pressure Card
          BloodPressureAnalyticsCard(
            bpData: _getBloodPressureChartData(),
            onTapDetails: () {
              // TODO: Navigate to detailed BP view
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
        ],
      ),
    );
  }
}
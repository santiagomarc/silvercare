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
import 'dart:async';
import 'package:intl/intl.dart';

class CaregiverAnalytics extends StatefulWidget {
  const CaregiverAnalytics({Key? key}) : super(key: key);

  @override
  State<CaregiverAnalytics> createState() => _CaregiverAnalyticsState();
}

class _CaregiverAnalyticsState extends State<CaregiverAnalytics> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  
  String? _elderlyId;
  String _elderlyName = 'Patient';
  bool _isLoading = true;

  // Smart metrics
  double _medicationAdherence = 0.0;
  int _missedDosesCount = 0;
  int _totalDosesCount = 0;
  
  double _taskCompletionRate = 0.0;
  int _completedTasksCount = 0;
  int _totalTasksCount = 0;
  
  int _criticalAlertsCount = 0;
  double _activityScore = 0.0;
  
  // Health data for cards
  Map<String, dynamic> _bloodPressureData = {'average': '0/0', 'status': 'No Data', 'statusColor': Colors.grey, 'count': 0};
  Map<String, dynamic> _sugarLevelData = {'average': 0.0, 'status': 'No Data', 'statusColor': Colors.grey, 'count': 0};
  Map<String, dynamic> _temperatureData = {'average': 0.0, 'status': 'No Data', 'statusColor': Colors.grey, 'count': 0};
  Map<String, dynamic> _heartRateData = {'average': 0.0, 'status': 'No Data', 'statusColor': Colors.grey, 'count': 0};
  
  List<Map<String, dynamic>> _bpChartRecords = [];
  List<Map<String, dynamic>> _tempChartRecords = [];
  List<Map<String, dynamic>> _hrChartRecords = [];
  List<Map<String, dynamic>> _sugarChartRecords = [];

  @override
  void initState() {
    super.initState();
    _loadElderlyDataAndAnalytics();
  }

  Future<void> _loadElderlyDataAndAnalytics() async {
    try {
      final profile = await UserService.getUserProfile(_auth.currentUser?.uid);
      if (profile != null && profile['elderlyId'] != null) {
        _elderlyId = profile['elderlyId'];
        
        // Get elderly name
        final elderlyDoc = await _firestore.collection('elderly').doc(_elderlyId).get();
        if (elderlyDoc.exists) {
          _elderlyName = elderlyDoc.data()?['username'] ?? 'Patient';
        }

        await Future.wait([
          _calculateMedicationAdherence(),
          _calculateTaskCompletion(),
          _loadHealthData(),
          _calculateCriticalAlerts(),
          _calculateActivityScore(),
        ]);
      }

      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading analytics: $e');
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _calculateMedicationAdherence() async {
    if (_elderlyId == null) return;

    try {
      final now = DateTime.now();
      final weekAgo = now.subtract(Duration(days: 7));

      // Get all medication schedules
      final schedules = await _firestore
          .collection('medication_schedules')
          .where('elderlyId', isEqualTo: _elderlyId)
          .get();

      int totalExpectedDoses = 0;
      int takenDoses = 0;

      for (var schedule in schedules.docs) {
        final data = schedule.data();
        final timesOfDay = List<String>.from(data['timesOfDay'] ?? []);
        final startDate = (data['startDate'] as Timestamp).toDate();

        // Count expected doses in the past week
        for (int i = 0; i < 7; i++) {
          final checkDate = weekAgo.add(Duration(days: i));
          if (checkDate.isAfter(startDate) && checkDate.isBefore(now)) {
            totalExpectedDoses += timesOfDay.length;

            // Check completion for each dose time
            for (var time in timesOfDay) {
              final doseId = '${schedule.id}_${checkDate.toIso8601String().substring(0, 10)}_${time.replaceAll(':', '')}';
              final completion = await _firestore
                  .collection('dose_completions')
                  .doc(doseId)
                  .get();

              if (completion.exists && completion.data()?['isTaken'] == true) {
                takenDoses++;
              }
            }
          }
        }
      }

      _totalDosesCount = totalExpectedDoses;
      _missedDosesCount = totalExpectedDoses - takenDoses;
      _medicationAdherence = totalExpectedDoses > 0 ? (takenDoses / totalExpectedDoses) * 100 : 100.0;
    } catch (e) {
      print('Error calculating medication adherence: $e');
    }
  }

  Future<void> _calculateTaskCompletion() async {
    if (_elderlyId == null) return;

    try {
      final now = DateTime.now();
      final weekAgo = now.subtract(Duration(days: 7));

      final tasks = await _firestore
          .collection('checklist_items')
          .where('elderlyId', isEqualTo: _elderlyId)
          .where('dueDate', isGreaterThanOrEqualTo: Timestamp.fromDate(weekAgo))
          .get();

      _totalTasksCount = tasks.docs.length;
      _completedTasksCount = tasks.docs.where((doc) => doc.data()['isCompleted'] == true).length;
      _taskCompletionRate = _totalTasksCount > 0 ? (_completedTasksCount / _totalTasksCount) * 100 : 100.0;
    } catch (e) {
      print('Error calculating task completion: $e');
    }
  }

  Future<void> _loadHealthData() async {
    if (_elderlyId == null) return;

    try {
      final now = DateTime.now();
      final today = DateTime(now.year, now.month, now.day);

      final healthDocs = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: _elderlyId)
          .orderBy('measuredAt', descending: true)
          .limit(200)
          .get();

      // Process all health data
      List<Map<String, dynamic>> bpRecords = [];
      List<Map<String, dynamic>> sugarRecords = [];
      List<Map<String, dynamic>> tempRecords = [];
      List<Map<String, dynamic>> hrRecords = [];

      double bpSysTotal = 0, bpDiaTotal = 0, sugarTotal = 0, tempTotal = 0, hrTotal = 0;
      int bpCount = 0, sugarCount = 0, tempCount = 0, hrCount = 0;

      for (var doc in healthDocs.docs) {
        final data = doc.data();
        final type = data['type'];
        final value = (data['value'] ?? 0).toDouble();
        final measuredAt = (data['measuredAt'] as Timestamp).toDate();

        switch (type) {
          case 'blood_pressure':
            final systolic = (data['systolic'] ?? value).toDouble();
            final diastolic = (data['diastolic'] ?? 0).toDouble();
            bpRecords.add({'systolic': systolic, 'diastolic': diastolic, 'measuredAt': measuredAt});
            if (measuredAt.isAfter(today)) {
              bpSysTotal += systolic;
              bpDiaTotal += diastolic;
              bpCount++;
            }
            break;
          case 'sugar_level':
            sugarRecords.add({'value': value, 'measuredAt': measuredAt});
            if (measuredAt.isAfter(today)) {
              sugarTotal += value;
              sugarCount++;
            }
            break;
          case 'temperature':
            tempRecords.add({'value': value, 'measuredAt': measuredAt});
            if (measuredAt.isAfter(today)) {
              tempTotal += value;
              tempCount++;
            }
            break;
          case 'heart_rate':
            hrRecords.add({'value': value, 'measuredAt': measuredAt});
            if (measuredAt.isAfter(today)) {
              hrTotal += value;
              hrCount++;
            }
            break;
        }
      }

      _bpChartRecords = bpRecords;
      _sugarChartRecords = sugarRecords;
      _tempChartRecords = tempRecords;
      _hrChartRecords = hrRecords;

      // Calculate averages
      if (bpCount > 0) {
        final avgSys = (bpSysTotal / bpCount).round();
        final avgDia = (bpDiaTotal / bpCount).round();
        _bloodPressureData = {
          'average': '$avgSys/$avgDia',
          'status': _getBPStatus(avgSys, avgDia),
          'statusColor': _getBPColor(avgSys, avgDia),
          'count': bpCount,
        };
      }

      if (sugarCount > 0) {
        final avg = sugarTotal / sugarCount;
        _sugarLevelData = {
          'average': avg,
          'status': _getSugarStatus(avg),
          'statusColor': _getSugarColor(avg),
          'count': sugarCount,
        };
      }

      if (tempCount > 0) {
        final avg = tempTotal / tempCount;
        _temperatureData = {
          'average': avg,
          'status': _getTempStatus(avg),
          'statusColor': _getTempColor(avg),
          'count': tempCount,
        };
      }

      if (hrCount > 0) {
        final avg = hrTotal / hrCount;
        _heartRateData = {
          'average': avg,
          'status': _getHRStatus(avg),
          'statusColor': _getHRColor(avg),
          'count': hrCount,
        };
      }
    } catch (e) {
      print('Error loading health data: $e');
    }
  }

  Future<void> _calculateCriticalAlerts() async {
    if (_elderlyId == null) return;

    try {
      final weekAgo = DateTime.now().subtract(Duration(days: 7));

      final notifications = await _firestore
          .collection('notifications')
          .where('elderlyId', isEqualTo: _elderlyId)
          .where('severity', isEqualTo: 'negative')
          .where('timestamp', isGreaterThanOrEqualTo: Timestamp.fromDate(weekAgo))
          .get();

      _criticalAlertsCount = notifications.docs.length;
    } catch (e) {
      print('Error calculating critical alerts: $e');
    }
  }

  Future<void> _calculateActivityScore() async {
    // Weighted activity score based on multiple factors
    final adherenceScore = _medicationAdherence * 0.4;
    final taskScore = _taskCompletionRate * 0.3;
    final alertPenalty = _criticalAlertsCount > 0 ? (_criticalAlertsCount * 5).clamp(0, 30) : 0;
    
    _activityScore = (adherenceScore + taskScore - alertPenalty).clamp(0, 100);
  }

  // Status helpers
  String _getBPStatus(int sys, int dia) {
    if (sys < 90 || dia < 60) return 'Low';
    if (sys > 140 || dia > 90) return 'High';
    return 'Normal';
  }

  Color _getBPColor(int sys, int dia) {
    if (sys < 90 || dia < 60) return Colors.blue;
    if (sys > 140 || dia > 90) return Colors.red;
    return Colors.green;
  }

  String _getSugarStatus(double sugar) {
    if (sugar < 70) return 'Low';
    if (sugar >= 140) return 'High';
    return 'Normal';
  }

  Color _getSugarColor(double sugar) {
    if (sugar < 70) return Colors.blue;
    if (sugar >= 140) return Colors.red;
    return Colors.green;
  }

  String _getTempStatus(double temp) {
    if (temp < 36.1) return 'Low';
    if (temp > 37.5) return 'Fever';
    return 'Normal';
  }

  Color _getTempColor(double temp) {
    if (temp < 36.1) return Colors.blue;
    if (temp > 37.5) return Colors.red;
    return Colors.green;
  }

  String _getHRStatus(double hr) {
    if (hr < 60) return 'Low';
    if (hr > 100) return 'High';
    return 'Normal';
  }

  Color _getHRColor(double hr) {
    if (hr < 60) return Colors.blue;
    if (hr > 100) return Colors.red;
    return Colors.green;
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: Colors.grey[100],
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_elderlyId == null) {
      return Scaffold(
        backgroundColor: Colors.grey[100],
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.person_off_outlined, size: 80, color: Colors.grey[400]),
                SizedBox(height: 16),
                Text(
                  'No Patient Assigned',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey[700],
                  ),
                ),
                SizedBox(height: 8),
                Text(
                  'Connect with an elderly patient to view analytics',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.grey[100],
      body: RefreshIndicator(
        onRefresh: _loadElderlyDataAndAnalytics,
        child: SingleChildScrollView(
          physics: AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF6C63FF), Color(0xFF5A52D5)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 8,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                padding: const EdgeInsets.fromLTRB(20, 60, 20, 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$_elderlyName\'s Analytics',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 28),
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Comprehensive health & activity overview',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 14),
                        color: Colors.white.withOpacity(0.9),
                      ),
                    ),
                  ],
                ),
              ),

              SizedBox(height: 16),

              // Smart Metrics Grid
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: _buildSmartMetricsGrid(),
              ),

              SizedBox(height: 20),

              // Health Score Card
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: HealthScoreCard(
                  bpData: _bloodPressureData,
                  sugarData: _sugarLevelData,
                  tempData: _temperatureData,
                  hrData: _heartRateData,
                ),
              ),

              SizedBox(height: 16),

              // BMI Wellness
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: BMIWellnessCard(),
              ),

              SizedBox(height: 16),

              // Detailed Vital Cards
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: BloodPressureAnalyticsCard(
                  bpData: _bpChartRecords,
                  onTapDetails: () {},
                ),
              ),

              SizedBox(height: 16),

              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: SugarLevelAnalyticsCard(sugarData: _sugarChartRecords),
              ),

              SizedBox(height: 16),

              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: TemperatureAnalyticsCard(tempData: _tempChartRecords),
              ),

              SizedBox(height: 16),

              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: HeartRateAnalyticsCard(hrData: _hrChartRecords),
              ),

              SizedBox(height: 16),

              // Weekly Trends
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: HealthTrendsCard(),
              ),

              SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSmartMetricsGrid() {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.35,
      children: [
        _buildMetricCard(
          title: 'Medication\nAdherence',
          value: '${_medicationAdherence.toStringAsFixed(0)}%',
          subtitle: '$_missedDosesCount of $_totalDosesCount missed',
          icon: Icons.medication_rounded,
          color: _medicationAdherence >= 80 ? Colors.green : (_medicationAdherence >= 60 ? Colors.orange : Colors.red),
          iconBgColor: _medicationAdherence >= 80 ? Colors.green.shade50 : (_medicationAdherence >= 60 ? Colors.orange.shade50 : Colors.red.shade50),
        ),
        _buildMetricCard(
          title: 'Task\nCompletion',
          value: '${_taskCompletionRate.toStringAsFixed(0)}%',
          subtitle: '$_completedTasksCount of $_totalTasksCount done',
          icon: Icons.check_circle_outline,
          color: _taskCompletionRate >= 80 ? Colors.blue : (_taskCompletionRate >= 60 ? Colors.orange : Colors.grey),
          iconBgColor: _taskCompletionRate >= 80 ? Colors.blue.shade50 : (_taskCompletionRate >= 60 ? Colors.orange.shade50 : Colors.grey.shade50),
        ),
        _buildMetricCard(
          title: 'Critical\nAlerts',
          value: '$_criticalAlertsCount',
          subtitle: 'Past 7 days',
          icon: Icons.warning_amber_rounded,
          color: _criticalAlertsCount == 0 ? Colors.green : (_criticalAlertsCount <= 3 ? Colors.orange : Colors.red),
          iconBgColor: _criticalAlertsCount == 0 ? Colors.green.shade50 : (_criticalAlertsCount <= 3 ? Colors.orange.shade50 : Colors.red.shade50),
        ),
        _buildMetricCard(
          title: 'Activity\nScore',
          value: '${_activityScore.toStringAsFixed(0)}',
          subtitle: 'Overall engagement',
          icon: Icons.trending_up,
          color: _activityScore >= 75 ? Colors.purple : (_activityScore >= 50 ? Colors.indigo : Colors.grey),
          iconBgColor: _activityScore >= 75 ? Colors.purple.shade50 : (_activityScore >= 50 ? Colors.indigo.shade50 : Colors.grey.shade50),
        ),
      ],
    );
  }

  Widget _buildMetricCard({
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color color,
    required Color iconBgColor,
  }) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 13),
                      fontWeight: FontWeight.w600,
                      color: Colors.grey[700],
                      height: 1.2,
                    ),
                  ),
                ),
                Container(
                  padding: EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: iconBgColor,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
              ],
            ),
            Spacer(),
            Text(
              value,
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 28),
                fontWeight: FontWeight.bold,
                color: color,
                fontFamily: 'Montserrat',
              ),
            ),
            SizedBox(height: 2),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 11),
                color: Colors.grey[600],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

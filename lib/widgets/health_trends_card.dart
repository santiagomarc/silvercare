import 'package:flutter/material.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../services/user_service.dart';

class HealthTrendsCard extends StatefulWidget {
  const HealthTrendsCard({Key? key}) : super(key: key);

  @override
  State<HealthTrendsCard> createState() => _HealthTrendsCardState();
}

class _HealthTrendsCardState extends State<HealthTrendsCard> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  
  bool _isLoading = true;
  Map<String, TrendData> _trends = {};

  @override
  void initState() {
    super.initState();
    _loadTrendsData();
  }

  Future<void> _loadTrendsData() async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) {
        setState(() => _isLoading = false);
        return;
      }

      // Get user profile to determine elderly ID
      final profile = await UserService.getUserProfile(userId);
      if (profile == null) {
        setState(() => _isLoading = false);
        return;
      }

      String? elderlyId;
      final userType = profile['userType'] as String?;

      if (userType == 'elderly') {
        elderlyId = userId;
      } else if (userType == 'caregiver') {
        elderlyId = profile['elderlyId'] as String?;
      }

      if (elderlyId == null || elderlyId.isEmpty) {
        setState(() => _isLoading = false);
        return;
      }

      // Calculate date ranges
      final now = DateTime.now();
      final thisWeekStart = now.subtract(Duration(days: 7));
      final lastWeekStart = now.subtract(Duration(days: 14));

      // Fetch health data for last 2 weeks
      final snapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: elderlyId)
          .where('measuredAt', isGreaterThan: Timestamp.fromDate(lastWeekStart))
          .get();

      // Process data for each type
      final Map<String, List<double>> thisWeekData = {
        'blood_pressure_sys': [],
        'blood_pressure_dia': [],
        'sugar_level': [],
        'temperature': [],
        'heart_rate': [],
      };
      final Map<String, List<double>> lastWeekData = {
        'blood_pressure_sys': [],
        'blood_pressure_dia': [],
        'sugar_level': [],
        'temperature': [],
        'heart_rate': [],
      };

      for (var doc in snapshot.docs) {
        final data = doc.data();
        final type = data['type'] as String?;
        final value = (data['value'] as num?)?.toDouble() ?? 0;
        final measuredAt = (data['measuredAt'] as Timestamp?)?.toDate();

        if (measuredAt == null || value == 0) continue;

        final isThisWeek = measuredAt.isAfter(thisWeekStart);

        switch (type) {
          case 'blood_pressure':
            final systolic = (data['systolic'] as num?)?.toDouble() ?? value;
            final diastolic = (data['diastolic'] as num?)?.toDouble() ?? value - 40;
            if (isThisWeek) {
              thisWeekData['blood_pressure_sys']!.add(systolic);
              thisWeekData['blood_pressure_dia']!.add(diastolic);
            } else {
              lastWeekData['blood_pressure_sys']!.add(systolic);
              lastWeekData['blood_pressure_dia']!.add(diastolic);
            }
            break;
          case 'sugar_level':
            if (isThisWeek) {
              thisWeekData['sugar_level']!.add(value);
            } else {
              lastWeekData['sugar_level']!.add(value);
            }
            break;
          case 'temperature':
            if (isThisWeek) {
              thisWeekData['temperature']!.add(value);
            } else {
              lastWeekData['temperature']!.add(value);
            }
            break;
          case 'heart_rate':
            if (isThisWeek) {
              thisWeekData['heart_rate']!.add(value);
            } else {
              lastWeekData['heart_rate']!.add(value);
            }
            break;
        }
      }

      // Calculate trends
      setState(() {
        _trends = {
          'Blood Pressure (Sys)': _calculateTrend(
            lastWeekData['blood_pressure_sys']!,
            thisWeekData['blood_pressure_sys']!,
            'mmHg',
            Icons.bloodtype,
            Colors.red,
          ),
          'Blood Pressure (Dia)': _calculateTrend(
            lastWeekData['blood_pressure_dia']!,
            thisWeekData['blood_pressure_dia']!,
            'mmHg',
            Icons.bloodtype,
            Colors.blue,
          ),
          'Sugar Level': _calculateTrend(
            lastWeekData['sugar_level']!,
            thisWeekData['sugar_level']!,
            'mg/dL',
            Icons.water_drop,
            Colors.green,
          ),
          'Temperature': _calculateTrend(
            lastWeekData['temperature']!,
            thisWeekData['temperature']!,
            '°C',
            Icons.thermostat,
            Colors.orange,
          ),
          'Heart Rate': _calculateTrend(
            lastWeekData['heart_rate']!,
            thisWeekData['heart_rate']!,
            'bpm',
            Icons.favorite,
            Colors.pink,
          ),
        };
        _isLoading = false;
      });
    } catch (e) {
      print('Error loading trends data: $e');
      setState(() => _isLoading = false);
    }
  }

  TrendData _calculateTrend(
    List<double> lastWeek,
    List<double> thisWeek,
    String unit,
    IconData icon,
    Color color,
  ) {
    if (lastWeek.isEmpty || thisWeek.isEmpty) {
      return TrendData(
        lastWeekAvg: 0,
        thisWeekAvg: 0,
        change: 0,
        changePercent: 0,
        unit: unit,
        icon: icon,
        color: color,
        hasData: false,
      );
    }

    final lastAvg = lastWeek.reduce((a, b) => a + b) / lastWeek.length;
    final thisAvg = thisWeek.reduce((a, b) => a + b) / thisWeek.length;
    final change = thisAvg - lastAvg;
    final changePercent = lastAvg != 0 ? (change / lastAvg) * 100 : 0.0;

    return TrendData(
      lastWeekAvg: lastAvg,
      thisWeekAvg: thisAvg,
      change: change,
      changePercent: changePercent.toDouble(),
      unit: unit,
      icon: icon,
      color: color,
      hasData: true,
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Card(
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(16))),
        child: Padding(
          padding: EdgeInsets.all(40.0),
          child: Center(child: CircularProgressIndicator()),
        ),
      );
    }

    final hasAnyData = _trends.values.any((trend) => trend.hasData);

    if (!hasAnyData) {
      return Card(
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            children: [
              Icon(Icons.trending_up, size: 48, color: Colors.grey.shade400),
              const SizedBox(height: 12),
              Text(
                'Not Enough Data',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Record vitals for at least 2 weeks to see trends',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Card(
      elevation: 3,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.purple.shade50,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.trending_up, color: Colors.purple.shade700, size: 24),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Weekly Trends',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      Text(
                        'This Week vs Last Week',
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(Icons.info_outline, size: 20, color: Colors.grey.shade400),
              ],
            ),
            const SizedBox(height: 20),

            // Trends List
            ..._trends.entries.where((e) => e.value.hasData).map((entry) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _buildTrendItem(entry.key, entry.value),
              );
            }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildTrendItem(String label, TrendData trend) {
    final isImproving = _isImprovement(label, trend.change);
    final trendColor = isImproving ? Colors.green : Colors.red;
    final trendIcon = trend.change > 0 ? Icons.trending_up : trend.change < 0 ? Icons.trending_down : Icons.trending_flat;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: trend.color.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: trend.color.withOpacity(0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(trend.icon, size: 20, color: trend.color),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: trendColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: trendColor.withOpacity(0.5)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(trendIcon, size: 14, color: trendColor),
                    const SizedBox(width: 4),
                    Text(
                      '${trend.changePercent >= 0 ? '+' : ''}${trend.changePercent.toStringAsFixed(1)}%',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: trendColor,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _buildWeekStat(
                  'Last Week',
                  trend.lastWeekAvg,
                  trend.unit,
                  Colors.grey.shade400,
                ),
              ),
              const SizedBox(width: 12),
              Icon(Icons.arrow_forward, size: 16, color: Colors.grey.shade400),
              const SizedBox(width: 12),
              Expanded(
                child: _buildWeekStat(
                  'This Week',
                  trend.thisWeekAvg,
                  trend.unit,
                  trend.color,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildWeekStat(String label, double value, String unit, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 10,
            color: Colors.grey.shade600,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          '${value.toStringAsFixed(1)} $unit',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }

  bool _isImprovement(String label, double change) {
    // For most vitals, lower is better or no change is good
    // But this is simplified - in reality, you'd want values in normal range
    if (label.contains('Blood Pressure') || label.contains('Sugar Level')) {
      return change <= 0; // Lower is better
    }
    if (label.contains('Temperature')) {
      return change.abs() < 0.1; // Stable is better
    }
    if (label.contains('Heart Rate')) {
      return change <= 0 && change > -10; // Slightly lower is good, but not too low
    }
    return false;
  }
}

class TrendData {
  final double lastWeekAvg;
  final double thisWeekAvg;
  final double change;
  final double changePercent;
  final String unit;
  final IconData icon;
  final Color color;
  final bool hasData;

  TrendData({
    required this.lastWeekAvg,
    required this.thisWeekAvg,
    required this.change,
    required this.changePercent,
    required this.unit,
    required this.icon,
    required this.color,
    required this.hasData,
  });
}

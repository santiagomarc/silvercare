import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';

class BloodPressureAnalyticsCard extends StatefulWidget {
  final List<Map<String, dynamic>> bpData; // List of {systolic, diastolic, measuredAt}
  final void Function()? onTapDetails;
  const BloodPressureAnalyticsCard({
    required this.bpData,
    this.onTapDetails,
    Key? key,
  }) : super(key: key);

  @override
  State<BloodPressureAnalyticsCard> createState() => _BloodPressureAnalyticsCardState();
}

class _BloodPressureAnalyticsCardState extends State<BloodPressureAnalyticsCard> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avgSystolic = 0;
  double _avgDiastolic = 0;

  @override
  void initState() {
    super.initState();
    _applyFilter();
  }

  void _applyFilter() {
    DateTime now = DateTime.now();
    DateTime start;
    if (_selectedFilter == 'Day') {
      start = now.subtract(Duration(days: 1));
    } else if (_selectedFilter == 'Week') {
      start = now.subtract(Duration(days: 7));
    } else {
      start = now.subtract(Duration(days: 30));
    }
    _filteredData = widget.bpData.where((d) {
      final measuredAt = d['measuredAt'] as DateTime?;
      return measuredAt != null && measuredAt.isAfter(start);
    }).toList();
    if (_filteredData.isNotEmpty) {
      _avgSystolic = _filteredData.map((d) => d['systolic'] as num? ?? 0).reduce((a, b) => a + b) / _filteredData.length;
      _avgDiastolic = _filteredData.map((d) => d['diastolic'] as num? ?? 0).reduce((a, b) => a + b) / _filteredData.length;
    } else {
      _avgSystolic = 0;
      _avgDiastolic = 0;
    }
    setState(() {});
  }

  String _getBloodPressureStatus(double systolic, double diastolic) {
    if (systolic == 0 && diastolic == 0) return 'No Data';
    if (systolic < 90 || diastolic < 60) return 'Low';
    if (systolic > 140 || diastolic > 90) return 'High';
    return 'Normal';
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'High':
        return Colors.red;
      case 'Low':
        return Colors.blue;
      case 'No Data':
        return Colors.grey;
      default:
        return Colors.green;
    }
  }

  @override
  void didUpdateWidget(covariant BloodPressureAnalyticsCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    _applyFilter();
  }

  @override
  Widget build(BuildContext context) {
    final status = _getBloodPressureStatus(_avgSystolic, _avgDiastolic);
    final statusColor = _getStatusColor(status);
    return GestureDetector(
      onTap: () {
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (context) => BloodPressureDetailedView(
            bpData: widget.bpData,
            filter: _selectedFilter,
          ),
        );
      },
      child: Card(
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Icon(Icons.bloodtype, color: Colors.orange.shade700, size: 24),
                      SizedBox(width: 8),
                      Text('Blood Pressure', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                  DropdownButton<String>(
                    value: _selectedFilter,
                    items: ['Day', 'Week', 'Month']
                        .map((f) => DropdownMenuItem(value: f, child: Text(f)))
                        .toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          _selectedFilter = val;
                          _applyFilter();
                        });
                      }
                    },
                  ),
                ],
              ),
              SizedBox(height: 12),
              Text('AVG Systolic: ${_avgSystolic.toStringAsFixed(1)} mmHg', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              Text('AVG Diastolic: ${_avgDiastolic.toStringAsFixed(1)} mmHg', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              Text('Status: $status', style: TextStyle(fontSize: 14, color: statusColor)),
              SizedBox(height: 16),
              SizedBox(
                height: 180,
                child: _filteredData.isEmpty
                    ? Center(child: Text('No data for selected period'))
                    : LineChart(
                        LineChartData(
                          gridData: FlGridData(show: true),
                          titlesData: FlTitlesData(
                            leftTitles: AxisTitles(
                              sideTitles: SideTitles(showTitles: true, reservedSize: 40),
                            ),
                            bottomTitles: AxisTitles(
                              sideTitles: SideTitles(
                                showTitles: true,
                                getTitlesWidget: (value, meta) {
                                  int idx = value.toInt();
                                  if (idx < 0 || idx >= _filteredData.length) return Container();
                                  final date = _filteredData[idx]['measuredAt'] as DateTime?;
                                  return Text(date != null ? '${date.month}/${date.day}' : '', style: TextStyle(fontSize: 10));
                                },
                                reservedSize: 32,
                              ),
                            ),
                            rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                            topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          ),
                          borderData: FlBorderData(show: true),
                          lineBarsData: [
                            LineChartBarData(
                              spots: [
                                for (int i = 0; i < _filteredData.length; i++)
                                  FlSpot(i.toDouble(), (_filteredData[i]['systolic'] as num?)?.toDouble() ?? 0),
                              ],
                              isCurved: true,
                              color: Colors.red,
                              barWidth: 2,
                              dotData: FlDotData(show: true),
                              belowBarData: BarAreaData(show: false),
                              curveSmoothness: 0.2,
                            ),
                            LineChartBarData(
                              spots: [
                                for (int i = 0; i < _filteredData.length; i++)
                                  FlSpot(i.toDouble(), (_filteredData[i]['diastolic'] as num?)?.toDouble() ?? 0),
                              ],
                              isCurved: true,
                              color: Colors.blue,
                              barWidth: 2,
                              dotData: FlDotData(show: true),
                              belowBarData: BarAreaData(show: false),
                              curveSmoothness: 0.2,
                            ),
                          ],
                        ),
                      ),
              ),
              SizedBox(height: 8),
              Text('Tap for detailed view', style: TextStyle(fontSize: 12, color: Colors.grey)),
            ],
          ),
        ),
      ),
    );
  }
}

// Detailed Blood Pressure View Screen
class BloodPressureDetailedView extends StatefulWidget {
  final List<Map<String, dynamic>> bpData;
  final String filter;
  
  const BloodPressureDetailedView({
    required this.bpData,
    required this.filter,
    Key? key,
  }) : super(key: key);

  @override
  State<BloodPressureDetailedView> createState() => _BloodPressureDetailedViewState();
}

class _BloodPressureDetailedViewState extends State<BloodPressureDetailedView> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avgSystolic = 0;
  double _avgDiastolic = 0;
  double _highestSystolic = 0;
  double _lowestSystolic = 0;
  double _highestDiastolic = 0;
  double _lowestDiastolic = 0;
  DateTime? _highestSystolicDate;
  DateTime? _lowestSystolicDate;
  DateTime? _highestDiastolicDate;
  DateTime? _lowestDiastolicDate;

  @override
  void initState() {
    super.initState();
    _selectedFilter = widget.filter;
    _applyFilter();
  }

  void _applyFilter() {
    DateTime now = DateTime.now();
    DateTime start;
    if (_selectedFilter == 'Day') {
      start = now.subtract(Duration(days: 1));
    } else if (_selectedFilter == 'Week') {
      start = now.subtract(Duration(days: 7));
    } else {
      start = now.subtract(Duration(days: 30));
    }
    
    _filteredData = widget.bpData.where((d) {
      final measuredAt = d['measuredAt'] as DateTime?;
      return measuredAt != null && measuredAt.isAfter(start);
    }).toList();
    
    if (_filteredData.isNotEmpty) {
      // Calculate averages
      _avgSystolic = _filteredData.map((d) => d['systolic'] as num? ?? 0).reduce((a, b) => a + b) / _filteredData.length;
      _avgDiastolic = _filteredData.map((d) => d['diastolic'] as num? ?? 0).reduce((a, b) => a + b) / _filteredData.length;
      
      // Find highest and lowest
      _highestSystolic = _filteredData[0]['systolic'] as double;
      _lowestSystolic = _filteredData[0]['systolic'] as double;
      _highestDiastolic = _filteredData[0]['diastolic'] as double;
      _lowestDiastolic = _filteredData[0]['diastolic'] as double;
      _highestSystolicDate = _filteredData[0]['measuredAt'] as DateTime;
      _lowestSystolicDate = _filteredData[0]['measuredAt'] as DateTime;
      _highestDiastolicDate = _filteredData[0]['measuredAt'] as DateTime;
      _lowestDiastolicDate = _filteredData[0]['measuredAt'] as DateTime;
      
      for (var record in _filteredData) {
        double systolic = (record['systolic'] as num?)?.toDouble() ?? 0;
        double diastolic = (record['diastolic'] as num?)?.toDouble() ?? 0;
        DateTime? date = record['measuredAt'] as DateTime?;
        
        if (systolic > _highestSystolic) {
          _highestSystolic = systolic;
          _highestSystolicDate = date;
        }
        if (systolic < _lowestSystolic) {
          _lowestSystolic = systolic;
          _lowestSystolicDate = date;
        }
        if (diastolic > _highestDiastolic) {
          _highestDiastolic = diastolic;
          _highestDiastolicDate = date;
        }
        if (diastolic < _lowestDiastolic) {
          _lowestDiastolic = diastolic;
          _lowestDiastolicDate = date;
        }
      }
    } else {
      _avgSystolic = 0;
      _avgDiastolic = 0;
      _highestSystolic = 0;
      _lowestSystolic = 0;
      _highestDiastolic = 0;
      _lowestDiastolic = 0;
    }
    setState(() {});
  }

  String _getBloodPressureStatus(double systolic, double diastolic) {
    if (systolic < 90 || diastolic < 60) return "Low";
    if (systolic > 140 || diastolic > 90) return "High";
    return "Normal";
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'High':
        return Colors.red;
      case 'Low':
        return Colors.blue;
      default:
        return Colors.green;
    }
  }

  List<String> _generateInsights() {
    List<String> insights = [];
    
    if (_filteredData.isEmpty) {
      insights.add('No blood pressure readings recorded in the selected period.');
      return insights;
    }
    
    String periodText = _selectedFilter == 'Day' ? 'today' : 
                       _selectedFilter == 'Week' ? 'this week' : 
                       'this month';
    
    // Overall status
    String status = _getBloodPressureStatus(_avgSystolic, _avgDiastolic);
    if (status == 'High') {
      insights.add('⚠️ Your average blood pressure is high. Consider reducing salt intake and increasing physical activity.');
    } else if (status == 'Low') {
      insights.add('⚠️ Your average blood pressure is low. Ensure adequate hydration and avoid sudden position changes.');
    } else {
      insights.add('✅ Your blood pressure is within normal range. Keep up the good work!');
    }
    
    // Variability
    double systolicRange = _highestSystolic - _lowestSystolic;
    double diastolicRange = _highestDiastolic - _lowestDiastolic;
    
    if (systolicRange > 30 || diastolicRange > 20) {
      insights.add('📊 High variability detected in your readings ${periodText}. Try to measure at consistent times.');
    } else {
      insights.add('📊 Your readings show consistent patterns ${periodText}.');
    }
    
    // Frequency
    int readingsCount = _filteredData.length;
    if (_selectedFilter == 'Week' && readingsCount < 3) {
      insights.add('📅 Only $readingsCount reading${readingsCount != 1 ? 's' : ''} recorded this week. Regular monitoring is recommended.');
    } else if (_selectedFilter == 'Month' && readingsCount < 10) {
      insights.add('📅 $readingsCount reading${readingsCount != 1 ? 's' : ''} recorded this month. Consider more frequent monitoring.');
    } else if (readingsCount >= 10) {
      insights.add('✅ Great job! You\'ve been consistently tracking your blood pressure with $readingsCount readings ${periodText}.');
    }
    
    // Trend analysis
    if (_filteredData.length >= 3) {
      var recent = _filteredData.sublist(_filteredData.length - 3);
      double recentAvgSys = recent.map((d) => d['systolic'] as num? ?? 0).reduce((a, b) => a + b) / 3;
      
      if (recentAvgSys > _avgSystolic + 5) {
        insights.add('📈 Recent readings show an upward trend. Monitor closely and consult healthcare provider if it continues.');
      } else if (recentAvgSys < _avgSystolic - 5) {
        insights.add('📉 Recent readings show improvement compared to earlier values.');
      }
    }
    
    return insights;
  }

  @override
  Widget build(BuildContext context) {
    List<String> insights = _generateInsights();
    String status = _getBloodPressureStatus(_avgSystolic, _avgDiastolic);
    Color statusColor = _getStatusColor(status);
    
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(24),
          topRight: Radius.circular(24),
        ),
      ),
      child: Column(
        children: [
          // Handle bar
          Container(
            margin: EdgeInsets.only(top: 12, bottom: 8),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey.shade300,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          // Header
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Icon(Icons.bloodtype, color: Colors.orange.shade700, size: 24),
                    SizedBox(width: 8),
                    Text('BP Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ],
                ),
                Row(
                  children: [
                    Container(
                      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.orange.shade700,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedFilter,
                          isDense: true,
                          style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                          dropdownColor: Colors.orange.shade700,
                          icon: Icon(Icons.arrow_drop_down, color: Colors.white, size: 20),
                          items: ['Day', 'Week', 'Month']
                              .map((f) => DropdownMenuItem(
                                    value: f,
                                    child: Text(f, style: TextStyle(color: Colors.white)),
                                  ))
                              .toList(),
                          onChanged: (val) {
                            if (val != null) {
                              setState(() {
                                _selectedFilter = val;
                                _applyFilter();
                              });
                            }
                          },
                        ),
                      ),
                    ),
                    SizedBox(width: 8),
                    IconButton(
                      icon: Icon(Icons.close, size: 20),
                      onPressed: () => Navigator.pop(context),
                      padding: EdgeInsets.zero,
                      constraints: BoxConstraints(),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Divider(height: 1),
          // Content
          Expanded(
            child: SingleChildScrollView(
              padding: EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Status Banner (Compact)
                  Container(
                    width: double.infinity,
                    padding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: statusColor, width: 1.5),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Status: $status', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: statusColor)),
                        Text('${_filteredData.length} readings', style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
                      ],
                    ),
                  ),
                  SizedBox(height: 12),
                  
                  // Average Values (Compact Row)
                  Row(
                    children: [
                      Expanded(
                        child: _buildCompactStatCard('AVG Systolic', '${_avgSystolic.toStringAsFixed(1)}', 'mmHg', Colors.red.shade700),
                      ),
                      SizedBox(width: 8),
                      Expanded(
                        child: _buildCompactStatCard('AVG Diastolic', '${_avgDiastolic.toStringAsFixed(1)}', 'mmHg', Colors.blue.shade700),
                      ),
                    ],
                  ),
                  SizedBox(height: 8),
                  
                  // Highest Values (Compact)
                  Row(
                    children: [
                      Expanded(
                        child: _buildCompactStatCard(
                          'High Sys',
                          '${_highestSystolic.toStringAsFixed(0)}',
                          _highestSystolicDate != null ? '${_highestSystolicDate!.month}/${_highestSystolicDate!.day}' : '',
                          Colors.red.shade400,
                        ),
                      ),
                      SizedBox(width: 8),
                      Expanded(
                        child: _buildCompactStatCard(
                          'High Dia',
                          '${_highestDiastolic.toStringAsFixed(0)}',
                          _highestDiastolicDate != null ? '${_highestDiastolicDate!.month}/${_highestDiastolicDate!.day}' : '',
                          Colors.red.shade400,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 8),
                  
                  // Lowest Values (Compact)
                  Row(
                    children: [
                      Expanded(
                        child: _buildCompactStatCard(
                          'Low Sys',
                          '${_lowestSystolic.toStringAsFixed(0)}',
                          _lowestSystolicDate != null ? '${_lowestSystolicDate!.month}/${_lowestSystolicDate!.day}' : '',
                          Colors.green.shade600,
                        ),
                      ),
                      SizedBox(width: 8),
                      Expanded(
                        child: _buildCompactStatCard(
                          'Low Dia',
                          '${_lowestDiastolic.toStringAsFixed(0)}',
                          _lowestDiastolicDate != null ? '${_lowestDiastolicDate!.month}/${_lowestDiastolicDate!.day}' : '',
                          Colors.green.shade600,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 12),
                  
                  // Compact Chart
                  Container(
                    height: 200,
                    padding: EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: _filteredData.isEmpty
                        ? Center(child: Text('No data', style: TextStyle(fontSize: 12)))
                        : LineChart(
                            LineChartData(
                              gridData: FlGridData(show: true, drawVerticalLine: false),
                              titlesData: FlTitlesData(
                                leftTitles: AxisTitles(
                                  sideTitles: SideTitles(showTitles: true, reservedSize: 35, interval: 20),
                                ),
                                bottomTitles: AxisTitles(
                                  sideTitles: SideTitles(
                                    showTitles: true,
                                    getTitlesWidget: (value, meta) {
                                      int idx = value.toInt();
                                      if (idx < 0 || idx >= _filteredData.length) return Container();
                                      final date = _filteredData[idx]['measuredAt'] as DateTime?;
                                      return Padding(
                                        padding: const EdgeInsets.only(top: 4.0),
                                        child: Text(date != null ? '${date.month}/${date.day}' : '', style: TextStyle(fontSize: 9)),
                                      );
                                    },
                                    reservedSize: 22,
                                  ),
                                ),
                                rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                                topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                              ),
                              borderData: FlBorderData(show: true),
                              lineBarsData: [
                                LineChartBarData(
                                  spots: [
                                    for (int i = 0; i < _filteredData.length; i++)
                                      FlSpot(i.toDouble(), (_filteredData[i]['systolic'] as num?)?.toDouble() ?? 0),
                                  ],
                                  isCurved: true,
                                  color: Colors.red,
                                  barWidth: 2,
                                  dotData: FlDotData(show: true),
                                  belowBarData: BarAreaData(show: false),
                                  curveSmoothness: 0.2,
                                ),
                                LineChartBarData(
                                  spots: [
                                    for (int i = 0; i < _filteredData.length; i++)
                                      FlSpot(i.toDouble(), (_filteredData[i]['diastolic'] as num?)?.toDouble() ?? 0),
                                  ],
                                  isCurved: true,
                                  color: Colors.blue,
                                  barWidth: 2,
                                  dotData: FlDotData(show: true),
                                  belowBarData: BarAreaData(show: false),
                                  curveSmoothness: 0.2,
                                ),
                              ],
                            ),
                          ),
                  ),
                  SizedBox(height: 8),
                  // Legend
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(width: 16, height: 2, color: Colors.red),
                      SizedBox(width: 4),
                      Text('Systolic', style: TextStyle(fontSize: 10)),
                      SizedBox(width: 12),
                      Container(width: 16, height: 2, color: Colors.blue),
                      SizedBox(width: 4),
                      Text('Diastolic', style: TextStyle(fontSize: 10)),
                    ],
                  ),
                  SizedBox(height: 12),
                  
                  // Insights (Compact)
                  Text('Insights', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  SizedBox(height: 8),
                  ...insights.map((insight) => Container(
                    margin: EdgeInsets.only(bottom: 6),
                    padding: EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.blue.shade200, width: 0.5),
                    ),
                    child: Text(insight, style: TextStyle(fontSize: 12, height: 1.3)),
                  )).toList(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCompactStatCard(String title, String value, String subtitle, Color color) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.3), width: 1),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: TextStyle(fontSize: 10, color: Colors.grey.shade700, fontWeight: FontWeight.w600)),
          SizedBox(height: 4),
          Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
          if (subtitle.isNotEmpty)
            Text(subtitle, style: TextStyle(fontSize: 9, color: Colors.grey.shade600)),
        ],
      ),
    );
  }
}

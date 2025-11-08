import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';

class HeartRateAnalyticsCard extends StatefulWidget {
  final List<Map<String, dynamic>> hrData; // List of {value, measuredAt}
  final void Function()? onTapDetails;

  const HeartRateAnalyticsCard({
    this.hrData = const [],
    this.onTapDetails,
    Key? key,
  }) : super(key: key);

  @override
  State<HeartRateAnalyticsCard> createState() => _HeartRateAnalyticsCardState();
}

class _HeartRateAnalyticsCardState extends State<HeartRateAnalyticsCard> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avgHr = 0;
  double _highestHr = 0;
  double _lowestHr = 0;

  @override
  void initState() {
    super.initState();
    _applyFilter();
  }

  @override
  void didUpdateWidget(covariant HeartRateAnalyticsCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.hrData != widget.hrData) {
      _applyFilter();
    }
  }

  void _applyFilter() {
    DateTime now = DateTime.now();
    DateTime start;
    if (_selectedFilter == 'Day') {
      start = now.subtract(const Duration(days: 1));
    } else if (_selectedFilter == 'Week') {
      start = now.subtract(const Duration(days: 7));
    } else {
      start = now.subtract(const Duration(days: 30));
    }

    _filteredData = widget.hrData.where((d) {
      final measuredAt = d['measuredAt'];
      if (measuredAt is DateTime) return measuredAt.isAfter(start);
      if (measuredAt is int) return DateTime.fromMillisecondsSinceEpoch(measuredAt).isAfter(start);
      if (measuredAt is double) return DateTime.fromMillisecondsSinceEpoch(measuredAt.toInt()).isAfter(start);
      return false;
    }).toList();

    // Sort ascending by date for charting
    _filteredData.sort((a, b) {
      final aDate = a['measuredAt'] is DateTime
          ? a['measuredAt'] as DateTime
          : (a['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(a['measuredAt']) : DateTime.now());
      final bDate = b['measuredAt'] is DateTime
          ? b['measuredAt'] as DateTime
          : (b['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(b['measuredAt']) : DateTime.now());
      return aDate.compareTo(bDate);
    });

    if (_filteredData.isNotEmpty) {
      final values = _filteredData.map((d) {
        final v = d['value'];
        if (v is num) return v.toDouble();
        if (v is String) return double.tryParse(v) ?? 0.0;
        return 0.0;
      }).toList();

      _avgHr = values.reduce((a, b) => a + b) / values.length;
      _highestHr = values.reduce((a, b) => a > b ? a : b);
      _lowestHr = values.reduce((a, b) => a < b ? a : b);
    } else {
      _avgHr = 0;
      _highestHr = 0;
      _lowestHr = 0;
    }

    if (mounted) setState(() {});
  }

  String _formatDateShort(DateTime dt) => '${dt.month}/${dt.day}';

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

  @override
  Widget build(BuildContext context) {
    final status = _getHeartRateStatus(_avgHr);
    final statusColor = _getHeartRateStatusColor(_avgHr);

    return GestureDetector(
      onTap: () {
        // show floating detailed view
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (context) => HeartRateDetailedView(
            hrData: widget.hrData,
            filter: _selectedFilter,
          ),
        );

        if (widget.onTapDetails != null) widget.onTapDetails!();
      },
      child: Card(
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header row with filter
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Icon(Icons.favorite, color: Colors.pink.shade700, size: 24),
                      const SizedBox(width: 8),
                      const Text('Heart Rate', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
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
              const SizedBox(height: 12),

              // Averages & status
              Text('AVG: ${_avgHr == 0 ? '0' : _avgHr.toStringAsFixed(0)} bpm',
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              Text('Status: $status', style: TextStyle(fontSize: 14, color: statusColor)),
              const SizedBox(height: 12),

              // Chart area
              SizedBox(
                height: 180,
                child: _filteredData.isEmpty
                    ? Center(child: Text('No data for selected period', style: TextStyle(color: Colors.grey.shade600)))
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
                                  final measuredAt = _filteredData[idx]['measuredAt'];
                                  DateTime? date;
                                  if (measuredAt is DateTime) date = measuredAt;
                                  else if (measuredAt is int) date = DateTime.fromMillisecondsSinceEpoch(measuredAt);
                                  else if (measuredAt is double) date = DateTime.fromMillisecondsSinceEpoch(measuredAt.toInt());
                                  return Text(date != null ? _formatDateShort(date) : '', style: const TextStyle(fontSize: 10));
                                },
                                reservedSize: 32,
                              ),
                            ),
                            rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                            topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          ),
                          borderData: FlBorderData(show: true),
                          minY: (_lowestHr > 0) ? (_lowestHr - 10) : 40,
                          maxY: (_highestHr > 0) ? (_highestHr + 10) : 160,
                          lineBarsData: [
                            LineChartBarData(
                              spots: [
                                for (int i = 0; i < _filteredData.length; i++)
                                  FlSpot(i.toDouble(), ((_filteredData[i]['value'] as num?)?.toDouble() ?? 0)),
                              ],
                              isCurved: true,
                              color: Colors.pink.shade700,
                              barWidth: 2,
                              dotData: FlDotData(show: true),
                              belowBarData: BarAreaData(show: false),
                              curveSmoothness: 0.2,
                            ),
                          ],
                        ),
                      ),
              ),
              const SizedBox(height: 8),
              Text('Tap for detailed view', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ],
          ),
        ),
      ),
    );
  }
}

// Detailed Heart Rate View (floating bottom sheet)
class HeartRateDetailedView extends StatefulWidget {
  final List<Map<String, dynamic>> hrData;
  final String filter;

  const HeartRateDetailedView({
    required this.hrData,
    required this.filter,
    Key? key,
  }) : super(key: key);

  @override
  State<HeartRateDetailedView> createState() => _HeartRateDetailedViewState();
}

class _HeartRateDetailedViewState extends State<HeartRateDetailedView> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avgHr = 0;
  double _highestHr = 0;
  double _lowestHr = 0;
  DateTime? _highestHrDate;
  DateTime? _lowestHrDate;

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
      start = now.subtract(const Duration(days: 1));
    } else if (_selectedFilter == 'Week') {
      start = now.subtract(const Duration(days: 7));
    } else {
      start = now.subtract(const Duration(days: 30));
    }

    _filteredData = widget.hrData.where((d) {
      final measuredAt = d['measuredAt'];
      if (measuredAt is DateTime) return measuredAt.isAfter(start);
      if (measuredAt is int) return DateTime.fromMillisecondsSinceEpoch(measuredAt).isAfter(start);
      if (measuredAt is double) return DateTime.fromMillisecondsSinceEpoch(measuredAt.toInt()).isAfter(start);
      return false;
    }).toList();

    _filteredData.sort((a, b) {
      final aDate = a['measuredAt'] is DateTime
          ? a['measuredAt'] as DateTime
          : (a['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(a['measuredAt']) : DateTime.now());
      final bDate = b['measuredAt'] is DateTime
          ? b['measuredAt'] as DateTime
          : (b['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(b['measuredAt']) : DateTime.now());
      return aDate.compareTo(bDate);
    });

    if (_filteredData.isNotEmpty) {
      final values = _filteredData.map((d) {
        final v = d['value'];
        if (v is num) return v.toDouble();
        if (v is String) return double.tryParse(v) ?? 0.0;
        return 0.0;
      }).toList();

      _avgHr = values.reduce((a, b) => a + b) / values.length;
      _highestHr = values.reduce((a, b) => a > b ? a : b);
      _lowestHr = values.reduce((a, b) => a < b ? a : b);

      // find dates
      for (var rec in _filteredData) {
        final val = (rec['value'] is num) ? (rec['value'] as num).toDouble() : double.tryParse(rec['value'].toString()) ?? 0.0;
        final date = rec['measuredAt'] is DateTime
            ? rec['measuredAt'] as DateTime
            : (rec['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(rec['measuredAt']) : null);
        if (val == _highestHr && date != null) _highestHrDate = date;
        if (val == _lowestHr && date != null) _lowestHrDate = date;
      }
    } else {
      _avgHr = 0;
      _highestHr = 0;
      _lowestHr = 0;
      _highestHrDate = null;
      _lowestHrDate = null;
    }

    if (mounted) setState(() {});
  }

  String _formatDateShort(DateTime dt) => '${dt.month}/${dt.day}';

  String _getHeartRateStatus(double hr) {
    if (hr == 0) return "No Data";
    if (hr < 60) return "Low";
    if (hr > 100) return "High";
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
      insights.add('No heart rate readings recorded in the selected period.');
      return insights;
    }

    String periodText = _selectedFilter == 'Day' ? 'today' : _selectedFilter == 'Week' ? 'this week' : 'this month';
    String status = _getHeartRateStatus(_avgHr);

    if (status == 'High') {
      insights.add('⚠️ Average heart rate is elevated. Ensure adequate rest and consult a healthcare provider if it persists.');
    } else if (status == 'Low') {
      insights.add('⚠️ Average heart rate is low. Seek medical advice if you have symptoms like dizziness.');
    } else {
      insights.add('✅ Average heart rate is within the normal range.');
    }

    int readingsCount = _filteredData.length;
    if (_selectedFilter == 'Week' && readingsCount < 3) {
      insights.add('📅 Only $readingsCount reading${readingsCount != 1 ? 's' : ''} recorded this week. Consider measuring more frequently.');
    } else if (_selectedFilter == 'Month' && readingsCount < 10) {
      insights.add('📅 $readingsCount reading${readingsCount != 1 ? 's' : ''} recorded this month. Regular monitoring helps detect trends.');
    } else if (readingsCount >= 10) {
      insights.add('✅ Good consistency: $readingsCount readings recorded ${periodText}.');
    }

    // Trend: compare last 3 to overall average
    if (_filteredData.length >= 3) {
      final recent = _filteredData.sublist(_filteredData.length - 3);
      double recentAvg = recent.map((d) {
        final v = d['value'];
        if (v is num) return v.toDouble();
        if (v is String) return double.tryParse(v) ?? 0.0;
        return 0.0;
      }).reduce((a, b) => a + b) / 3;

      if (recentAvg > _avgHr + 5) {
        insights.add('📈 Recent readings show an upward trend. Monitor closely and seek care if it continues.');
      } else if (recentAvg < _avgHr - 5) {
        insights.add('📉 Recent readings show improvement compared to earlier values.');
      }
    }

    return insights;
  }

  @override
  Widget build(BuildContext context) {
    final status = _getHeartRateStatus(_avgHr);
    final statusColor = _getStatusColor(status);
    final insights = _generateInsights();

    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.only(topLeft: Radius.circular(24), topRight: Radius.circular(24)),
      ),
      child: Column(
        children: [
          // Handle
          Container(
            margin: const EdgeInsets.only(top: 12, bottom: 8),
            width: 40,
            height: 4,
            decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
          ),
          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Icon(Icons.favorite, color: Colors.pink.shade700, size: 24),
                    const SizedBox(width: 8),
                    const Text('Heart Rate Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ],
                ),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(color: Colors.pink.shade700, borderRadius: BorderRadius.circular(8)),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedFilter,
                          isDense: true,
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                          dropdownColor: Colors.pink,
                          icon: const Icon(Icons.arrow_drop_down, color: Colors.white, size: 20),
                          items: ['Day', 'Week', 'Month']
                              .map((f) => DropdownMenuItem(value: f, child: Text(f, style: const TextStyle(color: Colors.white))))
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
                    const SizedBox(width: 8),
                    IconButton(
                      icon: const Icon(Icons.close, size: 20),
                      onPressed: () => Navigator.pop(context),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Status banner
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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
                  const SizedBox(height: 12),

                  // Average / High / Low cards
                  Row(
                    children: [
                      Expanded(
                        child: _buildCompactStatCard('AVG HR', _avgHr == 0 ? '0' : _avgHr.toStringAsFixed(0), 'bpm', Colors.pink.shade700),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildCompactStatCard(
                          'High',
                          _highestHr == 0 ? '0' : _highestHr.toStringAsFixed(0),
                          _highestHrDate != null ? _formatDateShort(_highestHrDate!) : '',
                          Colors.red.shade400,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: _buildCompactStatCard(
                          'Low',
                          _lowestHr == 0 ? '0' : _lowestHr.toStringAsFixed(0),
                          _lowestHrDate != null ? _formatDateShort(_lowestHrDate!) : '',
                          Colors.blue.shade600,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Container()), // spacer to align layout similar to other views
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Chart
                  Container(
                    height: 200,
                    padding: const EdgeInsets.all(12),
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
                                  sideTitles: SideTitles(showTitles: true, reservedSize: 35),
                                ),
                                bottomTitles: AxisTitles(
                                  sideTitles: SideTitles(
                                    showTitles: true,
                                    getTitlesWidget: (value, meta) {
                                      int idx = value.toInt();
                                      if (idx < 0 || idx >= _filteredData.length) return Container();
                                      final measuredAt = _filteredData[idx]['measuredAt'];
                                      DateTime? date;
                                      if (measuredAt is DateTime) date = measuredAt;
                                      else if (measuredAt is int) date = DateTime.fromMillisecondsSinceEpoch(measuredAt);
                                      else if (measuredAt is double) date = DateTime.fromMillisecondsSinceEpoch(measuredAt.toInt());
                                      return Padding(
                                        padding: const EdgeInsets.only(top: 4.0),
                                        child: Text(date != null ? _formatDateShort(date) : '', style: const TextStyle(fontSize: 9)),
                                      );
                                    },
                                    reservedSize: 22,
                                  ),
                                ),
                                rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                                topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                              ),
                              borderData: FlBorderData(show: true),
                              minY: (_lowestHr > 0) ? (_lowestHr - 10) : 40,
                              maxY: (_highestHr > 0) ? (_highestHr + 10) : 160,
                              lineBarsData: [
                                LineChartBarData(
                                  spots: [
                                    for (int i = 0; i < _filteredData.length; i++)
                                      FlSpot(i.toDouble(), (_filteredData[i]['value'] as num?)?.toDouble() ?? 0),
                                  ],
                                  isCurved: true,
                                  color: Colors.pink.shade700,
                                  barWidth: 2,
                                  dotData: FlDotData(show: true),
                                  belowBarData: BarAreaData(show: false),
                                  curveSmoothness: 0.2,
                                ),
                              ],
                            ),
                          ),
                  ),
                  const SizedBox(height: 8),

                  // Insights
                  const Text('Insights', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  ...insights.map((insight) => Container(
                        margin: const EdgeInsets.only(bottom: 6),
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.blue.shade50,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.blue.shade200, width: 0.5),
                        ),
                        child: Text(insight, style: const TextStyle(fontSize: 12, height: 1.3)),
                      )),
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
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.3), width: 1),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: TextStyle(fontSize: 10, color: Colors.grey.shade700, fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
          if (subtitle.isNotEmpty) Text(subtitle, style: TextStyle(fontSize: 9, color: Colors.grey.shade600)),
        ],
      ),
    );
  }
}
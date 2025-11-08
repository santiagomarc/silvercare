import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';

class TemperatureAnalyticsCard extends StatefulWidget {
  final List<Map<String, dynamic>> tempData; // List of {value, measuredAt}
  final void Function()? onTapDetails;

  const TemperatureAnalyticsCard({
    this.tempData = const [],
    this.onTapDetails,
    Key? key,
  }) : super(key: key);

  @override
  State<TemperatureAnalyticsCard> createState() => _TemperatureAnalyticsCardState();
}

class _TemperatureAnalyticsCardState extends State<TemperatureAnalyticsCard> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avgTemp = 0;
  double _highestTemp = 0;
  double _lowestTemp = 0;

  @override
  void initState() {
    super.initState();
    _applyFilter();
  }

  @override
  void didUpdateWidget(covariant TemperatureAnalyticsCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Re-apply filter when parent data changes
    if (oldWidget.tempData != widget.tempData) {
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

    _filteredData = widget.tempData.where((d) {
      final measuredAt = d['measuredAt'];
      if (measuredAt is DateTime) {
        return measuredAt.isAfter(start);
      } else if (measuredAt is int) {
        // unix millis
        return DateTime.fromMillisecondsSinceEpoch(measuredAt).isAfter(start);
      } else if (measuredAt is double) {
        return DateTime.fromMillisecondsSinceEpoch(measuredAt.toInt()).isAfter(start);
      }
      return false;
    }).toList();

    // Sort by date ascending for charting
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
      final temps = _filteredData.map((d) {
        final val = d['value'];
        if (val is num) return val.toDouble();
        if (val is String) return double.tryParse(val) ?? 0.0;
        return 0.0;
      }).toList();

      _avgTemp = temps.reduce((a, b) => a + b) / temps.length;
      _highestTemp = temps.reduce((a, b) => a > b ? a : b);
      _lowestTemp = temps.reduce((a, b) => a < b ? a : b);
    } else {
      _avgTemp = 0;
      _highestTemp = 0;
      _lowestTemp = 0;
    }

    if (mounted) setState(() {});
  }

  String _formatDateShort(DateTime dt) => '${dt.month}/${dt.day}';

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

  @override
  Widget build(BuildContext context) {
    final status = _getTemperatureStatus(_avgTemp);
    final statusColor = _getTemperatureStatusColor(_avgTemp);

    return GestureDetector(
      onTap: () {
        // show floating detailed view
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (context) => TemperatureDetailedView(
            tempData: widget.tempData,
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
                      Icon(Icons.thermostat, color: Colors.blue.shade700, size: 24),
                      const SizedBox(width: 8),
                      Text('Temperature', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
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
              Text('AVG: ${_avgTemp == 0 ? '0.0' : _avgTemp.toStringAsFixed(1)} °C',
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
                                  final date = _filteredData[idx]['measuredAt'] as DateTime?;
                                  return Text(date != null ? _formatDateShort(date) : '', style: const TextStyle(fontSize: 10));
                                },
                                reservedSize: 32,
                              ),
                            ),
                            rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                            topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          ),
                          borderData: FlBorderData(show: true),
                          minY: (_lowestTemp > 0) ? (_lowestTemp - 1) : 35,
                          maxY: (_highestTemp > 0) ? (_highestTemp + 1) : 40,
                          lineBarsData: [
                            LineChartBarData(
                              spots: [
                                for (int i = 0; i < _filteredData.length; i++)
                                  FlSpot(i.toDouble(), ((_filteredData[i]['value'] as num?)?.toDouble() ?? 0)),
                              ],
                              isCurved: true,
                              color: Colors.orange.shade700,
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

// Detailed Temperature View (floating bottom sheet)
class TemperatureDetailedView extends StatefulWidget {
  final List<Map<String, dynamic>> tempData;
  final String filter;

  const TemperatureDetailedView({
    required this.tempData,
    required this.filter,
    Key? key,
  }) : super(key: key);

  @override
  State<TemperatureDetailedView> createState() => _TemperatureDetailedViewState();
}

class _TemperatureDetailedViewState extends State<TemperatureDetailedView> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avgTemp = 0;
  double _highestTemp = 0;
  double _lowestTemp = 0;
  DateTime? _highestTempDate;
  DateTime? _lowestTempDate;

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

    _filteredData = widget.tempData.where((d) {
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
      final temps = _filteredData.map((d) {
        final val = d['value'];
        if (val is num) return val.toDouble();
        if (val is String) return double.tryParse(val) ?? 0.0;
        return 0.0;
      }).toList();

      _avgTemp = temps.reduce((a, b) => a + b) / temps.length;
      _highestTemp = temps.reduce((a, b) => a > b ? a : b);
      _lowestTemp = temps.reduce((a, b) => a < b ? a : b);

      // find dates for high/low
      for (var rec in _filteredData) {
        final val = rec['value'];
        final v = (val is num) ? val.toDouble() : (val is String ? double.tryParse(val) ?? 0.0 : 0.0);
        final date = rec['measuredAt'] is DateTime
            ? rec['measuredAt'] as DateTime
            : (rec['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(rec['measuredAt']) : null);
        if (v == _highestTemp && date != null) _highestTempDate = date;
        if (v == _lowestTemp && date != null) _lowestTempDate = date;
      }
    } else {
      _avgTemp = 0;
      _highestTemp = 0;
      _lowestTemp = 0;
      _highestTempDate = null;
      _lowestTempDate = null;
    }

    if (mounted) setState(() {});
  }

  String _formatDateShort(DateTime dt) => '${dt.month}/${dt.day}';

  String _getTemperatureStatus(double temp) {
    if (temp == 0) return "No Data";
    if (temp < 36.1) return "Low";
    if (temp > 37.5) return "Fever";
    return "Normal";
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'Fever':
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
      insights.add('No temperature readings recorded in the selected period.');
      return insights;
    }

    String periodText = _selectedFilter == 'Day' ? 'today' : _selectedFilter == 'Week' ? 'this week' : 'this month';
    String status = _getTemperatureStatus(_avgTemp);

    if (status == 'Fever') {
      insights.add('⚠️ Average temperature indicates fever. Monitor for other symptoms and seek medical advice if it persists.');
    } else if (status == 'Low') {
      insights.add('⚠️ Average temperature is below normal. Keep warm and re-check your readings.');
    } else {
      insights.add('✅ Average temperature is within the normal range.');
    }

    int readingsCount = _filteredData.length;
    if (_selectedFilter == 'Week' && readingsCount < 3) {
      insights.add('📅 Only $readingsCount reading${readingsCount != 1 ? 's' : ''} recorded this week. Consider measuring more frequently.');
    } else if (_selectedFilter == 'Month' && readingsCount < 10) {
      insights.add('📅 $readingsCount reading${readingsCount != 1 ? 's' : ''} recorded this month. Regular monitoring helps detect trends.');
    } else if (readingsCount >= 10) {
      insights.add('✅ Good consistency: $readingsCount readings recorded ${periodText}.');
    }

    // Trend: compare last 3 to overall average if possible
    if (_filteredData.length >= 3) {
      final recent = _filteredData.sublist(_filteredData.length - 3);
      double recentAvg = recent.map((d) {
        final val = d['value'];
        if (val is num) return val.toDouble();
        if (val is String) return double.tryParse(val) ?? 0.0;
        return 0.0;
      }).reduce((a, b) => a + b) / 3;

      if (recentAvg > _avgTemp + 0.3) {
        insights.add('📈 Recent readings show a rising trend compared to earlier values.');
      } else if (recentAvg < _avgTemp - 0.3) {
        insights.add('📉 Recent readings show a slight decline compared to earlier values.');
      }
    }

    return insights;
  }

  @override
  Widget build(BuildContext context) {
    final status = _getTemperatureStatus(_avgTemp);
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
                    Icon(Icons.thermostat, color: Colors.blue.shade700, size: 24),
                    const SizedBox(width: 8),
                    const Text('Temperature Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ],
                ),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(color: Colors.blue.shade700, borderRadius: BorderRadius.circular(8)),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedFilter,
                          isDense: true,
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                          dropdownColor: Colors.blue,
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
                        child: _buildCompactStatCard('AVG Temp', _avgTemp == 0 ? '0.0' : _avgTemp.toStringAsFixed(1), '°C', Colors.orange.shade700),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildCompactStatCard(
                          'High',
                          _highestTemp == 0 ? '0.0' : _highestTemp.toStringAsFixed(1),
                          _highestTempDate != null ? _formatDateShort(_highestTempDate!) : '',
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
                          _lowestTemp == 0 ? '0.0' : _lowestTemp.toStringAsFixed(1),
                          _lowestTempDate != null ? _formatDateShort(_lowestTempDate!) : '',
                          Colors.blue.shade600,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Container()), // spacer to align layout similar to BP view
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
                                      final date = _filteredData[idx]['measuredAt'] as DateTime?;
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
                              minY: (_lowestTemp > 0) ? (_lowestTemp - 1) : 35,
                              maxY: (_highestTemp > 0) ? (_highestTemp + 1) : 40,
                              lineBarsData: [
                                LineChartBarData(
                                  spots: [
                                    for (int i = 0; i < _filteredData.length; i++)
                                      FlSpot(i.toDouble(), (_filteredData[i]['value'] as num?)?.toDouble() ?? 0),
                                  ],
                                  isCurved: true,
                                  color: Colors.orange.shade700,
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
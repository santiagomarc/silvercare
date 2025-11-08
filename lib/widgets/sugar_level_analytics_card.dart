import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';

class SugarLevelAnalyticsCard extends StatefulWidget {
  final List<Map<String, dynamic>> sugarData; // List of {value, measuredAt}
  final void Function()? onTapDetails;

  const SugarLevelAnalyticsCard({
    this.sugarData = const [],
    this.onTapDetails,
    Key? key,
  }) : super(key: key);

  @override
  State<SugarLevelAnalyticsCard> createState() => _SugarLevelAnalyticsCardState();
}

class _SugarLevelAnalyticsCardState extends State<SugarLevelAnalyticsCard> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avg = 0;
  double _highest = 0;
  double _lowest = 0;

  @override
  void initState() {
    super.initState();
    _applyFilter();
  }

  @override
  void didUpdateWidget(covariant SugarLevelAnalyticsCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.sugarData != widget.sugarData) _applyFilter();
  }

  void _applyFilter() {
    final now = DateTime.now();
    DateTime start;
    if (_selectedFilter == 'Day') {
      start = now.subtract(const Duration(days: 1));
    } else if (_selectedFilter == 'Week') {
      start = now.subtract(const Duration(days: 7));
    } else {
      start = now.subtract(const Duration(days: 30));
    }

    _filteredData = widget.sugarData.where((d) {
      final measuredAt = d['measuredAt'];
      if (measuredAt is DateTime) return measuredAt.isAfter(start);
      if (measuredAt is int) return DateTime.fromMillisecondsSinceEpoch(measuredAt).isAfter(start);
      if (measuredAt is double) return DateTime.fromMillisecondsSinceEpoch(measuredAt.toInt()).isAfter(start);
      return false;
    }).toList();

    // sort ascending by date
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

      _avg = values.reduce((a, b) => a + b) / values.length;
      _highest = values.reduce((a, b) => a > b ? a : b);
      _lowest = values.reduce((a, b) => a < b ? a : b);
    } else {
      _avg = 0;
      _highest = 0;
      _lowest = 0;
    }

    if (mounted) setState(() {});
  }

  String _formatDateShort(DateTime dt) => '${dt.month}/${dt.day}';
  String _getStatus(double sugar) {
    if (sugar == 0) return 'No Data';
    if (sugar < 70) return 'Low';
    if (sugar >= 140) return 'High';
    return 'Normal';
  }

  Color _getStatusColor(double sugar) {
    if (sugar == 0) return Colors.grey;
    if (sugar < 70) return Colors.blue;
    if (sugar >= 140) return Colors.red;
    return Colors.green;
  }

  @override
  Widget build(BuildContext context) {
    final status = _getStatus(_avg);
    final statusColor = _getStatusColor(_avg);

    return GestureDetector(
      onTap: () {
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (_) => SugarLevelDetailedView(
            sugarData: widget.sugarData,
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
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Row(children: [
                Icon(Icons.water_drop, color: Colors.green.shade700, size: 24),
                const SizedBox(width: 8),
                const Text('Sugar Level', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              ]),
              DropdownButton<String>(
                value: _selectedFilter,
                items: ['Day', 'Week', 'Month'].map((f) => DropdownMenuItem(value: f, child: Text(f))).toList(),
                onChanged: (val) {
                  if (val != null) {
                    setState(() {
                      _selectedFilter = val;
                      _applyFilter();
                    });
                  }
                },
              ),
            ]),
            const SizedBox(height: 12),
            Text('AVG: ${_avg == 0 ? '0' : _avg.toStringAsFixed(1)} mg/dL',
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            Text('Status: $status', style: TextStyle(fontSize: 14, color: statusColor)),
            const SizedBox(height: 12),
            SizedBox(
              height: 180,
              child: _filteredData.isEmpty
                  ? Center(child: Text('No data for selected period', style: TextStyle(color: Colors.grey.shade600)))
                  : LineChart(
                      LineChartData(
                        gridData: FlGridData(show: true),
                        titlesData: FlTitlesData(
                          leftTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, reservedSize: 40)),
                          bottomTitles: AxisTitles(
                            sideTitles: SideTitles(
                              showTitles: true,
                              getTitlesWidget: (value, meta) {
                                final idx = value.toInt();
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
                        minY: (_lowest > 0) ? (_lowest - 20) : 40,
                        maxY: (_highest > 0) ? (_highest + 20) : 260,
                        lineBarsData: [
                          LineChartBarData(
                            spots: [
                              for (int i = 0; i < _filteredData.length; i++)
                                FlSpot(i.toDouble(), ((_filteredData[i]['value'] as num?)?.toDouble() ?? 0)),
                            ],
                            isCurved: true,
                            color: Colors.green.shade700,
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
          ]),
        ),
      ),
    );
  }
}

// Detailed Sugar Level View
class SugarLevelDetailedView extends StatefulWidget {
  final List<Map<String, dynamic>> sugarData;
  final String filter;

  const SugarLevelDetailedView({
    required this.sugarData,
    required this.filter,
    Key? key,
  }) : super(key: key);

  @override
  State<SugarLevelDetailedView> createState() => _SugarLevelDetailedViewState();
}

class _SugarLevelDetailedViewState extends State<SugarLevelDetailedView> {
  String _selectedFilter = 'Week';
  List<Map<String, dynamic>> _filteredData = [];
  double _avg = 0;
  double _highest = 0;
  double _lowest = 0;
  DateTime? _highestDate;
  DateTime? _lowestDate;

  @override
  void initState() {
    super.initState();
    _selectedFilter = widget.filter;
    _applyFilter();
  }

  void _applyFilter() {
    final now = DateTime.now();
    DateTime start;
    if (_selectedFilter == 'Day') {
      start = now.subtract(const Duration(days: 1));
    } else if (_selectedFilter == 'Week') {
      start = now.subtract(const Duration(days: 7));
    } else {
      start = now.subtract(const Duration(days: 30));
    }

    _filteredData = widget.sugarData.where((d) {
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

      _avg = values.reduce((a, b) => a + b) / values.length;
      _highest = values.reduce((a, b) => a > b ? a : b);
      _lowest = values.reduce((a, b) => a < b ? a : b);

      for (var rec in _filteredData) {
        final val = (rec['value'] is num) ? (rec['value'] as num).toDouble() : double.tryParse(rec['value'].toString()) ?? 0.0;
        final date = rec['measuredAt'] is DateTime
            ? rec['measuredAt'] as DateTime
            : (rec['measuredAt'] is int ? DateTime.fromMillisecondsSinceEpoch(rec['measuredAt']) : null);
        if (val == _highest && date != null) _highestDate = date;
        if (val == _lowest && date != null) _lowestDate = date;
      }
    } else {
      _avg = 0;
      _highest = 0;
      _lowest = 0;
      _highestDate = null;
      _lowestDate = null;
    }

    if (mounted) setState(() {});
  }

  String _formatDateShort(DateTime dt) => '${dt.month}/${dt.day}';

  String _getStatus(double sugar) {
    if (sugar == 0) return 'No Data';
    if (sugar < 70) return 'Low';
    if (sugar >= 140) return 'High';
    return 'Normal';
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
    final List<String> insights = [];
    if (_filteredData.isEmpty) {
      insights.add('No sugar level readings recorded in the selected period.');
      return insights;
    }

    final periodText = _selectedFilter == 'Day' ? 'today' : _selectedFilter == 'Week' ? 'this week' : 'this month';
    final status = _getStatus(_avg);

    if (status == 'High') {
      insights.add('⚠️ Average sugar level is elevated. Monitor carbohydrate intake and consult your healthcare provider.');
    } else if (status == 'Low') {
      insights.add('⚠️ Average sugar level is low. Consider regular meal timing and balanced nutrition.');
    } else {
      insights.add('✅ Average sugar level is within normal range.');
    }

    final readingsCount = _filteredData.length;
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
      final recentAvg = recent.map((d) {
        final v = d['value'];
        if (v is num) return v.toDouble();
        if (v is String) return double.tryParse(v) ?? 0.0;
        return 0.0;
      }).reduce((a, b) => a + b) / 3;

      if (recentAvg > _avg + 10) {
        insights.add('📈 Recent readings show an upward trend compared to earlier values.');
      } else if (recentAvg < _avg - 10) {
        insights.add('📉 Recent readings show a downward trend compared to earlier values.');
      }
    }

    return insights;
  }

  @override
  Widget build(BuildContext context) {
    final status = _getStatus(_avg);
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
                    Icon(Icons.water_drop, color: Colors.green.shade700, size: 24),
                    const SizedBox(width: 8),
                    const Text('Sugar Level Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ],
                ),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(color: Colors.green.shade700, borderRadius: BorderRadius.circular(8)),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedFilter,
                          isDense: true,
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                          dropdownColor: Colors.green,
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
                        child: _buildCompactStatCard('AVG', _avg == 0 ? '0' : _avg.toStringAsFixed(1), 'mg/dL', Colors.green.shade700),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildCompactStatCard(
                          'High',
                          _highest == 0 ? '0' : _highest.toStringAsFixed(0),
                          _highestDate != null ? _formatDateShort(_highestDate!) : '',
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
                          _lowest == 0 ? '0' : _lowest.toStringAsFixed(0),
                          _lowestDate != null ? _formatDateShort(_lowestDate!) : '',
                          Colors.blue.shade600,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Container()), // spacer
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
                                leftTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, reservedSize: 35)),
                                bottomTitles: AxisTitles(
                                  sideTitles: SideTitles(
                                    showTitles: true,
                                    getTitlesWidget: (value, meta) {
                                      final idx = value.toInt();
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
                              minY: (_lowest > 0) ? (_lowest - 20) : 40,
                              maxY: (_highest > 0) ? (_highest + 20) : 260,
                              lineBarsData: [
                                LineChartBarData(
                                  spots: [
                                    for (int i = 0; i < _filteredData.length; i++)
                                      FlSpot(i.toDouble(), (_filteredData[i]['value'] as num?)?.toDouble() ?? 0),
                                  ],
                                  isCurved: true,
                                  color: Colors.green.shade700,
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
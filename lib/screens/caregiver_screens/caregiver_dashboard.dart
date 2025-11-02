import 'package:flutter/material.dart';
import 'dart:math' as math;
import 'package:cloud_firestore/cloud_firestore.dart';

class CaregiverDashboard extends StatelessWidget {
  CaregiverDashboard({super.key});

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375; // base screen width
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  final userName = "Lola Granny";
  final age = 70;

  final List<Map<String, dynamic>> healthData = [
    {
      "title": "Blood Pressure",
      "value": "120/80",
      "unit": "mmHg",
      "icon": Icons.bloodtype,
      "color": Colors.orangeAccent,
    },
    {
      "title": "Sugar Level",
      "value": 95,
      "unit": "mg/dL",
      "icon": Icons.stacked_bar_chart,
      "color": Colors.green,
    },
    {
      "title": "Temperature",
      "value": 36.7,
      "unit": "°C",
      "icon": Icons.thermostat,
      "color": Colors.blueAccent,
    },
    {
      "title": "Heart Rate",
      "value": 72,
      "unit": "bpm",
      "icon": Icons.monitor_heart,
      "color": Colors.pinkAccent,
    },
  ];

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final screenHeight = MediaQuery.of(context).size.height;
    final bool isTablet = screenWidth >= 600;
    final bool isDesktop = screenWidth >= 1000;
    final bool isLandscape = screenWidth > screenHeight;

    double getResponsiveValue(double phone, double tablet, double desktop) {
      if (isDesktop) return desktop;
      if (isTablet) return tablet;
      return phone;
    }

    double getGridChildAspectRatio(int crossAxisCount) {
      final cardWidth = (screenWidth - (crossAxisCount - 1) * getResponsiveValue(8, 12, 16)) / crossAxisCount;
      final cardHeight = isLandscape ? 140.0 : 180.0;
      return cardWidth / cardHeight;
    }

    final crossAxisCount = isDesktop ? 4 : isTablet ? 3 : 2;

    final bottomPadding = MediaQuery.of(context).viewInsets.bottom + MediaQuery.of(context).padding.bottom;
    final horizontalPad = isLandscape ? getResponsiveValue(12, 16, 32) : getResponsiveValue(16, 24, 48);

    return SafeArea(
      child: SingleChildScrollView(
        padding: EdgeInsets.fromLTRB(
          horizontalPad,
          getResponsiveValue(12, 16, 20),
          horizontalPad,
          getResponsiveValue(12, 16, 20) + bottomPadding,
        ),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
              // --- User Card ---
              Container(
                width: double.infinity,
                padding: EdgeInsets.all(getResponsiveValue(12, 20, 28)),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Builder(builder: (context) {
                      final avatarRadius = math.min(getResponsiveValue(35, 45, 55), screenWidth * 0.12);
                      final iconSize = math.min(getResponsiveValue(40, 55, 65), screenWidth * 0.12);
                      return Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: Colors.deepPurpleAccent.withOpacity(0.1),
                            radius: avatarRadius,
                            child: Icon(
                              Icons.person,
                              color: Colors.deepPurpleAccent,
                              size: iconSize,
                            ),
                          ),
                          SizedBox(width: getResponsiveValue(12, 18, 24)),
                        ],
                      );
                    }),
                    Flexible(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            userName,
                            style: TextStyle(
                              fontSize: _getResponsiveFontSize(context, 24),
                              fontWeight: FontWeight.w700,
                              color: Colors.black,
                            ),
                          ),
                          SizedBox(height: getResponsiveValue(4, 6, 8)),
                          Text(
                            'Age: $age',
                            style: TextStyle(
                              fontSize: _getResponsiveFontSize(context, 14),
                              fontWeight: FontWeight.w600,
                              color: Colors.black,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              SizedBox(height: getResponsiveValue(20, 24, 28)),

              //Latest Health Data
              Row(
                children: [
                  Flexible(
                    flex: 0,
                    child: _buildSectionIcon(
                        Icons.health_and_safety, Colors.green, getResponsiveValue(28, 32, 36)),
                  ),
                  SizedBox(width: getResponsiveValue(8, 12, 16)),
                  Expanded(
                    child: Text(
                      'Latest Health Data',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 18),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: getResponsiveValue(12, 16, 20)),

              GridView.builder(
                physics: const NeverScrollableScrollPhysics(),
                shrinkWrap: true,
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: crossAxisCount,
                  crossAxisSpacing: getResponsiveValue(8, 12, 16),
                  mainAxisSpacing: getResponsiveValue(8, 12, 16),
                  childAspectRatio: isLandscape ? (getGridChildAspectRatio(crossAxisCount) * 0.9) : getGridChildAspectRatio(crossAxisCount),
                ),
                itemCount: healthData.length,
                itemBuilder: (context, index) {
                  final data = healthData[index];

                  if (data['title'] == 'Blood Pressure') {
                  return StreamBuilder<QuerySnapshot>(
                    stream: FirebaseFirestore.instance
                    .collection('health_data')
                    .where('elderlyId', isEqualTo: 'test-user-123')
                    .where('type', isEqualTo: 'blood_pressure')
                    .orderBy('measuredAt', descending: true)
                    .limit(1)
                    .snapshots(),

                    builder: (context, snapshot) {
                      String displayValue = data['value'];
                      String? status;
                      Color? statusColor;

                      if (snapshot.hasData && snapshot.data!.docs.isNotEmpty) {
                        final doc = snapshot.data!.docs.first;
                        final map = doc.data() as Map<String, dynamic>;

                        num? systolic = map['systolic'];
                        num? diastolic = map['diastolic'];

                        String formatValue(num? value) {
                          if (value == null) return '--';
                          if (value % 1 == 0) return value.toInt().toString(); // removes .0 if whole number
                          return value.toString();
                        }

                        final systolicStr = formatValue(systolic);
                        final diastolicStr = formatValue(diastolic);
                        displayValue = '$systolicStr/$diastolicStr';

                        // 💡 Optional: Compute blood pressure status automatically
                        final sys = systolic ?? 0;
                        final dia = diastolic ?? 0;
                        if (sys < 90 || dia < 60) {
                          status = 'LOW';
                          statusColor = Colors.blue;
                        } else if (sys > 140 || dia > 90) {
                          status = 'HIGH';
                          statusColor = Colors.red;
                        } else {
                          status = 'NORMAL';
                          statusColor = Colors.green;
                        }
                      }

                      return HealthCard(
                        title: data['title'],
                        value: displayValue,
                        unit: data['unit'],
                        status: status,
                        statusColor: statusColor,
                        icon: data['icon'],
                        color: data['color'],
                        getFontSize: _getResponsiveFontSize,
                      );
                    },
                  );
                }


                  return HealthCard(
                    title: data['title'],
                    value: data['value'],
                    unit: data['unit'],
                    icon: data['icon'],
                    color: data['color'],
                    getFontSize: _getResponsiveFontSize,
                  );
                },
              ),
              SizedBox(height: getResponsiveValue(24, 28, 32)),

              //Reminders
              Row(
                children: [
                  _buildSectionIcon(Icons.notification_important,
                      const Color.fromARGB(255, 211, 58, 65), getResponsiveValue(28, 32, 36)),
                  SizedBox(width: getResponsiveValue(12, 16, 20)),
                  Expanded(
                    child: Text(
                      'Reminders',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 18),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: getResponsiveValue(12, 16, 20)),

              Container(
                width: double.infinity,
                padding: EdgeInsets.all(getResponsiveValue(10, 12, 16)),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 6,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ReminderItem(
                      icon: Icons.medication,
                      title: "Take blood pressure medicine",
                      time: "8:00 AM",
                      color: Colors.deepPurpleAccent,
                      getFontSize: _getResponsiveFontSize,
                    ),
                    SizedBox(height: 10),
                    ReminderItem(
                      icon: Icons.calendar_today,
                      title: "Doctor’s appointment - Cardiology",
                      time: "3:00 PM",
                      color: Colors.redAccent,
                      getFontSize: _getResponsiveFontSize,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
    );
    
  }

  Widget _buildSectionIcon(IconData icon, Color color, double size) {
    return Container(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(2, 4),
          ),
        ],
      ),
      padding: EdgeInsets.all(size / 3),
      child: Icon(icon, color: color, size: size),
    );
  }
}

// --- HealthCard ---
class HealthCard extends StatelessWidget {
  final String title;
  final dynamic value;
  final String unit;
  final String? status;
  final Color? statusColor;
  final IconData icon;
  final Color color;
  final double Function(BuildContext, double) getFontSize;

  const HealthCard({
    super.key,
    required this.title,
    required this.value,
    required this.unit,
    this.status,
    this.statusColor,
    required this.icon,
    required this.color,
    required this.getFontSize,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final bool isTablet = screenWidth >= 600;
    final bool isDesktop = screenWidth >= 1000;

    double getResponsiveValue(double phone, double tablet, double desktop) {
      if (isDesktop) return desktop;
      if (isTablet) return tablet;
      return phone;
    }

    return Container(
      padding: EdgeInsets.all(getResponsiveValue(12, 18, 24)),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 4,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: color.withOpacity(0.1),
                child: Icon(icon, color: color),
              ),
              SizedBox(width: getResponsiveValue(6, 8, 10)),
              Expanded(
                child: Text(
                  title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: getFontSize(context, 12),
                    fontWeight: FontWeight.w600,
                    color: Colors.black,
                  ),
                ),
              ),
            ],
          ),

          SizedBox(height: getResponsiveValue(8, 10, 12)),

          Center(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: RichText(
                textAlign: TextAlign.center,
                text: TextSpan(
                  style: const TextStyle(color: Colors.black),
                  children: [
                    TextSpan(
                      text: "$value ",
                      style: TextStyle(
                        fontSize: getFontSize(context, 28),
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    TextSpan(
                      text: unit,
                      style: TextStyle(
                        fontSize: getFontSize(context, 14),
                        fontWeight: FontWeight.w500,
                        color: Colors.grey,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Status badge (optional)
          if (status != null && status!.isNotEmpty) ...[
            SizedBox(height: getResponsiveValue(8, 10, 12)),
            Center(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: (statusColor ?? Colors.grey).withOpacity(0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  status!,
                  style: TextStyle(
                    color: statusColor ?? Colors.grey,
                    fontWeight: FontWeight.w700,
                    fontSize: getFontSize(context, 12),
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

//ReminderItem
class ReminderItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final String time;
  final Color color;
  final double Function(BuildContext, double) getFontSize;

  const ReminderItem({
    super.key,
    required this.icon,
    required this.title,
    required this.time,
    required this.color,
    required this.getFontSize,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final bool isTablet = screenWidth >= 600;
    final bool isDesktop = screenWidth >= 1000;

    double getResponsiveValue(double phone, double tablet, double desktop) {
      if (isDesktop) return desktop;
      if (isTablet) return tablet;
      return phone;
    }

    return Container(
      padding: EdgeInsets.symmetric(
        vertical: getResponsiveValue(10, 14, 18),
        horizontal: getResponsiveValue(12, 16, 20),
      ),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: color.withOpacity(0.1),
            radius: getResponsiveValue(20, 24, 28),
            child: Icon(icon, color: color, size: getResponsiveValue(22, 26, 30)),
          ),
          SizedBox(width: getResponsiveValue(12, 14, 16)),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: getFontSize(context, 16),
                    color: Colors.black,
                  ),
                ),
                Text(
                  time,
                  style: TextStyle(
                    fontSize: getFontSize(context, 14),
                    color: Colors.grey,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
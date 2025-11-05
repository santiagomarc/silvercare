import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:silvercare/models/elderly_model.dart'; // Import ElderlyModel
import 'package:silvercare/services/user_service.dart';
import 'package:cloud_firestore/cloud_firestore.dart'; // Import Firestore
import 'package:silvercare/models/health_data_model.dart';
import 'package:silvercare/services/mood_service.dart';
import 'add_medication_screen.dart'; // Import the medication screen
import 'add_checklist_screen.dart'; // Import the checklist screen

class CaregiverDashboard extends StatefulWidget {
  const CaregiverDashboard({super.key});

  @override
  State<CaregiverDashboard> createState() => _CaregiverDashboardState();
}

class _CaregiverDashboardState extends State<CaregiverDashboard> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  Map<String, dynamic>? _caregiverProfile;
  ElderlyModel? _linkedElderly; // Store the full elderly model
  String? _managingElderlyId;
  bool _isLoading = true;

  // Health data variables
  Map<String, dynamic> _healthData = {
    'bloodPressure': {'systolic': 0.0, 'diastolic': 0.0, 'timestamp': null},
    'sugarLevel': {'value': 0.0, 'timestamp': null},
    'temperature': {'value': 0.0, 'timestamp': null},
    'heartRate': {'value': 0.0, 'timestamp': null},
  };
  MoodRecord? _elderlyMood;

  // Stream subscriptions for real-time updates
  final List<dynamic> _streamSubscriptions = [];

  @override
  void initState() {
    super.initState();
    _fetchCaregiverData();
  }

  @override
  void dispose() {
    // Cancel all stream subscriptions
    for (var subscription in _streamSubscriptions) {
      subscription.cancel();
    }
    super.dispose();
  }

  Future<void> _fetchCaregiverData() async {
    // 1. Fetch the caregiver's profile
    final profile = await UserService.getUserProfile(_auth.currentUser?.uid);
    if (!mounted) return;

    if (profile != null) {
      // 2. CRITICAL FIX: Get the correct field 'elderlyId' from your model
      final String? elderlyId = profile['elderlyId'];
      ElderlyModel? elderlyModel;

      // 3. If an elderlyId exists, fetch that elderly's profile
      if (elderlyId != null && elderlyId.isNotEmpty) {
        try {
          final elderlyDoc = await FirebaseFirestore.instance
              .collection('elderly')
              .doc(elderlyId)
              .get();
          if (elderlyDoc.exists) {
            elderlyModel = ElderlyModel.fromDoc(elderlyDoc);
          }
          
          // Fetch health data for the connected elderly
          await _fetchHealthData(elderlyId);
        } catch (e) {
          print("Error fetching elderly profile: $e");
        }
      }

      setState(() {
        _caregiverProfile = profile;
        _managingElderlyId = elderlyId; // Store the ID
        _linkedElderly = elderlyModel; // Store the full profile
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _fetchHealthData(String elderlyId) async {
    try {
      // Setup real-time listeners for each health data type
      _setupBloodPressureStream(elderlyId);
      _setupSugarLevelStream(elderlyId);
      _setupTemperatureStream(elderlyId);
      _setupHeartRateStream(elderlyId);
      _setupMoodStream(elderlyId);
    } catch (e) {
      print("Error setting up health data streams: $e");
    }
  }

  void _setupBloodPressureStream(String elderlyId) {
    final now = DateTime.now();
    final oneDayAgo = now.subtract(const Duration(days: 1));

    final subscription = FirebaseFirestore.instance
        .collection('health_data')
        .where('elderlyId', isEqualTo: elderlyId)
        .where('type', isEqualTo: 'blood_pressure')
        .where('measuredAt', isGreaterThan: oneDayAgo)
        .orderBy('measuredAt', descending: true)
        .limit(1)  // Only need 1 document since both values are in the same record
        .snapshots()
        .listen((snapshot) {
      if (snapshot.docs.isNotEmpty) {
        final doc = snapshot.docs.first;
        final data = doc.data();
        final oldValue = _healthData['bloodPressure'];
        
        // Read systolic and diastolic from the same document
        final systolic = (data['systolic'] ?? data['value'] ?? 0).toDouble();
        final diastolic = (data['diastolic'] ?? 0).toDouble();
        final timestamp = (data['measuredAt'] as Timestamp).toDate();
        
        setState(() {
          _healthData['bloodPressure'] = {
            'systolic': systolic,
            'diastolic': diastolic,
            'timestamp': timestamp,
          };
        });

        // Show notification if value changed
        if (oldValue['timestamp'] != null && 
            oldValue['timestamp'] != timestamp) {
          _showHealthUpdateNotification('Blood Pressure', 
            '${systolic.toInt()}/${diastolic.toInt()} mmHg');
        }
      }
    });
    _streamSubscriptions.add(subscription);
  }

  void _setupSugarLevelStream(String elderlyId) {
    final now = DateTime.now();
    final oneDayAgo = now.subtract(const Duration(days: 1));

    final subscription = FirebaseFirestore.instance
        .collection('health_data')
        .where('elderlyId', isEqualTo: elderlyId)
        .where('type', isEqualTo: 'sugar_level')
        .where('measuredAt', isGreaterThan: oneDayAgo)
        .orderBy('measuredAt', descending: true)
        .limit(1)
        .snapshots()
        .listen((snapshot) {
      if (snapshot.docs.isNotEmpty) {
        final sugarData = HealthDataModel.fromDoc(snapshot.docs.first);
        final oldTimestamp = _healthData['sugarLevel']['timestamp'];
        
        setState(() {
          _healthData['sugarLevel'] = {
            'value': sugarData.value,
            'timestamp': sugarData.measuredAt,
          };
        });

        if (oldTimestamp != null && oldTimestamp != sugarData.measuredAt) {
          _showHealthUpdateNotification('Sugar Level', '${sugarData.value.toInt()} mg/dL');
        }
      }
    });
    _streamSubscriptions.add(subscription);
  }

  void _setupTemperatureStream(String elderlyId) {
    final now = DateTime.now();
    final oneDayAgo = now.subtract(const Duration(days: 1));

    final subscription = FirebaseFirestore.instance
        .collection('health_data')
        .where('elderlyId', isEqualTo: elderlyId)
        .where('type', isEqualTo: 'temperature')
        .where('measuredAt', isGreaterThan: oneDayAgo)
        .orderBy('measuredAt', descending: true)
        .limit(1)
        .snapshots()
        .listen((snapshot) {
      if (snapshot.docs.isNotEmpty) {
        final tempData = HealthDataModel.fromDoc(snapshot.docs.first);
        final oldTimestamp = _healthData['temperature']['timestamp'];
        
        setState(() {
          _healthData['temperature'] = {
            'value': tempData.value,
            'timestamp': tempData.measuredAt,
          };
        });

        if (oldTimestamp != null && oldTimestamp != tempData.measuredAt) {
          _showHealthUpdateNotification('Temperature', '${tempData.value.toStringAsFixed(1)} °C');
        }
      }
    });
    _streamSubscriptions.add(subscription);
  }

  void _setupHeartRateStream(String elderlyId) {
    final now = DateTime.now();
    final oneDayAgo = now.subtract(const Duration(days: 1));

    final subscription = FirebaseFirestore.instance
        .collection('health_data')
        .where('elderlyId', isEqualTo: elderlyId)
        .where('type', isEqualTo: 'heart_rate')
        .where('measuredAt', isGreaterThan: oneDayAgo)
        .orderBy('measuredAt', descending: true)
        .limit(1)
        .snapshots()
        .listen((snapshot) {
      if (snapshot.docs.isNotEmpty) {
        final hrData = HealthDataModel.fromDoc(snapshot.docs.first);
        final oldTimestamp = _healthData['heartRate']['timestamp'];
        
        setState(() {
          _healthData['heartRate'] = {
            'value': hrData.value,
            'timestamp': hrData.measuredAt,
          };
        });

        if (oldTimestamp != null && oldTimestamp != hrData.measuredAt) {
          _showHealthUpdateNotification('Heart Rate', '${hrData.value.toInt()} bpm');
        }
      }
    });
    _streamSubscriptions.add(subscription);
  }

  void _setupMoodStream(String elderlyId) {
    final now = DateTime.now();
    final dateString = "${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}";

    final subscription = FirebaseFirestore.instance
        .collection('elders')
        .doc(elderlyId)
        .collection('moods')
        .doc(dateString)
        .snapshots()
        .listen((snapshot) {
      if (snapshot.exists) {
        final newMood = MoodRecord.fromMap(snapshot.data()!);
        final hadMood = _elderlyMood != null;
        
        setState(() {
          _elderlyMood = newMood;
        });

        if (hadMood && _elderlyMood?.timestamp != newMood.timestamp) {
          _showHealthUpdateNotification('Mood', '${newMood.emoji} ${newMood.mood}');
        }
      }
    });
    _streamSubscriptions.add(subscription);
  }

  void _showHealthUpdateNotification(String vitalName, String value) {
    if (!mounted) return;
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.update, color: Colors.white, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                '$vitalName updated: $value',
                style: const TextStyle(fontWeight: FontWeight.w500),
              ),
            ),
          ],
        ),
        backgroundColor: Colors.blue.shade700,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 3),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375; // base screen width
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  // Get health data dynamically
  List<Map<String, dynamic>> _getHealthData() {
    final bp = _healthData['bloodPressure'];
    final systolic = bp['systolic']?.toInt() ?? 0;
    final diastolic = bp['diastolic']?.toInt() ?? 0;
    
    final sugarValue = _healthData['sugarLevel']['value']?.toDouble() ?? 0.0;
    final tempValue = _healthData['temperature']['value']?.toDouble() ?? 0.0;
    final hrValue = _healthData['heartRate']['value']?.toDouble() ?? 0.0;
    
    return [
      {
        "title": "Blood Pressure",
        "value": systolic > 0 ? "$systolic/$diastolic" : "N/A",
        "unit": "mmHg",
        "icon": Icons.bloodtype,
        "color": Colors.orange.shade900,
        "status": _getBloodPressureStatus(systolic, diastolic),
        "statusColor": _getBloodPressureStatusColor(systolic, diastolic),
      },
      {
        "title": "Sugar Level",
        "value": sugarValue > 0 ? sugarValue.toInt() : "N/A",
        "unit": "mg/dL",
        "icon": Icons.water_drop,
        "color": Colors.green.shade900,
        "status": _getSugarLevelStatus(sugarValue),
        "statusColor": _getSugarLevelStatusColor(sugarValue),
      },
      {
        "title": "Temperature",
        "value": tempValue > 0 ? tempValue.toStringAsFixed(1) : "N/A",
        "unit": "°C",
        "icon": Icons.thermostat,
        "color": Colors.lightBlue.shade900,
        "status": _getTemperatureStatus(tempValue),
        "statusColor": _getTemperatureStatusColor(tempValue),
      },
      {
        "title": "Heart Rate",
        "value": hrValue > 0 ? hrValue.toInt() : "N/A",
        "unit": "bpm",
        "icon": Icons.favorite,
        "color": Colors.pink.shade900,
        "status": _getHeartRateStatus(hrValue),
        "statusColor": _getHeartRateStatusColor(hrValue),
      },
    ];
  }

  // Blood Pressure Status
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

  // Sugar Level Status
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

  // Temperature Status
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

  // Heart Rate Status
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

  final List<Map<String, dynamic>> recentActivities = [
    {
      "title": "Lola took her Metformin",
      "time": "10:05 AM",
      "icon": Icons.check_circle,
      "color": Colors.green,
    },
    {
      "title": "Lola missed her 9:00 AM Paracetamol",
      "time": "9:30 AM",
      "icon": Icons.warning,
      "color": Colors.red,
    },
    {
      "title": "Lola's heart rate was high",
      "time": "8:15 AM",
      "icon": Icons.favorite,
      "color": Colors.red,
    },
  ];
  // --- End Mock Data ---

  @override
  Widget build(BuildContext context) {
    // Get the elderly's name, or a default
    final String elderlyName = _linkedElderly?.username ?? "Your Patient";

    return Scaffold(
      backgroundColor: Colors.grey[200],
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildHeader(),
                  const SizedBox(height: 24),

                  // --- NEW: Elder Management Panel ---
                  _buildSectionTitle(context, "Elder Management"),
                  const SizedBox(height: 16),
                  _buildManagementPanel(),
                  // --- End New Panel ---

                  const SizedBox(height: 24),
                  
                  // Mood Display Widget - Always show section if elderly is connected
                  if (_managingElderlyId != null) ...[
                    _buildSectionTitle(context, "$elderlyName's Mood Today"),
                    const SizedBox(height: 16),
                    _buildMoodWidget(),
                    const SizedBox(height: 24),
                  ],
                  
                  // Use the dynamically loaded name
                  _buildSectionTitle(context, "$elderlyName's Vitals Overview"),
                  const SizedBox(height: 16),
                  _buildHealthGrid(),
                  const SizedBox(height: 24),
                  _buildSectionTitle(context, "Recent Activity"),
                  const SizedBox(height: 16),
                  _buildRecentActivities(),
                ],
              ),
            ),
    );
  }

  Widget _buildSectionTitle(BuildContext context, String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8.0),
      child: Text(
        title,
        style: TextStyle(
          fontSize: _getResponsiveFontSize(context, 18),
          fontWeight: FontWeight.w700,
          color: Colors.black87,
        ),
      ),
    );
  }

  Widget _buildHeader() {
    String caregiverName = _caregiverProfile?['fullName'] ?? 'Caregiver';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome back,',
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 16),
              color: Colors.grey[600],
            ),
          ),
          Text(
            caregiverName,
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 26),
              fontWeight: FontWeight.bold,
              color: Colors.black,
            ),
          ),
        ],
      ),
    );
  }

  // --- NEW: Management Panel ---
  Widget _buildManagementPanel() {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 16,
      mainAxisSpacing: 16,
      childAspectRatio: 1.2,
      children: [
        _buildManagementCard(
          title: "Manage Medications",
          icon: Icons.medication_rounded,
          color: Colors.blue.shade700,
          onTap: () {
            // --- CRITICAL FIX: Add guard clause ---
            if (_managingElderlyId == null || _managingElderlyId!.isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('No elderly patient assigned. Please update your profile.'),
                  backgroundColor: Colors.red,
                ),
              );
              return;
            }
            
            // If check passes, navigate
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => AddMedicationScreen(elderlyId: _managingElderlyId!),
              ),
            );
          },
        ),
        _buildManagementCard(
          title: "Manage Checklist",
          icon: Icons.checklist_rounded,
          color: Colors.green.shade700,
          onTap: () {
            // Check if we have an elderly to manage
            if (_managingElderlyId == null || _managingElderlyId!.isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('⚠️ No elderly assigned to manage.')),
              );
              return;
            }
            
            // Navigate to Add Checklist Screen
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => AddChecklistScreen(elderlyId: _managingElderlyId!),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildManagementCard({
    required String title,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Card(
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Icon(icon, size: 40, color: color),
              const SizedBox(height: 12),
              Text(
                title,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 15),
                  fontWeight: FontWeight.w600,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  // --- End New Widgets ---

  Widget _buildHealthGrid() {
    final healthData = _getHealthData();
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 1.2,
      ),
      itemCount: healthData.length,
      itemBuilder: (context, index) {
        final item = healthData[index];
        return _buildHealthCard(
          title: item['title'],
          value: item['value'].toString(),
          unit: item['unit'],
          icon: item['icon'],
          color: item['color'],
          status: item['status'],
          statusColor: item['statusColor'],
        );
      },
    );
  }

  Widget _buildHealthCard({
    required String title,
    required String value,
    required String unit,
    required IconData icon,
    required Color color,
    String? status,
    Color? statusColor,
  }) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, size: 32, color: color),
                if (status != null && statusColor != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: statusColor, width: 1),
                    ),
                    child: Text(
                      status,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 10),
                        fontWeight: FontWeight.bold,
                        color: statusColor,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 14),
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 4),
            RichText(
              text: TextSpan(
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 22),
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                  fontFamily: 'Montserrat',
                ),
                children: [
                  TextSpan(text: value),
                  TextSpan(
                    text: ' $unit',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 14),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMoodWidget() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: _elderlyMood == null
            ? Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade200,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.sentiment_neutral,
                      size: 40,
                      color: Colors.grey.shade400,
                    ),
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'No mood reported',
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 18),
                            fontWeight: FontWeight.w600,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Waiting for today\'s mood check-in',
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 13),
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              )
            : Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: _getMoodColor(_elderlyMood!.moodLevel).withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      _elderlyMood!.emoji,
                      style: const TextStyle(fontSize: 40),
                    ),
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _elderlyMood!.mood,
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 20),
                            fontWeight: FontWeight.bold,
                            color: Colors.black87,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Reported today',
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 14),
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _getMoodColor(_elderlyMood!.moodLevel),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      _getMoodLabel(_elderlyMood!.moodLevel),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Color _getMoodColor(int moodLevel) {
    switch (moodLevel) {
      case 0:
        return Colors.red.shade700;
      case 1:
        return Colors.orange.shade600;
      case 2:
        return Colors.yellow.shade700;
      case 3:
        return Colors.lightGreen.shade600;
      case 4:
        return Colors.green.shade700;
      default:
        return Colors.grey;
    }
  }

  String _getMoodLabel(int moodLevel) {
    switch (moodLevel) {
      case 0:
        return 'Poor';
      case 1:
        return 'Fair';
      case 2:
        return 'Okay';
      case 3:
        return 'Good';
      case 4:
        return 'Excellent';
      default:
        return 'Unknown';
    }
  }

  Widget _buildRecentActivities() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Column(
        children: recentActivities.map((activity) {
          return Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              children: [
                Icon(activity['icon'], color: activity['color'], size: 28),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        activity['title'],
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 15),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      Text(
                        activity['time'],
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 13),
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }
}


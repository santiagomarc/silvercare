import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:silvercare/services/user_service.dart';
import 'add_medication_screen.dart'; // Import the new screen

class CaregiverDashboard extends StatefulWidget {
  const CaregiverDashboard({super.key});

  @override
  State<CaregiverDashboard> createState() => _CaregiverDashboardState();
}

class _CaregiverDashboardState extends State<CaregiverDashboard> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  Map<String, dynamic>? _caregiverProfile;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchCaregiverData();
  }

  Future<void> _fetchCaregiverData() async {
    final profile = await UserService.getUserProfile(_auth.currentUser?.uid);
    if (mounted) {
      setState(() {
        _caregiverProfile = profile;
        _isLoading = false;
      });
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375; // base screen width
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  // --- Mock Data (Kept from your original file for UI) ---
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
      "value": 75,
      "unit": "bpm",
      "icon": Icons.favorite,
      "color": Colors.redAccent,
    },
  ];

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
                  _buildSectionTitle(context, "Lola's Vitals Overview"),
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
            // This is the navigation you requested
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => const AddMedicationScreen(),
              ),
            );
          },
        ),
        _buildManagementCard(
          title: "Manage Checklist",
          icon: Icons.checklist_rounded,
          color: Colors.green.shade700,
          onTap: () {
            // TODO: Create and navigate to AddChecklistScreen
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Manage Checklist coming soon!')),
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
            Icon(icon, size: 32, color: color),
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

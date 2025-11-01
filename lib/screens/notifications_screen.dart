import 'package:flutter/material.dart';
// FIX: Using the correct file name
import '../widgets/nav_bar_svg.dart'; 

const String _logoAssetPath = 'assets/icons/silvercare.png'; 

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final int _currentIndex = 0; 
  
  final List<String> _navLabels = const [
    'Notifications',
    'Calendar',     
    'Wellness',      
    'Home',          
    'Profile',       
  ];
  
  final Color _missedMedColor = const Color(0xFFCD5C5C);
  final Color _checklistColor = const Color(0xFF008000);
  final Color _upcomingsColor = const Color(0xFF000080);
  final Color _titleTextColor = const Color(0xFF808080);
  final Color _bronzeBgColor = const Color(0xFFCD7F32); 

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  // FIX: Using the local _navLabels list
  void _handleTabTap(int index) {
    String destination = _navLabels[index];
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text("Navigation Demo"),
          content: Text("You would navigate to the '$destination' screen now."),
          actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text("Close"))],
        );
      },
    );
  }

  void _showComingSoonDialog(BuildContext context, String feature) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          title: Text(
            'Action: $feature',
            style: TextStyle(
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w600,
              fontSize: _getResponsiveFontSize(context, 18),
            ),
          ),
          content: const Text(
            'This button would typically navigate to the detail screen.',
            style: TextStyle(fontFamily: 'Inter', fontSize: 14, color: Color(0xFF666666)),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('OK', style: TextStyle(color: Color(0xFF2C2C2C), fontFamily: 'Inter', fontWeight: FontWeight.w500)),
            ),
          ],
        );
      },
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 20, bottom: 20, left: 20, right: 20),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Container(
            width: 55,
            height: 55,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.blueGrey,
              border: Border.all(color: Colors.white, width: 3),
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 5, offset: const Offset(0, 3)),
              ],
            ),
            child: const Icon(Icons.person_outline, color: Colors.white, size: 30),
          ),

          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                width: 55,
                height: 55, 
                child: Image.asset(
                  _logoAssetPath,
                  fit: BoxFit.contain,
                  errorBuilder: (context, error, stackTrace) {
                    return const Icon(Icons.shield, color: Colors.grey, size: 30); 
                  },
                ),
              ),
              const SizedBox(width: 15),
              Text(
                'SILVER CARE',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.black,
                  fontSize: _getResponsiveFontSize(context, 21),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w800,
                  shadows: [Shadow(offset: const Offset(0, 3), blurRadius: 4, color: Colors.black.withOpacity(0.50))],
                ),
              ),
            ],
          ),

          InkWell(
            onTap: () => _showComingSoonDialog(context, 'Settings'),
            child: Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.8),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 4, offset: const Offset(0, 2))],
              ),
              child: const Icon(Icons.settings_outlined, color: Color(0xFF2C2C2C), size: 24),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationsTitle(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: Colors.black, width: 2),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.5), blurRadius: 4, offset: const Offset(0, 4))],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.notifications_active_outlined, size: 32, color: _titleTextColor), 
            const SizedBox(width: 10),
            Text(
              'NOTIFICATIONS',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: _titleTextColor,
                fontSize: _getResponsiveFontSize(context, 32),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                shadows: [Shadow(offset: const Offset(0, 4), blurRadius: 4, color: Colors.black.withOpacity(0.25))],
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  Widget _AlertButton({
    required BuildContext context,
    required String title,
    required Color color,
    required IconData icon,
  }) {
    return InkWell(
      onTap: () => _showComingSoonDialog(context, title),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 10),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: color.withOpacity(0.5), width: 1),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.3), blurRadius: 6, offset: const Offset(0, 4))],
        ),
        child: Row(
          children: [
            Icon(icon, size: 36, color: color),
            const SizedBox(width: 20),
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  color: color,
                  fontSize: _getResponsiveFontSize(context, 30),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w800,
                  shadows: [Shadow(offset: const Offset(0, 3), blurRadius: 4, color: Colors.black.withOpacity(0.25))],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(context),
            const SizedBox(height: 10),
            _buildNotificationsTitle(context),
            const SizedBox(height: 30),

            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: _bronzeBgColor,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(30),
                    topRight: Radius.circular(30),
                  ),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.3), blurRadius: 8, offset: const Offset(0, -5))],
                ),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.only(top: 30, left: 10, right: 10, bottom: 40),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _AlertButton(context: context, title: 'Missed Medications', color: _missedMedColor, icon: Icons.error_outline),
                      const SizedBox(height: 25),
                      _AlertButton(context: context, title: 'Checklist', color: _checklistColor, icon: Icons.check_box_outlined),
                      const SizedBox(height: 25),
                      _AlertButton(context: context, title: 'Upcomings', color: _upcomingsColor, icon: Icons.calendar_today_outlined),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SilverCareNavBar(
        currentIndex: _currentIndex,
        onTap: _handleTabTap,
      ),
    );
  }
}

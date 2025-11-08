import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../widgets/nav_bar_svg.dart';
import 'home_screen.dart';
import 'profile_screen.dart';
import 'analytics_screen.dart';
import 'calendar_screen.dart'; 

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _currentIndex = 0;

  // This is the main navigation container that holds all app screens
  final List<Widget> _screens = [
    const HomeScreenContent(), // Home
    const CalendarScreen(),    
    const AnalyticsScreenContent(), // Analytics
    const PlaceholderScreen(title: 'Wellness', color: Color(0xFF9C27B0)), // Wellness
    const ProfileScreenContent(), // Profile
  ];

  void _onNavTap(int index) {
    // Add haptic feedback
    HapticFeedback.lightImpact();
    
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: SilverCareNavBar(
        currentIndex: _currentIndex,
        onTap: _onNavTap,
      ),
    );
  }
}

class PlaceholderScreen extends StatelessWidget {
  final String title;
  final Color color;

  const PlaceholderScreen({
    super.key,
    required this.title,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // SilverCare Header
            Text(
              'SILVERCARE',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.black,
                fontSize: 24,
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                shadows: [
                  Shadow(
                    offset: const Offset(0, 2),
                    blurRadius: 4,
                    color: Colors.black.withValues(alpha: 0.50),
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 60),
            
            // Icon representing the screen
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: color.withOpacity(0.15),
                shape: BoxShape.circle,
                border: Border.all(
                  color: color.withOpacity(0.3),
                  width: 3,
                ),
              ),
              child: Icon(
                _getIconForTitle(title),
                size: 60,
                color: color,
              ),
            ),
            
            const SizedBox(height: 40),
            
            // Title
            Text(
              title,
              style: TextStyle(
                color: Colors.black,
                fontSize: 32,
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w700,
              ),
            ),
            
            const SizedBox(height: 20),
            
            // Coming soon message
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: color.withOpacity(0.3)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.1),
                    blurRadius: 8,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Text(
                    'Coming Soon! 🚀',
                    style: TextStyle(
                      color: color,
                      fontSize: 20,
                      fontFamily: 'Montserrat',
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  
                  const SizedBox(height: 8),
                  
                  Text(
                    'This feature is currently under development.\nStay tuned for updates!',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Color(0xFF666666),
                      fontSize: 16,
                      fontFamily: 'Montserrat',
                      fontWeight: FontWeight.w400,
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

  IconData _getIconForTitle(String title) {
    switch (title) {
      case 'Calendar':
        return Icons.calendar_today_rounded;
      case 'Health':
        return Icons.favorite_rounded;
      case 'Wellness':
        return Icons.person_rounded;
      default:
        return Icons.help_outline;
    }
  }
}

class HomeScreenContent extends StatelessWidget {
  const HomeScreenContent({super.key});

  @override
  Widget build(BuildContext context) {
    return const HomeScreen();
  }
}

class ProfileScreenContent extends StatelessWidget {
  const ProfileScreenContent({super.key});

  @override
  Widget build(BuildContext context) {
    return const ProfileScreen();
  }
}

class AnalyticsScreenContent extends StatelessWidget {
  const AnalyticsScreenContent({super.key});

  @override
  Widget build(BuildContext context) {
    return const AnalyticsScreen();
  }
}
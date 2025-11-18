import 'package:flutter/material.dart';
// IMPORT THE NEW FILE
import 'memory_match_screen.dart';

class WellnessScreen extends StatefulWidget {
  const WellnessScreen({super.key});

  @override
  State<WellnessScreen> createState() => _WellnessScreenState();
}

class _WellnessScreenState extends State<WellnessScreen> {
  static const Color _primaryColor = Color(0xFF9C27B0);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _cardColor = Colors.white;
  static const Color _textPrimary = Color(0xFF2D3748);

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  // --- Header Widgets ---

  Widget _buildHeader(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 20),
      child: Center(
        child: Text(
          'SILVER CARE',
          style: TextStyle(
            color: _textPrimary,
            fontSize: _getResponsiveFontSize(context, 28),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w900,
            letterSpacing: 2.0,
          ),
        ),
      ),
    );
  }

  Widget _ScreenHeaderButton(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 10, 20, 20),
      height: 80,
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(40),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.1),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
        border: Border.all(color: _primaryColor.withOpacity(0.3), width: 2),
      ),
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.spa_rounded, size: 32, color: _primaryColor),
            const SizedBox(width: 12),
            Text(
              'WELLNESS',
              style: TextStyle(
                color: _textPrimary,
                fontSize: _getResponsiveFontSize(context, 24),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                letterSpacing: 1.2,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- Navigation Helper ---

  void _navigateToPlaceholder(BuildContext context, String title, Color themeColor) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => Scaffold(
          backgroundColor: _backgroundColor,
          appBar: AppBar(
            title: Text(title, style: const TextStyle(fontFamily: 'Montserrat', fontWeight: FontWeight.bold)),
            backgroundColor: themeColor,
            foregroundColor: Colors.white,
            elevation: 0,
            centerTitle: true,
          ),
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(30),
                  decoration: BoxDecoration(
                    color: themeColor.withOpacity(0.15),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.construction_rounded, size: 60, color: themeColor),
                ),
                const SizedBox(height: 30),
                Text(
                  'Feature In Development',
                  style: TextStyle(
                    fontSize: 22, 
                    fontWeight: FontWeight.bold, 
                    fontFamily: 'Montserrat',
                    color: _textPrimary,
                  ),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Check back soon for updates!',
                  style: TextStyle(fontSize: 16, color: Colors.grey, fontFamily: 'Inter'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // --- Modern Button Widget ---

  Widget _buildModernMenuButton({
    required BuildContext context,
    required String title,
    required IconData icon,
    required List<Color> gradientColors,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      height: 110, 
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(
            color: gradientColors.last.withOpacity(0.4),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: gradientColors,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(30),
          child: Stack(
            children: [
              Positioned(
                right: -20,
                top: -20,
                child: Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                ),
              ),
              
              Center(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 25),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.25),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white.withOpacity(0.4), width: 1),
                        ),
                        child: Icon(icon, size: 32, color: Colors.white),
                      ),
                      const SizedBox(width: 20),
                      
                      Expanded(
                        child: Text(
                          title,
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: _getResponsiveFontSize(context, 18),
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.5,
                            shadows: [
                              Shadow(
                                color: Colors.black.withOpacity(0.1),
                                offset: const Offset(0, 1),
                                blurRadius: 2,
                              )
                            ],
                          ),
                        ),
                      ),
                      
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 20),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _backgroundColor,
      body: SafeArea(
        child: Column(
          children: [
            _buildHeader(context),
            _ScreenHeaderButton(context),
            
            Expanded(
              child: Container(
                width: double.infinity,
                margin: const EdgeInsets.symmetric(horizontal: 20),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(35)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black12,
                      blurRadius: 20,
                      offset: Offset(0, -5),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(35)),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(25, 35, 25, 25),
                    child: Column(
                      children: [
                        _buildModernMenuButton(
                          context: context,
                          title: 'Words of the Day',
                          icon: Icons.menu_book_rounded,
                          gradientColors: [
                            const Color(0xFFFFD54F),
                            const Color(0xFF7B1FA2),
                          ],
                          onTap: () => _navigateToPlaceholder(context, 'Words of the Day', const Color(0xFF7B1FA2)),
                        ),

                        _buildModernMenuButton(
                          context: context,
                          title: 'Breathing Exercise',
                          icon: Icons.air_rounded,
                          gradientColors: [
                            const Color(0xFF4DB6AC),
                            const Color(0xFF1565C0),
                          ],
                          onTap: () => _navigateToPlaceholder(context, 'Breathing Exercise', const Color(0xFF1565C0)),
                        ),
                        
                        _buildModernMenuButton(
                          context: context,
                          title: 'Morning Stretch Guide',
                          icon: Icons.accessibility_new_rounded,
                          gradientColors: [
                            const Color(0xFFFF8A65),
                            const Color(0xFFC62828),
                          ],
                          onTap: () => _navigateToPlaceholder(context, 'Morning Stretch', const Color(0xFFC62828)),
                        ),
                        
                        // NEW: Memory Match Button
                        _buildModernMenuButton(
                          context: context,
                          title: 'Memory Match', // Changed from Trivia
                          icon: Icons.psychology_rounded, // Changed Icon to represent mind/brain
                          gradientColors: [
                            const Color(0xFF4FC3F7), // Light Blue
                            const Color(0xFF283593), // Indigo
                          ],
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(builder: (context) => const MemoryMatchScreen()),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
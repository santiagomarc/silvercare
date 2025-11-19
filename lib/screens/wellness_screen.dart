import 'package:flutter/material.dart';
// IMPORT THE NEW FILE
import 'memory_match_screen.dart';
import 'breathing_exercise_screen.dart';
import 'word_of_the_day_screen.dart';
import 'morning_stretch_screen.dart';

class WellnessScreen extends StatefulWidget {
  const WellnessScreen({super.key});

  @override
  State<WellnessScreen> createState() => _WellnessScreenState();
}

class _WellnessScreenState extends State<WellnessScreen> {
  static const Color _primaryColor = Color(0xFF7B1FA2);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _textPrimary = Color(0xFF2D3748);

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  // --- Header Widgets ---

  Widget _buildHeader(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 20),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [_primaryColor, _primaryColor.withOpacity(0.7)],
              ),
              borderRadius: BorderRadius.circular(10),
              boxShadow: [
                BoxShadow(
                  color: _primaryColor.withOpacity(0.3),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: const Icon(Icons.favorite_rounded, color: Colors.white, size: 16),
          ),
          const SizedBox(width: 8),
          ShaderMask(
            shaderCallback: (bounds) => LinearGradient(
              colors: [_primaryColor, const Color(0xFF9C27B0)],
            ).createShader(bounds),
            child: Text(
              'SILVER CARE',
              style: TextStyle(
                color: Colors.white,
                fontSize: _getResponsiveFontSize(context, 22),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w900,
                letterSpacing: 2.0,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _ScreenHeaderButton(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 0, 20, 10),
      height: 70,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(
            color: _primaryColor.withOpacity(0.25),
            blurRadius: 25,
            offset: const Offset(0, 10),
            spreadRadius: 0,
          ),
          BoxShadow(
            color: Colors.white.withOpacity(0.9),
            blurRadius: 10,
            offset: const Offset(0, -4),
            spreadRadius: 0,
          ),
        ],
      ),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(30),
          color: Colors.white,
          border: Border.all(
            width: 2,
            color: _primaryColor.withOpacity(0.2),
          ),
        ),
        child: Stack(
          children: [
            // Decorative elements
            Positioned(
              left: -20,
              top: -10,
              child: Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      _primaryColor.withOpacity(0.03),
                      _primaryColor.withOpacity(0.0),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              right: -30,
              bottom: -20,
              child: Container(
                width: 100,
                height: 100,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      const Color(0xFFFFC107).withOpacity(0.06),
                      const Color(0xFFFFC107).withOpacity(0.0),
                    ],
                  ),
                ),
              ),
            ),
            
            // Main content
            Center(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Icon with enhanced styling
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [
                          _primaryColor,
                          const Color(0xFF9C27B0),
                        ],
                      ),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: _primaryColor.withOpacity(0.4),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                          spreadRadius: 1,
                        ),
                        BoxShadow(
                          color: Colors.white.withOpacity(0.8),
                          blurRadius: 6,
                          offset: const Offset(0, -2),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.spa_rounded, size: 24, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  
                  // Title with gradient
                  ShaderMask(
                    shaderCallback: (bounds) => LinearGradient(
                      colors: [
                        _textPrimary,
                        _primaryColor,
                      ],
                    ).createShader(bounds),
                    child: Text(
                      'WELLNESS',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: _getResponsiveFontSize(context, 22),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w900,
                        letterSpacing: 1.5,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  
                  // Sparkle badge
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFFFFC107), Color(0xFFFF9800)],
                      ),
                      borderRadius: BorderRadius.circular(14),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFFFFC107).withOpacity(0.4),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.auto_awesome_rounded, 
                      size: 14, 
                      color: Colors.white,
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



  // --- Modern Button Widget ---

  Widget _buildModernMenuButton({
    required BuildContext context,
    required String title,
    required IconData icon,
    required List<Color> gradientColors,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      height: 95, 
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: gradientColors.last.withOpacity(0.35),
            blurRadius: 15,
            offset: const Offset(0, 8),
            spreadRadius: 1,
          ),
          BoxShadow(
            color: gradientColors.first.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(28),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(28),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: gradientColors,
              stops: gradientColors.length == 3 
                ? [0.0, 0.5, 1.0]
                : [0.0, 1.0],
            ),
          ),
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(28),
            splashColor: Colors.white.withOpacity(0.3),
            highlightColor: Colors.white.withOpacity(0.1),
            child: Stack(
              children: [
                // Decorative circles
                Positioned(
                  right: -30,
                  top: -30,
                  child: Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.08),
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
                Positioned(
                  right: 20,
                  bottom: -20,
                  child: Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.05),
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
                
                Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        // Icon container with glow effect
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.25),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white.withOpacity(0.5), width: 2),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.white.withOpacity(0.3),
                                blurRadius: 10,
                                spreadRadius: 2,
                              ),
                            ],
                          ),
                          child: Icon(icon, size: 34, color: Colors.white),
                        ),
                        const SizedBox(width: 18),
                        
                        Expanded(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                title,
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: _getResponsiveFontSize(context, 18),
                                  fontFamily: 'Montserrat',
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.5,
                                  shadows: [
                                    Shadow(
                                      color: Colors.black.withOpacity(0.2),
                                      offset: const Offset(0, 2),
                                      blurRadius: 4,
                                    )
                                  ],
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Tap to explore',
                                style: TextStyle(
                                  color: Colors.white.withOpacity(0.85),
                                  fontSize: _getResponsiveFontSize(context, 12),
                                  fontFamily: 'Inter',
                                  fontWeight: FontWeight.w500,
                                  letterSpacing: 0.3,
                                ),
                              ),
                            ],
                          ),
                        ),
                        
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.25),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white.withOpacity(0.3), width: 1),
                          ),
                          child: const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 22),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              const Color(0xFFF3E5F5),
              const Color(0xFFFFF8E1),
              _backgroundColor,
            ],
            stops: const [0.0, 0.5, 1.0],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              _buildHeader(context),
              _ScreenHeaderButton(context),
              
              const SizedBox(height: 5),
              
              Expanded(
                child: Container(
                  width: double.infinity,
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.95),
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(40)),
                    boxShadow: [
                      BoxShadow(
                        color: _primaryColor.withOpacity(0.12),
                        blurRadius: 25,
                        offset: const Offset(0, -8),
                        spreadRadius: 2,
                      ),
                    ],
                  ),
                  child: ClipRRect(
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(40)),
                    child: Column(
                      children: [
                        // Decorative top bar
                        Container(
                          height: 5,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                const Color(0xFFFFC107),
                                _primaryColor,
                                const Color(0xFF4DB6AC),
                              ],
                            ),
                          ),
                        ),
                        
                        Expanded(
                          child: SingleChildScrollView(
                            physics: const BouncingScrollPhysics(),
                            padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Section Header
                                Padding(
                                  padding: const EdgeInsets.only(left: 8, bottom: 15),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(10),
                                        decoration: BoxDecoration(
                                          gradient: LinearGradient(
                                            colors: [_primaryColor.withOpacity(0.8), _primaryColor],
                                          ),
                                          borderRadius: BorderRadius.circular(12),
                                          boxShadow: [
                                            BoxShadow(
                                              color: _primaryColor.withOpacity(0.3),
                                              blurRadius: 8,
                                              offset: const Offset(0, 3),
                                            ),
                                          ],
                                        ),
                                        child: const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 20),
                                      ),
                                      const SizedBox(width: 12),
                                      Text(
                                        'Wellness Activities',
                                        style: TextStyle(
                                          fontSize: 18,
                                          fontFamily: 'Montserrat',
                                          fontWeight: FontWeight.w800,
                                          color: _textPrimary,
                                          letterSpacing: 0.5,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                
                                _buildModernMenuButton(
                                  context: context,
                                  title: 'Words of the Day',
                                  icon: Icons.menu_book_rounded,
                                  gradientColors: [
                                    const Color(0xFFE1BEE7),
                                    const Color(0xFFBA68C8),
                                    const Color(0xFF7B1FA2),
                                  ],
                                  onTap: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(builder: (context) => const WordOfTheDayScreen()),
                                    );
                                  },
                                ),

                                _buildModernMenuButton(
                                  context: context,
                                  title: 'Breathing Exercise',
                                  icon: Icons.air_rounded,
                                  gradientColors: [
                                    const Color(0xFF80DEEA),
                                    const Color(0xFF4DB6AC),
                                    const Color(0xFF26A69A),
                                  ],
                                  onTap: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(builder: (context) => const BreathingExerciseScreen()),
                                    );
                                  }
                                ),
                                
                        _buildModernMenuButton(
                          context: context,
                          title: 'Morning Stretch Guide',
                          icon: Icons.accessibility_new_rounded,
                          gradientColors: [
                            const Color(0xFFFFAB91),
                            const Color(0xFFFF8A65),
                            const Color(0xFFC62828),
                          ],
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(builder: (context) => const MorningStretchScreen()),
                            );
                          },
                        ),                                // NEW: Memory Match Button
                                _buildModernMenuButton(
                                  context: context,
                                  title: 'Memory Match',
                                  icon: Icons.psychology_rounded,
                                  gradientColors: [
                                    const Color(0xFF81D4FA),
                                    const Color(0xFF4FC3F7),
                                    const Color(0xFF29B6F6),
                                  ],
                                  onTap: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(builder: (context) => const MemoryMatchScreen()),
                                    );
                                  },
                                ),
                                
                                const SizedBox(height: 10),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
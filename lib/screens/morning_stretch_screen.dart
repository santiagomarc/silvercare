import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class MorningStretchScreen extends StatefulWidget {
  const MorningStretchScreen({super.key});

  @override
  State<MorningStretchScreen> createState() => _MorningStretchScreenState();
}

class _MorningStretchScreenState extends State<MorningStretchScreen> {
  static const Color _primaryColor = Color(0xFFC62828);
  static const Color _accentColor = Color(0xFFFF8A65);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _textPrimary = Color(0xFF2D3748);

  int _currentStretchIndex = 0;
  final PageController _pageController = PageController();

  // Stretching exercises curated for elderly users
  final List<Map<String, dynamic>> _stretchExercises = [
    {
      'title': 'Neck Rolls',
      'duration': '2 minutes',
      'difficulty': 'Easy',
      'icon': Icons.face_rounded,
      'benefits': ['Relieves neck tension', 'Improves flexibility', 'Reduces stiffness'],
      'steps': [
        'Sit or stand comfortably with your back straight',
        'Slowly tilt your head to the right, bringing your ear towards your shoulder',
        'Hold for 5 seconds',
        'Return to center and repeat on the left side',
        'Gently roll your head in a circular motion (5 times each direction)',
        'Breathe deeply and relax'
      ],
      'caution': 'Move slowly and stop if you feel pain or dizziness',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Shoulder Rolls',
      'duration': '2 minutes',
      'difficulty': 'Easy',
      'icon': Icons.accessibility_new_rounded,
      'benefits': ['Releases shoulder tension', 'Improves posture', 'Increases mobility'],
      'steps': [
        'Sit or stand with your arms relaxed at your sides',
        'Lift both shoulders up towards your ears',
        'Roll them back and down in a circular motion',
        'Repeat 10 times backwards',
        'Then roll forward 10 times',
        'Shake out your arms gently'
      ],
      'caution': 'Keep movements slow and controlled',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Seated Spinal Twist',
      'duration': '3 minutes',
      'difficulty': 'Easy',
      'icon': Icons.self_improvement_rounded,
      'benefits': ['Improves spine flexibility', 'Aids digestion', 'Reduces back pain'],
      'steps': [
        'Sit comfortably in a chair with feet flat on the floor',
        'Place your right hand on the back of the chair',
        'Place your left hand on your right knee',
        'Gently twist your torso to the right',
        'Hold for 15-20 seconds while breathing deeply',
        'Return to center and repeat on the other side'
      ],
      'caution': 'Don\'t force the twist - go only as far as comfortable',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Arm Raises',
      'duration': '2 minutes',
      'difficulty': 'Easy',
      'icon': Icons.pan_tool_rounded,
      'benefits': ['Strengthens shoulders', 'Improves range of motion', 'Boosts circulation'],
      'steps': [
        'Stand or sit with your back straight',
        'Let your arms hang naturally at your sides',
        'Slowly raise both arms forward to shoulder height',
        'Hold for 2 seconds',
        'Slowly lower them back down',
        'Repeat 10 times, rest, then do another set'
      ],
      'caution': 'If standing feels unstable, do this sitting down',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Ankle Circles',
      'duration': '2 minutes',
      'difficulty': 'Very Easy',
      'icon': Icons.directions_walk_rounded,
      'benefits': ['Improves ankle mobility', 'Prevents stiffness', 'Reduces swelling'],
      'steps': [
        'Sit in a chair with good back support',
        'Lift your right foot slightly off the ground',
        'Rotate your ankle clockwise 10 times',
        'Then rotate counter-clockwise 10 times',
        'Point and flex your toes 10 times',
        'Repeat with the left foot'
      ],
      'caution': 'Hold onto the chair if needed for balance',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Gentle Knee Lifts',
      'duration': '3 minutes',
      'difficulty': 'Easy',
      'icon': Icons.airline_seat_legroom_normal_rounded,
      'benefits': ['Strengthens legs', 'Improves balance', 'Increases hip flexibility'],
      'steps': [
        'Sit in a sturdy chair with feet flat on floor',
        'Hold onto the sides of the chair for support',
        'Slowly lift your right knee up towards your chest',
        'Hold for 3 seconds',
        'Slowly lower it back down',
        'Repeat 10 times on each leg'
      ],
      'caution': 'Keep your back straight and move slowly',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Wrist and Finger Stretches',
      'duration': '2 minutes',
      'difficulty': 'Very Easy',
      'icon': Icons.back_hand_rounded,
      'benefits': ['Reduces arthritis pain', 'Improves dexterity', 'Prevents stiffness'],
      'steps': [
        'Extend your right arm forward, palm down',
        'Gently pull fingers back with your left hand',
        'Hold for 10 seconds',
        'Then bend fingers down and hold for 10 seconds',
        'Make a fist and release 10 times',
        'Repeat with the left hand'
      ],
      'caution': 'Stretch gently - no forcing',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
    {
      'title': 'Seated March',
      'duration': '3 minutes',
      'difficulty': 'Easy',
      'icon': Icons.transfer_within_a_station_rounded,
      'benefits': ['Boosts circulation', 'Warms up the body', 'Improves coordination'],
      'steps': [
        'Sit up straight in your chair',
        'Lift your right knee up as if marching',
        'Lower it back down',
        'Lift your left knee up',
        'Continue alternating for 1-2 minutes',
        'Swing your arms naturally as you march'
      ],
      'caution': 'Start slowly and increase pace gradually',
      'videoUrl': 'https://www.youtube.com/watch?v=SedzswEwpPw',
    },
  ];

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _showCompletionDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(30),
          ),
          child: Container(
            padding: const EdgeInsets.all(30),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(30),
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  Colors.white,
                  _accentColor.withOpacity(0.1),
                ],
              ),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Success Icon
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [_accentColor, _primaryColor],
                    ),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: _primaryColor.withOpacity(0.3),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.celebration_rounded,
                    color: Colors.white,
                    size: 60,
                  ),
                ),
                
                const SizedBox(height: 25),
                
                // Congratulations Text
                const Text(
                  'Congratulations! 🎉',
                  style: TextStyle(
                    fontSize: 28,
                    fontFamily: 'Montserrat',
                    fontWeight: FontWeight.w900,
                    color: Color(0xFF2D3748),
                  ),
                  textAlign: TextAlign.center,
                ),
                
                const SizedBox(height: 15),
                
                // Success Message
                Text(
                  'You\'ve completed all 8 stretching exercises!',
                  style: TextStyle(
                    fontSize: 16,
                    fontFamily: 'Inter',
                    color: _textPrimary.withOpacity(0.7),
                    height: 1.5,
                  ),
                  textAlign: TextAlign.center,
                ),
                
                const SizedBox(height: 10),
                
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: _primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.fitness_center_rounded, color: _primaryColor, size: 24),
                      const SizedBox(width: 10),
                      Text(
                        'Great work! Keep it up!',
                        style: TextStyle(
                          fontSize: 15,
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w700,
                          color: _primaryColor,
                        ),
                      ),
                    ],
                  ),
                ),
                
                const SizedBox(height: 25),
                
                // Reminder
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.blue[50],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.blue, width: 1),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.schedule_rounded, color: Colors.blue, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'See you tomorrow for your next session!',
                          style: TextStyle(
                            fontSize: 13,
                            fontFamily: 'Inter',
                            fontWeight: FontWeight.w600,
                            color: Colors.blue[900],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                
                const SizedBox(height: 25),
                
                // Done Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pop(); // Close dialog
                      Navigator.of(context).pop(); // Go back to wellness screen
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _primaryColor,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                      elevation: 5,
                      shadowColor: _primaryColor.withOpacity(0.5),
                    ),
                    child: const Text(
                      'Done',
                      style: TextStyle(
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w700,
                        fontSize: 16,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<void> _openVideo(String url) async {
    try {
      final uri = Uri.parse(url);
      
      // Try to launch the URL
      final canLaunch = await canLaunchUrl(uri);
      
      if (canLaunch) {
        await launchUrl(
          uri,
          mode: LaunchMode.externalApplication,
        );
      } else {
        // Show error message
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: const Text(
                'Cannot open video. Please check if YouTube is installed.',
                style: TextStyle(fontFamily: 'Inter'),
              ),
              backgroundColor: Colors.red[700],
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              duration: const Duration(seconds: 3),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Error: $e',
              style: const TextStyle(fontFamily: 'Inter'),
            ),
            backgroundColor: Colors.red[700],
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            duration: const Duration(seconds: 3),
          ),
        );
      }
    }
  }

  void _nextStretch() {
    if (_currentStretchIndex < _stretchExercises.length - 1) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
    }
  }

  void _previousStretch() {
    if (_currentStretchIndex > 0) {
      _pageController.previousPage(
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
    }
  }

  Color _getDifficultyColor(String difficulty) {
    switch (difficulty.toLowerCase()) {
      case 'very easy':
        return Colors.green;
      case 'easy':
        return Colors.lightGreen;
      case 'moderate':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;

    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              _accentColor.withOpacity(0.2),
              _primaryColor.withOpacity(0.15),
              _backgroundColor,
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              // Custom App Bar
              Container(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
                      style: IconButton.styleFrom(
                        backgroundColor: _primaryColor,
                        padding: const EdgeInsets.all(12),
                      ),
                      onPressed: () => Navigator.pop(context),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'MORNING STRETCH',
                            style: TextStyle(
                              color: _textPrimary,
                              fontSize: screenWidth < 360 ? 18 : 20,
                              fontFamily: 'Montserrat',
                              fontWeight: FontWeight.w900,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Gentle exercises for seniors',
                            style: TextStyle(
                              color: _textPrimary.withOpacity(0.6),
                              fontSize: 13,
                              fontFamily: 'Inter',
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [_accentColor, _primaryColor],
                        ),
                        borderRadius: BorderRadius.circular(15),
                        boxShadow: [
                          BoxShadow(
                            color: _primaryColor.withOpacity(0.3),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: const Icon(Icons.accessibility_new_rounded, color: Colors.white, size: 24),
                    ),
                  ],
                ),
              ),

              // Progress Indicator
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 8),
                child: Row(
                  children: [
                    Text(
                      'Exercise ${_currentStretchIndex + 1} of ${_stretchExercises.length}',
                      style: TextStyle(
                        color: _textPrimary.withOpacity(0.7),
                        fontSize: 14,
                        fontFamily: 'Inter',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const Spacer(),
                    Text(
                      '${(((_currentStretchIndex + 1) / _stretchExercises.length) * 100).toInt()}%',
                      style: TextStyle(
                        color: _primaryColor,
                        fontSize: 14,
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24.0),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: LinearProgressIndicator(
                    value: (_currentStretchIndex + 1) / _stretchExercises.length,
                    backgroundColor: Colors.grey[200],
                    valueColor: AlwaysStoppedAnimation<Color>(_primaryColor),
                    minHeight: 8,
                  ),
                ),
              ),

              const SizedBox(height: 20),

              // Main Content - PageView
              Expanded(
                child: PageView.builder(
                  controller: _pageController,
                  onPageChanged: (index) {
                    setState(() {
                      _currentStretchIndex = index;
                    });
                  },
                  itemCount: _stretchExercises.length,
                  itemBuilder: (context, index) {
                    return _buildStretchCard(_stretchExercises[index]);
                  },
                ),
              ),

              // Video Demo Button
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 10),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () => _openVideo(_stretchExercises[_currentStretchIndex]['videoUrl']),
                    icon: const Icon(Icons.play_circle_filled_rounded, size: 26),
                    label: const Text(
                      'Watch Video Demo',
                      style: TextStyle(
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                        letterSpacing: 0.5,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red[600],
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                      elevation: 8,
                      shadowColor: Colors.red.withOpacity(0.5),
                    ),
                  ),
                ),
              ),

              // Navigation Buttons
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 20),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    // Previous Button
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8.0),
                        child: _buildLargeNavButton(
                          icon: Icons.arrow_back_rounded,
                          label: 'Previous',
                          onPressed: _currentStretchIndex > 0 ? _previousStretch : null,
                        ),
                      ),
                    ),

                    // Next Button or Finish
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(left: 8.0),
                        child: _buildLargeNavButton(
                          icon: _currentStretchIndex < _stretchExercises.length - 1
                              ? Icons.arrow_forward_rounded
                              : Icons.check_circle_rounded,
                          label: _currentStretchIndex < _stretchExercises.length - 1 ? 'Next' : 'Finish',
                          onPressed: () {
                            if (_currentStretchIndex < _stretchExercises.length - 1) {
                              _nextStretch();
                            } else {
                              _showCompletionDialog();
                            }
                          },
                          isPrimary: true,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStretchCard(Map<String, dynamic> stretch) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20),
      child: Card(
        elevation: 10,
        shadowColor: _primaryColor.withOpacity(0.3),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(30),
        ),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(30),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Colors.white,
                _accentColor.withOpacity(0.03),
              ],
            ),
          ),
          child: SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.all(28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Icon and Title
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [_accentColor, _primaryColor],
                        ),
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: _primaryColor.withOpacity(0.3),
                            blurRadius: 10,
                            offset: const Offset(0, 5),
                          ),
                        ],
                      ),
                      child: Icon(stretch['icon'], color: Colors.white, size: 32),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            stretch['title'],
                            style: TextStyle(
                              fontSize: 22,
                              fontFamily: 'Montserrat',
                              fontWeight: FontWeight.w800,
                              color: _textPrimary,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Icon(Icons.timer_rounded, size: 16, color: _primaryColor),
                              const SizedBox(width: 4),
                              Text(
                                stretch['duration'],
                                style: TextStyle(
                                  fontSize: 14,
                                  fontFamily: 'Inter',
                                  fontWeight: FontWeight.w600,
                                  color: _textPrimary.withOpacity(0.7),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: _getDifficultyColor(stretch['difficulty']),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        stretch['difficulty'],
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontFamily: 'Inter',
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // Benefits
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        _primaryColor.withOpacity(0.08),
                        _accentColor.withOpacity(0.08),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.stars_rounded, color: _primaryColor, size: 20),
                          const SizedBox(width: 8),
                          Text(
                            'Benefits',
                            style: TextStyle(
                              fontSize: 16,
                              fontFamily: 'Montserrat',
                              fontWeight: FontWeight.w700,
                              color: _primaryColor,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      ...List.generate(
                        stretch['benefits'].length,
                        (index) => Padding(
                          padding: const EdgeInsets.only(bottom: 6),
                          child: Row(
                            children: [
                              Icon(Icons.check_circle, color: _primaryColor, size: 18),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  stretch['benefits'][index],
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontFamily: 'Inter',
                                    color: _textPrimary.withOpacity(0.8),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Steps
                Text(
                  'Step-by-Step Instructions',
                  style: TextStyle(
                    fontSize: 18,
                    fontFamily: 'Montserrat',
                    fontWeight: FontWeight.w800,
                    color: _textPrimary,
                  ),
                ),
                const SizedBox(height: 16),

                ...List.generate(
                  stretch['steps'].length,
                  (index) => Padding(
                    padding: const EdgeInsets.only(bottom: 16),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [_accentColor, _primaryColor],
                            ),
                            shape: BoxShape.circle,
                          ),
                          child: Center(
                            child: Text(
                              '${index + 1}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontFamily: 'Montserrat',
                                fontWeight: FontWeight.w700,
                                fontSize: 14,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(
                              stretch['steps'][index],
                              style: TextStyle(
                                fontSize: 15,
                                fontFamily: 'Inter',
                                color: _textPrimary,
                                height: 1.5,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                const SizedBox(height: 16),

                // Caution
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.amber[50],
                    borderRadius: BorderRadius.circular(15),
                    border: Border.all(color: Colors.amber, width: 2),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 24),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          stretch['caution'],
                          style: TextStyle(
                            fontSize: 13,
                            fontFamily: 'Inter',
                            fontWeight: FontWeight.w600,
                            color: Colors.orange[900],
                            height: 1.4,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLargeNavButton({
    required IconData icon,
    required String label,
    required VoidCallback? onPressed,
    bool isPrimary = false,
  }) {
    final isEnabled = onPressed != null;
    
    return ElevatedButton(
      onPressed: onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: isEnabled 
            ? (isPrimary ? _primaryColor : Colors.white)
            : Colors.grey[300],
        foregroundColor: isEnabled 
            ? (isPrimary ? Colors.white : _primaryColor)
            : Colors.grey,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        elevation: isEnabled ? 5 : 0,
        shadowColor: isPrimary ? _primaryColor.withOpacity(0.5) : Colors.grey.withOpacity(0.3),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (label == 'Previous') ...[
            Icon(icon, size: 22),
            const SizedBox(width: 8),
          ],
          Text(
            label,
            style: TextStyle(
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w700,
              fontSize: 16,
            ),
          ),
          if (label != 'Previous') ...[
            const SizedBox(width: 8),
            Icon(icon, size: 22),
          ],
        ],
      ),
    );
  }
}

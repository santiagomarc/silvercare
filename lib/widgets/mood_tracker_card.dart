import 'dart:async';
import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import '../services/mood_service.dart';
import '../models/elderly_model.dart';

class MoodTrackerCard extends StatefulWidget {
  const MoodTrackerCard({super.key});

  @override
  State<MoodTrackerCard> createState() => _MoodTrackerCardState();
}

class _MoodTrackerCardState extends State<MoodTrackerCard> {
  double _sliderValue = 2; // Default: neutral (0-4 scale)
  bool _isLoading = true;
  String _username = 'User';
  Timer? _debounceTimer;
  
  // Mood data with colors matching your design
  final List<String> _emojis = ['😢', '☹️', '😐', '🙂', '😄'];
  final List<String> _moods = ['Very Sad', 'Sad', 'Neutral', 'Happy', 'Very Happy'];
  final List<Color> _colors = [
    const Color(0xFFFF5252), // Red - Very Sad
    const Color(0xFFFF9800), // Orange - Sad  
    const Color(0xFFFFC107), // Yellow - Neutral
    const Color(0xFF8BC34A), // Yellow Green - Happy
    const Color(0xFF4CAF50), // Green - Very Happy
  ];

  @override
  void initState() {
    super.initState();
    _loadUserData();
    _loadTodayMood();
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  Future<void> _loadUserData() async {
    try {
      final user = FirebaseAuth.instance.currentUser;
      if (user != null) {
        print('Loading user data for UID: ${user.uid}');
        
        // Get username from elderly profile - try both collection names
        DocumentSnapshot? elderDoc;
        
        // Try 'elders' collection first
        elderDoc = await FirebaseFirestore.instance
            .collection('elders')
            .doc(user.uid)
            .get();
        
        if (!elderDoc.exists) {
          elderDoc = await FirebaseFirestore.instance
              .collection('elderly')
              .doc(user.uid)
              .get();
        }
        
        if (elderDoc.exists) {
          print('Elder document found with data: ${elderDoc.data()}');
          final elderData = ElderlyModel.fromDoc(elderDoc);
          print('Extracted username: ${elderData.username}');
          
          setState(() {
            _username = elderData.username.isNotEmpty ? elderData.username : 'User';
          });
        } else {
          print('No elder document found for user');
          setState(() {
            _username = 'User';
          });
        }
      }
    } catch (e) {
      print('Error loading user data: $e');
      setState(() {
        _username = 'User';
      });
    }
  }

  Future<void> _loadTodayMood() async {
    try {
      final todayMood = await MoodService.getTodayMood();
      if (todayMood != null) {
        setState(() {
          _sliderValue = todayMood.moodLevel.toDouble();
        });
      }
    } catch (e) {
      print('Error loading today\'s mood: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _debouncedSave(double value) {
    // Cancel the previous timer if it exists
    _debounceTimer?.cancel();
    
    // Start a new timer
    _debounceTimer = Timer(const Duration(milliseconds: 800), () {
      _saveMoodAutomatically(value);
    });
  }

  Future<void> _saveMoodAutomatically(double value) async {
    final moodLevel = value.round();
    final success = await MoodService.saveMoodData(
      mood: _moods[moodLevel],
      emoji: _emojis[moodLevel],
      moodLevel: moodLevel,
    );

    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${_emojis[moodLevel]} Today\'s emotion saved!'),
          duration: const Duration(milliseconds: 1500),
          backgroundColor: _colors[moodLevel].withOpacity(0.9),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    }
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: const Color.fromARGB(255, 187, 187, 187), // Dark gray background
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFF4A4A4A), width: 2), // Gray outline
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.2),
              blurRadius: 8,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: const Center(
          child: CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
          ),
        ),
      );
    }

    final currentMoodIndex = _sliderValue.round();
    final currentColor = _colors[currentMoodIndex];

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: const Color.fromARGB(96, 204, 204, 204), // Dark gray background
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF4A4A4A), width: 2), // Gray outline
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.2),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Username greeting - centered
          Center(
            child: Text(
              'Hello, $_username! 👋',
              style: TextStyle(
                color: Colors.white, // White text on dark background
                fontSize: _getResponsiveFontSize(context, 20),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          
          const SizedBox(height: 16),
          
          // How are you feeling question - centered
          Center(
            child: Text(
              'How are you feeling today?',
              style: TextStyle(
                color: const Color.fromARGB(255, 0, 0, 0), // Black text
                fontSize: _getResponsiveFontSize(context, 16),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          
          const SizedBox(height: 20),
          
          // Emoji faces row
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: List.generate(5, (index) {
              final isSelected = index == currentMoodIndex;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: isSelected ? _colors[index].withOpacity(0.2) : Colors.transparent,
                  borderRadius: BorderRadius.circular(50),
                  border: isSelected 
                      ? Border.all(color: _colors[index], width: 2)
                      : null,
                ),
                child: Text(
                  _emojis[index],
                  style: TextStyle(
                    fontSize: isSelected ? 32 : 24,
                    shadows: isSelected ? [
                      Shadow(
                        offset: const Offset(0, 2),
                        blurRadius: 4,
                        color: Colors.black.withValues(alpha: 0.2),
                      ),
                    ] : null,
                  ),
                ),
              );
            }),
          ),
          
          const SizedBox(height: 20),
          
          // Custom slider with mood colors
          SliderTheme(
            data: SliderTheme.of(context).copyWith(
              activeTrackColor: currentColor,
              inactiveTrackColor: currentColor.withOpacity(0.3),
              thumbColor: currentColor,
              overlayColor: currentColor.withOpacity(0.2),
              thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 12),
              trackHeight: 6,
              tickMarkShape: const RoundSliderTickMarkShape(tickMarkRadius: 4),
              activeTickMarkColor: currentColor,
              inactiveTickMarkColor: currentColor.withOpacity(0.3),
            ),
            child: Slider(
              value: _sliderValue,
              min: 0,
              max: 4,
              divisions: 4,
              onChanged: (value) {
                setState(() {
                  _sliderValue = value;
                });
                // Use debounced save to avoid too many Firebase calls
                _debouncedSave(value);
              },
            ),
          ),
          
          const SizedBox(height: 12),
          
          // Current mood text
          Center(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 300),
              child: Text(
                _moods[currentMoodIndex],
                key: ValueKey(currentMoodIndex),
                style: TextStyle(
                  color: currentColor,
                  fontSize: _getResponsiveFontSize(context, 16),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
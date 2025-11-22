import 'dart:async';

import 'package:flutter/material.dart';

class BreathingExerciseScreen extends StatefulWidget {
  const BreathingExerciseScreen({Key? key}) : super(key: key);

  @override
  State<BreathingExerciseScreen> createState() => _BreathingExerciseScreenState();
}

class _BreathingExerciseScreenState extends State<BreathingExerciseScreen>
    with TickerProviderStateMixin {
  final List<String> _steps = ['Inhale', 'Hold', 'Exhale', 'Hold'];

  late AnimationController _animController;
  late Animation<double> _sizeAnim;

  Timer? _tick;
  bool _isRunning = false;
  int _stepIndex = 0;
  int _stepDurationSeconds = 4; 
  int _secondsLeft = 4;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(vsync: this, value: 0.0);
    _sizeAnim = Tween<double>(begin: 0.6, end: 1.0).animate(
      CurvedAnimation(parent: _animController, curve: Curves.easeInOut),
    );
    _secondsLeft = _stepDurationSeconds;
  }

  @override
  void dispose() {
    _tick?.cancel();
    _animController.dispose();
    super.dispose();
  }

  void _start() {
    if (_isRunning) return;
    setState(() {
      _isRunning = true;
    });
    _startStep();
  }

  void _pause() {
    _tick?.cancel();
    _animController.stop();
    setState(() {
      _isRunning = false;
    });
  }

  void _reset() {
    _tick?.cancel();
    _animController.reset();
    setState(() {
      _isRunning = false;
      _stepIndex = 0;
      _secondsLeft = _stepDurationSeconds;
    });
  }

  void _startStep() {
    _tick?.cancel();
    setState(() {
      _secondsLeft = _stepDurationSeconds;
    });

    final current = _steps[_stepIndex];

    // configure animation for inhale/exhale. Holds are static.
    if (current == 'Inhale') {
      _animController.duration = Duration(seconds: _stepDurationSeconds);
      _animController.forward(from: 0.0);
    } else if (current == 'Exhale') {
      _animController.duration = Duration(seconds: _stepDurationSeconds);
      // ensure controller at 1.0 before reversing so we get full shrink
      _animController.value = 1.0;
      _animController.reverse(from: 1.0);
    } else {
      // Hold: keep the current visual state
      _animController.stop();
    }

    // Start second-granular tick to drive countdown and step transitions
    _tick = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        _secondsLeft -= 1;
        if (_secondsLeft <= 0) {
          // Next step
          _stepIndex = (_stepIndex + 1) % _steps.length;
          _startStep();
        }
      });
    });
  }

  void _toggleRunning() {
    if (_isRunning) {
      _pause();
    } else {
      _start();
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clamped = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clamped;
  }

  @override
  Widget build(BuildContext context) {
    final current = _steps[_stepIndex];

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Breathing Exercise', style: TextStyle(fontFamily: 'Montserrat', fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF1565C0),
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
              Text(
                'Box Breathing Exercise',
                style: TextStyle(
                  color: Colors.grey[700],
                  fontSize: _getResponsiveFontSize(context, 24),
                  fontWeight: FontWeight.w600,
                  fontFamily: 'Montserrat',
                ),
              ),
              const SizedBox(height:4),
              Text(
                'Anxiety reduction, calming the nervous system',
                style: TextStyle(

                  color: Colors.grey[800],
                  fontSize: _getResponsiveFontSize(context, 10),

                  fontFamily: 'Montserrat',
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 20),

              Text(
                'Follow the guide: inhale → hold → exhale → hold',
                textAlign: TextAlign.center,
                style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 14))
              ),
              const SizedBox(height: 8),
              Icon(Icons.air_rounded, size: 40, color: const Color(0xFF1565C0)),
              const SizedBox(height: 8),

              // Animated circle
              SizedBox(
                height: 350,
                child: Center(
                  child: AnimatedBuilder(
                    animation: _sizeAnim,
                    builder: (context, child) {
                      final scale = _sizeAnim.value;
                      final size = 120.0 + 160.0 * scale; // 120..280
                      return Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: size,
                            height: size,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: const Color(0xFFDFF6FF),
                              border: Border.all(
                                color: const Color(0xFF7EC8FF),
                                width: 3,
                              ),
                            ),
                            alignment: Alignment.center,
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  current,
                                  style: TextStyle(
                                    fontSize: _getResponsiveFontSize(context, 24),
                                    fontWeight: FontWeight.w700,
                                    color: const Color(0xFF1F6FB2),
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  '$_secondsLeft',
                                  style: TextStyle(
                                    fontSize: _getResponsiveFontSize(context, 28),
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF0E4E7A),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ),

              const SizedBox(height: 12),

              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  ElevatedButton.icon(
                    onPressed: _toggleRunning,
                    icon: Icon(_isRunning ? Icons.pause : Icons.play_arrow, size: 22),
                    label: Text(_isRunning ? 'Pause' : 'Start'),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size(160, 56),
                      textStyle: TextStyle(fontSize: _getResponsiveFontSize(context, 16)),
                      backgroundColor: const Color(0xFF7EC8FF),
                      foregroundColor: Colors.white,
                    ),
                  ),
                  const SizedBox(width: 12),
                  OutlinedButton.icon(
                    onPressed: _reset,
                    icon: const Icon(Icons.refresh, size: 20),
                    label: Text('Reset'),
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(160, 56),
                      textStyle: TextStyle(fontSize: _getResponsiveFontSize(context, 16)),
                      side: const BorderSide(color: Color(0xFF7EC8FF)),
                      foregroundColor: const Color(0xFF1F6FB2),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // settings step duration
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'Seconds per side:',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 16),
                      color: const Color(0xFF1F6FB2),
                    ),
                  ),
                  const SizedBox(width: 12),
                  DropdownButton<int>(
                    value: _stepDurationSeconds,
                    dropdownColor: const Color(0xFFF0FBFF),
                    iconEnabledColor: const Color(0xFF1F6FB2),
                    iconSize: 28,
                    style: TextStyle(fontSize: _getResponsiveFontSize(context, 16), color: const Color(0xFF1F6FB2)),
                    items: [2, 3, 4, 5, 6]
                        .map((s) => DropdownMenuItem<int>(
                              value: s,
                              child: Text(
                                '$s s',
                                style: TextStyle(fontSize: _getResponsiveFontSize(context, 16)),
                              ),
                            ))
                        .toList(),
                    onChanged: _isRunning
                        ? null
                        : (v) {
                            if (v == null) return;
                            setState(() {
                              _stepDurationSeconds = v;
                              _secondsLeft = v;
                            });
                          },
                  ),
                ],
              ),

              const SizedBox(height: 24),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

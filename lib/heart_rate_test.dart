import 'package:flutter/material.dart';
import 'package:silvercare/screens/heart_rate_screen.dart';

void main() {
  runApp(const TestApp());
}

class TestApp extends StatelessWidget {
  const TestApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Heart Rate Test',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: const HeartRateScreen(),
    );
  }
}

// Run with: flutter run -d chrome -t lib/heart_rate_test.dart
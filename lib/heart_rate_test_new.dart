import 'package:flutter/material.dart';
import 'package:silvercare/screens/heart_rate_screen.dart';

void main() {
  runApp(const TestAppNew());
}

class TestAppNew extends StatelessWidget {
  const TestAppNew({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Heart Rate Integrated Design Test',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        fontFamily: 'Montserrat',
      ),
      home: const HeartRateScreenNew(),
      debugShowCheckedModeBanner: false,
    );
  }
}

// Run with: flutter run -d chrome -t lib/heart_rate_test_new.dart
// This version includes Google Fit integration with the new Figma design
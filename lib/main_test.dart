import 'package:flutter/material.dart';

// Import all your groupmates' screens here
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/register_screen.dart';

void main() {
  runApp(
    MaterialApp(
      debugShowCheckedModeBanner: false,
      home: TestScreen(), // 👈 Change this line to test a different screen
    ),
  );
}

/// Change this to whatever you want to test
Widget TestScreen() {
  // Example: return LoginScreen();
  // Example: return RegisterScreen();
  // Example: return DashboardScreen();
  
  return DashboardScreen(); // 👈 currently testing dashboard
}

// how to run on chrome:
//flutter run -d chrome -t lib/main_test.dart

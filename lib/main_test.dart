import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:silvercare/config/firebase_options.dart';

// Import your screens
import 'screens/profile_screen.dart';
import 'screens/calendar_screen.dart'; 

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );

  runApp(
    MaterialApp(
      debugShowCheckedModeBanner: false,
      // 2. CHANGE INITIAL ROUTE TO CALENDAR
      initialRoute: '/calendar',

      routes: {
        '/profile': (context) => const ProfileScreen(),
        // 3. ADD CALENDAR ROUTE
        '/calendar': (context) => const CalendarScreen(),

        '/signin': (context) => const Scaffold(
          body: Center(
            child: Text(
              '--- Sign In Screen Placeholder ---',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
          ),
        ),
      },
    ),
  );
}
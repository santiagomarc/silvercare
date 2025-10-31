

import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart'; // <--- NEW
import 'package:silvercare/config/firebase_options.dart'; // <--- NEW

// Import your screens
import 'screens/profile_screen.dart';

// --- IMPORTANT: INITIALIZE FIREBASE HERE ---
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // You need this block to satisfy ProfileScreen's Firebase dependency
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  // ------------------------------------------

  runApp(
    MaterialApp(
      debugShowCheckedModeBanner: false,
      home: TestScreen(), // 👈 Change this line to test a different screen
      // Add a sign-in route for sign-out navigation to work
      routes: {
        '/signin': (context) => const Center(child: Text('Sign In Screen Placeholder')),
      },
    ),
  );
}

/// Change this to whatever you want to test
Widget TestScreen() {
  // We wrap ProfileScreen in a Builder to ensure it can access the new '/signin' route.
  return Builder(
    builder: (context) {
      return const ProfileScreen(); 
    }
  );
}

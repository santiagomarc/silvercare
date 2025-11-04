import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart'; // REQUIRED
import 'package:silvercare/config/firebase_options.dart'; // REQUIRED

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
      // 1. We START the app on the '/profile' route.
      initialRoute: '/profile', 
      
      // 2. Define all necessary routes for the ProfileScreen's navigation.
      routes: {
        // The ProfileScreen itself is now defined here as the target of '/profile'
        '/profile': (context) => const ProfileScreen(), 
        
        // This is the placeholder destination for the "Log Out" button
        '/signin': (context) => const Center(
          child: Text(
            '--- Sign In Screen Placeholder ---',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          ),
        ),
      },
    ),
  );
}

// NOTE: The separate TestScreen() and Builder() are no longer needed.

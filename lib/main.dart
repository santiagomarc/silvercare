import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:silvercare/config/firebase_options.dart';
import 'package:silvercare/screens/welcome_screen.dart';
import 'package:silvercare/screens/signin_screen.dart';
import 'package:silvercare/screens/signup_screen.dart';
import 'package:silvercare/screens/main_screen.dart';
import 'package:silvercare/screens/heart_rate_screen.dart';


void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  
  runApp(const SilverCareApp());
}

class SilverCareApp extends StatelessWidget {
  const SilverCareApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SilverCare',
      theme: ThemeData(
        // Light theme for better accessibility for elderly users
        brightness: Brightness.light,
        primarySwatch: Colors.blue,
        scaffoldBackgroundColor: const Color(0xFFDEDEDE),
        fontFamily: 'Montserrat',
      ),
      // Start with welcome screen
      home: const WelcomeScreen(),
      // Define all app routes
      routes: {
        '/signin': (context) => const SignInScreen(),
        '/signup': (context) => const SignUpScreen(),
        '/main': (context) => const MainScreen(), // Main app with navbar
        '/heart_rate': (context) => const HeartRateScreen(),
        
      },
      // Handle unknown routes
      onUnknownRoute: (settings) {
        return MaterialPageRoute(
          builder: (context) => const WelcomeScreen(),
        );
      },
    );
  }
}

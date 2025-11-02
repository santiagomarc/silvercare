import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../services/user_service.dart';
import 'welcome_screen.dart';
import 'main_screen.dart';
import 'caregiver_screens/caregiver_screen.dart';

class AuthWrapper extends StatelessWidget {
  const AuthWrapper({super.key});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<User?>(
      stream: FirebaseAuth.instance.authStateChanges(),
      builder: (context, snapshot) {
        // Show loading while checking auth state
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Scaffold(
            backgroundColor: Color(0xFFDEDEDE),
            body: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(
                    color: Color(0xFF6C63FF),
                  ),
                  SizedBox(height: 16),
                  Text(
                    'Loading SilverCare...',
                    style: TextStyle(
                      fontSize: 16,
                      fontFamily: 'Montserrat',
                      fontWeight: FontWeight.w600,
                      color: Colors.black87,
                    ),
                  ),
                ],
              ),
            ),
          );
        }

        // If user is not signed in, show welcome screen
        if (!snapshot.hasData || snapshot.data == null) {
          return const WelcomeScreen();
        }

        // User is signed in, determine their type and show appropriate screen
        return FutureBuilder<UserType>(
          future: UserService.getUserType(),
          builder: (context, userTypeSnapshot) {
            // Show loading while determining user type
            if (userTypeSnapshot.connectionState == ConnectionState.waiting) {
              return const Scaffold(
                backgroundColor: Color(0xFFDEDEDE),
                body: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      CircularProgressIndicator(
                        color: Color(0xFF6C63FF),
                      ),
                      SizedBox(height: 16),
                      Text(
                        'Setting up your dashboard...',
                        style: TextStyle(
                          fontSize: 16,
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w600,
                          color: Colors.black87,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }

            // Navigate based on user type
            final userType = userTypeSnapshot.data ?? UserType.unknown;
            
            switch (userType) {
              case UserType.elderly:
                return const MainScreen();
              case UserType.caregiver:
                return const CaregiverScreen();
              case UserType.unknown:
                // If user type is unknown, sign them out and show welcome screen
                FirebaseAuth.instance.signOut();
                return const WelcomeScreen();
            }
          },
        );
      },
    );
  }
}
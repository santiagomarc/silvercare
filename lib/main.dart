import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:silvercare/screens/signin_screen.dart';
import 'package:silvercare/screens/signup_screen.dart';
import 'package:firebase_core/firebase_core.dart';
import 'firebase_options.dart';

void main() async {
  // Catch errors in the zone
  runZonedGuarded(() async {
    WidgetsFlutterBinding.ensureInitialized();
    
    // Filter out annoying DebugService errors from Flutter framework
    FlutterError.onError = (FlutterErrorDetails details) {
      final errorString = details.exception.toString();
      if (!errorString.contains('DebugService') && 
          !errorString.contains('Unsupported operation: Cannot send Null') &&
          !errorString.contains('Error serving requests')) {
        FlutterError.presentError(details);
      }
    };
    
    // Also override debugPrint to filter console output
    if (kDebugMode) {
      final originalDebugPrint = debugPrint;
      debugPrint = (String? message, {int? wrapWidth}) {
        if (message != null && 
            !message.contains('DebugService') && 
            !message.contains('Unsupported operation: Cannot send Null') &&
            !message.contains('Error serving requests')) {
          originalDebugPrint(message, wrapWidth: wrapWidth);
        }
      };
    }
    
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
    
    // Test Firebase connection
    debugPrint('🔥 Firebase initialized successfully!');
    debugPrint('📱 Platform: ${DefaultFirebaseOptions.currentPlatform.projectId}');
    
    runApp(const FigmaToCodeApp());
  }, (error, stack) {
    // Handle errors gracefully - ignore DebugService errors
    final errorString = error.toString();
    if (!errorString.contains('DebugService') && 
        !errorString.contains('Unsupported operation: Cannot send Null') &&
        !errorString.contains('Error serving requests')) {
      debugPrint('⚠️ Error: $error');
      if (kDebugMode) {
        debugPrint('Stack trace: $stack');
      }
    }
  });
}

class FigmaToCodeApp extends StatelessWidget {
  const FigmaToCodeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      theme: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: const Color.fromARGB(255, 18, 32, 47),
      ),
      home: const SilverCareApp(),
    );
  }
}

class SilverCareApp extends StatelessWidget {
  const SilverCareApp({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              // Top spacer
              const Spacer(flex: 2),
              
              // Logo
              _buildLogo(),
              
              const SizedBox(height: 40),
              
              // Silver Care title
              _buildTitle(),
              
              // Main content spacer
              const Spacer(flex: 3),
              
              // Sign Up button
              _buildSignUpButton(context),
              
              const SizedBox(height: 16),
              
              // Sign In button
              _buildSignInButton(context),
              
              // Bottom spacer
              const Spacer(flex: 2),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLogo() {
    return Center(
      child: Container(
        width: 120,
        height: 120,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 8,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: const Image(
            image: AssetImage('assets/imgs/logo.png'),
            fit: BoxFit.cover,
          ),
        ),
      ),
    );
  }

  Widget _buildTitle() {
    return Builder(
      builder: (context) => Text(
        'SILVERCARE',
        textAlign: TextAlign.center,
        style: TextStyle(
          color: Colors.black,
          fontSize: _getResponsiveFontSize(context, 40),
          fontFamily: 'Montserrat',
          fontWeight: FontWeight.w800,
          shadows: [
            Shadow(
              offset: const Offset(0, 4),
              blurRadius: 4, 
              color: Colors.black.withValues(alpha: 0.50)
            )
          ],
        ),
      ),
    );
  }

  Widget _buildSignUpButton(BuildContext context) {
    return SizedBox(
      width: MediaQuery.of(context).size.width * 0.6, // 60% of screen width
      height: 48,
      child: ElevatedButton(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const SignUpScreen()),
          );
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
          elevation: 8,
          shadowColor: Colors.black.withOpacity(0.25),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(82),
          ),
        ),
        child: Text(
          'SIGN UP',
          style: TextStyle(
            fontSize: _getResponsiveFontSize(context, 30),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
  }

  Widget _buildSignInButton(BuildContext context) {
    return SizedBox(
      width: MediaQuery.of(context).size.width * 0.6, // 60% of screen width
      height: 48,
      child: ElevatedButton(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const SignInScreen()),
          );
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
          elevation: 8,
          shadowColor: Colors.black.withOpacity(0.25),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(82),
          ),
        ),
        child: Text(
          'SIGN IN',
          style: TextStyle(
            fontSize: _getResponsiveFontSize(context, 30),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
  }

  // Responsive font sizing based on screen width
  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }
}

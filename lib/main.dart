import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:silvercare/config/firebase_options.dart';
import 'package:silvercare/screens/auth_wrapper.dart';
import 'package:silvercare/screens/welcome_screen.dart';
import 'package:silvercare/screens/signin_screen.dart';
import 'package:silvercare/screens/signup_screen.dart';
import 'package:silvercare/screens/main_screen.dart';
import 'package:silvercare/screens/caregiver_screens/caregiver_screen.dart';
import 'package:silvercare/screens/heart_rate_screen.dart';
import 'package:silvercare/screens/blood_pressure_screen.dart';
import 'package:silvercare/screens/temperature_screen.dart';
import 'package:silvercare/screens/sugar_level_screen.dart';
import 'package:silvercare/screens/notifications_screen.dart';
import 'package:silvercare/screens/caregiver_screens/sos_alert_screen.dart';
import 'package:silvercare/services/notification_service.dart';
import 'package:silvercare/services/push_notification_service.dart';
import 'package:silvercare/services/sos_listener_service.dart';
import 'package:silvercare/screens/calendar_screen.dart';






void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );

  // Initialize notification service and wait for completion
  await NotificationService().initialize();
  
  // Initialize push notification service
  await PushNotificationService().initialize();
  await PushNotificationService().requestPermissions();
  
  runApp(const SilverCareApp());
}

class SilverCareApp extends StatelessWidget {
  const SilverCareApp({super.key});

  // Global navigator key for notification routing
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  @override
  Widget build(BuildContext context) {
    // Set the navigator key in NotificationService and SOSListenerService
    NotificationService.navigatorKey = navigatorKey;
    SOSListenerService.navigatorKey = navigatorKey;

    return MaterialApp(
      navigatorKey: navigatorKey,
      title: 'SilverCare',
      debugShowCheckedModeBanner: false, // Optional: removes the debug banner
      theme: ThemeData(
        // Light theme for better accessibility for elderly users
        brightness: Brightness.light,
        primarySwatch: Colors.blue,
        scaffoldBackgroundColor: const Color(0xFFDEDEDE),
        fontFamily: 'Montserrat',
      ),
      // Start with auth wrapper to handle authentication state
      home: const AuthWrapper(),
      // Define all app routes
      routes: {
        '/welcome': (context) => const WelcomeScreen(),
        '/signin': (context) => const SignInScreen(),
        '/signup': (context) => const SignUpScreen(),
        '/main': (context) => const MainScreen(), // Main app with navbar for elderly users
        '/caregiver': (context) => const CaregiverScreen(), // Caregiver dashboard
        '/heart_rate': (context) => const HeartRateScreen(),
        '/blood_pressure': (context) => const BloodPressureScreen(),
        '/temperature': (context) => const TemperatureScreen(),
        '/sugar_level': (context) => const SugarLevelScreen(),
        '/notifications': (context) => const NotificationsScreen(),
        // 2. ADD THIS ROUTE
        '/calendar': (context) => const CalendarScreen(),
        '/sos_alert': (context) {
          final alertId = ModalRoute.of(context)?.settings.arguments as String?;
          return SOSAlertScreen(alertId: alertId);
        },
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
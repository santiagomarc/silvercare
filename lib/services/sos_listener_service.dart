import 'dart:async';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_ringtone_player/flutter_ringtone_player.dart';
import '../models/sos_alert_model.dart';

/// Real-time Firestore listener for SOS alerts
/// Listens to sos_alerts collection and triggers alarm + notification
/// NO CLOUD FUNCTIONS NEEDED - Works entirely client-side!
class SOSListenerService {
  static final SOSListenerService _instance = SOSListenerService._internal();
  factory SOSListenerService() => _instance;
  SOSListenerService._internal();

  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FlutterLocalNotificationsPlugin _notifications = 
      FlutterLocalNotificationsPlugin();

  StreamSubscription<QuerySnapshot>? _alertsSubscription;
  static bool _alarmPlaying = false;
  static Timer? _alarmTimer;
  String? _lastProcessedAlertId; // Prevent duplicate notifications
  DateTime? _listenerStartTime; // Track when listener started
  
  // Global navigator key for navigation
  static GlobalKey<NavigatorState>? navigatorKey;
  
  // Public getter for alarm playing state
  static bool get isAlarmPlaying => _alarmPlaying;

  /// Initialize the listener (call this for CAREGIVER users only!)
  Future<void> startListening() async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) {
        print('⚠️ No user logged in, skipping SOS listener');
        return;
      }

      // Get caregiver's ID
      final caregiverDoc = await _firestore
          .collection('caregivers')
          .where('userId', isEqualTo: userId)
          .limit(1)
          .get();

      if (caregiverDoc.docs.isEmpty) {
        print('⚠️ Not a caregiver account, skipping SOS listener');
        return;
      }

      final elderlyId = caregiverDoc.docs.first.data()['elderlyId'];

      if (elderlyId == null || elderlyId.isEmpty) {
        print('⚠️ Caregiver not assigned to any elderly, skipping SOS listener');
        return;
      }

      print('✅ Starting SOS listener for elderly: $elderlyId');

      // Track when listener started to avoid processing old alerts
      _listenerStartTime = DateTime.now();

      // Listen to sos_alerts collection for this elderly user
      // Simplified query (no orderBy) to avoid needing composite index
      _alertsSubscription = _firestore
          .collection('sos_alerts')
          .where('elderId', isEqualTo: elderlyId)
          .where('status', isEqualTo: 'active')
          .snapshots()
          .listen(_handleNewAlert);

      print('✅ SOS Listener active - will trigger on new alerts');
    } catch (e) {
      print('❌ Error starting SOS listener: $e');
    }
  }

  /// Handle new SOS alert from Firestore
  void _handleNewAlert(QuerySnapshot snapshot) {
    if (snapshot.docs.isEmpty) return;

    for (var doc in snapshot.docChanges) {
      if (doc.type == DocumentChangeType.added) {
        final alert = SOSAlertModel.fromDoc(doc.doc);
        
        // Skip alerts created before listener started (to avoid showing old resolved alerts)
        if (_listenerStartTime != null && alert.timestamp.isBefore(_listenerStartTime!)) {
          print('⚠️ Alert ${alert.id} created before listener started, skipping');
          continue;
        }
        
        // Prevent duplicate notifications for same alert
        if (_lastProcessedAlertId == alert.id) {
          print('⚠️ Alert ${alert.id} already processed, skipping');
          continue;
        }

        print('🚨 NEW SOS ALERT DETECTED: ${alert.id}');
        _lastProcessedAlertId = alert.id;

        // Play alarm sound
        playSOSAlarm();

        // Show local notification
        _showSOSNotification(alert);
        
        // Navigate to alert screen immediately
        _navigateToAlertScreen(alert);
      }
    }
  }

  /// Play SOS alarm sound (loops for 5 minutes or until stopped)
  static Future<void> playSOSAlarm() async {
    if (_alarmPlaying) {
      print('⚠️ Alarm already playing');
      return;
    }

    _alarmPlaying = true;
    print('🔔 Playing SOS alarm...');

    try {
      // Play alarm sound in a loop
      FlutterRingtonePlayer().play(
        android: AndroidSounds.alarm,
        ios: IosSounds.alarm,
        looping: true,
        volume: 1.0,
      );

      // Auto-stop after 5 minutes
      _alarmTimer = Timer(const Duration(minutes: 5), () {
        stopSOSAlarm();
      });
    } catch (e) {
      print('❌ Error playing alarm: $e');
      _alarmPlaying = false;
    }
  }

  /// Stop the SOS alarm
  static Future<void> stopSOSAlarm() async {
    if (!_alarmPlaying) return;

    print('🔕 Stopping SOS alarm');
    _alarmPlaying = false;
    _alarmTimer?.cancel();
    _alarmTimer = null;

    try {
      await FlutterRingtonePlayer().stop();
    } catch (e) {
      print('❌ Error stopping alarm: $e');
    }
  }

  /// Show local notification for SOS alert
  Future<void> _showSOSNotification(SOSAlertModel alert) async {
    const androidDetails = AndroidNotificationDetails(
      'sos_alert_channel',
      'SOS Alerts',
      channelDescription: 'Emergency SOS alerts from elderly users',
      importance: Importance.max,
      priority: Priority.high,
      showWhen: true,
      fullScreenIntent: true,
      category: AndroidNotificationCategory.alarm,
      visibility: NotificationVisibility.public,
      icon: '@mipmap/ic_launcher',
    );

    const iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
      sound: 'default',
      interruptionLevel: InterruptionLevel.critical,
    );

    const details = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _notifications.show(
      alert.id.hashCode,
      '🚨 EMERGENCY SOS ALERT',
      '${alert.elderName} needs immediate assistance!',
      details,
      payload: alert.id, // Pass alert ID for navigation
    );

    print('✅ Local notification shown for alert: ${alert.id}');
  }

  /// Navigate to SOS alert screen using global navigator key
  void _navigateToAlertScreen(SOSAlertModel alert) {
    if (navigatorKey?.currentState != null) {
      print('📍 Navigating to SOS screen for alert: ${alert.id}');
      navigatorKey!.currentState!.pushNamed(
        '/sos_alert',
        arguments: alert.id,
      );
    } else {
      print('⚠️ Navigator key not available - user must tap notification');
    }
  }
  
  /// Stop listening (call on logout)
  void stopListening() {
    print('🛑 Stopping SOS listener');
    _alertsSubscription?.cancel();
    _alertsSubscription = null;
    _lastProcessedAlertId = null;
    stopSOSAlarm();
  }
}

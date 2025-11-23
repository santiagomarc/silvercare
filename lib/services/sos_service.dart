import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import '../models/sos_alert_model.dart';
import '../models/elderly_model.dart';
import '../models/health_data_model.dart';
import 'persistent_notification_service.dart';

/// Service to handle SOS emergency alerts
/// Simplified version - just creates alerts in Firestore
/// Caregiver side uses SOSListenerService for real-time detection
class SOSService {
  static final SOSService _instance = SOSService._internal();
  factory SOSService() => _instance;
  SOSService._internal();

  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Trigger an SOS alert - called by elderly user
  /// Creates alert document in Firestore that caregiver's listener will detect
  Future<SOSAlertModel?> triggerSOSAlert() async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) {
        print('❌ No authenticated user');
        throw Exception('No authenticated user');
      }

      print('🚨 Triggering SOS alert for user: $userId');

      // 1. Get elderly profile
      print('📋 Fetching elderly profile...');
      final elderlyDoc = await _firestore
          .collection('elderly')
          .where('userId', isEqualTo: userId)
          .limit(1)
          .get();

      if (elderlyDoc.docs.isEmpty) {
        print('❌ Elderly profile not found');
        throw Exception('Elderly profile not found. Please complete your profile first.');
      }

      final elderlyData = ElderlyModel.fromDoc(elderlyDoc.docs.first);
      final elderlyId = elderlyData.id;
      final caregiverId = elderlyData.caregiverId;

      print('✅ Elderly profile found: $elderlyId');

      if (caregiverId == null || caregiverId.isEmpty) {
        print('❌ No caregiver assigned to this elderly user');
        throw Exception('No caregiver assigned. Please ask your caregiver to connect with you first.');
      }

      print('✅ Caregiver assigned: $caregiverId');

      // 2. Get user name
      print('📋 Fetching user name...');
      final userDoc = await _firestore.collection('users').doc(userId).get();
      final userName = userDoc.data()?['fullName'] ?? 'Unknown User';
      print('✅ User name: $userName');

      // 3. Get current location (with timeout)
      LocationData? locationData;
      try {
        print('📍 Getting current location...');
        locationData = await _getCurrentLocation().timeout(
          const Duration(seconds: 10),
          onTimeout: () {
            print('⚠️ Location request timed out');
            throw Exception('Location timeout');
          },
        );
        print('✅ Location obtained: ${locationData.latitude}, ${locationData.longitude}');
      } catch (e) {
        print('⚠️ Could not get location: $e');
        // Continue without location - it's not critical for SOS
      }

      // 4. Get latest vitals summary
      print('📊 Fetching latest vitals...');
      VitalsSummary? vitalsSummary = await _getLatestVitals(elderlyId);
      if (vitalsSummary != null && vitalsSummary.hasData) {
        print('✅ Vitals found');
      } else {
        print('⚠️ No recent vitals found');
      }

      // 5. Create SOS alert in Firestore
      // The caregiver's SOSListenerService will detect this in real-time
      print('💾 Creating SOS alert in Firestore...');
      final sosAlert = SOSAlertModel(
        id: '', // Will be set by Firestore
        elderId: elderlyId,
        elderName: userName,
        timestamp: DateTime.now(),
        location: locationData,
        vitalsSummary: vitalsSummary,
        alertType: 'sos_alert',
        status: 'active',
      );

      final alertDoc = await _firestore.collection('sos_alerts').add(sosAlert.toMap());
      final createdAlert = sosAlert.copyWith(id: alertDoc.id);

      print('✅ SOS alert created: ${alertDoc.id}');
      
      // Create notification for elderly user
      try {
        final persistentNotificationService = PersistentNotificationService();
        await persistentNotificationService.createSOSAlertSent(
          elderlyId: userId,
          alertId: alertDoc.id,
        );
        print('✅ SOS notification created for elderly');
      } catch (e) {
        print('⚠️ Failed to create SOS notification: $e');
      }
      
      print('✅ SOS alert process completed - caregiver listener will be notified');
      
      return createdAlert;
    } catch (e) {
      print('❌ Error triggering SOS alert: $e');
      rethrow;
    }
  }

  /// Get current GPS location with smart address formatting
  /// Prioritizes: Street, Barangay (SubLocality), City (Locality)
  Future<LocationData> _getCurrentLocation() async {
    // Check if location services are enabled
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw Exception('Location services are disabled');
    }

    // Check permissions
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        throw Exception('Location permissions are denied');
      }
    }

    if (permission == LocationPermission.deniedForever) {
      throw Exception('Location permissions are permanently denied');
    }

    // Get current position (High accuracy for better street matching)
    Position position = await Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
    );

    // Reverse geocode to get human-readable address
    String? address;
    try {
      List<Placemark> placemarks = await placemarkFromCoordinates(
        position.latitude,
        position.longitude,
      );
      
      if (placemarks.isNotEmpty) {
        final place = placemarks.first;
        
        final List<String> components = [];

        // 1. Street (e.g., "Rizal Street" or "Block 5")
        if (place.street != null && place.street!.isNotEmpty && place.street != place.name) {
           components.add(place.street!);
        } else if (place.name != null && place.name!.isNotEmpty) {
           // Fallback to name if street is empty (often contains building name)
           components.add(place.name!);
        }

        // 2. Barangay (Stored as 'subLocality' in geocoding)
        if (place.subLocality != null && place.subLocality!.isNotEmpty) {
          components.add(place.subLocality!);
        }

        // 3. City (Locality)
        if (place.locality != null && place.locality!.isNotEmpty) {
          components.add(place.locality!);
        }
        
        // 4. Province/Region (AdministrativeArea) - Optional, good if city is missing
        if ((place.locality == null || place.locality!.isEmpty) && 
            place.administrativeArea != null && place.administrativeArea!.isNotEmpty) {
          components.add(place.administrativeArea!);
        }

        // Join them with commas: "Rizal St, Brgy. San Jose, Calamba City"
        if (components.isNotEmpty) {
          address = components.join(', ');
        } else {
          // Absolute fallback if everything else is empty
          address = place.country ?? "Unknown Location";
        }
      }
    } catch (e) {
      print('⚠️ Could not reverse geocode: $e');
      // We leave address as null here; the UI will display coordinates as a last resort
    }

    return LocationData(
      latitude: position.latitude,
      longitude: position.longitude,
      address: address,
      accuracy: position.accuracy,
    );
  }

  /// Get latest vitals for elderly user
  Future<VitalsSummary?> _getLatestVitals(String elderlyId) async {
    try {
      // 1. Use userId from auth to ensure we are querying the correct user
      final userId = _auth.currentUser?.uid;
      if (userId == null) return null;
      
      print('🔎 Fetching vitals for user: $userId');

      // 2. Widened search window to 30 days to ensure test data is found
      final now = DateTime.now();
      final lookBack = now.subtract(const Duration(days: 30));

      // 3. EXECUTE QUERY
      // NOTE: This query requires a Firestore Index: health_data (elderlyId ASC, measuredAt DESC)
      final healthDataSnapshot = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: userId)
          .where('measuredAt', isGreaterThan: Timestamp.fromDate(lookBack))
          .orderBy('measuredAt', descending: true)
          .get();

      print('📄 Found ${healthDataSnapshot.docs.length} health records in last 30 days');

      if (healthDataSnapshot.docs.isEmpty) {
        print('⚠️ No health records found. CHECK FIRESTORE INDEX if data exists in DB.');
        return null;
      }

      // Extract latest values for each type
      double? heartRate;
      double? bpSystolic;
      double? bpDiastolic;
      double? sugarLevel;
      double? temperature;
      DateTime? lastUpdated;

      for (var doc in healthDataSnapshot.docs) {
        // Use the model to safely parse data
        final data = HealthDataModel.fromDoc(doc);
        
        // Update lastUpdated to the most recent record found
        if (lastUpdated == null || data.measuredAt.isAfter(lastUpdated)) {
          lastUpdated = data.measuredAt;
        }
        
        // Fill in values if they haven't been found yet (because we sort descending, first found is latest)
        switch (data.type) {
          case 'heart_rate':
            if (heartRate == null) heartRate = data.value;
            break;
            
          case 'blood_pressure':
            if (bpSystolic == null) {
              // Try to get systolic from specific field, fallback to value
              // In your DB structure: value = systolic
              bpSystolic = data.value; 
              
              // Try to get diastolic from metadata (populated by fromDoc) OR from specific field in doc
              final rawData = doc.data() as Map<String, dynamic>;
              if (data.metadata != null && data.metadata!.containsKey('diastolic')) {
                bpDiastolic = (data.metadata!['diastolic'] as num).toDouble();
              } else if (rawData.containsKey('diastolic')) {
                bpDiastolic = (rawData['diastolic'] as num).toDouble();
              } else {
                // Fallback estimate if data is corrupted
                bpDiastolic = data.value * 0.67; 
              }
              print('   🩸 Found BP: $bpSystolic / $bpDiastolic');
            }
            break;
            
          case 'sugar_level':
            if (sugarLevel == null) sugarLevel = data.value;
            break;
            
          case 'temperature':
            if (temperature == null) temperature = data.value;
            break;
        }
      }

      final summary = VitalsSummary(
        heartRate: heartRate,
        bloodPressureSystolic: bpSystolic,
        bloodPressureDiastolic: bpDiastolic,
        sugarLevel: sugarLevel,
        temperature: temperature,
        lastUpdated: lastUpdated,
      );
      
      print('✅ Generated Vitals Summary: $summary');
      return summary;
      
    } catch (e) {
      print('⚠️ Error getting vitals: $e');
      return null;
    }
  }
  /// Acknowledge an SOS alert (called by caregiver)
  Future<void> acknowledgeAlert(String alertId) async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) return;

      // Get caregiver ID
      final caregiverDoc = await _firestore
          .collection('caregivers')
          .where('userId', isEqualTo: userId)
          .limit(1)
          .get();

      if (caregiverDoc.docs.isEmpty) return;

      final caregiverId = caregiverDoc.docs.first.id;

      await _firestore.collection('sos_alerts').doc(alertId).update({
        'status': 'acknowledged',
        'caregiverId': caregiverId,
        'acknowledgedAt': FieldValue.serverTimestamp(),
      });

      print('✅ SOS alert acknowledged: $alertId');
    } catch (e) {
      print('❌ Error acknowledging alert: $e');
    }
  }

  /// Resolve an SOS alert (called by caregiver)
  Future<void> resolveAlert(String alertId) async {
    try {
      await _firestore.collection('sos_alerts').doc(alertId).update({
        'status': 'resolved',
        'resolvedAt': FieldValue.serverTimestamp(),
      });

      print('✅ SOS alert resolved: $alertId');
    } catch (e) {
      print('❌ Error resolving alert: $e');
    }
  }

  /// Get active SOS alerts for a caregiver
  Stream<List<SOSAlertModel>> getActiveAlertsForCaregiver(String elderlyId) {
    return _firestore
        .collection('sos_alerts')
        .where('elderId', isEqualTo: elderlyId)
        .where('status', isEqualTo: 'active')
        .snapshots()
        .map((snapshot) =>
            snapshot.docs.map((doc) => SOSAlertModel.fromDoc(doc)).toList());
  }

  /// Get SOS alert by ID
  Future<SOSAlertModel?> getAlertById(String alertId) async {
    try {
      final doc = await _firestore.collection('sos_alerts').doc(alertId).get();
      if (!doc.exists) return null;
      return SOSAlertModel.fromDoc(doc);
    } catch (e) {
      print('❌ Error getting alert: $e');
      return null;
    }
  }
}

import 'package:cloud_firestore/cloud_firestore.dart';

/// Model representing an SOS emergency alert triggered by an elderly user
class SOSAlertModel {
  final String id; // Document ID from Firestore
  final String elderId; // Reference to elderly user
  final String elderName; // Full name of the elderly person
  final DateTime timestamp; // When the SOS was triggered
  final LocationData? location; // GPS coordinates of the elderly
  final VitalsSummary? vitalsSummary; // Latest vitals at the time of SOS
  final String alertType; // Always "sos_alert"
  final String status; // "active" | "acknowledged" | "resolved"
  final String? caregiverId; // Who received/acknowledged the alert
  final DateTime? acknowledgedAt; // When the caregiver acknowledged
  final DateTime? resolvedAt; // When the alert was resolved

  SOSAlertModel({
    required this.id,
    required this.elderId,
    required this.elderName,
    required this.timestamp,
    this.location,
    this.vitalsSummary,
    this.alertType = 'sos_alert',
    this.status = 'active',
    this.caregiverId,
    this.acknowledgedAt,
    this.resolvedAt,
  });

  /// Convert to Map for saving to Firestore
  Map<String, dynamic> toMap() {
    return {
      'elderId': elderId,
      'elderName': elderName,
      'timestamp': Timestamp.fromDate(timestamp),
      'location': location?.toMap(),
      'vitalsSummary': vitalsSummary?.toMap(),
      'alertType': alertType,
      'status': status,
      'caregiverId': caregiverId,
      'acknowledgedAt': acknowledgedAt != null ? Timestamp.fromDate(acknowledgedAt!) : null,
      'resolvedAt': resolvedAt != null ? Timestamp.fromDate(resolvedAt!) : null,
    };
  }

  /// Create SOSAlertModel from Firestore document
  factory SOSAlertModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return SOSAlertModel(
      id: doc.id,
      elderId: data['elderId'] ?? '',
      elderName: data['elderName'] ?? '',
      timestamp: (data['timestamp'] as Timestamp?)?.toDate() ?? DateTime.now(),
      location: data['location'] != null ? LocationData.fromMap(data['location']) : null,
      vitalsSummary: data['vitalsSummary'] != null 
          ? VitalsSummary.fromMap(data['vitalsSummary']) 
          : null,
      alertType: data['alertType'] ?? 'sos_alert',
      status: data['status'] ?? 'active',
      caregiverId: data['caregiverId'],
      acknowledgedAt: (data['acknowledgedAt'] as Timestamp?)?.toDate(),
      resolvedAt: (data['resolvedAt'] as Timestamp?)?.toDate(),
    );
  }

  /// Create SOSAlertModel from Map
  factory SOSAlertModel.fromMap(Map<String, dynamic> map, String id) {
    return SOSAlertModel(
      id: id,
      elderId: map['elderId'] ?? '',
      elderName: map['elderName'] ?? '',
      timestamp: (map['timestamp'] as Timestamp?)?.toDate() ?? DateTime.now(),
      location: map['location'] != null ? LocationData.fromMap(map['location']) : null,
      vitalsSummary: map['vitalsSummary'] != null 
          ? VitalsSummary.fromMap(map['vitalsSummary']) 
          : null,
      alertType: map['alertType'] ?? 'sos_alert',
      status: map['status'] ?? 'active',
      caregiverId: map['caregiverId'],
      acknowledgedAt: (map['acknowledgedAt'] as Timestamp?)?.toDate(),
      resolvedAt: (map['resolvedAt'] as Timestamp?)?.toDate(),
    );
  }

  /// Copy with method for updating fields
  SOSAlertModel copyWith({
    String? id,
    String? elderId,
    String? elderName,
    DateTime? timestamp,
    LocationData? location,
    VitalsSummary? vitalsSummary,
    String? alertType,
    String? status,
    String? caregiverId,
    DateTime? acknowledgedAt,
    DateTime? resolvedAt,
  }) {
    return SOSAlertModel(
      id: id ?? this.id,
      elderId: elderId ?? this.elderId,
      elderName: elderName ?? this.elderName,
      timestamp: timestamp ?? this.timestamp,
      location: location ?? this.location,
      vitalsSummary: vitalsSummary ?? this.vitalsSummary,
      alertType: alertType ?? this.alertType,
      status: status ?? this.status,
      caregiverId: caregiverId ?? this.caregiverId,
      acknowledgedAt: acknowledgedAt ?? this.acknowledgedAt,
      resolvedAt: resolvedAt ?? this.resolvedAt,
    );
  }

  @override
  String toString() {
    return 'SOSAlertModel(id: $id, elderName: $elderName, status: $status, timestamp: $timestamp)';
  }
}

/// GPS location data for the elderly user
class LocationData {
  final double latitude;
  final double longitude;
  final String? address; // Human-readable address (optional)
  final double? accuracy; // GPS accuracy in meters (optional)

  LocationData({
    required this.latitude,
    required this.longitude,
    this.address,
    this.accuracy,
  });

  Map<String, dynamic> toMap() {
    return {
      'latitude': latitude,
      'longitude': longitude,
      'address': address,
      'accuracy': accuracy,
    };
  }

  factory LocationData.fromMap(Map<String, dynamic> map) {
    return LocationData(
      latitude: (map['latitude'] ?? 0.0).toDouble(),
      longitude: (map['longitude'] ?? 0.0).toDouble(),
      address: map['address'],
      accuracy: map['accuracy']?.toDouble(),
    );
  }

  @override
  String toString() {
    return 'LocationData(lat: $latitude, lng: $longitude, address: $address)';
  }
}

/// Summary of the elderly's latest vital signs
class VitalsSummary {
  final double? bloodPressureSystolic;
  final double? bloodPressureDiastolic;
  final double? heartRate;
  final double? sugarLevel;
  final double? temperature;
  final DateTime? lastUpdated; // When these vitals were recorded

  VitalsSummary({
    this.bloodPressureSystolic,
    this.bloodPressureDiastolic,
    this.heartRate,
    this.sugarLevel,
    this.temperature,
    this.lastUpdated,
  });

  Map<String, dynamic> toMap() {
    return {
      'bloodPressureSystolic': bloodPressureSystolic,
      'bloodPressureDiastolic': bloodPressureDiastolic,
      'heartRate': heartRate,
      'sugarLevel': sugarLevel,
      'temperature': temperature,
      'lastUpdated': lastUpdated != null ? Timestamp.fromDate(lastUpdated!) : null,
    };
  }

  factory VitalsSummary.fromMap(Map<String, dynamic> map) {
    return VitalsSummary(
      bloodPressureSystolic: map['bloodPressureSystolic']?.toDouble(),
      bloodPressureDiastolic: map['bloodPressureDiastolic']?.toDouble(),
      heartRate: map['heartRate']?.toDouble(),
      sugarLevel: map['sugarLevel']?.toDouble(),
      temperature: map['temperature']?.toDouble(),
      lastUpdated: (map['lastUpdated'] as Timestamp?)?.toDate(),
    );
  }

  /// Check if any vitals data exists
  bool get hasData {
    return bloodPressureSystolic != null ||
        bloodPressureDiastolic != null ||
        heartRate != null ||
        sugarLevel != null ||
        temperature != null;
  }

  /// Get formatted blood pressure string
  String? get bloodPressureFormatted {
    if (bloodPressureSystolic != null && bloodPressureDiastolic != null) {
      return '${bloodPressureSystolic!.toInt()}/${bloodPressureDiastolic!.toInt()} mmHg';
    }
    return null;
  }

  @override
  String toString() {
    return 'VitalsSummary(BP: $bloodPressureFormatted, HR: $heartRate, Sugar: $sugarLevel, Temp: $temperature)';
  }
}

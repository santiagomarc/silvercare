import 'package:cloud_firestore/cloud_firestore.dart';

class HealthDataModel {
  final String id; // Document ID from Firestore
  final String elderlyId; // Reference to elderly collection
  final String type; // "blood_pressure" | "heart_rate" | "sugar_level" | "temperature"
  final double value; // The measurement value
  final DateTime measuredAt; // When the measurement was taken
  final DateTime createdAt; // When the record was created

  HealthDataModel({
    required this.id,
    required this.elderlyId,
    required this.type,
    required this.value,
    required this.measuredAt,
    required this.createdAt,
  });

  // Convert to Map for saving to Firestore
  Map<String, dynamic> toMap() {
    return {
      'elderlyId': elderlyId,
      'type': type,
      'value': value,
      'measuredAt': Timestamp.fromDate(measuredAt),
      'createdAt': Timestamp.fromDate(createdAt),
    };
  }

  // Create HealthDataModel from Firestore document
  factory HealthDataModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return HealthDataModel(
      id: doc.id,
      elderlyId: data['elderlyId'] ?? '',
      type: data['type'] ?? 'heart_rate',
      value: (data['value'] ?? 0).toDouble(),
      measuredAt: (data['measuredAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
      createdAt: (data['createdAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  // Create HealthDataModel from Map
  factory HealthDataModel.fromMap(Map<String, dynamic> map, String id) {
    return HealthDataModel(
      id: id,
      elderlyId: map['elderlyId'] ?? '',
      type: map['type'] ?? 'heart_rate',
      value: (map['value'] ?? 0).toDouble(),
      measuredAt: (map['measuredAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
      createdAt: (map['createdAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  // Copy with method for updating fields
  HealthDataModel copyWith({
    String? id,
    String? elderlyId,
    String? type,
    double? value,
    DateTime? measuredAt,
    DateTime? createdAt,
  }) {
    return HealthDataModel(
      id: id ?? this.id,
      elderlyId: elderlyId ?? this.elderlyId,
      type: type ?? this.type,
      value: value ?? this.value,
      measuredAt: measuredAt ?? this.measuredAt,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  // Helper method to get the display name for the health data type
  String get typeDisplayName {
    switch (type) {
      case 'blood_pressure':
        return 'Blood Pressure';
      case 'heart_rate':
        return 'Heart Rate';
      case 'sugar_level':
        return 'Blood Sugar';
      case 'temperature':
        return 'Body Temperature';
      default:
        return 'Health Data';
    }
  }

  // Helper method to get the appropriate unit for the health data type
  String get unit {
    switch (type) {
      case 'blood_pressure':
        return 'mmHg';
      case 'heart_rate':
        return 'bpm';
      case 'sugar_level':
        return 'mg/dL';
      case 'temperature':
        return '°C';
      default:
        return '';
    }
  }

  // Helper method to get formatted value with unit
  String get formattedValue {
    switch (type) {
      case 'blood_pressure':
        // For blood pressure, we might need to handle systolic/diastolic differently
        // For now, we'll just show the value
        return '${value.toInt()} $unit';
      case 'heart_rate':
        return '${value.toInt()} $unit';
      case 'sugar_level':
        return '${value.toInt()} $unit';
      case 'temperature':
        return '${value.toStringAsFixed(1)} $unit';
      default:
        return value.toString();
    }
  }

  // Helper method to check if the value is within normal range
  bool get isNormalRange {
    switch (type) {
      case 'heart_rate':
        return value >= 60 && value <= 100;
      case 'sugar_level':
        return value >= 70 && value <= 140; // Fasting glucose
      case 'temperature':
        return value >= 36.1 && value <= 37.2; // Celsius
      case 'blood_pressure':
        // This is simplified - actual BP has systolic/diastolic
        return value >= 90 && value <= 140; // Systolic range
      default:
        return true;
    }
  }

  // Helper method to get status color based on normal range
  String get statusColor {
    return isNormalRange ? 'green' : 'red';
  }

  @override
  String toString() {
    return 'HealthDataModel(id: $id, type: $type, value: $formattedValue, measuredAt: $measuredAt)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is HealthDataModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}

// Enum for health data types (optional, for type safety)
enum HealthDataType {
  bloodPressure('blood_pressure'),
  heartRate('heart_rate'),
  sugarLevel('sugar_level'),
  temperature('temperature');

  const HealthDataType(this.value);
  final String value;

  static HealthDataType fromString(String value) {
    return HealthDataType.values.firstWhere(
      (type) => type.value == value,
      orElse: () => HealthDataType.heartRate,
    );
  }
}
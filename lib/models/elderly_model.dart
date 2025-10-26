import 'package:cloud_firestore/cloud_firestore.dart';

class ElderlyModel {
  final String id; // Document ID from Firestore
  final String userId; // Reference to users collection
  final String username;
  final String phoneNumber;
  final String sex; // "Male" | "Female" | "Other"
  final int? age;
  final double? weight;
  final double? height;
  final String? caregiverId; // 1:1 relationship
  final EmergencyContact? emergencyContact;
  final MedicalInfo? medicalInfo;
  final bool profileCompleted;
  final DateTime createdAt;

  ElderlyModel({
    required this.id,
    required this.userId,
    required this.username,
    required this.phoneNumber,
    required this.sex,
    this.age,
    this.weight,
    this.height,
    this.caregiverId,
    this.emergencyContact,
    this.medicalInfo,
    this.profileCompleted = false,
    required this.createdAt,
  });

  // Convert to Map for saving to Firestore
  Map<String, dynamic> toMap() {
    return {
      'userId': userId,
      'username': username,
      'phoneNumber': phoneNumber,
      'sex': sex,
      'age': age,
      'weight': weight,
      'height': height,
      'caregiverId': caregiverId,
      'emergencyContact': emergencyContact?.toMap(),
      'medicalInfo': medicalInfo?.toMap(),
      'profileCompleted': profileCompleted,
      'createdAt': Timestamp.fromDate(createdAt),
    };
  }

  // Create ElderlyModel from Firestore document
  factory ElderlyModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return ElderlyModel(
      id: doc.id,
      userId: data['userId'] ?? '',
      username: data['username'] ?? '',
      phoneNumber: data['phoneNumber'] ?? '',
      sex: data['sex'] ?? 'Male',
      age: data['age']?.toInt(),
      weight: data['weight']?.toDouble(),
      height: data['height']?.toDouble(),
      caregiverId: data['caregiverId'],
      emergencyContact: data['emergencyContact'] != null
          ? EmergencyContact.fromMap(data['emergencyContact'])
          : null,
      medicalInfo: data['medicalInfo'] != null
          ? MedicalInfo.fromMap(data['medicalInfo'])
          : null,
      profileCompleted: data['profileCompleted'] ?? false,
      createdAt: (data['createdAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  // Create ElderlyModel from Map
  factory ElderlyModel.fromMap(Map<String, dynamic> map, String id) {
    return ElderlyModel(
      id: id,
      userId: map['userId'] ?? '',
      username: map['username'] ?? '',
      phoneNumber: map['phoneNumber'] ?? '',
      sex: map['sex'] ?? 'Male',
      age: map['age']?.toInt(),
      weight: map['weight']?.toDouble(),
      height: map['height']?.toDouble(),
      caregiverId: map['caregiverId'],
      emergencyContact: map['emergencyContact'] != null
          ? EmergencyContact.fromMap(map['emergencyContact'])
          : null,
      medicalInfo: map['medicalInfo'] != null
          ? MedicalInfo.fromMap(map['medicalInfo'])
          : null,
      profileCompleted: map['profileCompleted'] ?? false,
      createdAt: (map['createdAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  // Copy with method for updating fields
  ElderlyModel copyWith({
    String? id,
    String? userId,
    String? username,
    String? phoneNumber,
    String? sex,
    int? age,
    double? weight,
    double? height,
    String? caregiverId,
    EmergencyContact? emergencyContact,
    MedicalInfo? medicalInfo,
    bool? profileCompleted,
    DateTime? createdAt,
  }) {
    return ElderlyModel(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      username: username ?? this.username,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      sex: sex ?? this.sex,
      age: age ?? this.age,
      weight: weight ?? this.weight,
      height: height ?? this.height,
      caregiverId: caregiverId ?? this.caregiverId,
      emergencyContact: emergencyContact ?? this.emergencyContact,
      medicalInfo: medicalInfo ?? this.medicalInfo,
      profileCompleted: profileCompleted ?? this.profileCompleted,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  @override
  String toString() {
    return 'ElderlyModel(id: $id, username: $username, profileCompleted: $profileCompleted)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is ElderlyModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}

class EmergencyContact {
  final String name;
  final String phone;
  final String relationship;

  EmergencyContact({
    required this.name,
    required this.phone,
    required this.relationship,
  });

  Map<String, dynamic> toMap() {
    return {
      'name': name,
      'phone': phone,
      'relationship': relationship,
    };
  }

  factory EmergencyContact.fromMap(Map<String, dynamic> map) {
    return EmergencyContact(
      name: map['name'] ?? '',
      phone: map['phone'] ?? '',
      relationship: map['relationship'] ?? '',
    );
  }
}

class MedicalInfo {
  final List<String> conditions;
  final List<String> medications;
  final List<String> allergies;

  MedicalInfo({
    this.conditions = const [],
    this.medications = const [],
    this.allergies = const [],
  });

  Map<String, dynamic> toMap() {
    return {
      'conditions': conditions,
      'medications': medications,
      'allergies': allergies,
    };
  }

  factory MedicalInfo.fromMap(Map<String, dynamic> map) {
    return MedicalInfo(
      conditions: List<String>.from(map['conditions'] ?? []),
      medications: List<String>.from(map['medications'] ?? []),
      allergies: List<String>.from(map['allergies'] ?? []),
    );
  }
}
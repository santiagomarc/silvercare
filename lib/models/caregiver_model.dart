import 'package:cloud_firestore/cloud_firestore.dart';

class CaregiverModel {
  final String id; // Document ID from Firestore
  final String userId; // Reference to users collection
  final String email;
  final String? fullName; // Caregiver's full name
  final String? elderlyId; // 1:1 relationship
  final String relationship; // "Spouse" | "Child" | "Professional Caregiver"
  final DateTime createdAt;

  CaregiverModel({
    required this.id,
    required this.userId,
    required this.email,
    this.fullName,
    this.elderlyId,
    required this.relationship,
    required this.createdAt,
  });

  // Convert to Map for saving to Firestore
  Map<String, dynamic> toMap() {
    return {
      'userId': userId,
      'email': email,
      'fullName': fullName,
      'elderlyId': elderlyId,
      'relationship': relationship,
      'createdAt': Timestamp.fromDate(createdAt),
    };
  }

  // Create CaregiverModel from Firestore document
  factory CaregiverModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return CaregiverModel(
      id: doc.id,
      userId: data['userId'] ?? '',
      email: data['email'] ?? '',
      fullName: data['fullName'],
      elderlyId: data['elderlyId'],
      relationship: data['relationship'] ?? 'Professional Caregiver',
      createdAt: (data['createdAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  // Create CaregiverModel from Map
  factory CaregiverModel.fromMap(Map<String, dynamic> map, String id) {
    return CaregiverModel(
      id: id,
      userId: map['userId'] ?? '',
      email: map['email'] ?? '',
      fullName: map['fullName'],
      elderlyId: map['elderlyId'],
      relationship: map['relationship'] ?? 'Professional Caregiver',
      createdAt: (map['createdAt'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  // Copy with method for updating fields
  CaregiverModel copyWith({
    String? id,
    String? userId,
    String? email,
    String? fullName,
    String? elderlyId,
    String? relationship,
    DateTime? createdAt,
  }) {
    return CaregiverModel(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      email: email ?? this.email,
      fullName: fullName ?? this.fullName,
      elderlyId: elderlyId ?? this.elderlyId,
      relationship: relationship ?? this.relationship,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  // Helper method to check if caregiver is assigned to an elderly
  bool get isAssigned => elderlyId != null && elderlyId!.isNotEmpty;

  // Helper method to get relationship display name
  String get relationshipDisplayName {
    switch (relationship) {
      case 'Spouse':
        return 'Spouse';
      case 'Child':
        return 'Adult Child';
      case 'Professional Caregiver':
        return 'Professional Caregiver';
      default:
        return 'Caregiver';
    }
  }

  @override
  String toString() {
    return 'CaregiverModel(id: $id, email: $email, relationship: $relationship, isAssigned: $isAssigned)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is CaregiverModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
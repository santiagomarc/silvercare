import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';

enum UserType { elderly, caregiver, unknown }

class UserService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Determines the user type by checking which collection contains the current user's ID
  static Future<UserType> getUserType([String? userId]) async {
    try {
      final String uid = userId ?? _auth.currentUser?.uid ?? '';
      
      if (uid.isEmpty) {
        print('UserService: No user ID provided or user not authenticated');
        return UserType.unknown;
      }

      print('UserService: Checking user type for ID: $uid');

      // Check if user exists in elderly collection
      final elderlyDoc = await _firestore.collection('elderly').doc(uid).get();
      if (elderlyDoc.exists) {
        print('UserService: User found in elderly collection');
        return UserType.elderly;
      }

      // Check if user exists in caregivers collection
      final caregiverDoc = await _firestore.collection('caregivers').doc(uid).get();
      if (caregiverDoc.exists) {
        print('UserService: User found in caregivers collection');
        return UserType.caregiver;
      }

      print('UserService: User not found in any specific collection');
      return UserType.unknown;
      
    } catch (e) {
      print('UserService: Error determining user type: $e');
      return UserType.unknown;
    }
  }

  /// Gets user profile data based on user type
  static Future<Map<String, dynamic>?> getUserProfile([String? userId]) async {
    try {
      final String uid = userId ?? _auth.currentUser?.uid ?? '';
      
      if (uid.isEmpty) {
        return null;
      }

      final userType = await getUserType(uid);
      
      switch (userType) {
        case UserType.elderly:
          final elderlyDoc = await _firestore.collection('elderly').doc(uid).get();
          if (elderlyDoc.exists) {
            final data = elderlyDoc.data() as Map<String, dynamic>;
            data['userType'] = 'elderly';
            data['id'] = elderlyDoc.id;
            return data;
          }
          break;
          
        case UserType.caregiver:
          final caregiverDoc = await _firestore.collection('caregivers').doc(uid).get();
          if (caregiverDoc.exists) {
            final data = caregiverDoc.data() as Map<String, dynamic>;
            data['userType'] = 'caregiver';
            data['id'] = caregiverDoc.id;
            return data;
          }
          break;
          
        case UserType.unknown:
          return null;
      }
      
      return null;
      
    } catch (e) {
      print('UserService: Error getting user profile: $e');
      return null;
    }
  }
  // todo: incomplete profiles are routed to a profile-completion flow.
  /// Check if the current user has completed their profile
  static Future<bool> isProfileCompleted([String? userId]) async {
    try {
      final userType = await getUserType(userId);
      final String uid = userId ?? _auth.currentUser?.uid ?? '';
      
      switch (userType) {
        case UserType.elderly:
          final elderlyDoc = await _firestore.collection('elderly').doc(uid).get();
          if (elderlyDoc.exists) {
            final data = elderlyDoc.data() as Map<String, dynamic>;
            return data['profileCompleted'] ?? false;
          }
          break;
          
        case UserType.caregiver:
          // For caregivers, we can check if they have a relationship set
          final caregiverDoc = await _firestore.collection('caregivers').doc(uid).get();
          if (caregiverDoc.exists) {
            final data = caregiverDoc.data() as Map<String, dynamic>;
            return data['relationship'] != null && data['relationship'].toString().isNotEmpty;
          }
          break;
          
        case UserType.unknown:
          return false;
      }
      
      return false;
      
    } catch (e) {
      print('UserService: Error checking profile completion: $e');
      return false;
    }
  }

  /// Get the appropriate route for the user based on their type
  static Future<String> getUserHomeRoute([String? userId]) async {
    final userType = await getUserType(userId);
    
    switch (userType) {
      case UserType.elderly:
        return '/main'; // MainScreen with elderly navigation
      case UserType.caregiver:
        return '/caregiver'; // CaregiverScreen
      case UserType.unknown:
        return '/signin'; // Back to sign in if user type unknown
    }
  }
}
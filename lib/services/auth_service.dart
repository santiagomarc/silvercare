import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/models.dart';

class AuthService {
  static final FirebaseAuth _auth = FirebaseAuth.instance;
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  // Get current user
  static User? get currentUser => _auth.currentUser;
  
  // Auth state changes stream
  static Stream<User?> get authStateChanges => _auth.authStateChanges();

  // Get user data by ID
  static Future<UserModel?> getUserById(String userId) async {
    try {
      DocumentSnapshot doc = await _firestore.collection('users').doc(userId).get();
      if (doc.exists) {
        return UserModel.fromDoc(doc);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Get elderly data by user ID
  static Future<ElderlyModel?> getElderlyByUserId(String userId) async {
    try {
      DocumentSnapshot doc = await _firestore.collection('elderly').doc(userId).get();
      if (doc.exists) {
        return ElderlyModel.fromDoc(doc);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Get caregiver data by user ID
  static Future<CaregiverModel?> getCaregiverByUserId(String userId) async {
    try {
      DocumentSnapshot doc = await _firestore.collection('caregivers').doc(userId).get();
      if (doc.exists) {
        return CaregiverModel.fromDoc(doc);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Sign out
  static Future<void> signOut() async {
    try {
      await _auth.signOut();
    } catch (e) {
      rethrow;
    }
  }

  // Update last login time
  static Future<void> updateLastLogin(String userId) async {
    try {
      await _firestore.collection('users').doc(userId).update({
        'lastLoginAt': FieldValue.serverTimestamp(),
      });
    } catch (e) {
      // Silently fail - not critical
    }
  }
}
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

  // Check if user is signed in
  static bool get isSignedIn => _auth.currentUser != null;

  // Sign in anonymously for demo purposes
  static Future<User?> signInAnonymously() async {
    try {
      final UserCredential result = await _auth.signInAnonymously();
      print('✅ Signed in anonymously: ${result.user?.uid}');
      return result.user;
    } on FirebaseAuthException catch (e) {
      print('❌ Firebase auth error: ${e.code} - ${e.message}');
      throw Exception('Authentication failed: ${e.message}');
    } catch (error) {
      print('❌ Error signing in anonymously: $error');
      throw Exception('Authentication failed: $error');
    }
  }

  // Ensure user is authenticated (sign in anonymously if not)
  static Future<User> ensureAuthenticated() async {
    final user = _auth.currentUser;
    if (user != null) {
      return user;
    }
    
    // Sign in anonymously if not authenticated
    final newUser = await signInAnonymously();
    if (newUser == null) {
      throw Exception('Failed to authenticate user');
    }
    return newUser;
  }

  // Get user ID or throw error
  static String get userId {
    final user = _auth.currentUser;
    if (user == null) {
      throw Exception('User not authenticated');
    }
    return user.uid;
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
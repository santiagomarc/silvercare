import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';

class MoodRecord {
  final String mood;
  final String emoji;
  final int moodLevel; // 0-4 scale
  final DateTime timestamp;
  final String date; // YYYY-MM-DD format for easy querying

  MoodRecord({
    required this.mood,
    required this.emoji,
    required this.moodLevel,
    required this.timestamp,
    required this.date,
  });

  Map<String, dynamic> toMap() {
    return {
      'mood': mood,
      'emoji': emoji,
      'moodLevel': moodLevel,
      'timestamp': Timestamp.fromDate(timestamp),
      'date': date,
    };
  }

  factory MoodRecord.fromMap(Map<String, dynamic> map) {
    return MoodRecord(
      mood: map['mood'] ?? '',
      emoji: map['emoji'] ?? '😐',
      moodLevel: map['moodLevel'] ?? 2,
      timestamp: (map['timestamp'] as Timestamp?)?.toDate() ?? DateTime.now(),
      date: map['date'] ?? '',
    );
  }
}

class MoodService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  // Save mood data automatically
  static Future<bool> saveMoodData({
    required String mood,
    required String emoji,
    required int moodLevel,
  }) async {
    try {
      final user = _auth.currentUser;
      if (user == null) return false;

      final now = DateTime.now();
      final dateString = "${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}";

      final moodRecord = MoodRecord(
        mood: mood,
        emoji: emoji,
        moodLevel: moodLevel,
        timestamp: now,
        date: dateString,
      );

      // Save to user's mood subcollection
      await _firestore
          .collection('elders')
          .doc(user.uid)
          .collection('moods')
          .doc(dateString)
          .set(moodRecord.toMap(), SetOptions(merge: true));

      return true;
    } catch (e) {
      print('Error saving mood data: $e');
      return false;
    }
  }

  // Get today's mood
  static Future<MoodRecord?> getTodayMood() async {
    try {
      final user = _auth.currentUser;
      if (user == null) return null;

      final now = DateTime.now();
      final dateString = "${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}";

      final doc = await _firestore
          .collection('elders')
          .doc(user.uid)
          .collection('moods')
          .doc(dateString)
          .get();

      if (doc.exists) {
        return MoodRecord.fromMap(doc.data()!);
      }
      return null;
    } catch (e) {
      print('Error getting today\'s mood: $e');
      return null;
    }
  }

  // Get mood history (for caregivers)
  static Future<List<MoodRecord>> getMoodHistory({int days = 7}) async {
    try {
      final user = _auth.currentUser;
      if (user == null) return [];

      final endDate = DateTime.now();
      final startDate = endDate.subtract(Duration(days: days));

      final querySnapshot = await _firestore
          .collection('elders')
          .doc(user.uid)
          .collection('moods')
          .where('timestamp', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
          .where('timestamp', isLessThanOrEqualTo: Timestamp.fromDate(endDate))
          .orderBy('timestamp', descending: true)
          .get();

      return querySnapshot.docs
          .map((doc) => MoodRecord.fromMap(doc.data()))
          .toList();
    } catch (e) {
      print('Error getting mood history: $e');
      return [];
    }
  }
}
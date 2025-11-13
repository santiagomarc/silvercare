import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:silvercare/models/notification_model.dart';

/// Service for managing persistent event-based notifications in Firestore
/// These are historical records of events (dose taken, task completed, etc.)
/// Different from local push notifications (flutter_local_notifications)
class PersistentNotificationService {
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;
  
  static const String _collection = 'notifications';
  static const int _retentionDays = 30;
  
  /// Get current user's elderly ID
  String? get _currentElderlyId => _auth.currentUser?.uid;
  
  /// Test function to verify Firestore connection
  Future<void> testNotificationCreation() async {
    if (_currentElderlyId == null) {
      print('❌ No user logged in');
      return;
    }
    
    try {
      print('🧪 Testing notification creation...');
      print('   Current user ID: $_currentElderlyId');
      
      final testNotification = NotificationModel(
        id: '',
        elderlyId: _currentElderlyId!,
        type: 'test',
        title: 'Test Notification',
        message: 'This is a test notification',
        timestamp: DateTime.now(),
        severity: 'reminder',
        metadata: {'test': true},
      );
      
      await _addNotification(testNotification);
      print('✅ Test notification created successfully!');
    } catch (e) {
      print('❌ Test failed: $e');
      print('Stack trace: ${StackTrace.current}');
    }
  }
  
  // ============ CREATE NOTIFICATION EVENTS ============
  
  /// General method to create any notification
  Future<void> createNotification({
    required String title,
    required String message,
    required String severity,
    String? type,
    Map<String, dynamic>? metadata,
    String? customId,
  }) async {
    if (_currentElderlyId == null) return;
    
    final notification = NotificationModel(
      id: '',
      elderlyId: _currentElderlyId!,
      type: type ?? 'general',
      title: title,
      message: message,
      timestamp: DateTime.now(),
      severity: severity,
      metadata: metadata ?? {},
      customId: customId,
    );
    
    await _addNotification(notification);
  }
  
  /// Create a medication reminder notification (15 min before dose)
  Future<void> createMedicationReminder({
    required String elderlyId,
    required String medicationName,
    required DateTime scheduledTime,
    required String medicationId,
  }) async {
    final notification = NotificationModel(
      id: '', // Firestore will generate
      elderlyId: elderlyId,
      type: 'medication_reminder',
      title: '⏰ Upcoming Medication',
      message: '$medicationName - Due in 15 minutes',
      timestamp: DateTime.now(),
      severity: 'reminder', // Blue
      metadata: {
        'medicationId': medicationId,
        'scheduledTime': scheduledTime.toIso8601String(),
        'medicationName': medicationName,
      },
    );
    
    await _addNotification(notification);
  }
  
  /// Create a medication missed notification (1 hour after scheduled time)
  Future<void> createMedicationMissed({
    required String elderlyId,
    required String medicationName,
    required DateTime scheduledTime,
    required String medicationId,
  }) async {
    final notification = NotificationModel(
      id: '',
      elderlyId: elderlyId,
      type: 'medication_missed',
      title: '⚠️ Missed Medication',
      message: '$medicationName (scheduled for ${_formatTime(scheduledTime)}) was not taken',
      timestamp: DateTime.now(),
      severity: 'negative', // Red
      metadata: {
        'medicationId': medicationId,
        'scheduledTime': scheduledTime.toIso8601String(),
        'medicationName': medicationName,
      },
    );
    
    await _addNotification(notification);
  }
  
  /// Create a medication taken notification (when dose is marked as taken)
  /// Returns the notification ID so it can be deleted/updated if undone
  Future<String?> createMedicationTaken({
    required String elderlyId,
    required String medicationName,
    required DateTime scheduledTime,
    required DateTime takenAt,
    required bool isTakenLate,
    required String medicationId,
  }) async {
    final minutesLate = takenAt.difference(scheduledTime).inMinutes;
    final isLate = isTakenLate && minutesLate > 0;
    
    final notification = NotificationModel(
      id: '',
      elderlyId: elderlyId,
      type: 'medication_taken',
      title: isLate ? '✓ Medication Taken (Late)' : '✓ Medication Taken',
      message: isLate
          ? '$medicationName taken at ${_formatTime(takenAt)} ($minutesLate min late)'
          : '$medicationName taken on time at ${_formatTime(takenAt)}',
      timestamp: takenAt,
      severity: 'positive', // Green (even if late, it's still positive they took it)
      metadata: {
        'medicationId': medicationId,
        'scheduledTime': scheduledTime.toIso8601String(),
        'takenAt': takenAt.toIso8601String(),
        'medicationName': medicationName,
        'isLate': isLate,
        'minutesLate': minutesLate,
      },
    );
    
    return await _addNotificationWithId(notification);
  }
  
  /// Create a task completed notification
  /// Returns the notification ID so it can be deleted/updated if undone
  Future<String?> createTaskCompleted({
    required String elderlyId,
    required String taskName,
    required String category,
    required DateTime completedAt,
    required String taskId,
  }) async {
    final notification = NotificationModel(
      id: '',
      elderlyId: elderlyId,
      type: 'task_completed',
      title: '✓ Task Completed',
      message: '$taskName completed successfully',
      timestamp: completedAt,
      severity: 'positive', // Green
      metadata: {
        'taskId': taskId,
        'taskName': taskName,
        'category': category,
        'completedAt': completedAt.toIso8601String(),
      },
    );
    
    return await _addNotificationWithId(notification);
  }
  
  /// Create a task overdue notification
  Future<void> createTaskOverdue({
    required String elderlyId,
    required String taskName,
    required String category,
    required DateTime dueDate,
    required String taskId,
  }) async {
    final notification = NotificationModel(
      id: '',
      elderlyId: elderlyId,
      type: 'task_overdue',
      title: '⚠️ Overdue Task',
      message: '$taskName was due at ${_formatTime(dueDate)}',
      timestamp: DateTime.now(),
      severity: 'negative', // Red
      metadata: {
        'taskId': taskId,
        'taskName': taskName,
        'category': category,
        'dueDate': dueDate.toIso8601String(),
      },
    );
    
    await _addNotification(notification);
  }
  
  // ============ READ NOTIFICATIONS ============
  
  /// Get all notifications for current user (last 30 days)
  Stream<List<NotificationModel>> getNotifications() {
    if (_currentElderlyId == null) return Stream.value([]);
    
    final cutoffDate = DateTime.now().subtract(const Duration(days: _retentionDays));
    
    // Simplified query - no composite index needed
    // Filter by elderlyId only, then filter by date in Dart
    return _firestore
        .collection(_collection)
        .where('elderlyId', isEqualTo: _currentElderlyId)
        .orderBy('timestamp', descending: true)
        .snapshots()
        .map((snapshot) {
          // Filter out old notifications in Dart
          return snapshot.docs
              .map((doc) => NotificationModel.fromDoc(doc))
              .where((notif) => notif.timestamp.isAfter(cutoffDate))
              .toList();
        })
        .handleError((error) {
          print('❌ Error in getNotifications stream: $error');
          return <NotificationModel>[];
        });
  }
  
  /// Get notifications for specific elderly (for caregiver view)
  Stream<List<NotificationModel>> getNotificationsForElderly(String elderlyId) {
    final cutoffDate = DateTime.now().subtract(const Duration(days: _retentionDays));
    
    // Simplified query - no composite index needed
    // Filter by elderlyId only, then filter by date in Dart
    return _firestore
        .collection(_collection)
        .where('elderlyId', isEqualTo: elderlyId)
        .orderBy('timestamp', descending: true)
        .snapshots()
        .map((snapshot) {
          // Filter out old notifications in Dart
          return snapshot.docs
              .map((doc) => NotificationModel.fromDoc(doc))
              .where((notif) => notif.timestamp.isAfter(cutoffDate))
              .toList();
        })
        .handleError((error) {
          print('❌ Error in getNotificationsForElderly stream: $error');
          return <NotificationModel>[];
        });
  }
  
  /// Mark notification as read
  Future<void> markAsRead(String notificationId) async {
    await _firestore
        .collection(_collection)
        .doc(notificationId)
        .update({'isRead': true});
  }
  
  /// Mark all notifications as read for current user
  Future<void> markAllAsRead() async {
    if (_currentElderlyId == null) return;
    
    final batch = _firestore.batch();
    final unreadDocs = await _firestore
        .collection(_collection)
        .where('elderlyId', isEqualTo: _currentElderlyId)
        .where('isRead', isEqualTo: false)
        .get();
    
    for (var doc in unreadDocs.docs) {
      batch.update(doc.reference, {'isRead': true});
    }
    
    await batch.commit();
  }
  
  /// Get unread count for badge
  Stream<int> getUnreadCount() {
    if (_currentElderlyId == null) return Stream.value(0);
    
    return _firestore
        .collection(_collection)
        .where('elderlyId', isEqualTo: _currentElderlyId)
        .where('isRead', isEqualTo: false)
        .snapshots()
        .map((snapshot) => snapshot.docs.length);
  }
  
  // ============ DELETE/CLEANUP ============
  
  /// Delete a specific notification (for undo functionality)
  Future<void> deleteNotification(String notificationId) async {
    try {
      await _firestore.collection(_collection).doc(notificationId).delete();
      print('🗑️ Notification deleted: $notificationId');
    } catch (e) {
      print('❌ Error deleting notification: $e');
    }
  }
  
  /// Delete notifications older than 30 days (call this periodically)
  Future<void> cleanupOldNotifications() async {
    if (_currentElderlyId == null) return;
    
    final cutoffDate = DateTime.now().subtract(const Duration(days: _retentionDays));
    
    final oldDocs = await _firestore
        .collection(_collection)
        .where('elderlyId', isEqualTo: _currentElderlyId)
        .where('timestamp', isLessThan: Timestamp.fromDate(cutoffDate))
        .get();
    
    final batch = _firestore.batch();
    for (var doc in oldDocs.docs) {
      batch.delete(doc.reference);
    }
    
    await batch.commit();
    print('🗑️ Cleaned up ${oldDocs.docs.length} old notifications');
  }
  
  // ============ PRIVATE HELPERS ============
  
  Future<void> _addNotification(NotificationModel notification) async {
    try {
      final docRef = await _firestore.collection(_collection).add(notification.toMap());
      print('📬 Notification created: ${notification.title} (ID: ${docRef.id})');
    } catch (e) {
      print('❌ Error creating notification: $e');
      print('Stack trace: ${StackTrace.current}');
      rethrow;
    }
  }
  
  /// Add notification and return the document ID (for undo functionality)
  Future<String?> _addNotificationWithId(NotificationModel notification) async {
    try {
      final docRef = await _firestore.collection(_collection).add(notification.toMap());
      print('📬 Notification created: ${notification.title} (ID: ${docRef.id})');
      return docRef.id;
    } catch (e) {
      print('❌ Error creating notification: $e');
      print('Stack trace: ${StackTrace.current}');
      return null;
    }
  }
  
  String _formatTime(DateTime dateTime) {
    final hour = dateTime.hour > 12 ? dateTime.hour - 12 : (dateTime.hour == 0 ? 12 : dateTime.hour);
    final period = dateTime.hour >= 12 ? 'PM' : 'AM';
    return '${hour.toString().padLeft(2, '0')}:${dateTime.minute.toString().padLeft(2, '0')} $period';
  }
}

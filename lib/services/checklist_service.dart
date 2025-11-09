import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
// Assuming your models are structured as defined previously
import 'package:silvercare/models/checklist_item_model.dart';
import 'package:silvercare/services/persistent_notification_service.dart';

class ChecklistService {
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final PersistentNotificationService _notificationService = PersistentNotificationService();

  static const String _checklistCollection = 'elderly_checklists';
  
  String get _elderlyId => _auth.currentUser?.uid ?? '';

  // --- Caregiver CRUD Operations (Managing Tasks) ---

  /// Adds a new checklist item.
  Future<String?> addChecklistItem(ChecklistItemModel model) async {
    // Note: No need to check _elderlyId here since caregiver is adding for the elderly
    try {
      print('📋 Adding checklist item for elderlyId: ${model.elderlyId}');
      print('📋 Task: ${model.task}, Category: ${model.category}, Due: ${model.dueDate}');
      final docRef = await _firestore
          .collection(_checklistCollection)
          .add(model.toMap());
      print('✅ Checklist item added with ID: ${docRef.id}');
      return docRef.id;
    } catch (e) {
      print('❌ Error adding checklist item: $e');
      return null;
    }
  }

  /// Updates an existing checklist item.
  Future<void> updateChecklistItem(ChecklistItemModel model) async {
    if (_elderlyId.isEmpty) return;
    try {
      await _firestore
          .collection(_checklistCollection)
          .doc(model.id)
          .update(model.toMap());
    } catch (e) {
      print('Error updating checklist item: $e');
    }
  }

  /// Deletes a checklist item.
  Future<void> deleteChecklistItem(String itemId) async {
    try {
      await _firestore.collection(_checklistCollection).doc(itemId).delete();
    } catch (e) {
      print('Error deleting checklist item: $e');
    }
  }

  // --- Elder/Home Screen Streams (Viewing Today's Tasks) ---

  /// Stream of all active checklist items for today.
  Stream<List<ChecklistItemModel>> getTodayChecklist() {
    if (_elderlyId.isEmpty) {
      print('⚠️ Cannot fetch checklist: No elderlyId (user not logged in)');
      return Stream.value([]);
    }
    
    print('🔍 Fetching checklist for elderlyId: $_elderlyId');
    final DateTime now = DateTime.now();
    final DateTime startOfDay = DateTime(now.year, now.month, now.day); 

    return _firestore
        .collection(_checklistCollection)
        .where('elderlyId', isEqualTo: _elderlyId)
        .snapshots()
        .map((snapshot) {
          print('📦 Received ${snapshot.docs.length} checklist items from Firestore');
          final tasks = snapshot.docs
              .map((doc) => ChecklistItemModel.fromDoc(doc))
              .toList();
          tasks.sort((a, b) => a.dueDate.compareTo(b.dueDate));
          
          final pendingTasks = tasks.where((task) {
            return task.dueDate.isAfter(startOfDay) ||
                   task.dueDate.isAtSameMomentAs(startOfDay);
          }).toList();

          pendingTasks.sort((a, b) => a.dueDate.compareTo(b.dueDate));
          print('✅ Returning ${pendingTasks.length} tasks for today');
          return pendingTasks;
        });
  }

  /// Stream of ALL checklist items for a specific elderly (for caregiver use).
  /// This allows caregivers to view and manage all checklist items.
  Stream<List<ChecklistItemModel>> getChecklistItemsForElderly(String elderlyId) {
    if (elderlyId.isEmpty) {
      print('⚠️ Cannot fetch checklist: elderlyId is empty');
      return Stream.value([]);
    }
    
    print('🔍 Caregiver fetching checklist for elderlyId: $elderlyId');
    return _firestore
        .collection(_checklistCollection)
        .where('elderlyId', isEqualTo: elderlyId)
        .orderBy('dueDate', descending: false)
        .snapshots()
        .map((snapshot) {
          print('📦 Found ${snapshot.docs.length} checklist items for elderly $elderlyId');
          return snapshot.docs
              .map((doc) => ChecklistItemModel.fromDoc(doc))
              .toList();
        });
  }

  // --- Elder Action ---

  /// Elder marks a task as complete or incomplete.
  Future<void> updateTaskStatus(String itemId, bool isCompleted) async {
    if (_elderlyId.isEmpty) return;

    try {
      final completedAt = isCompleted ? DateTime.now() : null;
      
      // Get the task document first to check for existing notification
      final taskDoc = await _firestore.collection(_checklistCollection).doc(itemId).get();
      if (!taskDoc.exists) {
        print('⚠️ Task document not found: $itemId');
        return;
      }
      
      final task = ChecklistItemModel.fromDoc(taskDoc);
      
      // Handle completing vs uncompleting
      if (isCompleted && completedAt != null) {
        // COMPLETING - Create notification and store ID
        print('📝 Creating notification for task: ${task.task}...');
        print('   elderlyId: $_elderlyId');
        
        final notificationId = await _notificationService.createTaskCompleted(
          elderlyId: _elderlyId,
          taskName: task.task,
          category: task.category,
          completedAt: completedAt,
          taskId: itemId,
        );
        
        // Update task with completion and notification ID
        await _firestore.collection(_checklistCollection).doc(itemId).update({
          'isCompleted': true,
          'completedAt': Timestamp.fromDate(completedAt),
          'notificationId': notificationId,
        });
        
        print('✅ Task marked complete with notification');
      } else {
        // UNCOMPLETING - Delete notification if it exists
        final data = taskDoc.data();
        final notificationId = data?['notificationId'] as String?;
        
        if (notificationId != null) {
          await _notificationService.deleteNotification(notificationId);
          print('🗑️ Deleted notification for uncompleted task');
        }
        
        // Update task to mark as incomplete
        await _firestore.collection(_checklistCollection).doc(itemId).update({
          'isCompleted': false,
          'completedAt': null,
          'notificationId': null,
        });
        
        print('✅ Task marked as incomplete, notification removed');
      }
    } catch (e) {
      print('❌ Error updating task status: $e');
      print('Stack trace: ${StackTrace.current}');
    }
  }
}
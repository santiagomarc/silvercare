import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
// Assuming your models are structured as defined previously
import 'package:silvercare/models/checklist_item_model.dart';

class ChecklistService {
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final FirebaseAuth _auth = FirebaseAuth.instance;

  static const String _checklistCollection = 'elderly_checklists';
  
  String get _elderlyId => _auth.currentUser?.uid ?? '';

  // --- Caregiver CRUD Operations (Managing Tasks) ---

  /// Adds a new checklist item.
  Future<String?> addChecklistItem(ChecklistItemModel model) async {
    if (_elderlyId.isEmpty) return null;
    try {
      final docRef = await _firestore
          .collection(_checklistCollection)
          .add(model.toMap());
      return docRef.id;
    } catch (e) {
      print('Error adding checklist item: $e');
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
    if (_elderlyId.isEmpty) return Stream.value([]);
    
    final DateTime now = DateTime.now();
    // Tasks due today or in the future
    final DateTime startOfDay = DateTime(now.year, now.month, now.day); 

    return _firestore
        .collection(_checklistCollection)
        .where('elderlyId', isEqualTo: _elderlyId)
        .snapshots()
        .map((snapshot) {
          final tasks = snapshot.docs
              .map((doc) => ChecklistItemModel.fromDoc(doc))
              .toList();
          tasks.sort((a, b) => a.dueDate.compareTo(b.dueDate));
          
          final pendingTasks = tasks.where((task) {
            return task.dueDate.isAfter(startOfDay) ||
                   task.dueDate.isAtSameMomentAs(startOfDay);
          }).toList();

          pendingTasks.sort((a, b) => a.dueDate.compareTo(b.dueDate));
          return pendingTasks;
        });
  }

  // --- Elder Action ---

  /// Elder marks a task as complete or incomplete.
  Future<void> updateTaskStatus(String itemId, bool isCompleted) async {
    if (_elderlyId.isEmpty) return;

    try {
      await _firestore.collection(_checklistCollection).doc(itemId).update({
        'isCompleted': isCompleted,
        'completedAt': isCompleted ? Timestamp.fromDate(DateTime.now()) : null,
      });
      print('✅ Checklist item $itemId completion status updated to $isCompleted');
    } catch (e) {
      print('Error updating task status: $e');
    }
  }
}
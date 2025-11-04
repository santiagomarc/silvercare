import 'package:cloud_firestore/cloud_firestore.dart';

class ChecklistItemModel {
  final String id; // Firestore document ID
  final String elderlyId; // Who this task is for
  final String caregiverId; // Who created this task
  
  final String task; // e.g., "Drink a glass of water"
  final String category; // e.g., "Morning", "Health"
  
  final DateTime createdAt;
  final DateTime dueDate; // The date this task is for
  
  bool isCompleted;
  DateTime? completedAt;

  ChecklistItemModel({
    required this.id,
    required this.elderlyId,
    required this.caregiverId,
    required this.task,
    this.category = 'General',
    required this.createdAt,
    required this.dueDate,
    this.isCompleted = false,
    this.completedAt,
  });

  Map<String, dynamic> toMap() {
    return {
      'elderlyId': elderlyId,
      'caregiverId': caregiverId,
      'task': task,
      'category': category,
      'createdAt': Timestamp.fromDate(createdAt),
      'dueDate': Timestamp.fromDate(dueDate),
      'isCompleted': isCompleted,
      'completedAt': completedAt != null ? Timestamp.fromDate(completedAt!) : null,
    };
  }

  factory ChecklistItemModel.fromDoc(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>;
    return ChecklistItemModel(
      id: doc.id,
      elderlyId: data['elderlyId'] ?? '',
      caregiverId: data['caregiverId'] ?? '',
      task: data['task'] ?? '',
      category: data['category'] ?? 'General',
      createdAt: (data['createdAt'] as Timestamp).toDate(),
      dueDate: (data['dueDate'] as Timestamp).toDate(),
      isCompleted: data['isCompleted'] ?? false,
      completedAt: (data['completedAt'] as Timestamp?)?.toDate(),
    );
  }
}
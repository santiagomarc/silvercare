import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:intl/intl.dart';
import 'package:silvercare/models/checklist_item_model.dart';
import 'package:silvercare/services/checklist_service.dart';
import 'package:silvercare/services/notification_service.dart';

class AddChecklistScreen extends StatefulWidget {
  final String elderlyId;
  const AddChecklistScreen({super.key, required this.elderlyId});

  @override
  State<AddChecklistScreen> createState() => _AddChecklistScreenState();
}

class _AddChecklistScreenState extends State<AddChecklistScreen> {
  final _formKey = GlobalKey<FormState>();
  final ChecklistService _checklistService = ChecklistService();
  final NotificationService _notificationService = NotificationService();
  final String _caregiverId = FirebaseAuth.instance.currentUser?.uid ?? 'unknown';

  // Form Fields
  String _task = '';
  String _category = 'General';
  DateTime _dueDate = DateTime.now();
  TimeOfDay _dueTime = TimeOfDay.now();

  final List<String> _categories = [
    'General',
    'Morning',
    'Afternoon',
    'Evening',
    'Health',
    'Medication',
    'Exercise',
    'Meals',
    'Hydration',
    'Personal Care',
  ];

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.9, 1.3);
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _dueDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Colors.blue.shade600,
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _dueDate) {
      setState(() {
        _dueDate = picked;
      });
    }
  }

  Future<void> _selectTime() async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: _dueTime,
      builder: (context, child) {
        return MediaQuery(
          data: MediaQuery.of(context).copyWith(alwaysUse24HourFormat: false),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _dueTime) {
      setState(() {
        _dueTime = picked;
      });
    }
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    _formKey.currentState!.save();

    // Combine date and time
    final DateTime fullDueDate = DateTime(
      _dueDate.year,
      _dueDate.month,
      _dueDate.day,
      _dueTime.hour,
      _dueTime.minute,
    );

    // Create the checklist item model
    final newChecklistItem = ChecklistItemModel(
      id: '', // Firestore will assign this
      elderlyId: widget.elderlyId,
      caregiverId: _caregiverId,
      task: _task,
      category: _category,
      createdAt: DateTime.now(),
      dueDate: fullDueDate,
      isCompleted: false,
    );

    try {
      // Save to Firestore
      final docId = await _checklistService.addChecklistItem(newChecklistItem);

      if (docId != null) {
        // Schedule notification for this task
        await _scheduleTaskNotification(docId, fullDueDate);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ Task "$_task" added successfully!'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.pop(context);
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Failed to add task: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _scheduleTaskNotification(String taskId, DateTime dueDate) async {
    // Schedule notification 15 minutes before the task is due
    final notificationTime = dueDate.subtract(const Duration(minutes: 15));

    // Only schedule if the notification time is in the future
    if (notificationTime.isAfter(DateTime.now())) {
      await _notificationService.scheduleNotification(
        id: taskId.hashCode, // Use task ID hash as notification ID
        title: '📋 Task Reminder',
        body: 'Upcoming task: $_task',
        scheduledDate: notificationTime,
        payload: 'checklist_$taskId',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final titleFontSize = _getResponsiveFontSize(context, 24);
    final labelFontSize = _getResponsiveFontSize(context, 16);

    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      appBar: AppBar(
        title: Text(
          'Add New Task',
          style: TextStyle(
            fontSize: titleFontSize * 0.9,
            fontWeight: FontWeight.w700,
            color: const Color(0xFF1E1E1E),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 1,
        iconTheme: const IconThemeData(color: Color(0xFF1E1E1E)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              // Task Description
              Text(
                'Task Description',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              _buildTextFormField(
                hintText: 'e.g., Drink a glass of water',
                onSaved: (value) => _task = value ?? '',
                validator: (value) =>
                    value!.isEmpty ? 'Task description is required.' : null,
                maxLines: 2,
              ),
              const SizedBox(height: 20),

              // Category Selection
              Text(
                'Category',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _category,
                decoration: InputDecoration(
                  fillColor: Colors.white,
                  filled: true,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: Colors.grey.shade300),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: Colors.grey.shade300),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: Colors.blue.shade400, width: 2),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
                items: _categories.map((category) {
                  return DropdownMenuItem(
                    value: category,
                    child: Text(category),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() {
                    _category = value ?? 'General';
                  });
                },
              ),
              const SizedBox(height: 30),

              // Due Date & Time Section
              Text(
                'Due Date & Time',
                style: TextStyle(
                  fontSize: titleFontSize * 0.8,
                  fontWeight: FontWeight.w700,
                  color: Colors.blue.shade800,
                ),
              ),
              const Divider(thickness: 1, height: 20),

              // Date Picker
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Date',
                          style: TextStyle(
                            fontSize: labelFontSize,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: _selectDate,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              border: Border.all(color: Colors.grey.shade300),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  DateFormat('MMM dd, yyyy').format(_dueDate),
                                  style: TextStyle(fontSize: labelFontSize),
                                ),
                                Icon(Icons.calendar_today,
                                    color: Colors.blue.shade600),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Time',
                          style: TextStyle(
                            fontSize: labelFontSize,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: _selectTime,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              border: Border.all(color: Colors.grey.shade300),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  _dueTime.format(context),
                                  style: TextStyle(fontSize: labelFontSize),
                                ),
                                Icon(Icons.access_time,
                                    color: Colors.blue.shade600),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 40),

              // Submit Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _submitForm,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue.shade600,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    elevation: 2,
                  ),
                  child: Text(
                    'Add Task',
                    style: TextStyle(
                      fontSize: labelFontSize,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextFormField({
    required String hintText,
    required FormFieldSetter<String> onSaved,
    FormFieldValidator<String>? validator,
    int maxLines = 1,
  }) {
    return TextFormField(
      decoration: InputDecoration(
        hintText: hintText,
        fillColor: Colors.white,
        filled: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: Colors.blue.shade400, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      ),
      maxLines: maxLines,
      onSaved: onSaved,
      validator: validator,
      style: TextStyle(fontSize: _getResponsiveFontSize(context, 16)),
    );
  }
}

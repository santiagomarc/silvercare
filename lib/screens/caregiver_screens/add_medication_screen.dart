import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:intl/intl.dart';
import 'package:silvercare/models/medication_model.dart';
import 'package:silvercare/services/medication_service.dart';
import 'package:silvercare/services/notification_service.dart';

class AddMedicationScreen extends StatefulWidget {
  final String elderlyId;
  const AddMedicationScreen({super.key, required this.elderlyId});

  @override
  State<AddMedicationScreen> createState() => _AddMedicationScreenState();
}

class _AddMedicationScreenState extends State<AddMedicationScreen> {
  final _formKey = GlobalKey<FormState>();
  final MedicationService _medicationService = MedicationService();
  final NotificationService _notificationService = NotificationService();
  final String _caregiverId = FirebaseAuth.instance.currentUser?.uid ?? 'unknown';

  // Form Fields
  String _name = '';
  String _dosage = '';
  String _instructions = '';
  List<String> _selectedDays = [];
  List<String> _timesOfDay = [];
  DateTime _startDate = DateTime.now();

  final List<String> _allDays = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 
    'Friday', 'Saturday', 'Sunday'
  ];

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.9, 1.3);
  }

  // --- Form Handlers ---

  void _addTimeSlot() async {
    final TimeOfDay? selectedTime = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
      builder: (context, child) {
        return MediaQuery(
          data: MediaQuery.of(context).copyWith(alwaysUse24HourFormat: true),
          child: child!,
        );
      },
    );

    if (selectedTime != null) {
      setState(() {
        final String timeString = 
            '${selectedTime.hour.toString().padLeft(2, '0')}:${selectedTime.minute.toString().padLeft(2, '0')}';
        if (!_timesOfDay.contains(timeString)) {
          _timesOfDay.add(timeString);
          _timesOfDay.sort();
        }
      });
    }
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    _formKey.currentState!.save();

    if (_selectedDays.isEmpty || _timesOfDay.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select at least one day and one time slot.')),
      );
      return;
    }
    
    // Create the model
    final newMedication = MedicationModel(
      id: '', // Firestore will assign this
      elderlyId: widget.elderlyId,
      caregiverId: _caregiverId,
      name: _name,
      dosage: _dosage,
      instructions: _instructions,
      daysOfWeek: _selectedDays,
      timesOfDay: _timesOfDay,
      startDate: _startDate,
    );

    // Log elderlyId for debugging
    print('Debugging: Elderly ID for new medication: ${widget.elderlyId}');

    // Save the model
    try {
      final docId = await _medicationService.addMedicationSchedule(newMedication);
      
      if (docId != null) {
        // Schedule notifications for all medication times
        await _scheduleMedicationNotifications(docId, newMedication);
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('✅ Schedule for $_name saved!')),
          );
          Navigator.pop(context);
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('❌ Failed to save: $e')),
        );
      }
    }
  }

  /// Schedules notifications for all medication times for the next 7 days
  Future<void> _scheduleMedicationNotifications(String medicationId, MedicationModel medication) async {
    final now = DateTime.now();
    
    // Schedule notifications for the next 7 days
    for (int dayOffset = 0; dayOffset < 7; dayOffset++) {
      final targetDate = now.add(Duration(days: dayOffset));
      final dayName = DateFormat('EEEE').format(targetDate);
      
      // Check if this day is in the selected days
      if (!medication.daysOfWeek.contains(dayName)) {
        continue;
      }
      
      // Schedule notification for each time slot on this day
      for (final timeString in medication.timesOfDay) {
        final timeParts = timeString.split(':');
        final hour = int.parse(timeParts[0]);
        final minute = int.parse(timeParts[1]);
        
        final scheduledDateTime = DateTime(
          targetDate.year,
          targetDate.month,
          targetDate.day,
          hour,
          minute,
        );
        
        if (scheduledDateTime.isAfter(now)) {
          final notificationId = '${medicationId}_${targetDate.toIso8601String().substring(0, 10)}_${timeString.replaceAll(':', '')}'.hashCode;
          
          await _notificationService.scheduleNotification(
            id: notificationId,
            title: '💊 Medication Reminder',
            body: 'Time to take ${medication.name} (${medication.dosage})',
            scheduledDate: scheduledDateTime,
            payload: 'medication_${medicationId}_${timeString}',
          );
          
          print('✅ Scheduled notification for ${medication.name} on $dayName at $timeString');
        }
      }
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
          'Add New Medication Schedule',
          style: TextStyle(
            fontSize: titleFontSize * 0.9,
            fontWeight: FontWeight.w700,
            color: const Color(0xFF1E1E1E),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 1,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              // Medication Name
              Text('Medication Name', style: TextStyle(fontSize: labelFontSize, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              _buildTextFormField(
                hintText: 'e.g., Paracetamol',
                onSaved: (value) => _name = value!,
                validator: (value) => value!.isEmpty ? 'Name is required.' : null,
              ),
              const SizedBox(height: 20),

              // Dosage & Instructions Row
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Dosage', style: TextStyle(fontSize: labelFontSize, fontWeight: FontWeight.w600)),
                        const SizedBox(height: 8),
                        _buildTextFormField(
                          hintText: 'e.g., 500mg',
                          onSaved: (value) => _dosage = value!,
                          validator: (value) => value!.isEmpty ? 'Dosage is required.' : null,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 15),
                  // Start Date (for tracking long-term use)
                   Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Start Date', style: TextStyle(fontSize: labelFontSize, fontWeight: FontWeight.w600)),
                        const SizedBox(height: 8),
                         GestureDetector(
                          onTap: () async {
                            final DateTime? picked = await showDatePicker(
                              context: context,
                              initialDate: _startDate,
                              firstDate: DateTime.now().subtract(const Duration(days: 365)),
                              lastDate: DateTime.now().add(const Duration(days: 365)),
                            );
                            if (picked != null && picked != _startDate) {
                              setState(() {
                                _startDate = picked;
                              });
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: Text(
                              DateFormat('MMM dd, yyyy').format(_startDate),
                              style: TextStyle(fontSize: labelFontSize * 0.95, color: Colors.black),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              
              // Instructions (Optional)
              Text('Special Instructions (Optional)', style: TextStyle(fontSize: labelFontSize, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              _buildTextFormField(
                hintText: 'e.g., Take with food or before bed',
                maxLines: 2,
                onSaved: (value) => _instructions = value ?? '',
              ),
              const SizedBox(height: 30),

              // --- Scheduling Section ---
              Text('Recurrence Schedule', style: TextStyle(fontSize: titleFontSize * 0.8, fontWeight: FontWeight.w700, color: Colors.blue.shade800)),
              const Divider(thickness: 1, height: 20),

              // Days of Week Selection
              Text('Days of the Week', style: TextStyle(fontSize: labelFontSize, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8.0,
                children: _allDays.map((day) {
                  final isSelected = _selectedDays.contains(day);
                  return FilterChip(
                    label: Text(day),
                    selected: isSelected,
                    onSelected: (selected) {
                      setState(() {
                        if (selected) {
                          _selectedDays.add(day);
                        } else {
                          _selectedDays.remove(day);
                        }
                      });
                    },
                    backgroundColor: Colors.white,
                    selectedColor: Colors.blue.shade100,
                    checkmarkColor: Colors.blue.shade800,
                    labelStyle: TextStyle(
                      color: isSelected ? Colors.blue.shade800 : Colors.black87,
                      fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                      side: BorderSide(
                        color: isSelected ? Colors.blue.shade800 : Colors.grey.shade300,
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 20),

              // Time Slots
              Text('Daily Time Slots', style: TextStyle(fontSize: labelFontSize, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 10.0,
                runSpacing: 10.0,
                children: [
                  ..._timesOfDay.map((time) => Chip(
                    label: Text(time),
                    onDeleted: () {
                      setState(() {
                        _timesOfDay.remove(time);
                      });
                    },
                    backgroundColor: Colors.green.shade100,
                    deleteIconColor: Colors.red.shade700,
                    labelStyle: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.w600),
                  )).toList(),
                  // Button to add a new time slot
                  ActionChip(
                    avatar: const Icon(Icons.add, color: Colors.blue),
                    label: const Text('Add Time'),
                    onPressed: _addTimeSlot,
                    backgroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                      side: BorderSide(color: Colors.blue.shade400),
                    ),
                    labelStyle: const TextStyle(color: Colors.blue, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
              const SizedBox(height: 40),

              // Submit Button
              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton.icon(
                  onPressed: _submitForm,
                  icon: const Icon(Icons.schedule_send_rounded, size: 24),
                  label: Text('Save Medication Schedule', style: TextStyle(fontSize: labelFontSize * 1.1)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green.shade600,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 4,
                  ),
                ),
              ),
              const SizedBox(height: 20),
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

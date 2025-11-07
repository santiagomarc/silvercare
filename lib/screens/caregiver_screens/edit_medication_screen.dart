import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:silvercare/models/medication_model.dart';
import 'package:silvercare/services/medication_service.dart';

class EditMedicationScreen extends StatefulWidget {
  final MedicationModel medication;
  const EditMedicationScreen({super.key, required this.medication});

  @override
  State<EditMedicationScreen> createState() => _EditMedicationScreenState();
}

class _EditMedicationScreenState extends State<EditMedicationScreen> {
  final _formKey = GlobalKey<FormState>();
  final MedicationService _medicationService = MedicationService();

  // Form Fields (initialized from existing medication)
  late String _name;
  late String _dosage;
  late String _instructions;
  late List<String> _selectedDays;
  late List<String> _timesOfDay;
  late DateTime _startDate;

  final List<String> _allDays = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday',
    'Friday', 'Saturday', 'Sunday'
  ];

  @override
  void initState() {
    super.initState();
    // Initialize with existing values
    _name = widget.medication.name;
    _dosage = widget.medication.dosage;
    _instructions = widget.medication.instructions;
    _selectedDays = List.from(widget.medication.daysOfWeek);
    _timesOfDay = List.from(widget.medication.timesOfDay);
    _startDate = widget.medication.startDate;
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.9, 1.3);
  }

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

    // Create updated model
    final updatedMedication = MedicationModel(
      id: widget.medication.id,
      elderlyId: widget.medication.elderlyId,
      caregiverId: widget.medication.caregiverId,
      name: _name,
      dosage: _dosage,
      instructions: _instructions,
      daysOfWeek: _selectedDays,
      timesOfDay: _timesOfDay,
      startDate: _startDate,
      endDate: widget.medication.endDate,
    );

    try {
      await _medicationService.updateMedicationSchedule(updatedMedication);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✅ Medication updated successfully!'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('❌ Failed to update: $e')),
        );
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
          'Edit Medication',
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
              Text(
                'Medication Name',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E1E1E),
                ),
              ),
              const SizedBox(height: 8),
              _buildTextFormField(
                hintText: 'e.g., Paracetamol',
                initialValue: _name,
                onSaved: (val) => _name = val!,
                validator: (val) => val == null || val.isEmpty ? 'Please enter medication name' : null,
              ),
              const SizedBox(height: 20),

              Text(
                'Dosage',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E1E1E),
                ),
              ),
              const SizedBox(height: 8),
              _buildTextFormField(
                hintText: 'e.g., 500mg',
                initialValue: _dosage,
                onSaved: (val) => _dosage = val!,
                validator: (val) => val == null || val.isEmpty ? 'Please enter dosage' : null,
              ),
              const SizedBox(height: 20),

              Text(
                'Instructions (Optional)',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E1E1E),
                ),
              ),
              const SizedBox(height: 8),
              _buildTextFormField(
                hintText: 'e.g., Take with food',
                initialValue: _instructions,
                onSaved: (val) => _instructions = val ?? '',
                maxLines: 3,
              ),
              const SizedBox(height: 20),

              Text(
                'Days of the Week',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E1E1E),
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
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
                    selectedColor: Colors.blue.shade300,
                    checkmarkColor: Colors.white,
                  );
                }).toList(),
              ),
              const SizedBox(height: 20),

              Text(
                'Times of Day',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E1E1E),
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  ..._timesOfDay.map((time) {
                    return Chip(
                      label: Text(time),
                      deleteIcon: const Icon(Icons.close, size: 18),
                      onDeleted: () {
                        setState(() {
                          _timesOfDay.remove(time);
                        });
                      },
                      backgroundColor: Colors.green.shade100,
                    );
                  }),
                  ActionChip(
                    label: const Text('+ Add Time'),
                    onPressed: _addTimeSlot,
                    backgroundColor: Colors.blue.shade100,
                  ),
                ],
              ),
              const SizedBox(height: 20),

              Text(
                'Start Date',
                style: TextStyle(
                  fontSize: labelFontSize,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E1E1E),
                ),
              ),
              const SizedBox(height: 8),
              ListTile(
                tileColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                  side: BorderSide(color: Colors.grey.shade300),
                ),
                title: Text(DateFormat('MMMM d, yyyy').format(_startDate)),
                trailing: const Icon(Icons.calendar_today),
                onTap: () async {
                  final DateTime? picked = await showDatePicker(
                    context: context,
                    initialDate: _startDate,
                    firstDate: DateTime(2000),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null && picked != _startDate) {
                    setState(() {
                      _startDate = picked;
                    });
                  }
                },
              ),
              const SizedBox(height: 32),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _submitForm,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue.shade600,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                  ),
                  child: Text(
                    'Update Medication',
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
    String? initialValue,
    int maxLines = 1,
  }) {
    return TextFormField(
      initialValue: initialValue,
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

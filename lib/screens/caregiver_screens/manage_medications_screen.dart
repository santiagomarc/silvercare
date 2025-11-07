import 'package:flutter/material.dart';
import 'package:silvercare/models/medication_model.dart';
import 'package:silvercare/services/medication_service.dart';
import 'package:silvercare/screens/caregiver_screens/edit_medication_screen.dart';
import 'package:intl/intl.dart';

class ManageMedicationsScreen extends StatefulWidget {
  final String elderlyId;
  const ManageMedicationsScreen({super.key, required this.elderlyId});

  @override
  State<ManageMedicationsScreen> createState() => _ManageMedicationsScreenState();
}

class _ManageMedicationsScreenState extends State<ManageMedicationsScreen> {
  final MedicationService _medicationService = MedicationService();

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.9, 1.3);
  }

  Future<void> _deleteMedication(MedicationModel medication) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Medication'),
        content: Text('Are you sure you want to delete "${medication.name}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await _medicationService.deleteMedicationSchedule(medication.id);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ "${medication.name}" deleted successfully'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('❌ Failed to delete: $e'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final titleFontSize = _getResponsiveFontSize(context, 24);

    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      appBar: AppBar(
        title: Text(
          'Manage Medications',
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
      body: StreamBuilder<List<MedicationModel>>(
        stream: _medicationService.getMedicationSchedulesForElderly(widget.elderlyId),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          
          if (snapshot.hasError) {
            return Center(
              child: Text(
                'Error loading medications: ${snapshot.error}',
                style: const TextStyle(color: Colors.red),
              ),
            );
          }
          
          if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.medication_outlined,
                    size: 80,
                    color: Colors.grey.shade400,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No medications found',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 18),
                      color: Colors.grey.shade600,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Add medications from the dashboard',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 14),
                      color: Colors.grey.shade500,
                    ),
                  ),
                ],
              ),
            );
          }

          final medications = snapshot.data!;
          
          return ListView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: medications.length,
            itemBuilder: (context, index) {
              final med = medications[index];
              return _buildMedicationCard(med);
            },
          );
        },
      ),
    );
  }

  Widget _buildMedicationCard(MedicationModel medication) {
    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.blue.shade200, width: 1),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  Icons.medication_liquid_rounded,
                  color: Colors.blue.shade700,
                  size: 32,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        medication.name,
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 20),
                          fontWeight: FontWeight.w700,
                          color: const Color(0xFF1E1E1E),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        medication.dosage,
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 16),
                          fontWeight: FontWeight.w500,
                          color: Colors.grey.shade700,
                        ),
                      ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  icon: const Icon(Icons.more_vert),
                  onSelected: (value) {
                    if (value == 'edit') {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => EditMedicationScreen(
                            medication: medication,
                          ),
                        ),
                      );
                    } else if (value == 'delete') {
                      _deleteMedication(medication);
                    }
                  },
                  itemBuilder: (context) => [
                    const PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          Icon(Icons.edit, size: 20),
                          SizedBox(width: 8),
                          Text('Edit'),
                        ],
                      ),
                    ),
                    const PopupMenuItem(
                      value: 'delete',
                      child: Row(
                        children: [
                          Icon(Icons.delete, color: Colors.red, size: 20),
                          SizedBox(width: 8),
                          Text('Delete', style: TextStyle(color: Colors.red)),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
            
            if (medication.instructions.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.info_outline, size: 18, color: Colors.blue.shade700),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        medication.instructions,
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 14),
                          color: Colors.blue.shade900,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            
            const SizedBox(height: 16),
            const Divider(height: 1),
            const SizedBox(height: 12),
            
            // Schedule Info
            Row(
              children: [
                Icon(Icons.calendar_today, size: 16, color: Colors.grey.shade600),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    medication.daysOfWeek.join(', '),
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 14),
                      color: Colors.grey.shade700,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.access_time, size: 16, color: Colors.grey.shade600),
                const SizedBox(width: 8),
                Expanded(
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    children: medication.timesOfDay.map((time) {
                      // Convert to 12-hour format
                      final parts = time.split(':');
                      final hour = int.parse(parts[0]);
                      final minute = parts[1];
                      final hour12 = hour > 12 ? hour - 12 : (hour == 0 ? 12 : hour);
                      final period = hour >= 12 ? 'PM' : 'AM';
                      
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.green.shade50,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.green.shade300),
                        ),
                        child: Text(
                          '$hour12:$minute $period',
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 13),
                            color: Colors.green.shade900,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.date_range, size: 16, color: Colors.grey.shade600),
                const SizedBox(width: 8),
                Text(
                  'Start: ${DateFormat('MMM d, yyyy').format(medication.startDate)}',
                  style: TextStyle(
                    fontSize: _getResponsiveFontSize(context, 14),
                    color: Colors.grey.shade700,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

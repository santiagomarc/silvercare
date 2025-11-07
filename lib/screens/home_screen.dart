import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart'; // Import for StreamBuilder
import 'package:silvercare/models/checklist_item_model.dart';
import 'package:silvercare/models/medication_model.dart';
import 'package:silvercare/services/checklist_service.dart';
import 'package:silvercare/services/medication_service.dart';
import 'package:silvercare/widgets/mood_tracker_card.dart';
import 'package:intl/intl.dart'; // Import for date formatting

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final FirebaseAuth _auth = FirebaseAuth.instance;

  // Services for our new cards
  final MedicationService _medicationService = MedicationService();
  final ChecklistService _checklistService = ChecklistService();
  // Firestore instance for real-time dose checking
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  @override
  void initState() {
    super.initState();
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Header Section
                _buildHeader(),

                const SizedBox(height: 30),

                // Mood Tracker Card
                const MoodTrackerCard(),

                const SizedBox(height: 30),

                // Health Vitals Monitor
                _buildQuickActions(),

                const SizedBox(height: 30),

                // --- NEW: Today's Medications Section ---
                _buildMedicationSection(),

                const SizedBox(height: 30),

                // --- NEW: Today's Checklist Section ---
                _buildChecklistSection(),

                const SizedBox(height: 40),

                // Sign Out Button
                _buildSignOutButton(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    // ... (Your existing _buildHeader code - no changes)
    return Row(
      children: [
        // Profile Icon (left side)
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.8),
            borderRadius: BorderRadius.circular(30),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 4,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: const Icon(
            Icons.person,
            color: Color(0xFF2C2C2C),
            size: 24,
          ),
        ),

        // Logo (center)
        Expanded(
          child: Text(
            'SILVERCARE',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Colors.black,
              fontSize: _getResponsiveFontSize(context, 24),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w800,
              shadows: [
                Shadow(
                  offset: const Offset(0, 2),
                  blurRadius: 4,
                  color: Colors.black.withValues(alpha: 0.50),
                ),
              ],
            ),
          ),
        ),

        // Notification Bell Icon (right side)
        GestureDetector(
          onTap: () {
            Navigator.pushNamed(context, '/notifications');
          },
          child: Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.8),
              borderRadius: BorderRadius.circular(30),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.1),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: const Icon(
              Icons.notifications,
              color: Color(0xFF2C2C2C),
              size: 24,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActions() {
    // ... (Your existing _buildQuickActions code - no changes)
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFF383838), width: 1),
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Text(
              'Health Vitals Monitor',
              style: TextStyle(
                color: const Color(0xFF1E1E1E),
                fontSize: _getResponsiveFontSize(context, 22),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 20),
          // 2x2 Grid for the first 4 vital measurements
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 16,
            crossAxisSpacing: 16,
            childAspectRatio: 1.0,
            children: [
              _buildVitalCard(
                icon: Icons.bloodtype,
                label: 'Blood Pressure',
                color: Colors.orange.shade900,
                onTap: () => Navigator.pushNamed(context, '/blood_pressure'),
              ),
              _buildVitalCard(
                icon: Icons.water_drop,
                label: 'Sugar Level',
                color: Colors.green.shade900,
                onTap: () => Navigator.pushNamed(context, '/sugar_level'),
              ),
              _buildVitalCard(
                icon: Icons.thermostat,
                label: 'Temperature',
                color: Colors.lightBlue.shade900,
                onTap: () => Navigator.pushNamed(context, '/temperature'),
              ),
              _buildVitalCard(
                icon: Icons.favorite,
                label: 'Heart Rate',
                color: Colors.pink.shade900,
                onTap: () => Navigator.pushNamed(context, '/heart_rate'),
              ),
            ],
          ),

          const SizedBox(height: 20),

          // Centered Emergency SOS button
          Center(
            child: SizedBox(
              height: 100,
              width: (MediaQuery.of(context).size.width - 48 - 48 - 16) /
                  2, // Match grid card width
              child: _buildVitalCard(
                icon: Icons.warning,
                label: 'Emergency SOS',
                color: Colors.red.shade900,
                onTap: () => _showEmergencySosDialog(),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVitalCard({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    // ... (Your existing _buildVitalCard code - no changes)
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(30),
      child: Container(
        decoration: BoxDecoration(
          color: color.withOpacity(0.2),
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: color.withOpacity(1), width: 2),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Colors.white,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.3),
                    blurRadius: 15,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Icon(
                icon,
                color: color,
                size: 24,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: color,
                fontSize: _getResponsiveFontSize(context, 14),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- NEW WIDGET: Today's Medications Section ---
  Widget _buildMedicationSection() {
    // Get the name of the current day (e.g., "Monday")
    final String today = DateFormat('EEEE').format(DateTime.now());
    final DateTime now = DateTime.now();
    final DateTime todayDate = DateTime(now.year, now.month, now.day);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          "Today's Medications",
          style: TextStyle(
            color: const Color(0xFF1E1E1E),
            fontSize: _getResponsiveFontSize(context, 22),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 16),
        StreamBuilder<List<MedicationModel>>(
          stream: _medicationService.getActiveMedicationSchedules(),
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return const Center(child: Text('Error loading medications.'));
            }
            if (!snapshot.hasData || snapshot.data!.isEmpty) {
              return _buildEmptyStateCard(
                icon: Icons.medication_rounded,
                message: 'No medications scheduled.',
              );
            }

            final schedules = snapshot.data!;
            
            // 1. Filter schedules to get only those due today
            final todaySchedules = schedules.where((schedule) {
              // Check if today is in the daysOfWeek list (e.g., "Monday")
              return schedule.daysOfWeek.contains(today);
              // TODO: Also check for 'specificDates'
            }).toList();

            if (todaySchedules.isEmpty) {
              return _buildEmptyStateCard(
                icon: Icons.medication_rounded,
                message: 'No medications scheduled for today.',
              );
            }

            // 2. Create a list of all individual doses for today
            List<Widget> todayDosesWidgets = [];
            for (var med in todaySchedules) {
              for (var time in med.timesOfDay) {
                // Check if this dose should still be shown (not taken or taken today)
                final String doseInstanceId =
                    '${med.id}_${todayDate.toIso8601String().substring(0, 10)}_${time.replaceAll(':', '')}';
                
                todayDosesWidgets.add(
                  StreamBuilder<DocumentSnapshot>(
                    stream: _firestore
                        .collection(MedicationService.completionCollection)
                        .doc(doseInstanceId)
                        .snapshots(),
                    builder: (context, completionSnapshot) {
                      // Items will automatically appear fresh at midnight when 'todayDate' changes
                      // since doseInstanceId includes the date
                      // Completed items stay visible until end of day for elder to review progress
                      
                      return _buildMedicationItem(med, time);
                    },
                  ),
                );
              }
            }

            return ListView(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              children: todayDosesWidgets,
            );
          },
        ),
      ],
    );
  }

  // --- NEW WIDGET: Single Medication Item ---
  Widget _buildMedicationItem(MedicationModel med, String time) {
    final DateTime now = DateTime.now();
    final DateTime scheduledDate = DateTime(now.year, now.month, now.day);
    
    final timeParts = time.split(':');
    final scheduledHour = int.parse(timeParts[0]);
    final scheduledMinute = int.parse(timeParts[1]);
    final scheduledDateTime = DateTime(
      now.year,
      now.month,
      now.day,
      scheduledHour,
      scheduledMinute,
    );
    
    // Grace period for "on time" vs "late"
    const int graceMinutes = 15;
    final DateTime graceDeadline = scheduledDateTime.add(Duration(minutes: graceMinutes));
    
    // Determine medication status BEFORE it's taken
    final bool isPastGrace = now.isAfter(graceDeadline);
    final bool isUpcoming = scheduledDateTime.isAfter(now) && 
                            scheduledDateTime.difference(now).inMinutes <= 30;
    
    // Create the unique ID for this dose instance
    final String doseInstanceId =
        '${med.id}_${scheduledDate.toIso8601String().substring(0, 10)}_${time.replaceAll(':', '')}';

    // Convert to 12-hour format
    final hour12 = scheduledHour > 12 ? scheduledHour - 12 : (scheduledHour == 0 ? 12 : scheduledHour);
    final period = scheduledHour >= 12 ? 'PM' : 'AM';
    final time12Hour = '${hour12.toString().padLeft(2, '0')}:${scheduledMinute.toString().padLeft(2, '0')} $period';

    return StreamBuilder<DocumentSnapshot>(
      stream: _firestore
          .collection(MedicationService.completionCollection)
          .doc(doseInstanceId)
          .snapshots(),
      builder: (context, snapshot) {
        // Check if the document exists and 'isTaken' is true
        bool isTaken = false;
        DateTime? takenAt;
        
        if (snapshot.hasData && snapshot.data!.exists) {
          final data = snapshot.data!.data() as Map<String, dynamic>;
          isTaken = data['isTaken'] ?? false;
          if (data['takenAt'] != null) {
            takenAt = (data['takenAt'] as Timestamp).toDate();
          }
        }
        
        // Determine actual status
        bool isMissed = false;
        bool isTakenLate = false;
        bool isTakenOnTime = false;
        
        if (isTaken && takenAt != null) {
          // Check if it was taken after the grace period
          if (takenAt.isAfter(graceDeadline)) {
            isTakenLate = true;
          } else {
            isTakenOnTime = true;
          }
        } else if (isPastGrace) {
          // Not taken and past grace period = missed
          isMissed = true;
        }
        
        // Can undo if taken within last 5 minutes
        final bool canUndo = isTaken && takenAt != null && 
                            DateTime.now().difference(takenAt).inMinutes < 5;
        
        // Card color based on status
        Color cardColor = Colors.white;
        Color borderColor = Colors.grey.shade300;
        
        if (isMissed) {
          cardColor = Colors.red.shade50;
          borderColor = Colors.red.shade300;
        } else if (isTakenLate) {
          cardColor = Colors.orange.shade50;
          borderColor = Colors.orange.shade400;
        } else if (isTakenOnTime) {
          cardColor = Colors.green.shade50;
          borderColor = Colors.green.shade300;
        } else if (isUpcoming) {
          cardColor = Colors.blue.shade50;
          borderColor = Colors.blue.shade300;
        }

        return Card(
          elevation: isMissed ? 4 : 2,
          shadowColor: isMissed ? Colors.red.withOpacity(0.3) : Colors.black.withOpacity(0.1),
          margin: const EdgeInsets.only(bottom: 12),
          color: cardColor,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(30),
            side: BorderSide(color: borderColor, width: 1.5),
          ),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                // Status Icon
                if (isMissed)
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.error_outline,
                      color: Colors.white,
                      size: 24,
                    ),
                  )
                else if (isTakenLate)
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.orange,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.warning_amber_rounded,
                      color: Colors.white,
                      size: 24,
                    ),
                  )
                else if (isTakenOnTime)
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.green,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_circle,
                      color: Colors.white,
                      size: 24,
                    ),
                  )
                else if (isUpcoming)
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.blue,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.access_time,
                      color: Colors.white,
                      size: 24,
                    ),
                  )
                else
                  Icon(
                    Icons.medication_liquid_rounded,
                    color: Colors.blue.shade800,
                    size: 32,
                  ),
                
                const SizedBox(width: 16),

                // Details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              med.name,
                              style: TextStyle(
                                fontSize: _getResponsiveFontSize(context, 18),
                                fontFamily: 'Montserrat',
                                fontWeight: FontWeight.w600,
                                color: isMissed ? Colors.red.shade900 : Colors.black,
                              ),
                            ),
                          ),
                          if (isMissed)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.red,
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: const Text(
                                'MISSED',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            )
                          else if (isTakenLate)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.orange,
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: const Text(
                                'LATE',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            )
                          else if (isUpcoming && !isTaken)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.blue,
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: const Text(
                                'SOON',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${med.dosage} • $time12Hour',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 16),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w400,
                          color: const Color(0xFF666666),
                        ),
                      ),
                      if (isTaken && takenAt != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Taken at ${DateFormat('h:mm a').format(takenAt)}${isTakenLate ? ' (late)' : ''}',
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 14),
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w500,
                            color: isTakenLate ? Colors.orange.shade700 : Colors.green.shade700,
                          ),
                        ),
                      ],
                      if (med.instructions.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          med.instructions,
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 13),
                            fontFamily: 'Montserrat',
                            fontStyle: FontStyle.italic,
                            color: Colors.grey.shade600,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(width: 12),

                // Action Button
                if (isTaken && canUndo)
                  // Undo button
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(
                        onPressed: () async {
                          final confirm = await showDialog<bool>(
                            context: context,
                            builder: (ctx) => AlertDialog(
                              title: const Text('Undo Dose'),
                              content: const Text('Mark this dose as not taken?'),
                              actions: [
                                TextButton(
                                  onPressed: () => Navigator.pop(ctx, false),
                                  child: const Text('Cancel'),
                                ),
                                TextButton(
                                  onPressed: () => Navigator.pop(ctx, true),
                                  child: const Text('Undo', style: TextStyle(color: Colors.red)),
                                ),
                              ],
                            ),
                          );
                          
                          if (confirm == true) {
                            // Delete the completion document
                            await _firestore
                                .collection(MedicationService.completionCollection)
                                .doc(doseInstanceId)
                                .delete();
                          }
                        },
                        icon: const Icon(Icons.undo, color: Colors.orange),
                        tooltip: 'Undo',
                      ),
                      Text(
                        'Undo',
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.orange.shade700,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  )
                else if (!isTaken)
                  // Checkbox to mark as taken
                  Transform.scale(
                    scale: 1.5,
                    child: Checkbox(
                      value: false,
                      onChanged: (bool? newValue) {
                        if (newValue == true) {
                          _medicationService.markDoseAsTaken(
                            scheduleId: med.id,
                            doseTime: time,
                            scheduledDate: scheduledDate,
                          );
                        }
                      },
                      activeColor: Colors.green,
                      shape: const CircleBorder(),
                    ),
                  )
                else
                  // Already taken, show checkmark
                  Icon(
                    Icons.check_circle,
                    color: isTakenLate ? Colors.orange : Colors.green,
                    size: 32,
                  ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildChecklistSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          "Today's Checklist",
          style: TextStyle(
            color: const Color(0xFF1E1E1E),
            fontSize: _getResponsiveFontSize(context, 22),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 16),
        StreamBuilder<List<ChecklistItemModel>>(
          stream: _checklistService.getTodayChecklist(),
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return const Center(child: Text('Error loading tasks.'));
            }
            if (!snapshot.hasData || snapshot.data!.isEmpty) {
              return _buildEmptyStateCard(
                icon: Icons.checklist_rounded,
                message: 'No tasks assigned for today.',
              );
            }

            // Filter tasks to only show those for today
            final tasks = snapshot.data!.where((task) {
              final taskDate = task.dueDate;
              final nowDate = DateTime.now();
              return taskDate.year == nowDate.year &&
                     taskDate.month == nowDate.month &&
                     taskDate.day == nowDate.day;
            }).toList();

            if (tasks.isEmpty) {
              return _buildEmptyStateCard(
                icon: Icons.checklist_rounded,
                message: 'No tasks assigned for today.',
              );
            }

            return ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: tasks.length,
              itemBuilder: (context, index) {
                final task = tasks[index];
                return _buildChecklistItem(task);
              },
            );
          },
        ),
      ],
    );
  }

  // --- NEW WIDGET: Single Checklist Item ---
  Widget _buildChecklistItem(ChecklistItemModel task) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.1),
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Row(
          children: [
            // Details
            Expanded(
              child: Text(
                task.task,
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 16),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w500,
                  decoration: task.isCompleted
                      ? TextDecoration.lineThrough
                      : TextDecoration.none,
                  color: task.isCompleted
                      ? const Color(0xFF666666)
                      : const Color(0xFF1E1E1E),
                ),
              ),
            ),
            const SizedBox(width: 16),
            // Checkbox
            Transform.scale(
              scale: 1.5, // Make checkbox larger
              child: Checkbox(
                value: task.isCompleted,
                onChanged: (bool? newValue) {
                  if (newValue != null) {
                    // Call the service to update status
                    _checklistService.updateTaskStatus(task.id, newValue);
                  }
                },
                activeColor: Colors.green,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- NEW WIDGET: Empty State Card ---
  Widget _buildEmptyStateCard(
      {required IconData icon, required String message}) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.1),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: const Color(0xFF666666), size: 28),
            const SizedBox(width: 16),
            Text(
              message,
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 16),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w500,
                color: const Color(0xFF666666),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showEmergencySosDialog() {
    // ... (Your existing _showEmergencySosDialog code - no changes)
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(30),
          ),
          title: Row(
            children: [
              Icon(
                Icons.warning_amber_rounded,
                color: Colors.red.shade700,
                size: 28,
              ),
              const SizedBox(width: 12),
              Text(
                'Emergency SOS',
                style: TextStyle(
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w600,
                  fontSize: _getResponsiveFontSize(context, 18),
                  color: Colors.red.shade700,
                ),
              ),
            ],
          ),
          content: Text(
            'This feature will send an emergency alert to your emergency contacts and local emergency services.',
            style: TextStyle(
              fontFamily: 'Montserrat',
              fontSize: _getResponsiveFontSize(context, 14),
              color: const Color(0xFF666666),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text(
                'Cancel',
                style: TextStyle(
                  color: Color(0xFF666666),
                  fontFamily: 'Montserrat',
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: const Text('🚨 Emergency SOS feature coming soon!'),
                    backgroundColor: Colors.red.shade600,
                    duration: const Duration(seconds: 3),
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
              ),
              child: const Text(
                'Activate SOS',
                style: TextStyle(
                  color: Colors.white,
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildSignOutButton() {
    // Corrected the typo from shade6600 to shade600
    return ElevatedButton(
      onPressed: _handleSignOut,
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.red.shade600, // Corrected typo
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(30),
        ),
        elevation: 2,
      ),
      child: const Text(
        'Sign Out',
        style: TextStyle(
          fontSize: 16,
          fontFamily: 'Montserrat',
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  Future<void> _handleSignOut() async {
    // ... (Your existing _handleSignOut code)
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(30),
          ),
          title: const Text(
            'Sign Out',
            style: TextStyle(
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w600,
              fontSize: 18,
            ),
          ),
          content: const Text(
            'Are you sure you want to sign out?',
            style: TextStyle(
              fontFamily: 'Montserrat',
              fontSize: 14,
              color: Color(0xFF666666),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text(
                'Cancel',
                style: TextStyle(
                  color: Color(0xFF666666),
                  fontFamily: 'Montserrat',
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () async {
                Navigator.of(context).pop(); // Close dialog

                try {
                  await _auth.signOut();

                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('👋 Signed out successfully!'),
                        backgroundColor: Colors.green,
                        duration: Duration(seconds: 2),
                      ),
                    );

                    // Navigate back to sign-in
                    Navigator.of(context).pushReplacementNamed('/signin');
                  }
                } catch (e) {
                  if (mounted) {
                    // Corrected the typo from f(context) to context
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('Error signing out: ${e.toString()}'),
                        backgroundColor: Colors.red,
                      ),
                    );
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
              ),
              child: const Text(
                'Sign Out',
                style: TextStyle(
                  color: Colors.white,
                  fontFamily: 'Montserrat',
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

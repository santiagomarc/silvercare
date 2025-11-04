import 'package:flutter/material.dart';
import 'package:silvercare/services/medication_service.dart';
import 'package:silvercare/services/checklist_service.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:intl/intl.dart';

const String _logoAssetPath = 'assets/icons/silvercare.png'; 

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  // Color coding for notification types
  final Color _negativeColor = const Color(0xFFCD5C5C); // Red - missed, alerts, dangers
  final Color _positiveColor = const Color(0xFF008000); // Green - completed, taken, good news
  final Color _reminderColor = const Color(0xFF000080); // Blue - upcomings, reminders
  final Color _titleTextColor = const Color(0xFF808080);
  
  final MedicationService _medicationService = MedicationService();
  final ChecklistService _checklistService = ChecklistService();
  final FirebaseFirestore _firestore = FirebaseFirestore.instance; 

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  void _showComingSoonDialog(BuildContext context, String feature) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          title: Text(
            'Action: $feature',
            style: TextStyle(
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w600,
              fontSize: _getResponsiveFontSize(context, 18),
            ),
          ),
          content: const Text(
            'This button would typically navigate to the detail screen.',
            style: TextStyle(fontFamily: 'Inter', fontSize: 14, color: Color(0xFF666666)),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('OK', style: TextStyle(color: Color(0xFF2C2C2C), fontFamily: 'Inter', fontWeight: FontWeight.w500)),
            ),
          ],
        );
      },
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 20, bottom: 20, left: 20, right: 20),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Back button
          GestureDetector(
            onTap: () => Navigator.of(context).pop(),
            child: Container(
              width: 55,
              height: 55,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white,
                border: Border.all(color: Colors.grey.withOpacity(0.3), width: 2),
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 5, offset: const Offset(0, 3)),
                ],
              ),
              child: const Icon(Icons.arrow_back, color: Color(0xFF2C2C2C), size: 30),
            ),
          ),

          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                width: 55,
                height: 55, 
                child: Image.asset(
                  _logoAssetPath,
                  fit: BoxFit.contain,
                  errorBuilder: (context, error, stackTrace) {
                    return const Icon(Icons.shield, color: Colors.grey, size: 30); 
                  },
                ),
              ),
              const SizedBox(width: 15),
              Text(
                'SILVER CARE',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.black,
                  fontSize: _getResponsiveFontSize(context, 21),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w800,
                  shadows: [Shadow(offset: const Offset(0, 3), blurRadius: 4, color: Colors.black.withOpacity(0.50))],
                ),
              ),
            ],
          ),

          InkWell(
            onTap: () => _showComingSoonDialog(context, 'Settings'),
            child: Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.8),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 4, offset: const Offset(0, 2))],
              ),
              child: const Icon(Icons.settings_outlined, color: Color(0xFF2C2C2C), size: 24),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationsTitle(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: Colors.black, width: 2),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.5), blurRadius: 4, offset: const Offset(0, 4))],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.notifications_active_outlined, size: 32, color: _titleTextColor), 
            const SizedBox(width: 10),
            Text(
              'NOTIFICATIONS',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: _titleTextColor,
                fontSize: _getResponsiveFontSize(context, 32),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                shadows: [Shadow(offset: const Offset(0, 4), blurRadius: 4, color: Colors.black.withOpacity(0.25))],
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  // Build a single notification card with color coding
  Widget _buildNotificationCard({
    required String title,
    required String subtitle,
    required String time,
    required IconData icon,
    required Color color,
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.3), width: 2),
          boxShadow: [
            BoxShadow(
              color: color.withOpacity(0.2),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Row(
          children: [
            // Icon circle with color
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.2),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(width: 16),
            
            // Content
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: color,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey.shade700,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    time,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade500,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ),
            ),
            
            // Arrow icon
            Icon(Icons.chevron_right, color: Colors.grey.shade400),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(context),
            const SizedBox(height: 10),
            _buildNotificationsTitle(context),
            const SizedBox(height: 30),

            // Main content area - Direct notification display
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(30),
                    topRight: Radius.circular(30),
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 8,
                      offset: const Offset(0, -3),
                    ),
                  ],
                ),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Header text
                      Text(
                        'Your Notifications',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 24),
                          fontWeight: FontWeight.w800,
                          color: Colors.black87,
                        ),
                      ),
                      Text(
                        'Stay updated with your health activities',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 14),
                          color: Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(height: 24),

                      // All notifications displayed directly
                      _buildAllNotifications(),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  // Build all notifications in a single stream
  Widget _buildAllNotifications() {
    return StreamBuilder<Map<String, dynamic>>(
      stream: _combineAllNotifications(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(
            child: Padding(
              padding: EdgeInsets.all(40.0),
              child: CircularProgressIndicator(),
            ),
          );
        }
        
        if (!snapshot.hasData || snapshot.data!['notifications'].isEmpty) {
          return Center(
            child: Column(
              children: [
                const SizedBox(height: 40),
                Icon(
                  Icons.notifications_none,
                  size: 80,
                  color: Colors.grey.shade300,
                ),
                const SizedBox(height: 16),
                Text(
                  'No notifications yet',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.grey.shade600,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Check back later for updates',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade500,
                  ),
                ),
              ],
            ),
          );
        }
        
        final List<Map<String, dynamic>> notifications = 
            List<Map<String, dynamic>>.from(snapshot.data!['notifications']);
        
        return Column(
          children: notifications.map((notif) {
            return _buildNotificationCard(
              title: notif['title'],
              subtitle: notif['subtitle'],
              time: notif['time'],
              icon: notif['icon'],
              color: notif['color'],
              onTap: () {
                // Navigate to main screen to see details
                Navigator.pushNamed(context, '/main');
              },
            );
          }).toList(),
        );
      },
    );
  }
  
  // Combine all notification sources into one stream
  Stream<Map<String, dynamic>> _combineAllNotifications() async* {
    await for (final meds in _medicationService.getActiveMedicationSchedules()) {
      await for (final tasks in _checklistService.getTodayChecklist()) {
        final List<Map<String, dynamic>> allNotifications = [];
        final now = DateTime.now();
        final today = DateFormat('EEEE').format(now);
        
        // Process medications
        for (final med in meds) {
          if (!med.daysOfWeek.contains(today)) continue;
          
          for (final timeStr in med.timesOfDay) {
            final timeParts = timeStr.split(':');
            final scheduledHour = int.parse(timeParts[0]);
            final scheduledMinute = int.parse(timeParts[1]);
            final scheduledDateTime = DateTime(
              now.year,
              now.month,
              now.day,
              scheduledHour,
              scheduledMinute,
            );
            
            // Check if dose is taken
            final doseId = '${med.id}_${now.toIso8601String().substring(0, 10)}_${timeStr.replaceAll(':', '')}';
            final doseDoc = await _firestore
                .collection(MedicationService.completionCollection)
                .doc(doseId)
                .get();
            
            final isTaken = doseDoc.exists && (doseDoc.data()?['isTaken'] ?? false);
            final isPast = now.isAfter(scheduledDateTime.add(const Duration(minutes: 15)));
            final isUpcoming = scheduledDateTime.isAfter(now) && 
                              scheduledDateTime.difference(now).inMinutes <= 30;
            
            // Convert to 12-hour format
            final hour12 = scheduledHour > 12 ? scheduledHour - 12 : (scheduledHour == 0 ? 12 : scheduledHour);
            final period = scheduledHour >= 12 ? 'PM' : 'AM';
            final time12Hour = '${hour12.toString().padLeft(2, '0')}:${scheduledMinute.toString().padLeft(2, '0')} $period';
            
            if (isTaken) {
              // GREEN - Positive notification (medication taken)
              final takenAt = doseDoc.data()?['takenAt'] as Timestamp?;
              final takenTime = takenAt != null ? DateFormat('h:mm a').format(takenAt.toDate()) : time12Hour;
              
              allNotifications.add({
                'title': '✓ ${med.name} Taken',
                'subtitle': '${med.dosage} taken successfully',
                'time': 'Taken at $takenTime',
                'icon': Icons.check_circle,
                'color': _positiveColor,
                'timestamp': takenAt?.toDate() ?? scheduledDateTime,
              });
            } else if (isPast) {
              // RED - Negative notification (missed medication)
              allNotifications.add({
                'title': '⚠ Missed: ${med.name}',
                'subtitle': 'You missed your ${med.dosage} dose',
                'time': 'Was due at $time12Hour',
                'icon': Icons.error_outline,
                'color': _negativeColor,
                'timestamp': scheduledDateTime,
              });
            } else if (isUpcoming) {
              // BLUE - Reminder notification (upcoming medication)
              final minutesLeft = scheduledDateTime.difference(now).inMinutes;
              allNotifications.add({
                'title': '⏰ Upcoming: ${med.name}',
                'subtitle': 'Take ${med.dosage} soon',
                'time': 'In $minutesLeft minutes ($time12Hour)',
                'icon': Icons.access_time,
                'color': _reminderColor,
                'timestamp': scheduledDateTime,
              });
            }
          }
        }
        
        // Process checklist tasks
        for (final task in tasks) {
          if (task.isCompleted) {
            // GREEN - Task completed
            final completedTime = task.completedAt != null 
                ? DateFormat('h:mm a').format(task.completedAt!) 
                : 'Earlier';
            
            allNotifications.add({
              'title': '✓ ${task.task}',
              'subtitle': 'Task completed successfully',
              'time': 'Completed at $completedTime',
              'icon': Icons.task_alt,
              'color': _positiveColor,
              'timestamp': task.completedAt ?? task.dueDate,
            });
          } else {
            final isPast = now.isAfter(task.dueDate);
            final isUpcoming = task.dueDate.isAfter(now) && 
                              task.dueDate.difference(now).inMinutes <= 30;
            
            if (isPast) {
              // RED - Overdue task
              allNotifications.add({
                'title': '⚠ Overdue: ${task.task}',
                'subtitle': 'This task was not completed on time',
                'time': 'Was due at ${DateFormat('h:mm a').format(task.dueDate)}',
                'icon': Icons.warning_amber,
                'color': _negativeColor,
                'timestamp': task.dueDate,
              });
            } else if (isUpcoming) {
              // BLUE - Upcoming task
              final minutesLeft = task.dueDate.difference(now).inMinutes;
              allNotifications.add({
                'title': '⏰ Reminder: ${task.task}',
                'subtitle': 'Task due soon',
                'time': 'Due in $minutesLeft minutes',
                'icon': Icons.event_note,
                'color': _reminderColor,
                'timestamp': task.dueDate,
              });
            }
          }
        }
        
        // Sort notifications by timestamp (most recent first)
        allNotifications.sort((a, b) => 
          (b['timestamp'] as DateTime).compareTo(a['timestamp'] as DateTime)
        );
        
        yield {'notifications': allNotifications};
        break;
      }
      break;
    }
  }
}

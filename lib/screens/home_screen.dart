import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart'; // Import for StreamBuilder
import 'package:silvercare/models/checklist_item_model.dart';
import 'package:silvercare/models/medication_model.dart';
import 'package:silvercare/services/checklist_service.dart';
import 'package:silvercare/services/medication_service.dart';
import 'package:silvercare/services/push_notification_service.dart';
import 'package:silvercare/services/persistent_notification_service.dart';
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
  final PushNotificationService _pushNotificationService = PushNotificationService();
  final PersistentNotificationService _persistentNotificationService = PersistentNotificationService();
  // Firestore instance for real-time dose checking
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  
  // Track which doses we've already created missed notifications for (to avoid duplicates)
  final Set<String> _missedNotificationsCreated = {};

  @override
  void initState() {
    super.initState();
    _scheduleNotifications();
    _checkHealthDataDebug(); // Debug health data
  }
  
  /// Debug method to check if any health data exists
  Future<void> _checkHealthDataDebug() async {
    try {
      final user = _auth.currentUser;
      if (user == null) return;
      
      print('🔍 === HEALTH DATA DEBUG ===');
      print('🔍 Checking health_data collection for elderlyId: ${user.uid}');
      
      // Query ALL health data for this user (no date filter)
      final allData = await _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: user.uid)
          .limit(10)
          .get();
      
      print('🔍 Total health_data documents found: ${allData.docs.length}');
      
      if (allData.docs.isEmpty) {
        print('🔍 ⚠️ NO HEALTH DATA FOUND! User may need to record vitals first.');
      } else {
        print('🔍 Sample documents:');
        for (var doc in allData.docs) {
          final data = doc.data();
          print('🔍   - ID: ${doc.id}');
          print('🔍     type: ${data['type']}');
          print('🔍     measuredAt: ${data['measuredAt']} (${data['measuredAt'].runtimeType})');
          print('🔍     value: ${data['value']}');
        }
      }
      print('🔍 === END DEBUG ===');
    } catch (e) {
      print('🔍 ❌ Error checking health data: $e');
    }
  }
  
  /// Check and create a missed medication notification if needed
  /// Uses in-memory tracking to avoid creating duplicate notifications
  Future<void> _checkAndCreateMissedNotification(
    MedicationModel med,
    DateTime scheduledDateTime,
    String doseInstanceId,
  ) async {
    // Skip if we already created a missed notification for this dose
    if (_missedNotificationsCreated.contains(doseInstanceId)) {
      return;
    }
    
    final elderlyId = _auth.currentUser?.uid;
    if (elderlyId == null) return;
    
    // Check if a missed notification already exists in Firestore
    // Use more specific query to avoid duplicates
    final existingNotifications = await _firestore
        .collection('notifications')
        .where('type', isEqualTo: 'medication_missed')
        .where('elderlyId', isEqualTo: elderlyId)
        .where('metadata.medicationId', isEqualTo: med.id)
        .where('metadata.scheduledTime', isEqualTo: scheduledDateTime.toIso8601String())
        .limit(1) // Only need to know if at least one exists
        .get();
    
    if (existingNotifications.docs.isEmpty) {
      // Create the missed notification
      await _persistentNotificationService.createMedicationMissed(
        elderlyId: elderlyId,
        medicationName: med.name,
        scheduledTime: scheduledDateTime,
        medicationId: med.id,
      );
      print('📬 Created missed notification for ${med.name} at ${DateFormat('h:mm a').format(scheduledDateTime)}');
      
      // Mark as created in our in-memory tracker
      _missedNotificationsCreated.add(doseInstanceId);
    } else {
      // Notification already exists, just mark it in our tracker
      _missedNotificationsCreated.add(doseInstanceId);
      print('ℹ️ Missed notification already exists for ${med.name} at ${DateFormat('h:mm a').format(scheduledDateTime)}');
    }
  }

  // Schedule push notifications for medication reminders
  void _scheduleNotifications() async {
    try {
      final user = _auth.currentUser;
      if (user != null) {
        await _pushNotificationService.scheduleMedicationNotifications(user.uid);
        await _pushNotificationService.scheduleDailyRefresh(user.uid);
        
        // Optional: Print count for debugging
        final count = await _pushNotificationService.getPendingNotificationsCount();
        print('Scheduled $count push notifications');
      }
    } catch (e) {
      print('⚠️ Could not schedule push notifications: $e');
      // Continue anyway - app will work without push notifications
    }
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
    final user = _auth.currentUser;
    if (user == null) {
      return Container(); // Return empty if no user
    }
    
    print('🔍 User ID: ${user.uid}');
    
    // Get start of today for filtering (midnight)
    final now = DateTime.now();
    final startOfToday = DateTime(now.year, now.month, now.day);
    final startOfTodayTimestamp = Timestamp.fromDate(startOfToday);
    
    print('🔍 Start of today: $startOfToday');
    print('🔍 Start of today timestamp: $startOfTodayTimestamp');
    
    return StreamBuilder<QuerySnapshot>(
      stream: _firestore
          .collection('health_data')
          .where('elderlyId', isEqualTo: user.uid)
          // Removed the date filter to avoid index requirement
          // We'll filter client-side instead
          .snapshots(),
      builder: (context, snapshot) {
        // Count recorded vitals for today
        int recordedCount = 0;
        bool hasBP = false;
        bool hasSugar = false;
        bool hasTemp = false;
        bool hasHR = false;
        
        // Debug logging
        print('📊 Vital Progress Debug:');
        print('   Connection state: ${snapshot.connectionState}');
        print('   Has data: ${snapshot.hasData}');
        print('   Has error: ${snapshot.hasError}');
        if (snapshot.hasError) {
          print('   Error: ${snapshot.error}');
        }
        print('   Docs count: ${snapshot.data?.docs.length ?? 0}');
        
        if (snapshot.hasData && snapshot.data!.docs.isNotEmpty) {
          print('   Documents found (filtering by today):');
          for (var doc in snapshot.data!.docs) {
            final data = doc.data() as Map<String, dynamic>;
            final type = data['type'] as String?;
            final measuredAt = data['measuredAt'];
            
            // Client-side filtering: only count today's records
            if (measuredAt is Timestamp) {
              final measuredDate = measuredAt.toDate();
              final measuredDateOnly = DateTime(measuredDate.year, measuredDate.month, measuredDate.day);
              
              // Skip if not from today
              if (!measuredDateOnly.isAtSameMomentAs(startOfToday)) {
                print('     ⏭️ Skipping $type from ${measuredDate.toString().substring(0, 10)} (not today)');
                continue;
              }
            } else {
              // Skip records without valid measuredAt
              print('     ⚠️ Skipping record with invalid measuredAt: $measuredAt');
              continue;
            }
            
            print('     - Type: $type, MeasuredAt: $measuredAt (${measuredAt.runtimeType})');
            
            switch (type) {
              case 'blood_pressure':
                if (!hasBP) {
                  hasBP = true;
                  recordedCount++;
                  print('     ✓ BP counted');
                }
                break;
              case 'sugar_level':
                if (!hasSugar) {
                  hasSugar = true;
                  recordedCount++;
                  print('     ✓ Sugar counted');
                }
                break;
              case 'temperature':
                if (!hasTemp) {
                  hasTemp = true;
                  recordedCount++;
                  print('     ✓ Temp counted');
                }
                break;
              case 'heart_rate':
                if (!hasHR) {
                  hasHR = true;
                  recordedCount++;
                  print('     ✓ HR counted');
                }
                break;
            }
          }
        }
        print('   Final count: $recordedCount/4 (BP:$hasBP, Sugar:$hasSugar, Temp:$hasTemp, HR:$hasHR)');
        
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
              // Title
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
              const SizedBox(height: 12),
              
              // Progress Indicator
              _buildVitalsProgressIndicator(recordedCount),
              
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
                    isRecorded: hasBP,
                  ),
                  _buildVitalCard(
                    icon: Icons.water_drop,
                    label: 'Sugar Level',
                    color: Colors.green.shade900,
                    onTap: () => Navigator.pushNamed(context, '/sugar_level'),
                    isRecorded: hasSugar,
                  ),
                  _buildVitalCard(
                    icon: Icons.thermostat,
                    label: 'Temperature',
                    color: Colors.lightBlue.shade900,
                    onTap: () => Navigator.pushNamed(context, '/temperature'),
                    isRecorded: hasTemp,
                  ),
                  _buildVitalCard(
                    icon: Icons.favorite,
                    label: 'Heart Rate',
                    color: Colors.pink.shade900,
                    onTap: () => Navigator.pushNamed(context, '/heart_rate'),
                    isRecorded: hasHR,
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
                    isRecorded: true, // Always true for emergency button
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
  
  Widget _buildVitalsProgressIndicator(int recordedCount) {
    final double progress = recordedCount / 4.0;
    final Color progressColor = recordedCount == 4 ? Colors.green : Colors.orange;
    
    String message;
    if (recordedCount == 4) {
      message = '🎉 All vitals recorded today! Great job!';
    } else if (recordedCount == 0) {
      message = '📊 Record your vitals to track your health';
    } else {
      message = '👍 Keep going! Record all vitals for best results';
    }
    
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: progressColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: progressColor.withOpacity(0.3), width: 2),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Today\'s Progress',
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 14),
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[800],
                ),
              ),
              Text(
                '$recordedCount/4 Recorded',
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 16),
                  fontWeight: FontWeight.w700,
                  color: progressColor,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 8,
              backgroundColor: Colors.grey[300],
              valueColor: AlwaysStoppedAnimation<Color>(progressColor),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            message,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 12),
              color: Colors.grey[600],
              fontStyle: FontStyle.italic,
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
    bool isRecorded = false,
  }) {
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
        child: Stack(
          children: [
            // Main content - centered
            Center(
              child: Padding(
                padding: const EdgeInsets.all(12.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 64,
                      height: 64,
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
                        size: 36,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      label,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: color,
                        fontSize: _getResponsiveFontSize(context, 16),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w700,
                        height: 1.2,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            // Badge indicator (only for non-emergency cards)
            if (label != 'Emergency SOS')
              Positioned(
                top: 10,
                right: 10,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: isRecorded ? Colors.green : Colors.orange,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.white, width: 2),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.2),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        isRecorded ? Icons.check_circle : Icons.pending,
                        color: Colors.white,
                        size: 16,
                      ),
                      const SizedBox(width: 5),
                      Text(
                        isRecorded ? 'Done' : 'Pending',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
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
            final todaySchedules = schedules.where((schedule) {
              return schedule.daysOfWeek.contains(today);
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
                // Items will automatically appear fresh at midnight when 'todayDate' changes
                // since doseInstanceId includes the date
                // Completed items stay visible until end of day for elder to review progress
                todayDosesWidgets.add(_buildMedicationItem(med, time));
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
    
    // NEW TIME ZONES (1-hour grace period)
    const int upcomingMinutes = 30; // Show SOON badge 30 min before
    const int graceMinutes = 60; // 1-hour grace period
    
    final DateTime upcomingStart = scheduledDateTime.subtract(const Duration(minutes: upcomingMinutes));
    final DateTime graceDeadline = scheduledDateTime.add(const Duration(minutes: graceMinutes));
    
    // Determine time zone
    final bool isUpcoming = now.isBefore(scheduledDateTime) && now.isAfter(upcomingStart);
    final bool isTakeNowTime = now.isAfter(scheduledDateTime) && now.isBefore(graceDeadline);
    final bool isMissedTime = now.isAfter(graceDeadline); // After grace period = MISSED
    
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
        
        // Determine actual status based on new time zones
        bool isTakenLate = false;
        bool isTakenOnTime = false;
        
        if (isTaken && takenAt != null) {
          // Check if it was taken after the 1-hour grace period
          if (takenAt.isAfter(graceDeadline)) {
            isTakenLate = true; // TAKEN LATE (orange) - only when ticked after grace period
          } else {
            isTakenOnTime = true;
          }
        }
        
        // Create missed notification if past grace period and not taken
        // This runs each time the widget builds, but we'll check if notification already exists
        if (isMissedTime && !isTaken) {
          // Check if we've already created a missed notification for this dose
          _checkAndCreateMissedNotification(med, scheduledDateTime, doseInstanceId);
        }
        
        // Can undo if taken within last 5 minutes
        final bool canUndo = isTaken && takenAt != null && 
                            DateTime.now().difference(takenAt).inMinutes < 5;
        
        // Determine if checkbox should be enabled
        // UPCOMING (SOON badge) = NOT tickable
        // TAKE NOW, MISSED = tickable
        final bool isTickable = !isTaken && (isTakeNowTime || isMissedTime);
        
        // Card color based on status
        Color cardColor = Colors.white;
        Color borderColor = Colors.grey.shade300;
        
        if (isMissedTime && !isTaken) {
          // MISSED TIME (past grace period) but not taken yet - RED
          cardColor = Colors.red.shade50;
          borderColor = Colors.red.shade400;
        } else if (isTakenLate) {
          // Was taken late (after grace period) - ORANGE
          cardColor = Colors.orange.shade50;
          borderColor = Colors.orange.shade400;
        } else if (isTakenOnTime) {
          // Was taken on time
          cardColor = Colors.green.shade50;
          borderColor = Colors.green.shade300;
        } else if (isTakeNowTime && !isTaken) {
          // TAKE NOW window
          cardColor = Colors.green.shade50;
          borderColor = Colors.green.shade400;
        } else if (isUpcoming) {
          // UPCOMING (SOON)
          cardColor = Colors.blue.shade50;
          borderColor = Colors.blue.shade300;
        }

        return Card(
          elevation: (isMissedTime && !isTaken) ? 4 : 2,
          shadowColor: (isMissedTime && !isTaken) ? Colors.red.withOpacity(0.3) : Colors.black.withOpacity(0.1),
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
                if (isTakenLate)
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
                                color: (isMissedTime && !isTaken) ? Colors.red.shade900 : Colors.black,
                              ),
                            ),
                          ),
                          // Badge system: SOON (blue), TAKE NOW (green), MISSED (red when not taken past grace), TAKEN LATE (orange when taken past grace)
                          if (isMissedTime && !isTaken)
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
                          else if (isTakenLate && isTaken)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.orange,
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: const Text(
                                'TAKEN LATE',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            )
                          else if (isTakeNowTime && !isTaken)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.green,
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: const Text(
                                'TAKE NOW',
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
                            // Call the undo method which deletes both the completion and notification
                            await _medicationService.undoDoseTaken(
                              scheduleId: med.id,
                              doseTime: time,
                              scheduledDate: scheduledDate,
                            );
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
                  // Checkbox to mark as taken (only if in TAKE NOW or LATE window)
                  isTickable
                    ? Transform.scale(
                        scale: 1.5,
                        child: Checkbox(
                          value: false,
                          onChanged: (bool? newValue) async {
                            if (newValue == true) {
                              await _medicationService.markDoseAsTaken(
                                scheduleId: med.id,
                                doseTime: time,
                                scheduledDate: scheduledDate,
                              );
                              // Reschedule notifications after marking dose
                              _scheduleNotifications();
                            }
                          },
                          activeColor: Colors.green,
                          shape: const CircleBorder(),
                        ),
                      )
                    : Container(
                        width: 48,
                        height: 48,
                        alignment: Alignment.center,
                        child: Icon(
                          Icons.lock_clock,
                          color: Colors.blue.shade300,
                          size: 28,
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
    final now = DateTime.now();
    final isOverdue = !task.isCompleted && now.isAfter(task.dueDate);
    final dueTimeStr = DateFormat('h:mm a').format(task.dueDate);
    
    // Get category icon and color
    IconData categoryIcon;
    Color categoryColor;
    
    switch (task.category.toLowerCase()) {
      case 'health':
        categoryIcon = Icons.favorite;
        categoryColor = Colors.red;
        break;
      case 'medication':
        categoryIcon = Icons.medication;
        categoryColor = Colors.blue;
        break;
      case 'meals':
        categoryIcon = Icons.restaurant;
        categoryColor = Colors.orange;
        break;
      case 'exercise':
        categoryIcon = Icons.fitness_center;
        categoryColor = Colors.green;
        break;
      case 'hydration':
        categoryIcon = Icons.water_drop;
        categoryColor = Colors.lightBlue;
        break;
      case 'personal care':
        categoryIcon = Icons.self_improvement;
        categoryColor = Colors.purple;
        break;
      case 'morning':
        categoryIcon = Icons.wb_sunny;
        categoryColor = Colors.amber;
        break;
      case 'afternoon':
        categoryIcon = Icons.wb_twilight;
        categoryColor = Colors.orange;
        break;
      case 'evening':
        categoryIcon = Icons.nights_stay;
        categoryColor = Colors.indigo;
        break;
      default: // 'general' and any other categories
        categoryIcon = Icons.check_box;
        categoryColor = Colors.grey;
    }
    
    // Card border color based on status
    Color borderColor;
    Color cardColor;
    
    if (task.isCompleted) {
      borderColor = Colors.green.shade300;
      cardColor = Colors.green.shade50;
    } else if (isOverdue) {
      borderColor = Colors.orange.shade400;
      cardColor = Colors.orange.shade50;
    } else {
      borderColor = Colors.grey.shade300;
      cardColor = Colors.white;
    }
    
    return Card(
      elevation: isOverdue ? 3 : 2,
      shadowColor: isOverdue ? Colors.orange.withOpacity(0.3) : Colors.black.withOpacity(0.1),
      margin: const EdgeInsets.only(bottom: 12),
      color: cardColor,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: borderColor, width: 1.5),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: [
            // Category Icon
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: categoryColor.withOpacity(0.2),
                shape: BoxShape.circle,
              ),
              child: Icon(
                categoryIcon,
                color: categoryColor,
                size: 24,
              ),
            ),
            
            const SizedBox(width: 16),
            
            // Details
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Task name and badges row
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          task.task,
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 16),
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w600,
                            decoration: task.isCompleted
                                ? TextDecoration.lineThrough
                                : TextDecoration.none,
                            color: task.isCompleted
                                ? const Color(0xFF666666)
                                : (isOverdue ? Colors.orange.shade900 : const Color(0xFF1E1E1E)),
                          ),
                        ),
                      ),
                      
                      // Overdue badge
                      if (isOverdue && !task.isCompleted)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.orange,
                            borderRadius: BorderRadius.circular(30),
                          ),
                          child: const Text(
                            'OVERDUE',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                    ],
                  ),
                  
                  const SizedBox(height: 6),
                  
                  // Category and time row
                  Row(
                    children: [
                      // Category badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: categoryColor.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: categoryColor.withOpacity(0.5), width: 1),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(categoryIcon, size: 12, color: categoryColor),
                            const SizedBox(width: 4),
                            Text(
                              task.category,
                              style: TextStyle(
                                fontSize: 11,
                                fontFamily: 'Montserrat',
                                fontWeight: FontWeight.w600,
                                color: categoryColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                      
                      const SizedBox(width: 8),
                      
                      // Time with "Before" framing
                      Icon(Icons.access_time, size: 14, color: Colors.grey.shade600),
                      const SizedBox(width: 4),
                      Text(
                        task.isCompleted
                            ? 'Completed ${task.completedAt != null ? DateFormat('h:mm a').format(task.completedAt!) : ''}'
                            : 'Before $dueTimeStr',
                        style: TextStyle(
                          fontSize: 12,
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w500,
                          color: task.isCompleted
                              ? Colors.green.shade700
                              : (isOverdue ? Colors.orange.shade700 : Colors.grey.shade700),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            
            const SizedBox(width: 12),
            
            // Checkbox
            Transform.scale(
              scale: 1.5,
              child: Checkbox(
                value: task.isCompleted,
                onChanged: (bool? newValue) {
                  if (newValue != null) {
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
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(30),
          ),
          contentPadding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
          title: Row(
            children: [
              const Icon(
                Icons.warning_amber_rounded,
                color: Colors.red,
                size: 24,
              ),
              const SizedBox(width: 8),
              Flexible(
                child: Text(
                  'Emergency SOS',
                  style: TextStyle(
                    fontFamily: 'Montserrat',
                    fontWeight: FontWeight.w600,
                    fontSize: _getResponsiveFontSize(context, 16),
                    color: Colors.red.shade700,
                  ),
                ),
              ),
            ],
          ),
          content: SingleChildScrollView(
            child: Text(
              'This feature will send an emergency alert to your emergency contacts and local emergency services.',
              style: TextStyle(
                fontFamily: 'Montserrat',
                fontSize: _getResponsiveFontSize(context, 14),
                color: const Color(0xFF666666),
              ),
              softWrap: true,
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

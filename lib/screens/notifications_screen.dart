import 'package:flutter/material.dart';
import 'package:silvercare/services/persistent_notification_service.dart';
import 'package:silvercare/models/notification_model.dart';
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
  final Color _warningColor = const Color(0xFFFFA500); // Orange - warnings, late
  final Color _titleTextColor = const Color(0xFF808080);
  
  final PersistentNotificationService _notificationService = PersistentNotificationService(); 

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
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

          // Empty spacer to balance the layout
          const SizedBox(width: 48),
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
  
  // Build all notifications from persistent storage
  Widget _buildAllNotifications() {
    return StreamBuilder<List<NotificationModel>>(
      stream: _notificationService.getNotifications(),
      builder: (context, snapshot) {
        // Show loading spinner
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(
            child: Padding(
              padding: EdgeInsets.all(40.0),
              child: CircularProgressIndicator(),
            ),
          );
        }
        
        // Handle errors
        if (snapshot.hasError) {
          print('❌ Error loading notifications: ${snapshot.error}');
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 80, color: Colors.red),
                const SizedBox(height: 16),
                Text(
                  'Error loading notifications',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.grey.shade700,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  '${snapshot.error}',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade500,
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () {
                    setState(() {}); // Retry
                  },
                  child: const Text('Retry'),
                ),
              ],
            ),
          );
        }
        
        // Handle empty state
        if (!snapshot.hasData || snapshot.data!.isEmpty) {
          print('📭 No notifications found in Firestore');
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
                  'Complete tasks or take medications to see notifications here',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade500,
                  ),
                ),
              ],
            ),
          );
        }
        
        final notifications = snapshot.data!;
        print('📬 Loaded ${notifications.length} notifications from Firestore');
        
        // Add shrinkWrap and physics to prevent layout issues
        return ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: notifications.length,
          itemBuilder: (context, index) {
            final notif = notifications[index];
            
            // Map severity to color
            Color color;
            IconData icon;
            
            switch (notif.severity) {
              case 'positive':
                color = _positiveColor;
                icon = Icons.check_circle;
                break;
              case 'negative':
                color = _negativeColor;
                icon = Icons.error_outline;
                break;
              case 'warning':
                color = _warningColor;
                icon = Icons.warning_amber_rounded;
                break;
              default: // 'reminder'
                color = _reminderColor;
                icon = Icons.info_outline;
            }
            
            // Format timestamp
            final timeStr = DateFormat('MMM d, h:mm a').format(notif.timestamp);
            
            return _buildNotificationCard(
              title: notif.title,
              subtitle: notif.message,
              time: timeStr,
              icon: icon,
              color: color,
              onTap: () {
                // Mark as read when tapped
                _notificationService.markAsRead(notif.id);
                // Navigate to home screen
                Navigator.pushNamed(context, '/main');
              },
            );
          },
        );
      },
    );
  }
}

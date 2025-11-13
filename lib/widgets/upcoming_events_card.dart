import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/calendar_model.dart';
import '../services/calendar_service.dart';

class UpcomingEventsCard extends StatelessWidget {
  const UpcomingEventsCard({super.key});

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  Future<List<CalendarEvent>> _getUpcomingEvents() async {
    final allEvents = await CalendarService.loadAllEvents();
    final now = DateTime.now();

    // Flatten all events and filter for upcoming ones
    List<CalendarEvent> upcomingEvents = [];
    allEvents.forEach((date, events) {
      for (var event in events) {
        if (event.eventDate.isAfter(now)) {
          upcomingEvents.add(event);
        }
      }
    });

    // Sort by date and take top 5
    upcomingEvents.sort((a, b) => a.eventDate.compareTo(b.eventDate));
    return upcomingEvents.take(5).toList();
  }

  Color _getEventColor(String eventType) {
    switch (eventType) {
      case 'Appointment':
        return Colors.redAccent;
      case 'Event':
        return Colors.purpleAccent;
      case 'Medication':
        return Colors.orangeAccent;
      default:
        return Colors.amber.shade700;
    }
  }

  IconData _getEventIcon(String eventType) {
    switch (eventType) {
      case 'Appointment':
        return Icons.medical_services_rounded;
      case 'Event':
        return Icons.event_rounded;
      case 'Medication':
        return Icons.medication_rounded;
      default:
        return Icons.notifications_active_rounded;
    }
  }

  String _getTimeUntil(DateTime eventDate) {
    final now = DateTime.now();
    final difference = eventDate.difference(now);

    if (difference.inDays > 0) {
      return '${difference.inDays} day${difference.inDays > 1 ? 's' : ''}';
    } else if (difference.inHours > 0) {
      return '${difference.inHours} hour${difference.inHours > 1 ? 's' : ''}';
    } else if (difference.inMinutes > 0) {
      return '${difference.inMinutes} minute${difference.inMinutes > 1 ? 's' : ''}';
    } else {
      return 'Now';
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<CalendarEvent>>(
      future: _getUpcomingEvents(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return Container(
            height: 120,
            margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 8,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: const Center(child: CircularProgressIndicator()),
          );
        }

        if (!snapshot.hasData || snapshot.data!.isEmpty) {
          return Container(
            margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: Colors.grey.shade300, width: 2),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 8,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              children: [
                Icon(
                  Icons.calendar_today,
                  color: Colors.grey.shade400,
                  size: 40,
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'No Upcoming Events',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 18),
                          fontWeight: FontWeight.w700,
                          color: Colors.grey.shade700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Your calendar is clear!',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 14),
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        }

        final events = snapshot.data!;

        return Container(
          margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.1),
                blurRadius: 8,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
                child: Row(
                  children: [
                    Icon(
                      Icons.upcoming_rounded,
                      color: const Color(0xFF1565C0),
                      size: 28,
                    ),
                    const SizedBox(width: 12),
                    Text(
                      'Upcoming Events',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 20),
                        fontWeight: FontWeight.w800,
                        color: const Color(0xFF1565C0),
                      ),
                    ),
                  ],
                ),
              ),

              // Divider
              Divider(height: 1, color: Colors.grey.shade300),

              // Events list
              ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: events.length,
                separatorBuilder: (context, index) => Divider(
                  height: 1,
                  indent: 70,
                  endIndent: 20,
                  color: Colors.grey.shade200,
                ),
                itemBuilder: (context, index) {
                  final event = events[index];
                  final color = _getEventColor(event.eventType);
                  final icon = _getEventIcon(event.eventType);
                  final dateStr = DateFormat(
                    'MMM d, h:mm a',
                  ).format(event.eventDate);
                  final timeUntil = _getTimeUntil(event.eventDate);

                  return Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                    child: Row(
                      children: [
                        // Icon
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: color.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(icon, color: color, size: 24),
                        ),
                        const SizedBox(width: 16),

                        // Event details
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                event.title,
                                style: TextStyle(
                                  fontSize: _getResponsiveFontSize(context, 16),
                                  fontWeight: FontWeight.w600,
                                  color: Colors.black87,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Text(
                                dateStr,
                                style: TextStyle(
                                  fontSize: _getResponsiveFontSize(context, 13),
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ),
                        ),

                        // Time until badge
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: color.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            'in $timeUntil',
                            style: TextStyle(
                              fontSize: _getResponsiveFontSize(context, 12),
                              fontWeight: FontWeight.w600,
                              color: color,
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ],
          ),
        );
      },
    );
  }
}

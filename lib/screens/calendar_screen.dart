import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:table_calendar/table_calendar.dart';
import '../models/calendar_model.dart';
import '../services/calendar_service.dart';
import '../widgets/upcoming_events_card.dart';

class CalendarScreen extends StatefulWidget {
  const CalendarScreen({super.key});

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  static const Color _primaryColor = Color(0xFF1565C0);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _cardColor = Colors.white;
  static const Color _textPrimary = Color(0xFF2D3748);
  static const Color _textSecondary = Color(0xFF718096);

  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  Map<DateTime, List<CalendarEvent>> _events = {};
  late final ValueNotifier<List<CalendarEvent>> _selectedEvents;
  bool _isLoading = true;

  // Dialog controllers
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  String _selectedEventType = 'Reminder';
  TimeOfDay _selectedTime = TimeOfDay.now();

  @override
  void initState() {
    super.initState();
    _selectedDay = _focusedDay;
    _selectedEvents = ValueNotifier(_getEventsForDay(_selectedDay!));
    _loadFirestoreEvents();
  }

  @override
  void dispose() {
    _selectedEvents.dispose();
    _titleController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  DateTime _normalizeDay(DateTime date) {
    return DateTime.utc(date.year, date.month, date.day);
  }

  Future<void> _loadFirestoreEvents() async {
    try {
      final newEvents = await CalendarService.loadAllEvents();

      if (mounted) {
        setState(() {
          _events = newEvents;
          _isLoading = false;
          if (_selectedDay != null) {
            _selectedEvents.value = _getEventsForDay(_selectedDay!);
          }
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text('Error loading: ${e.toString()}'),
              backgroundColor: Colors.red),
        );
      }
    }
  }

  List<CalendarEvent> _getEventsForDay(DateTime day) {
    return _events[_normalizeDay(day)] ?? [];
  }

  void _onDaySelected(DateTime selectedDay, DateTime focusedDay) {
    if (!isSameDay(_selectedDay, selectedDay)) {
      setState(() {
        _selectedDay = selectedDay;
        _focusedDay = focusedDay;
      });
      _selectedEvents.value = _getEventsForDay(selectedDay);
    }
  }

  // --- CRUD OPERATIONS ---

  Future<void> _addEventToFirestore() async {
    final title = _titleController.text.trim();
    if (title.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Please enter a title'),
            backgroundColor: Colors.orange),
      );
      return;
    }

    final DateTime fullDateTime = DateTime(
      _selectedDay!.year,
      _selectedDay!.month,
      _selectedDay!.day,
      _selectedTime.hour,
      _selectedTime.minute,
    );

    final success = await CalendarService.addEvent(
      title: title,
      description: _descriptionController.text.trim(),
      eventDate: fullDateTime,
      eventType: _selectedEventType,
    );

    if (mounted) {
      if (success) {
        Navigator.of(context).pop();
        _loadFirestoreEvents();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('✅ Event saved!'), backgroundColor: Colors.green),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('❌ Failed to save event'),
              backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _updateEventInFirestore(String eventId) async {
    final title = _titleController.text.trim();
    if (title.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Please enter a title'),
            backgroundColor: Colors.orange),
      );
      return;
    }

    final DateTime fullDateTime = DateTime(
      _selectedDay!.year,
      _selectedDay!.month,
      _selectedDay!.day,
      _selectedTime.hour,
      _selectedTime.minute,
    );

    final success = await CalendarService.updateEvent(
      eventId: eventId,
      title: title,
      description: _descriptionController.text.trim(),
      eventDate: fullDateTime,
      eventType: _selectedEventType,
    );

    if (mounted) {
      if (success) {
        Navigator.of(context).pop();
        _loadFirestoreEvents();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('✅ Event updated!'), backgroundColor: Colors.green),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('❌ Update failed'),
              backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _deleteEventFromFirestore(String eventId) async {
    final success = await CalendarService.deleteEvent(eventId);

    if (mounted) {
      if (success) {
        Navigator.of(context).pop(); // Close edit dialog
        _loadFirestoreEvents();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('🗑️ Event deleted'), backgroundColor: Colors.grey),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('❌ Delete failed'),
              backgroundColor: Colors.red),
        );
      }
    }
  }

  // --- UI ---

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  // Widget _buildHeader(BuildContext context) {
  //   return Container(
  //     padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 20),
  //     child: Center(
  //       child: Text(
  //         'SILVER CARE',
  //         style: TextStyle(
  //           color: _textPrimary,
  //           fontSize: _getResponsiveFontSize(context, 28),
  //           fontFamily: 'Montserrat',
  //           fontWeight: FontWeight.w900,
  //           letterSpacing: 2.0,
  //         ),
  //       ),
  //     ),
  //   );
  // }

  Widget _ScreenHeaderButton(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 10, 20, 20),
      height: 80,
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(40),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.15),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
        border: Border.all(color: _primaryColor.withOpacity(0.2), width: 2),
      ),
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.calendar_month_rounded, size: 28, color: _primaryColor),
            const SizedBox(width: 12),
            Text(
              'CALENDAR',
              style: TextStyle(
                color: _textPrimary,
                fontSize: _getResponsiveFontSize(context, 24),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                letterSpacing: 1.2,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _backgroundColor,
      body: SafeArea(
        child: Column(
          children: [
            // _buildHeader(context),
            _ScreenHeaderButton(context),
            
            // Upcoming Events Card
            const UpcomingEventsCard(),
            
            Expanded(
              child: Container(
                width: double.infinity,
                margin: const EdgeInsets.symmetric(horizontal: 20),
                decoration: BoxDecoration(
                  color: _primaryColor,
                  borderRadius: const BorderRadius.all(Radius.circular(25)),
                  boxShadow: [
                    BoxShadow(
                      color: _primaryColor.withOpacity(0.3),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: _isLoading
                    ? const Center(
                        child: CircularProgressIndicator(color: Colors.white))
                    : SingleChildScrollView(
                        child: Column(
                          children: [
                            _buildCalendar(),
                            _buildEventList(),
                          ],
                        ),
                      ),
              ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showEventDialog(), // Standard add
        backgroundColor: _primaryColor,
        foregroundColor: Colors.white,
        elevation: 4,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: const Icon(Icons.add_rounded, size: 32),
      ),
    );
  }

  Widget _buildCalendar() {
    return TableCalendar<CalendarEvent>(
      firstDay: DateTime.utc(2020, 1, 1),
      lastDay: DateTime.utc(2030, 12, 31),
      focusedDay: _focusedDay,
      selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
      calendarFormat: _calendarFormat,
      eventLoader: _getEventsForDay,
      startingDayOfWeek: StartingDayOfWeek.sunday,
      onDaySelected: _onDaySelected,
      onFormatChanged: (format) {
        if (_calendarFormat != format) {
          setState(() => _calendarFormat = format);
        }
      },
      onPageChanged: (focusedDay) {
        _focusedDay = focusedDay;
        _loadFirestoreEvents();
      },
      calendarStyle: CalendarStyle(
        defaultTextStyle:
            const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
        weekendTextStyle:
            const TextStyle(color: Colors.white70, fontWeight: FontWeight.w500),
        todayDecoration: BoxDecoration(
          color: Colors.white.withOpacity(0.3),
          shape: BoxShape.circle,
        ),
        selectedDecoration: const BoxDecoration(
          color: Colors.white,
          shape: BoxShape.circle,
        ),
        selectedTextStyle:
            TextStyle(color: _primaryColor, fontWeight: FontWeight.w800),
        outsideTextStyle: TextStyle(color: Colors.white.withOpacity(0.3)),
        markerDecoration: const BoxDecoration(
          color: Colors.amberAccent,
          shape: BoxShape.circle,
        ),
        markersMaxCount: 3,
      ),
      headerStyle: const HeaderStyle(
        formatButtonVisible: false,
        titleCentered: true,
        titleTextStyle: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w700,
            fontFamily: 'Montserrat'),
        leftChevronIcon: Icon(Icons.chevron_left_rounded, color: Colors.white),
        rightChevronIcon: Icon(Icons.chevron_right_rounded, color: Colors.white),
      ),
      daysOfWeekStyle: const DaysOfWeekStyle(
        weekdayStyle: TextStyle(
            color: Colors.white70,
            fontWeight: FontWeight.w600,
            fontFamily: 'Montserrat'),
        weekendStyle: TextStyle(
            color: Colors.white70,
            fontWeight: FontWeight.w600,
            fontFamily: 'Montserrat'),
      ),
    );
  }

  Widget _buildEventList() {
    return ValueListenableBuilder<List<CalendarEvent>>(
      valueListenable: _selectedEvents,
      builder: (context, value, _) {
        if (value.isEmpty) {
          return const Padding(
            padding: EdgeInsets.all(40.0),
            child: Center(
              child: Text(
                'No events for this day.\nTap the "+" button to add one!',
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: Colors.white70,
                    fontSize: 16,
                    fontFamily: 'Montserrat'),
              ),
            ),
          );
        }
        return ListView.builder(
          padding: const EdgeInsets.all(20),
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: value.length,
          itemBuilder: (context, index) {
            return _buildEventCard(value[index]);
          },
        );
      },
    );
  }

  Widget _buildEventCard(CalendarEvent event) {
    IconData iconData;
    Color iconColor;

    switch (event.eventType) {
      case 'Appointment':
        iconData = Icons.medical_services_rounded;
        iconColor = Colors.redAccent;
        break;
      case 'Event':
        iconData = Icons.event_rounded;
        iconColor = Colors.purpleAccent;
        break;
      case 'Medication':
        iconData = Icons.medication_rounded;
        iconColor = Colors.orangeAccent;
        break;
      default:
        iconData = Icons.notifications_active_rounded;
        iconColor = Colors.amber.shade700;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _showEventDialog(event: event),
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: iconColor.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(iconData, color: iconColor, size: 26),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // TITLE - Highlighted
                      Text(
                        event.title,
                        style: TextStyle(
                          color: _primaryColor,
                          fontSize: _getResponsiveFontSize(context, 18),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if (event.description.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          event.description,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: _textSecondary,
                            fontSize: _getResponsiveFontSize(context, 14),
                            fontFamily: 'Inter',
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                      const SizedBox(height: 10),
                      // BOTTOM ROW: Time (Grey) and Type (Highlighted)
                      Row(
                        children: [
                          Icon(Icons.access_time_rounded,
                              color: _textSecondary, size: 16),
                          const SizedBox(width: 6),
                          Text(
                            DateFormat.jm().format(event.eventDate),
                            style: TextStyle(
                              color: _textSecondary,
                              fontSize: _getResponsiveFontSize(context, 14),
                              fontFamily: 'Inter',
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const Spacer(),
                          // TYPE - Highlighted
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: _primaryColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              event.eventType,
                              style: TextStyle(
                                color: _primaryColor,
                                fontSize: _getResponsiveFontSize(context, 12),
                                fontFamily: 'Montserrat',
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showEventDialog({CalendarEvent? event}) {
    final isEditing = event != null;

    if (isEditing) {
      _titleController.text = event.title;
      _descriptionController.text = event.description;

      const validTypes = ['Reminder', 'Appointment', 'Event'];
      if (validTypes.contains(event.eventType)) {
        _selectedEventType = event.eventType;
      } else {
        _selectedEventType = 'Event';
      }

      _selectedTime = TimeOfDay.fromDateTime(event.eventDate);
      _selectedDay = event.eventDate;
    } else {
      _titleController.clear();
      _descriptionController.clear();
      _selectedEventType = 'Reminder';
      _selectedTime = TimeOfDay.now();
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: _backgroundColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (BuildContext context, StateSetter setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom + 20,
                top: 25,
                left: 25,
                right: 25,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Container(
                        width: 50,
                        height: 5,
                        decoration: BoxDecoration(
                            color: Colors.grey.shade300,
                            borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                    const SizedBox(height: 25),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isEditing ? 'Edit Entry' : 'New Entry',
                          style: TextStyle(
                            color: _textPrimary,
                            fontSize: _getResponsiveFontSize(context, 22),
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        if (isEditing)
                          TextButton.icon(
                            onPressed: () {
                              showDialog(
                                context: context,
                                builder: (ctx) => AlertDialog(
                                  title: const Text('Delete Event?'),
                                  content: const Text(
                                      'Are you sure you want to delete this event?'),
                                  actions: [
                                    TextButton(
                                      onPressed: () => Navigator.pop(ctx),
                                      child: const Text('Cancel'),
                                    ),
                                    TextButton(
                                      onPressed: () {
                                        Navigator.pop(ctx);
                                        _deleteEventFromFirestore(event.id);
                                      },
                                      child: const Text('Delete',
                                          style: TextStyle(color: Colors.red)),
                                    ),
                                  ],
                                ),
                              );
                            },
                            icon: const Icon(Icons.delete_outline_rounded,
                                color: Colors.red, size: 24),
                            label: const Text('Delete',
                                style: TextStyle(
                                    color: Colors.red,
                                    fontWeight: FontWeight.w600,
                                    fontFamily: 'Montserrat')),
                            style: TextButton.styleFrom(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 12, vertical: 8),
                            ),
                          ),
                      ],
                    ),
                    Text(
                      DateFormat.yMMMd().format(_selectedDay!),
                      style: TextStyle(
                        color: _textSecondary,
                        fontSize: _getResponsiveFontSize(context, 16),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 25),
                    _buildDialogTextField(
                        _titleController, 'What is it?', Icons.edit_rounded),
                    const SizedBox(height: 15),
                    InkWell(
                      onTap: () async {
                        final TimeOfDay? picked = await showTimePicker(
                          context: context,
                          initialTime: _selectedTime,
                          builder: (context, child) {
                            return Theme(
                              data: Theme.of(context).copyWith(
                                colorScheme:
                                    ColorScheme.light(primary: _primaryColor),
                              ),
                              child: child!,
                            );
                          },
                        );
                        if (picked != null && picked != _selectedTime) {
                          setModalState(() {
                            _selectedTime = picked;
                          });
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 16),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey.shade400),
                          borderRadius: BorderRadius.circular(12),
                          color: Colors.white,
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.access_time_rounded,
                                color: _textSecondary),
                            const SizedBox(width: 12),
                            Text(
                              _selectedTime.format(context),
                              style: TextStyle(
                                  fontSize: 16,
                                  color: _textPrimary,
                                  fontFamily: 'Inter'),
                            ),
                            const Spacer(),
                            Icon(Icons.arrow_drop_down_rounded,
                                color: _textSecondary),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 15),
                    _buildEventTypeDropdown(setModalState),
                    const SizedBox(height: 15),
                    _buildDialogTextField(_descriptionController,
                        'Notes (Optional)', Icons.notes_rounded,
                        maxLines: 3),
                    const SizedBox(height: 30),
                    SizedBox(
                      width: double.infinity,
                      height: 55,
                      child: ElevatedButton(
                        onPressed: isEditing
                            ? () => _updateEventInFirestore(event.id)
                            : _addEventToFirestore,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _primaryColor,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16)),
                        ),
                        child: Text(
                          isEditing ? 'Update Entry' : 'Save Entry',
                          style: const TextStyle(
                            fontSize: 18,
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildDialogTextField(
      TextEditingController controller, String label, IconData icon,
      {int maxLines = 1}) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      style: const TextStyle(fontFamily: 'Inter'),
      decoration: InputDecoration(
        labelText: label,
        alignLabelWithHint: maxLines > 1,
        prefixIcon: Icon(icon, color: _textSecondary),
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey.shade400)),
        enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey.shade400)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: _primaryColor, width: 2),
        ),
        filled: true,
        fillColor: Colors.white,
      ),
    );
  }

  Widget _buildEventTypeDropdown(StateSetter setModalState) {
    return DropdownButtonFormField<String>(
      value: _selectedEventType,
      decoration: InputDecoration(
        labelText: 'Type',
        prefixIcon: Icon(Icons.category_rounded, color: _textSecondary),
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey.shade400)),
        enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey.shade400)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: _primaryColor, width: 2),
        ),
        filled: true,
        fillColor: Colors.white,
      ),
      items: ['Reminder', 'Appointment', 'Event'].map((String value) {
        return DropdownMenuItem<String>(
          value: value,
          child: Text(value, style: const TextStyle(fontFamily: 'Inter')),
        );
      }).toList(),
      onChanged: (String? newValue) {
        if (newValue != null) {
          setModalState(() {
            _selectedEventType = newValue;
          });
        }
      },
    );
  }
}
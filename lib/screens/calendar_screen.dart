import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:intl/intl.dart';
import 'package:table_calendar/table_calendar.dart';

class CalendarEvent {
  final String id;
  final String title;
  final String description;
  final DateTime eventDate;
  final String eventType;

  CalendarEvent({
    required this.id,
    required this.title,
    required this.description,
    required this.eventDate,
    required this.eventType,
  });

  factory CalendarEvent.fromDoc(DocumentSnapshot doc) {
    Map<String, dynamic> data = doc.data() as Map<String, dynamic>;
    return CalendarEvent(
      id: doc.id,
      title: data['title'] ?? '',
      description: data['description'] ?? '',
      eventDate: (data['eventDate'] as Timestamp).toDate(),
      eventType: data['eventType'] ?? 'Reminder',
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'title': title,
      'description': description,
      'eventDate': Timestamp.fromDate(eventDate),
      'eventType': eventType,
    };
  }
}

class CalendarScreen extends StatefulWidget {
  const CalendarScreen({super.key});

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  static const Color _primaryColor = Color(0xFF4CAF50);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _cardColor = Colors.white;
  static const Color _textPrimary = Color(0xFF2D3748);
  static const Color _textSecondary = Color(0xFF718096);

  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  Map<DateTime, List<CalendarEvent>> _events = {};
  late final ValueNotifier<List<CalendarEvent>> _selectedEvents;
  bool _isLoading = true;

  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  String _selectedEventType = 'Reminder';

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

  Future<void> _loadFirestoreEvents() async {
    final user = _auth.currentUser;
    if (user == null) {
      setState(() => _isLoading = false);
      return;
    }

    _events = {};

    try {
      final snapshot = await _firestore
          .collection('elderly')
          .doc(user.uid)
          .collection('calendarEvents')
          .get();

      for (var doc in snapshot.docs) {
        final event = CalendarEvent.fromDoc(doc);
        final day = DateTime.utc(event.eventDate.year, event.eventDate.month, event.eventDate.day);

        if (_events[day] == null) {
          _events[day] = [];
        }
        _events[day]!.add(event);
      }

      setState(() {
        _isLoading = false;
        _selectedEvents.value = _getEventsForDay(_selectedDay!);
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading events: ${e.toString()}'), backgroundColor: Colors.red),
        );
      }
    }
  }

  List<CalendarEvent> _getEventsForDay(DateTime day) {
    final dayUtc = DateTime.utc(day.year, day.month, day.day);
    return _events[dayUtc] ?? [];
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

  Future<void> _addEventToFirestore() async {
    final user = _auth.currentUser;
    if (user == null) return;

    final title = _titleController.text;
    if (title.isEmpty) return;

    final newEvent = CalendarEvent(
      id: '',
      title: title,
      description: _descriptionController.text,
      eventDate: _selectedDay!,
      eventType: _selectedEventType,
    );

    try {
      await _firestore
          .collection('elderly')
          .doc(user.uid)
          .collection('calendarEvents')
          .add(newEvent.toMap());

      if (mounted) {
        Navigator.of(context).pop();
        _titleController.clear();
        _descriptionController.clear();
        _selectedEventType = 'Reminder';
        _loadFirestoreEvents();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('✅ Event added successfully!'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('❌ Failed to add event: ${e.toString()}'), backgroundColor: Colors.red),
        );
      }
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  Widget _buildHeader(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 20),
      child: Center(
        child: Text(
          'SILVER CARE',
          style: TextStyle(
            color: _textPrimary,
            fontSize: _getResponsiveFontSize(context, 28),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w900,
            letterSpacing: 2.0,
          ),
        ),
      ),
    );
  }

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
            Icon(Icons.calendar_today_outlined, size: 28, color: _primaryColor),
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
            _buildHeader(context),
            _ScreenHeaderButton(context),
            Expanded(
              child: Container(
                width: double.infinity,
                margin: const EdgeInsets.symmetric(horizontal: 20),
                decoration: BoxDecoration(
                  color: _primaryColor,
                  borderRadius: const BorderRadius.all(Radius.circular(25)),
                  boxShadow: [
                    BoxShadow(
                      color: const Color.fromRGBO(0, 0, 0, 0.1),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: _isLoading
                    ? const Center(child: CircularProgressIndicator(color: Colors.white))
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
        onPressed: _showAddEventDialog,
        backgroundColor: _primaryColor,
        foregroundColor: Colors.white,
        child: const Icon(Icons.add),
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
        defaultTextStyle: const TextStyle(color: Colors.white),
        weekendTextStyle: const TextStyle(color: Colors.white70),
        todayDecoration: const BoxDecoration(
          color: Color.fromRGBO(255, 255, 255, 0.3),
          shape: BoxShape.circle,
        ),
        selectedDecoration: const BoxDecoration(
          color: Colors.white,
          shape: BoxShape.circle,
        ),
        selectedTextStyle: TextStyle(color: _primaryColor, fontWeight: FontWeight.bold),
        outsideTextStyle: const TextStyle(color: Color.fromRGBO(255, 255, 255, 0.4)),
        markerDecoration: const BoxDecoration(
          color: Colors.white70,
          shape: BoxShape.circle,
        ),
      ),
      headerStyle: const HeaderStyle(
        formatButtonVisible: false,
        titleCentered: true,
        titleTextStyle: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
        leftChevronIcon: Icon(Icons.chevron_left, color: Colors.white),
        rightChevronIcon: Icon(Icons.chevron_right, color: Colors.white),
      ),
      daysOfWeekStyle: const DaysOfWeekStyle(
        weekdayStyle: TextStyle(color: Colors.white70, fontWeight: FontWeight.w600),
        weekendStyle: TextStyle(color: Colors.white70, fontWeight: FontWeight.w600),
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
                style: TextStyle(color: Colors.white70, fontSize: 16, fontFamily: 'Inter'),
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
      case 'Medication':
        iconData = Icons.medication_outlined;
        iconColor = Colors.redAccent;
        break;
      case 'Appointment':
        iconData = Icons.medical_services_outlined;
        iconColor = Colors.blueAccent;
        break;
      default:
        iconData = Icons.alarm_outlined;
        iconColor = Colors.amber.shade700;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: iconColor.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(iconData, color: iconColor, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  event.title,
                  style: TextStyle(
                    color: _textPrimary,
                    fontSize: _getResponsiveFontSize(context, 16),
                    fontFamily: 'Montserrat',
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (event.description.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    event.description,
                    style: TextStyle(
                      color: _textSecondary,
                      fontSize: _getResponsiveFontSize(context, 14),
                      fontFamily: 'Inter',
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.access_time, color: _textSecondary, size: 14),
                    const SizedBox(width: 4),
                    Text(
                      DateFormat.jm().format(event.eventDate),
                      style: TextStyle(
                        color: _textSecondary,
                        fontSize: _getResponsiveFontSize(context, 13),
                        fontFamily: 'Inter',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showAddEventDialog() {
    _titleController.clear();
    _descriptionController.clear();
    _selectedEventType = 'Reminder';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: _backgroundColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (BuildContext context, StateSetter setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
                top: 20,
                left: 20,
                right: 20,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Add Event for ${DateFormat.yMMMd().format(_selectedDay!)}',
                      style: TextStyle(
                        color: _textPrimary,
                        fontSize: _getResponsiveFontSize(context, 20),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 24),
                    _buildDialogTextField(_titleController, 'Title', Icons.title),
                    const SizedBox(height: 16),
                    _buildDialogTextField(_descriptionController, 'Description (Optional)', Icons.description_outlined, maxLines: 3),
                    const SizedBox(height: 16),
                    _buildEventTypeDropdown(setModalState),
                    const SizedBox(height: 30),
                    ElevatedButton(
                      onPressed: _addEventToFirestore,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _primaryColor,
                        foregroundColor: Colors.white,
                        minimumSize: const Size(double.infinity, 50),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text(
                        'Save Event',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 16),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildDialogTextField(TextEditingController controller, String label, IconData icon, {int maxLines = 1}) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: _textSecondary),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: _primaryColor, width: 2),
        ),
      ),
    );
  }

  Widget _buildEventTypeDropdown(StateSetter setModalState) {
    return DropdownButtonFormField<String>(
      value: _selectedEventType,
      decoration: InputDecoration(
        labelText: 'Event Type',
        prefixIcon: const Icon(Icons.category_outlined, color: _textSecondary),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: _primaryColor, width: 2),
        ),
      ),
      items: ['Reminder', 'Medication', 'Appointment'].map((String value) {
        return DropdownMenuItem<String>(
          value: value,
          child: Text(value),
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
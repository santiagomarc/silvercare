import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/blood_pressure_service.dart';

// Simple blood pressure record class
class BloodPressureRecord {
  final double systolic;
  final double diastolic;
  final DateTime dateTime;
  final String source;

  BloodPressureRecord({
    required this.systolic,
    required this.diastolic,
    required this.dateTime,
    this.source = 'manual',
  });
}

class BloodPressureScreen extends StatefulWidget {
  const BloodPressureScreen({super.key});

  @override
  State<BloodPressureScreen> createState() => _BloodPressureScreenState();
}

class _BloodPressureScreenState extends State<BloodPressureScreen> {
  final TextEditingController _systolicController = TextEditingController();
  final TextEditingController _diastolicController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _selectedTime = TimeOfDay.now();

  bool _isLoading = false;
  List<BloodPressureRecord> _bpData = [];

  @override
  void initState() {
    super.initState();
    _loadBPData();
  }

  @override
  void dispose() {
    _systolicController.dispose();
    _diastolicController.dispose();
    super.dispose();
  }

  Future<void> _loadBPData() async {
    setState(() => _isLoading = true);
    try {
      final data = await BloodPressureService.getBloodPressureData(days: 30);
      setState(() {
        // Convert Map data to BloodPressureRecord with actual systolic and diastolic values
        _bpData = data.map((bpData) => BloodPressureRecord(
          systolic: bpData['systolic'] as double, // Use actual systolic from Firestore
          diastolic: bpData['diastolic'] as double, // Use actual diastolic from Firestore
          dateTime: bpData['measuredAt'] as DateTime,
          source: bpData['source'] as String,
        )).toList();
      });
    } catch (error) {
      _showErrorSnackBar('Failed to load BP data: $error');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clamped = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clamped;
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFFFF9800), // 🟠 Orange
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) setState(() => _selectedDate = picked);
  }

  Future<void> _selectTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFFFF9800),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) setState(() => _selectedTime = picked);
  }

 Future<void> _saveBloodPressureData() async {
  if (!_formKey.currentState!.validate()) return;

  setState(() => _isLoading = true);

  try {
    final DateTime measurementDateTime = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      _selectedTime.hour,
      _selectedTime.minute,
    );

    final double systolic = double.parse(_systolicController.text);
    final double diastolic = double.parse(_diastolicController.text);

    // Add to local BP data for immediate display
    final newBPRecord = BloodPressureRecord(
      systolic: systolic,
      diastolic: diastolic,
      dateTime: measurementDateTime,
      source: 'manual',
    );
    
    setState(() {
      _bpData.insert(0, newBPRecord); // show instantly
    });

    _systolicController.clear();
    _diastolicController.clear();
    _selectedDate = DateTime.now();
    _selectedTime = TimeOfDay.now();

    // 2️⃣ Save to Firestore asynchronously
    BloodPressureService.saveBloodPressureData(
      systolic: systolic,
      diastolic: diastolic,
      measuredAt: measurementDateTime,
    );

    _showSuccessSnackBar('Blood pressure saved!');
  } catch (error) {
    _showErrorSnackBar('Failed to save BP: $error');
  } finally {
    setState(() => _isLoading = false);
  }
}


  void _showSuccessSnackBar(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showErrorSnackBar(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final screenHeight = MediaQuery.of(context).size.height;

    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : SingleChildScrollView(
                padding: EdgeInsets.symmetric(
                  horizontal: screenWidth * 0.06,
                  vertical: screenHeight * 0.03,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // Header
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                         GestureDetector(
                    onTap: () => Navigator.of(context).pop(),
                    child: Container(
                      width: screenWidth * 0.12,
                      height: screenWidth * 0.12,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.2),
                            blurRadius: 2,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.arrow_back,
                        color: Colors.black54,
                        size: 20,
                      ),
                    ),
                  ),
                        Text(
                          'BLOOD PRESSURE',
                          style: TextStyle(
                            fontSize: _getResponsiveFontSize(context, 24),
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w800,
                            color: Colors.black,
                            shadows: [
                              Shadow(
                                offset: const Offset(0, 3),
                                blurRadius: 4,
                                color: Colors.black.withOpacity(0.4),
                              ),
                            ],
                          ),
                        ),
                        Container(
                    width: screenWidth * 0.12,
                    height: screenWidth * 0.12,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.2),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.bloodtype,
                      color: Colors.orange,
                      size: 20,
                    ),
                  ),
                      ],
                    ),

                    SizedBox(height: screenHeight * 0.06),

                    // Input Card
                    Form(
                      key: _formKey,
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 24),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFF9800), // 🟠 Orange card
                          borderRadius: BorderRadius.circular(25),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.5),
                              blurRadius: 8,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            Text(
                              'Enter Blood Pressure Reading:',
                              style: TextStyle(
                                fontSize: _getResponsiveFontSize(context, 20),
                                fontWeight: FontWeight.w800,
                                color: Colors.black,
                              ),
                            ),
                            const SizedBox(height: 20),

                            // Two input fields
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                _bpInput(context, _systolicController, 'MAX'),
                                const SizedBox(width: 8),
                                Text( '/',
                                    style: TextStyle(
                                      fontSize: _getResponsiveFontSize(context, 28),
                                      fontWeight: FontWeight.w700,
                                      color: Colors.black,
                                    )),
                                const SizedBox(width: 8), 
                                _bpInput(context, _diastolicController, 'MIN'),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text(
                              'mmHg',
                              style: TextStyle(
                                fontSize: _getResponsiveFontSize(context, 16),
                                fontWeight: FontWeight.w700,
                                color: Colors.black,
                              ),
                            ),
                            const SizedBox(height: 30),

                            // Date and Time
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              children: [
                                _picker(context, 'DATE',
                                    DateFormat('dd MMM yyyy').format(_selectedDate).toUpperCase(),
                                    Icons.calendar_today, _selectDate),
                                _picker(context, 'TIME',
                                    _selectedTime.format(context).toUpperCase(),
                                    Icons.access_time, _selectTime),
                              ],
                            ),
                            const SizedBox(height: 32),

                            // Save button
                            Container(
                              width: double.infinity,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(25),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.3),
                                    blurRadius: 3,
                                    offset: const Offset(0, 7),
                                  ),
                                ],
                              ),
                              child: ElevatedButton(
                                onPressed: _saveBloodPressureData,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.white,
                                  foregroundColor: Colors.black,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 16,
                                    horizontal: 40,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(25),
                                  ),
                                ),
                                child: Text(
                                  'SAVE',
                                  style: TextStyle(
                                    fontSize: _getResponsiveFontSize(context, 18),
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    SizedBox(height: screenHeight * 0.05),

                    // Records
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'RECORDS:',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 28),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w800,
                          color: Colors.black,
                          shadows: [
                          Shadow(
                        offset: const Offset(0, 3),
                        blurRadius: 4,
                        color: Colors.black.withOpacity(0.25),
                      ),
                    ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    ..._buildBPRecords(context),
                  ],
                ),
              ),
      ),
    );
  }

  Widget _bpInput(BuildContext context, TextEditingController controller, String label) {
    return Container(
      width: 110,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(25),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.3),
            blurRadius: 3,
            offset: const Offset(0, 7),
          ),
        ],
      ),
      child: TextFormField(
        controller: controller,
        keyboardType: TextInputType.number,
        textAlign: TextAlign.center,
        style: TextStyle(
          fontSize: _getResponsiveFontSize(context, 22),
          fontWeight: FontWeight.w800,
          color: Colors.black,
        ),
        decoration: InputDecoration(
          hintText: label,
          hintStyle: const TextStyle(color: Colors.black38),
          border: InputBorder.none,
        ),
        validator: (value) {
          if (value == null || value.isEmpty) return 'Required';
          final int? val = int.tryParse(value);
          if (val == null || val < 40 || val > 250) return 'Invalid';
          return null;
        },
      ),
    );
  }

  Widget _picker(BuildContext context, String label, String value, IconData icon, Function() onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Text(label,
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 18),
                fontWeight: FontWeight.w700,
              )),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.3),
                  blurRadius: 3,
                  offset: const Offset(0, 7),
                ),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  value,
                  style: TextStyle(
                    fontSize: _getResponsiveFontSize(context, 14),
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(width: 4),
                Icon(icon, size: 16, color: Colors.black54),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Widget _circleButton(IconData icon, {Color iconColor = Colors.black54}) {
  //   return Container(
  //     width: 40,
  //     height: 44,
  //     decoration: const BoxDecoration(
  //       color: Colors.white,
  //       shape: BoxShape.circle,
  //     ),
  //     child: Icon(icon, color: iconColor, size: 20),
  //   );
  // }

  List<Widget> _buildBPRecords(BuildContext context) {
    if (_bpData.isEmpty) {
      return [
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(25),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.15),
                blurRadius: 8,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: const Text('No blood pressure records yet.'),
        ),
      ];
    }

    return _bpData.take(10).map((data) {
      final sys = data.systolic.toInt();
      final dia = data.diastolic.toInt();
      String status = 'Normal';
      Color statusColor = Colors.green;
      if (sys > 140 || dia > 90) {
        status = 'High';
        statusColor = Colors.red;
      } else if (sys < 90 || dia < 60) {
        status = 'Low';
        statusColor = Colors.blue;
      }

      return Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(25),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.15),
                blurRadius: 8,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    DateFormat('EEE, dd MMM yyyy, hh:mm a').format(data.dateTime),
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 14),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '$sys / $dia mmHg',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 20),
                      fontWeight: FontWeight.w800,
                      color: Colors.black,
                    ),
                  ),
                ],
              ),
              Flexible(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: statusColor, width: 1),
                  ),
                  child: Text(
                    status,
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 10),
                      fontWeight: FontWeight.w600,
                      color: statusColor,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }).toList();
  }
}

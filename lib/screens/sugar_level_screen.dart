import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/health_data_model.dart';
import '../services/sugar_level_service.dart';

class SugarLevelScreen extends StatefulWidget {
  const SugarLevelScreen({super.key});

  @override
  State<SugarLevelScreen> createState() => _SugarLevelScreenState();
}

class _SugarLevelScreenState extends State<SugarLevelScreen> {
  final TextEditingController _sugarController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _selectedTime = TimeOfDay.now();

  bool _isLoading = false;
  List<HealthDataModel> _sugarData = [];

  @override
  void initState() {
    super.initState();
    _loadSugarData();
  }

  @override
  void dispose() {
    _sugarController.dispose();
    super.dispose();
  }

  Future<void> _loadSugarData() async {
    setState(() => _isLoading = true);
    try {
      final data = await SugarLevelService.getSugarLevelData(days: 30);
      setState(() {
        _sugarData = data;
      });
    } catch (error) {
      _showErrorSnackBar('Failed to load sugar data: $error');
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
              primary: Colors.orange, // 🟠 Orange
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
              primary: Colors.orange,
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

  Future<void> _saveSugarLevelData() async {
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

      final double sugarValue = double.parse(_sugarController.text);

      final newSugar = HealthDataModel(
        id: '',
        elderlyId: 'tempUser', // replace with _auth.currentUser!.uid later
        type: 'sugar_level',
        measuredAt: measurementDateTime,
        createdAt: DateTime.now(),
        source: 'manual',
        value: sugarValue, 
      );

      setState(() {
        _sugarData.insert(0, newSugar);
      });

      _sugarController.clear();
      _selectedDate = DateTime.now();
      _selectedTime = TimeOfDay.now();

      SugarLevelService.saveSugarLevelData(
        value: sugarValue,
        measuredAt: measurementDateTime,
      );

      _showSuccessSnackBar('Sugar level saved!');
    } catch (error) {
      _showErrorSnackBar('Failed to save sugar data: $error');
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
      backgroundColor: const Color(0xFFF5E6D3),
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
                          'SUGAR LEVEL',
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
                            Icons.stacked_bar_chart,
                            color: Colors.greenAccent,
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
                          color: Colors.greenAccent, // 🟠 Orange card
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
                              'GLUCOSE VALUE:',
                              style: TextStyle(
                                fontSize: _getResponsiveFontSize(context, 28),
                                fontWeight: FontWeight.w800,
                                color: Colors.black,
                              ),
                            ),
                            const SizedBox(height: 20),

                            _sugarInput(context, _sugarController, 'mg/dL'),
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
                                onPressed: _saveSugarLevelData,
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
                    ..._buildSugarRecords(context),
                  ],
                ),
              ),
      ),
    );
  }

  Widget _sugarInput(BuildContext context, TextEditingController controller, String unit) {
  return Row(
    mainAxisAlignment: MainAxisAlignment.center,
    crossAxisAlignment: CrossAxisAlignment.center,
    children: [
      Container(
        width: 160,
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
            hintText: 'Enter value',
            hintStyle: TextStyle(color: Colors.black38, fontSize: _getResponsiveFontSize(context, 18)),
            border: InputBorder.none,
          ),
          validator: (value) {
            if (value == null || value.isEmpty) return 'Required';
            final double? val = double.tryParse(value);
            if (val == null || val < 40 || val > 400) return 'Invalid';
            return null;
          },
        ),
      ),
      const SizedBox(width: 10),
      Text(
        unit,
        style: TextStyle(
          fontSize: _getResponsiveFontSize(context, 18),
          fontWeight: FontWeight.w700,
          color: Colors.black,
        ),
      ),
    ],
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

  List<Widget> _buildSugarRecords(BuildContext context) {
    if (_sugarData.isEmpty) {
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
          child: const Text('No sugar level records yet.'),
        ),
      ];
    }

    return _sugarData.take(10).map((data) {
      final value = data.value?.toInt() ?? 0;
      String status = 'Normal';
      Color statusColor = Colors.green;

      if (value >= 180) {
        status = 'Very High';
        statusColor = Colors.red;
      } else if (value >= 140) {
        status = 'High';
        statusColor = Colors.orange;
      } else if (value < 70) {
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
                    DateFormat('EEE, dd MMM yyyy, hh:mm a').format(data.measuredAt),
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 14),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '$value mg/dL',
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
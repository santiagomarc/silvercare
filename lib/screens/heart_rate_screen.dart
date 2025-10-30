import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/health_data_model.dart';
import '../services/heart_rate_service.dart';
import '../services/google_fit_service.dart';

class HeartRateScreen extends StatefulWidget {
  const HeartRateScreen({super.key});

  @override
  State<HeartRateScreen> createState() => _HeartRateScreenState();
}

class _HeartRateScreenState extends State<HeartRateScreen> {
  final TextEditingController _bpmController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _selectedTime = TimeOfDay.now();
  
  bool _isLoading = false;
  bool _isSyncing = false;
  List<HealthDataModel> _heartRateData = [];
  HeartRateStats? _stats;

  @override
  void initState() {
    super.initState();
    _loadHeartRateData();
  }

  @override
  void dispose() {
    _bpmController.dispose();
    super.dispose();
  }

  Future<void> _loadHeartRateData() async {
    setState(() => _isLoading = true);
    try {
      final data = await HeartRateService.getHeartRateData(days: 7);
      final stats = await HeartRateService.getHeartRateStats(days: 30);
      setState(() {
        _heartRateData = data;
        _stats = stats;
      });
    } catch (error) {
      _showErrorSnackBar('Failed to load heart rate data: $error');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFFFF73CB),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  Future<void> _selectTime() async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: _selectedTime,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFFFF73CB),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedTime) {
      setState(() {
        _selectedTime = picked;
      });
    }
  }

  Future<void> _saveHeartRate() async {
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

      final double bpm = double.parse(_bpmController.text);
      await HeartRateService.saveHeartRateData(
        bpm: bpm,
        measuredAt: measurementDateTime,
        source: 'manual',
      );

      _bpmController.clear();
      _showSuccessSnackBar('Heart rate saved successfully!');
      setState(() {
        _selectedDate = DateTime.now();
        _selectedTime = TimeOfDay.now();
      });
      await _loadHeartRateData();
    } catch (error) {
      _showErrorSnackBar('Failed to save heart rate: $error');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _syncFromGoogleFit() async {
    setState(() => _isSyncing = true);
    try {
      // Sign in if not already signed in
      if (!GoogleFitService.isSignedIn) {
        final account = await GoogleFitService.signInWithGoogle();
        if (account == null) {
          _showInfoSnackBar('Google Fit sign-in was cancelled');
          return;
        }
        _showSuccessSnackBar('Connected to Google Fit as ${account.email}');
      }

      // Sync data from Google Fit (only gets new data, prevents duplicates)
      final List<HealthDataModel> syncedData = await HeartRateService.syncFromGoogleFit(days: 7);
      
      if (syncedData.isNotEmpty) {
        _showSuccessSnackBar('Synced ${syncedData.length} new heart rate readings from Google Fit');
        await _loadHeartRateData(); // Refresh the display
      } else {
        _showInfoSnackBar('No new heart rate data found in Google Fit. Make sure your smartwatch is syncing to Google Fit.');
      }
    } catch (error) {
      print('❌ Google Fit sync error: $error');
      _showErrorSnackBar('Google Fit sync failed: $error');
    } finally {
      setState(() => _isSyncing = false);
    }
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }

  void _showInfoSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.blue,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
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
              // Header Section
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Back button with better UX
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

                  // Screen title in header
                  Text(
                    'HEART RATE',
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 30),
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

                  // Heart icon with animation potential
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
                      Icons.favorite,
                      color: Colors.redAccent,
                      size: 20,
                    ),
                  ),
                ],
              ),

              SizedBox(height: screenHeight * 0.06),

              // Heart Rate Input Card
              Form(
                key: _formKey,
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 24),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFF73CB),
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
                      // Input Label
                      Text(
                        'HEART RATE',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 20),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w800,
                          color: Colors.black,
                          shadows: [
                            Shadow(
                              offset: const Offset(0, 3),
                              blurRadius: 3,
                              color: Colors.black.withOpacity(0.25),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      
                      // Heart Rate Input Field
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Container(
                            width: 120,
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
                              controller: _bpmController,
                              keyboardType: TextInputType.number,
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: _getResponsiveFontSize(context, 32),
                                fontFamily: 'Montserrat',
                                fontWeight: FontWeight.w800,
                                color: Colors.black,
                              ),
                              decoration: InputDecoration(
                                hintText: '--',
                                hintStyle: TextStyle(
                                  fontSize: _getResponsiveFontSize(context, 32),
                                  fontFamily: 'Montserrat',
                                  fontWeight: FontWeight.w800,
                                  color: Colors.black38,
                                ),
                                border: InputBorder.none,
                                contentPadding: EdgeInsets.zero,
                                isDense: true,
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Required';
                                }
                                if (value.length > 3) {
                                  return 'Max 3 digits';
                                }
                                final int? bpm = int.tryParse(value);
                                if (bpm == null || bpm < 30 || bpm > 220) {
                                  return 'Invalid BPM';
                                }
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: 16),
                          Text(
                            'BPM',
                            style: TextStyle(
                              fontSize: _getResponsiveFontSize(context, 28),
                              fontFamily: 'Montserrat',
                              fontWeight: FontWeight.w800,
                              color: Colors.black,
                              shadows: [
                                Shadow(
                                  offset: const Offset(0, 3),
                                  blurRadius: 3,
                                  color: Colors.black.withOpacity(0.25),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      
                      const SizedBox(height: 32),
                      
                      // Date and Time Pickers
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          // Date Picker
                          GestureDetector(
                            onTap: _selectDate,
                            child: Column(
                              children: [
                                Text(
                                  'DATE',
                                  style: TextStyle(
                                    fontSize: _getResponsiveFontSize(context, 18),
                                    fontFamily: 'Montserrat',
                                    fontWeight: FontWeight.w700,
                                    color: Colors.black,
                                  ),
                                ),
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
                                        DateFormat('dd MMM yyyy').format(_selectedDate).toUpperCase(),
                                        style: TextStyle(
                                          fontSize: _getResponsiveFontSize(context, 14),
                                          fontFamily: 'Montserrat',
                                          fontWeight: FontWeight.w600,
                                          color: Colors.black,
                                        ),
                                      ),
                                      const SizedBox(width: 4),
                                      const Icon(Icons.calendar_today, size: 16, color: Colors.black54),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          
                          // Time Picker
                          GestureDetector(
                            onTap: _selectTime,
                            child: Column(
                              children: [
                                Text(
                                  'TIME',
                                  style: TextStyle(
                                    fontSize: _getResponsiveFontSize(context, 18),
                                    fontFamily: 'Montserrat',
                                    fontWeight: FontWeight.w700,
                                    color: Colors.black,
                                  ),
                                ),
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
                                        _selectedTime.format(context).toUpperCase(),
                                        style: TextStyle(
                                          fontSize: _getResponsiveFontSize(context, 14),
                                          fontFamily: 'Montserrat',
                                          fontWeight: FontWeight.w600,
                                          color: Colors.black,
                                        ),
                                      ),
                                      const SizedBox(width: 4),
                                      const Icon(Icons.access_time, size: 16, color: Colors.black54),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      
                      const SizedBox(height: 32),
                      
                      // Save Button
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
                          onPressed: _saveHeartRate,
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
                            elevation: 0,
                            shadowColor: Colors.transparent,
                          ).copyWith(
                            overlayColor: WidgetStateProperty.all(Colors.grey.withOpacity(0.1)),
                          ),
                          child: Text(
                            'SAVE',
                            style: TextStyle(
                              fontSize: _getResponsiveFontSize(context, 18),
                              fontFamily: 'Montserrat',
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.0,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              SizedBox(height: screenHeight * 0.04),

              // Google Fit Sync Card
              _buildGoogleFitCard(),

              SizedBox(height: screenHeight * 0.05),

              // Records Section
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

              SizedBox(height: screenHeight * 0.02),

              // Real Heart Rate Records
              ..._buildSampleRecords(context),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildGoogleFitCard() {
    return Container(
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Text(
              'GOOGLE FIT SYNC',
              style: TextStyle(
                fontSize: _getResponsiveFontSize(context, 22),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                color: Colors.black,
                shadows: [
                  Shadow(
                    offset: const Offset(0, 3),
                    blurRadius: 3,
                    color: Colors.black.withOpacity(0.25),
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    const Icon(Icons.fitness_center, color: Color(0xFF4285F4), size: 24),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        GoogleFitService.isSignedIn
                            ? 'Connected as ${GoogleFitService.currentUser?.email ?? 'Unknown'}'
                            : 'Connect to sync smartwatch data',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 16),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w600,
                          color: Colors.black87,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0F8FF),
                    borderRadius: BorderRadius.circular(15),
                    border: Border.all(color: const Color(0xFF4285F4).withOpacity(0.3)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '💡 How this works:',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 14),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w700,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '• Your smartwatch syncs heart rate to Google Fit\n• This app fetches new data and prevents duplicates\n• Works with any Google Fit compatible device\n• Only latest readings are added to your records',
                        style: TextStyle(
                          fontSize: _getResponsiveFontSize(context, 13),
                          fontFamily: 'Montserrat',
                          fontWeight: FontWeight.w500,
                          color: const Color(0xFF6C757D),
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                Container(
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
                  child: ElevatedButton.icon(
                    onPressed: _isSyncing ? null : _syncFromGoogleFit,
                    icon: _isSyncing
                        ? SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                            ),
                          )
                        : const Icon(Icons.sync, color: Colors.white, size: 20),
                    label: Text(
                      _isSyncing 
                          ? 'Syncing...' 
                          : GoogleFitService.isSignedIn 
                              ? 'Sync from Google Fit'
                              : 'Connect Google Fit',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 16),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        letterSpacing: 1.0,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF4285F4),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(25),
                      ),
                      elevation: 0,
                      shadowColor: Colors.transparent,
                    ),
                  ),
                ),
                if (GoogleFitService.isSignedIn) ...[
                  const SizedBox(height: 12),
                  TextButton.icon(
                    onPressed: () async {
                      await GoogleFitService.signOut();
                      setState(() {});
                      _showInfoSnackBar('Disconnected from Google Fit');
                    },
                    icon: const Icon(Icons.logout, size: 16),
                    label: Text(
                      'Disconnect Google Fit',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 14),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.grey[600],
                    ),
                  ),
                ],
                const SizedBox(height: 10),
              ],
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _buildSampleRecords(BuildContext context) {
    // Show real data if available, otherwise show empty state
    if (_heartRateData.isEmpty) {
      return [
        Container(
          width: double.infinity,
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
          child: Column(
            children: [
              Icon(
                Icons.favorite_border,
                size: 48,
                color: Colors.grey[400],
              ),
              const SizedBox(height: 16),
              Text(
                'No heart rate records yet',
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 18),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[600],
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Add your first reading above or sync from Google Fit',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: _getResponsiveFontSize(context, 14),
                  fontFamily: 'Montserrat',
                  fontWeight: FontWeight.w500,
                  color: Colors.grey[500],
                ),
              ),
            ],
          ),
        ),
      ];
    }

    // Show real heart rate data (sorted by most recent first)
    return _heartRateData.take(10).map((data) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: _recordCard(
          context,
          date: DateFormat('EEEE, dd MMM yyyy, hh:mm a').format(data.measuredAt).toUpperCase(),
          bpm: '${data.value.toInt()} BPM',
          heartRate: data.value.toInt(),
          source: data.source, // Now using the actual source field
        ),
      );
    }).toList();
  }

  Widget _recordCard(
    BuildContext context, {
    required String date,
    required String bpm,
    required int heartRate,
    String source = 'manual',
  }) {
    // Determine heart rate status color
    Color statusColor = Colors.green;
    String status = 'Normal';
    
    if (heartRate < 60) {
      statusColor = Colors.blue;
      status = 'Low';
    } else if (heartRate > 100) {
      statusColor = Colors.red;
      status = 'High';
    }

    return Container(
      width: double.infinity,
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
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      date,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 14),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w600,
                        color: Colors.black87,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      bpm,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 20),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w800,
                        color: Colors.black,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: statusColor,
                        width: 1,
                      ),
                    ),
                    child: Text(
                      status,
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 12),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w600,
                        color: statusColor,
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: source == 'google_fit' ? const Color(0xFF4285F4).withOpacity(0.1) : Colors.grey[200],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      source == 'google_fit' ? 'Google Fit' : 'Manual',
                      style: TextStyle(
                        fontSize: _getResponsiveFontSize(context, 10),
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w600,
                        color: source == 'google_fit' ? const Color(0xFF4285F4) : Colors.grey[600],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
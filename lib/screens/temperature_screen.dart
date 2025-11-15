import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/health_data_model.dart';
import '../services/temperature_service.dart';
import '../services/google_fit_service.dart';

class TemperatureScreen extends StatefulWidget {
  const TemperatureScreen({super.key});

  @override
  State<TemperatureScreen> createState() => _TemperatureScreenState();
}

class _TemperatureScreenState extends State<TemperatureScreen> {
  final TextEditingController _tempController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _selectedTime = TimeOfDay.now();

  bool _isLoading = false;
  bool _isSyncing = false;
  bool _isAutoSyncing = false;
  List<HealthDataModel> _tempData = [];

  String _selectedUnit = '°C'; // 🔹 Default unit

  @override
  void initState() {
    super.initState();
    _loadTempData();
    // Auto-sync on screen entry (runs after first frame)
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _autoSyncFromGoogleFit();
    });
  }

  /// Auto-sync temperature data from Google Fit
  Future<void> _autoSyncFromGoogleFit() async {
    setState(() => _isAutoSyncing = true);
    try {
      print('🔄 [AUTO-SYNC] Starting temperature auto-sync...');
      
      // Check if signed in to Google Fit
      if (!GoogleFitService.isSignedIn) {
        print('⚠️ [AUTO-SYNC] Not signed in to Google Fit, skipping auto-sync');
        return;
      }
      
      print('✅ [AUTO-SYNC] Signed in to Google Fit, fetching data...');
      final count = await TemperatureService.syncFromGoogleFit(days: 30); // Extended to 30 days
      print('📊 [AUTO-SYNC] Synced $count new temperature readings');
      
      if (count > 0) {
        await _loadTempData(); // Reload to show synced data
      }
    } catch (e) {
      print('⚠️ [AUTO-SYNC] Auto-sync failed (silent): $e');
      // Silent fail - user can manually sync if needed
    } finally {
      setState(() => _isAutoSyncing = false);
    }
  }

  /// Manual sync from Google Fit
  Future<void> _syncFromGoogleFit() async {
    if (_isAutoSyncing) {
      _showErrorSnackBar('Auto-sync in progress, please wait...');
      return;
    }
    setState(() => _isSyncing = true);
    try {
      // Sign in if not already signed in
      if (!GoogleFitService.isSignedIn) {
        final account = await GoogleFitService.signInWithGoogle();
        if (account == null) {
          _showErrorSnackBar('Google sign-in cancelled');
          return;
        }
        _showSuccessSnackBar('Connected to Google Fit as ${account.email}');
      }

      // Sync data from Google Fit
      print('🔄 [MANUAL-SYNC] Starting manual temperature sync...');
      final count = await TemperatureService.syncFromGoogleFit(days: 30); // Extended to 30 days
      print('📊 [MANUAL-SYNC] Synced $count new temperature readings');
      await _loadTempData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(count > 0 
              ? '✅ Synced $count new temperature reading${count > 1 ? "s" : ""}'
              : 'No new data from Google Fit'),
            backgroundColor: count > 0 ? Colors.green : Colors.orange,
          ),
        );
      }
    } catch (e) {
      _showErrorSnackBar('Sync failed: $e');
    } finally {
      setState(() => _isSyncing = false);
    }
  }

  @override
  void dispose() {
    _tempController.dispose();
    super.dispose();
  }

  Future<void> _loadTempData() async {
    setState(() => _isLoading = true);
    try {
      final data = await TemperatureService.getTemperatureData(days: 30);
      setState(() {
        _tempData = data;
      });
    } catch (error) {
      _showErrorSnackBar('Failed to load temperature data: $error');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.light(
            primary: Colors.orange,
            onPrimary: Colors.white,
            surface: Colors.white,
            onSurface: Colors.black,
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) setState(() => _selectedDate = picked);
  }

  Future<void> _selectTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _selectedTime,
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.light(
            primary: Colors.orange,
            onPrimary: Colors.white,
            surface: Colors.white,
            onSurface: Colors.black,
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) setState(() => _selectedTime = picked);
  }

  Future<void> _saveTempData() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      final DateTime measuredAt = DateTime(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        _selectedTime.hour,
        _selectedTime.minute,
      );

      double tempValue = double.parse(_tempController.text);

      // 🔹 Convert to Celsius before saving if user entered °F
      double celsiusValue = _selectedUnit == '°F'
          ? (tempValue - 32) * 5 / 9
          : tempValue;

      await TemperatureService.saveTemperatureData(
        value: celsiusValue,
        measuredAt: measuredAt,
        unit: _selectedUnit,
      );

      final newTemp = HealthDataModel(
        id: '',
        elderlyId: 'tempUser',
        type: 'temperature',
        measuredAt: measuredAt,
        createdAt: DateTime.now(),
        source: 'manual',
        value: celsiusValue,
      );

      setState(() {
        _tempData.insert(0, newTemp);
      });

      _tempController.clear();
      _selectedDate = DateTime.now();
      _selectedTime = TimeOfDay.now();

      _showSuccessSnackBar('Temperature saved successfully!');
    } catch (error) {
      _showErrorSnackBar('Failed to save temperature data: $error');
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
                            child: const Icon(Icons.arrow_back,
                                color: Colors.black54, size: 20),
                          ),
                        ),
                        Text(
                          'TEMPERATURE',
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
                            Icons.thermostat,
                            color: Colors.lightBlueAccent,
                            size: 22,
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
                        padding: const EdgeInsets.symmetric(
                            vertical: 30, horizontal: 24),
                        decoration: BoxDecoration(
                          color: Colors.lightBlueAccent,
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
                              'TEMPERATURE VALUE:',
                              style: TextStyle(
                                fontSize: _getResponsiveFontSize(context, 24),
                                fontWeight: FontWeight.w800,
                                color: Colors.black,
                              ),
                            ),
                            const SizedBox(height: 20),

                            _tempInput(context),
                            const SizedBox(height: 30),

                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              children: [
                                _picker(
                                  context,
                                  'DATE',
                                  DateFormat('dd MMM yyyy')
                                      .format(_selectedDate)
                                      .toUpperCase(),
                                  Icons.calendar_today,
                                  _selectDate,
                                ),
                                _picker(
                                  context,
                                  'TIME',
                                  _selectedTime.format(context).toUpperCase(),
                                  Icons.access_time,
                                  _selectTime,
                                ),
                              ],
                            ),
                            const SizedBox(height: 32),

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
                                onPressed: _saveTempData,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.white,
                                  foregroundColor: Colors.black,
                                  padding: const EdgeInsets.symmetric(
                                      vertical: 16, horizontal: 40),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(25),
                                  ),
                                ),
                                child: Text(
                                  'SAVE',
                                  style: TextStyle(
                                    fontSize:
                                        _getResponsiveFontSize(context, 18),
                                    fontWeight: FontWeight.w700,
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
                    ..._buildTempRecords(context),
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
                        '• Your smartwatch syncs temperature to Google Fit\n• This app fetches new data and prevents duplicates\n• Works with any Google Fit compatible device\n• Only latest readings are added to your records',
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
                    onPressed: (_isSyncing || _isAutoSyncing) ? null : _syncFromGoogleFit,
                    icon: (_isSyncing || _isAutoSyncing)
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                            ),
                          )
                        : const Icon(Icons.sync, color: Colors.white, size: 20),
                    label: Text(
                      (_isSyncing || _isAutoSyncing)
                          ? (_isAutoSyncing ? 'Auto-syncing...' : 'Syncing...') 
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
                      _showErrorSnackBar('Disconnected from Google Fit');
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

  Widget _tempInput(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
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
            controller: _tempController,
            keyboardType: TextInputType.number,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: _getResponsiveFontSize(context, 22),
              fontWeight: FontWeight.w800,
              color: Colors.black,
            ),
            decoration: InputDecoration(
              hintText: 'Enter value',
              hintStyle: TextStyle(
                  color: Colors.black38,
                  fontSize: _getResponsiveFontSize(context, 18)),
              border: InputBorder.none,
            ),
            validator: (value) {
              if (value == null || value.isEmpty) return 'Required';
              final double? val = double.tryParse(value);
              if (val == null || val < 30 || val > 45) return 'Invalid';
              return null;
            },
          ),
        ),
        const SizedBox(width: 15),
       Container(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
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
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: _selectedUnit,
              alignment: Alignment.center,
              dropdownColor: Colors.white, 
              iconEnabledColor: Colors.black, 
              style: const TextStyle(
                fontSize: 16,
                color: Colors.black, 
                fontWeight: FontWeight.w700,
              ),
              onChanged: (val) => setState(() => _selectedUnit = val!),
              items: const [
                DropdownMenuItem(
                  value: '°C',
                  child: Center(
                    child: Text(
                      '°Celsius',
                      style: TextStyle(
                        fontSize: 16,
                        fontFamily: 'Montserrat',
                        color: Colors.black,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
                DropdownMenuItem(
                  value: '°F',
                  child: Center(
                    child: Text(
                      '°Fahrenheit',
                      style: TextStyle(
                        fontSize: 16,
                        fontFamily: 'Montserrat',
                        color: Colors.black,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        )
      ],
    );
  }

  Widget _picker(BuildContext context, String label, String value,
      IconData icon, Function() onTap) {
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

  List<Widget> _buildTempRecords(BuildContext context) {
    if (_tempData.isEmpty) {
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
          child: const Text('No temperature records yet.'),
        ),
      ];
    }

    return _tempData.take(10).map((data) {
      final double celsius = data.value ?? 0;
      final double fahrenheit = (celsius * 9 / 5) + 32;
      final String display =
          _selectedUnit == '°F' ? '${fahrenheit.toStringAsFixed(1)} °F' : '${celsius.toStringAsFixed(1)} °C';

      Color statusColor = Colors.green;
      String status = 'Normal';
      if (celsius > 38) {
        status = 'High Fever';
        statusColor = Colors.red;
      } else if (celsius > 37) {
        status = 'Mild Fever';
        statusColor = Colors.orange;
      } else if (celsius < 35) {
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
                    DateFormat('EEE, dd MMM yyyy, hh:mm a')
                        .format(data.measuredAt),
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 14),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    display,
                    style: TextStyle(
                      fontSize: _getResponsiveFontSize(context, 20),
                      fontWeight: FontWeight.w800,
                      color: Colors.black,
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
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
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: data.source == 'google_fit' ? const Color(0xFF4285F4) : Colors.grey[600],
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          data.source == 'google_fit' ? Icons.cloud_done : Icons.edit,
                          color: Colors.white,
                          size: 10,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          data.source == 'google_fit' ? 'Google Fit' : 'Manual',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: _getResponsiveFontSize(context, 9),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
    }).toList();
  }
}
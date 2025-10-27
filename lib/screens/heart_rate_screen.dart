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

  Future<void> _saveManualHeartRate() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      final double bpm = double.parse(_bpmController.text);
      await HeartRateService.saveHeartRateData(
        bpm: bpm,
        measuredAt: DateTime.now(),
        source: 'manual',
      );
      
      _bpmController.clear();
      _showSuccessSnackBar('Heart rate saved successfully!');
      await _loadHeartRateData();
    } catch (error) {
      _showErrorSnackBar('Failed to save heart rate: $error');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.green),
    );
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  void _showInfoSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.blue),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text(
          'Heart Rate Monitor',
          style: TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF1E1E1E)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _isLoading ? null : _loadHeartRateData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildStatsCard(),
                  const SizedBox(height: 20),
                  _buildManualInputCard(),
                  const SizedBox(height: 20),
                  _buildGoogleFitCard(),
                  const SizedBox(height: 20),
                  _buildRecentDataCard(),
                ],
              ),
            ),
    );
  }

  Widget _buildStatsCard() {
    if (_stats == null || !_stats!.hasData) {
      return _buildCard(
        title: 'Heart Rate Overview',
        child: const Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'No heart rate data available yet.\nAdd your first reading below!',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Color(0xFF6C757D),
              fontSize: 16,
            ),
          ),
        ),
      );
    }

    return _buildCard(
      title: 'Heart Rate Overview (Last 30 Days)',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: _buildStatItem('Average', _stats!.averageDisplay, Colors.blue),
            ),
            Expanded(
              child: _buildStatItem('Range', _stats!.rangeDisplay, Colors.orange),
            ),
            Expanded(
              child: _buildStatItem('Readings', '${_stats!.count}', Colors.green),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(String label, String value, Color color) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(
            fontSize: 14,
            color: Color(0xFF6C757D),
          ),
        ),
      ],
    );
  }

  Widget _buildManualInputCard() {
    return _buildCard(
      title: 'Manual Entry',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _bpmController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Heart Rate (BPM)',
                  hintText: 'Enter your heart rate',
                  suffixText: 'bpm',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter your heart rate';
                  }
                  final int? bpm = int.tryParse(value);
                  if (bpm == null || bpm < 30 || bpm > 220) {
                    return 'Please enter a valid heart rate (30-220 bpm)';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _isLoading ? null : _saveManualHeartRate,
                icon: const Icon(Icons.favorite, color: Colors.white),
                label: const Text(
                  'Save Heart Rate',
                  style: TextStyle(color: Colors.white, fontSize: 16),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
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

      // Sync data from Google Fit
      final List<HealthDataModel> syncedData = await HeartRateService.syncFromGoogleFit(days: 7);
      
      if (syncedData.isNotEmpty) {
        _showSuccessSnackBar('Synced ${syncedData.length} heart rate readings from Google Fit');
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

  Widget _buildGoogleFitCard() {
    return _buildCard(
      title: 'Google Fit Sync',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                const Icon(Icons.fitness_center, color: Color(0xFF4285F4)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    GoogleFitService.isSignedIn
                        ? 'Connected to Google Fit as ${GoogleFitService.currentUser?.email}'
                        : 'Connect to Google Fit for smartwatch data',
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF0F8FF),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFF4285F4).withOpacity(0.3)),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '💡 How this works:',
                    style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                  ),
                  SizedBox(height: 4),
                  Text(
                    '• Connect your Google account that has Google Fit\n• Your smartwatch syncs heart rate to Google Fit\n• This app fetches that data into SilverCare\n• Works with any Google Fit compatible device',
                    style: TextStyle(color: Color(0xFF6C757D), fontSize: 12),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _isSyncing ? null : _syncFromGoogleFit,
              icon: _isSyncing
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Icon(Icons.sync, color: Colors.white),
              label: Text(
                _isSyncing 
                    ? 'Syncing...' 
                    : GoogleFitService.isSignedIn 
                        ? 'Sync from Google Fit'
                        : 'Connect Google Fit',
                style: const TextStyle(color: Colors.white, fontSize: 16),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF4285F4),
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            if (GoogleFitService.isSignedIn) ...[
              const SizedBox(height: 8),
              TextButton.icon(
                onPressed: () async {
                  await GoogleFitService.signOut();
                  setState(() {});
                  _showInfoSnackBar('Disconnected from Google Fit (SilverCare login preserved)');
                },
                icon: const Icon(Icons.logout, size: 16),
                label: const Text('Disconnect Google Fit'),
                style: TextButton.styleFrom(
                  foregroundColor: Colors.grey[600],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildRecentDataCard() {
    if (_heartRateData.isEmpty) {
      return _buildCard(
        title: 'Recent Readings',
        child: const Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'No recent heart rate readings',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Color(0xFF6C757D),
              fontSize: 16,
            ),
          ),
        ),
      );
    }

    return _buildCard(
      title: 'Recent Readings (Last 7 Days)',
      child: Column(
        children: _heartRateData.take(10).map((data) {
          return ListTile(
            leading: CircleAvatar(
              backgroundColor: data.isNormalRange ? Colors.green : Colors.orange,
              child: const Icon(Icons.favorite, color: Colors.white, size: 20),
            ),
            title: Text(
              '${data.value.toInt()} bpm',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              DateFormat('MMM dd, yyyy • hh:mm a').format(data.measuredAt),
            ),
            trailing: Chip(
              label: Text(
                data.type == 'heart_rate' ? 'HR' : data.type,
                style: const TextStyle(fontSize: 10),
              ),
              backgroundColor: const Color(0xFFF8F9FA),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildCard({required String title, required Widget child}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text(
              title,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: Color(0xFF1E1E1E),
              ),
            ),
          ),
          const Divider(height: 1),
          child,
        ],
      ),
    );
  }
}
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:intl/intl.dart';
import '../../models/sos_alert_model.dart';
import '../../services/sos_service.dart';
import '../../services/sos_listener_service.dart';

/// Screen displayed to caregivers when an SOS alert is received
/// Shows elder's location on map, vitals, and allows acknowledging/resolving the alert
class SOSAlertScreen extends StatefulWidget {
  final String? alertId;

  const SOSAlertScreen({super.key, this.alertId});

  @override
  State<SOSAlertScreen> createState() => _SOSAlertScreenState();
}

class _SOSAlertScreenState extends State<SOSAlertScreen> {
  final SOSService _sosService = SOSService();
  SOSAlertModel? _alert;
  bool _loading = true;
  GoogleMapController? _mapController;

  @override
  void initState() {
    super.initState();
    _loadAlert();
  }

  @override
  void dispose() {
    _mapController?.dispose();
    super.dispose();
  }

  Future<void> _loadAlert() async {
    if (widget.alertId == null) {
      setState(() => _loading = false);
      return;
    }

    final alert = await _sosService.getAlertById(widget.alertId!);
    setState(() {
      _alert = alert;
      _loading = false;
    });
  }

  Future<void> _stopAlarm() async {
    await SOSListenerService.stopSOSAlarm();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Alarm stopped')),
    );
  }

  Future<void> _acknowledgeAlert() async {
    if (_alert == null) return;

    await _sosService.acknowledgeAlert(_alert!.id);
    await SOSListenerService.stopSOSAlarm();

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('✅ Alert acknowledged')),
      );
      setState(() {
        _alert = _alert!.copyWith(status: 'acknowledged');
      });
    }
  }

  Future<void> _resolveAlert() async {
    if (_alert == null) return;

    await _sosService.resolveAlert(_alert!.id);
    await SOSListenerService.stopSOSAlarm();

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('✅ Alert resolved')),
      );
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('SOS Alert'),
          backgroundColor: Colors.red,
        ),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_alert == null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('SOS Alert'),
          backgroundColor: Colors.red,
        ),
        body: const Center(
          child: Text('Alert not found'),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('🚨 EMERGENCY SOS ALERT'),
        backgroundColor: Colors.red,
        foregroundColor: Colors.white,
        elevation: 4,
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Alert Status Banner
            _buildStatusBanner(),

            // Map Section
            if (_alert!.location != null) _buildMapSection(),

            // Elder Info Card
            _buildElderInfoCard(),

            // Vitals Card
            if (_alert!.vitalsSummary?.hasData ?? false) _buildVitalsCard(),

            // Action Buttons
            _buildActionButtons(),

            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusBanner() {
    Color statusColor;
    IconData statusIcon;
    String statusText;

    switch (_alert!.status) {
      case 'active':
        statusColor = Colors.red;
        statusIcon = Icons.warning_amber_rounded;
        statusText = 'ACTIVE - Needs Immediate Attention';
        break;
      case 'acknowledged':
        statusColor = Colors.orange;
        statusIcon = Icons.check_circle_outline;
        statusText = 'ACKNOWLEDGED - In Progress';
        break;
      case 'resolved':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        statusText = 'RESOLVED';
        break;
      default:
        statusColor = Colors.grey;
        statusIcon = Icons.info_outline;
        statusText = 'Unknown Status';
    }

    return Container(
      padding: const EdgeInsets.all(16),
      color: statusColor,
      child: Row(
        children: [
          Icon(statusIcon, color: Colors.white, size: 28),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              statusText,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMapSection() {
    final location = _alert!.location!;
    final latLng = LatLng(location.latitude, location.longitude);

    return Container(
      height: 300,
      margin: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.2),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: GoogleMap(
          initialCameraPosition: CameraPosition(
            target: latLng,
            zoom: 15,
          ),
          markers: {
            Marker(
              markerId: const MarkerId('elder_location'),
              position: latLng,
              icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
              infoWindow: InfoWindow(
                title: _alert!.elderName,
                snippet: location.address ?? 'Current Location',
              ),
            ),
          },
          onMapCreated: (controller) {
            _mapController = controller;
          },
          myLocationButtonEnabled: true,
          zoomControlsEnabled: true,
        ),
      ),
    );
  }

  Widget _buildElderInfoCard() {
    final location = _alert!.location;
    final timestamp = DateFormat('MMM dd, yyyy - hh:mm a').format(_alert!.timestamp);

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.person, color: Colors.red, size: 32),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _alert!.elderName,
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'SOS Triggered',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const Divider(height: 24),
          _buildInfoRow(Icons.access_time, 'Time', timestamp),
          if (location?.address != null)
            _buildInfoRow(Icons.location_on, 'Location', location!.address!),
          if (location?.accuracy != null)
            _buildInfoRow(
              Icons.my_location,
              'Accuracy',
              '±${location!.accuracy!.toStringAsFixed(0)}m',
            ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: Colors.grey[600]),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                    fontFamily: 'Montserrat',
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                    fontFamily: 'Montserrat',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVitalsCard() {
    final vitals = _alert!.vitalsSummary!;
    final lastUpdated = vitals.lastUpdated != null
        ? DateFormat('MMM dd, hh:mm a').format(vitals.lastUpdated!)
        : 'Unknown';

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.favorite, color: Colors.red, size: 28),
              const SizedBox(width: 12),
              const Text(
                'Latest Vitals',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Montserrat',
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Last updated: $lastUpdated',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
              fontFamily: 'Montserrat',
            ),
          ),
          const Divider(height: 24),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              if (vitals.bloodPressureFormatted != null)
                _buildVitalChip(
                  Icons.monitor_heart,
                  'Blood Pressure',
                  vitals.bloodPressureFormatted!,
                  Colors.blue,
                ),
              if (vitals.heartRate != null)
                _buildVitalChip(
                  Icons.favorite,
                  'Heart Rate',
                  '${vitals.heartRate!.toInt()} bpm',
                  Colors.red,
                ),
              if (vitals.sugarLevel != null)
                _buildVitalChip(
                  Icons.water_drop,
                  'Blood Sugar',
                  '${vitals.sugarLevel!.toInt()} mg/dL',
                  Colors.orange,
                ),
              if (vitals.temperature != null)
                _buildVitalChip(
                  Icons.thermostat,
                  'Temperature',
                  '${vitals.temperature!.toStringAsFixed(1)}°C',
                  Colors.green,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildVitalChip(IconData icon, String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 18, color: color),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  color: color,
                  fontWeight: FontWeight.w600,
                  fontFamily: 'Montserrat',
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Montserrat',
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons() {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Stop Alarm Button (always visible if alarm is playing)
          if (SOSListenerService.isAlarmPlaying)
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton.icon(
                onPressed: _stopAlarm,
                icon: const Icon(Icons.volume_off, size: 24),
                label: const Text(
                  'Stop Alarm',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Montserrat',
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.orange,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 4,
                ),
              ),
            ),

          if (SOSListenerService.isAlarmPlaying) const SizedBox(height: 12),

          // Acknowledge Button (only for active alerts)
          if (_alert!.status == 'active')
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton.icon(
                onPressed: _acknowledgeAlert,
                icon: const Icon(Icons.check_circle_outline, size: 24),
                label: const Text(
                  'Acknowledge Alert',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Montserrat',
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 4,
                ),
              ),
            ),

          if (_alert!.status == 'active') const SizedBox(height: 12),

          // Resolve Button (for active or acknowledged alerts)
          if (_alert!.status == 'active' || _alert!.status == 'acknowledged')
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton.icon(
                onPressed: _resolveAlert,
                icon: const Icon(Icons.check_circle, size: 24),
                label: const Text(
                  'Mark as Resolved',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Montserrat',
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 4,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

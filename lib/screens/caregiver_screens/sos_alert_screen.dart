import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:intl/intl.dart';
import '../../models/sos_alert_model.dart';
import '../../services/sos_service.dart';
import '../../services/sos_listener_service.dart';

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

  // Modern Palette
  final Color _emergencyRed = const Color(0xFFE53935);
  final Color _bgGrey = const Color(0xFFF5F7FA);
  final Color _cardWhite = Colors.white;
  final Color _textDark = const Color(0xFF1A202C);

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
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Alarm silenced')));
    }
  }

  Future<void> _acknowledgeAlert() async {
    if (_alert == null) return;
    await _sosService.acknowledgeAlert(_alert!.id);
    await SOSListenerService.stopSOSAlarm();
    if (mounted) {
      setState(() => _alert = _alert!.copyWith(status: 'acknowledged'));
    }
  }

  Future<void> _resolveAlert() async {
    if (_alert == null) return;
    await _sosService.resolveAlert(_alert!.id);
    await SOSListenerService.stopSOSAlarm();
    if (mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return Scaffold(body: const Center(child: CircularProgressIndicator(color: Colors.red)));
    if (_alert == null) return const Scaffold(body: Center(child: Text('Alert not found')));

    return Scaffold(
      backgroundColor: _bgGrey,
      appBar: AppBar(
        title: const Text('EMERGENCY ALERT', style: TextStyle(fontFamily: 'Montserrat', fontWeight: FontWeight.w700, letterSpacing: 1.0)),
        centerTitle: true,
        backgroundColor: _emergencyRed,
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          if (SOSListenerService.isAlarmPlaying)
            IconButton(icon: const Icon(Icons.volume_off), onPressed: _stopAlarm),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildModernStatusHeader(),
              Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (_alert!.location != null) ...[
                      _buildSectionTitle('CURRENT LOCATION'),
                      const SizedBox(height: 8),
                      _buildMapCard(), // Bigger map
                      const SizedBox(height: 24),
                    ],
                    _buildSectionTitle('PATIENT DETAILS'),
                    const SizedBox(height: 8),
                    _buildElderInfoCard(),
                    const SizedBox(height: 24),
                    if (_alert!.vitalsSummary?.hasData ?? false) ...[
                      _buildSectionTitle('LIVE VITALS'),
                      const SizedBox(height: 8),
                      _buildCompactVitalsList(), // New compact list
                      const SizedBox(height: 24),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: _buildBottomActionBar(),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4),
      child: Text(
        title,
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.grey[600], letterSpacing: 1.2, fontFamily: 'Montserrat'),
      ),
    );
  }

  Widget _buildModernStatusHeader() {
    Color bgColor;
    IconData icon;
    String title;
    String subtitle;

    switch (_alert!.status) {
      case 'active':
        bgColor = _emergencyRed;
        icon = Icons.warning_amber_rounded;
        title = 'ACTIVE SOS';
        subtitle = 'Immediate attention required';
        break;
      case 'acknowledged':
        bgColor = Colors.orange.shade800;
        icon = Icons.check_circle_outline;
        title = 'ACKNOWLEDGED';
        subtitle = 'Response in progress';
        break;
      case 'resolved':
        bgColor = Colors.green.shade700;
        icon = Icons.check_circle;
        title = 'RESOLVED';
        subtitle = 'Situation is stable';
        break;
      default:
        bgColor = Colors.grey;
        icon = Icons.info_outline;
        title = 'UNKNOWN';
        subtitle = 'Status unknown';
    }

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
        boxShadow: [BoxShadow(color: bgColor.withOpacity(0.3), blurRadius: 12, offset: const Offset(0, 6))],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), shape: BoxShape.circle),
            child: Icon(icon, color: Colors.white, size: 32),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w900, fontFamily: 'Montserrat', letterSpacing: 0.5)),
                Text(subtitle, style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 14, fontFamily: 'Inter')),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMapCard() {
    final location = _alert!.location!;
    final latLng = LatLng(location.latitude, location.longitude);
    
    // Fallback if address is null (GPS Coordinates)
    final addressDisplay = location.address ?? 
        '${latLng.latitude.toStringAsFixed(5)}, ${latLng.longitude.toStringAsFixed(5)}';

    return Container(
      height: 400, // ⬆️ Increased Height
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 15, offset: const Offset(0, 5))],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          children: [
            GoogleMap(
              initialCameraPosition: CameraPosition(target: latLng, zoom: 16),
              markers: {
                Marker(
                  markerId: const MarkerId('elder_location'),
                  position: latLng,
                  icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
                ),
              },
              onMapCreated: (c) => _mapController = c,
              zoomControlsEnabled: false,
              mapToolbarEnabled: false,
              myLocationButtonEnabled: false,
            ),
            Positioned(
              bottom: 16, left: 16, right: 16,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 8, offset: const Offset(0, 4))],
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: _emergencyRed.withOpacity(0.1), shape: BoxShape.circle),
                      child: Icon(Icons.location_on, color: _emergencyRed, size: 24),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text("REPORTED LOCATION", style: TextStyle(color: Colors.grey[500], fontSize: 10, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 2),
                          Text(
                            addressDisplay,
                            style: TextStyle(color: _textDark, fontWeight: FontWeight.w700, fontSize: 14, fontFamily: 'Montserrat'),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildElderInfoCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardWhite,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: Colors.grey.shade200,
            child: const Icon(Icons.person, color: Colors.grey, size: 28),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(_alert!.elderName, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: _textDark, fontFamily: 'Montserrat')),
              Text(
                DateFormat('hh:mm a • MMM dd, yyyy').format(_alert!.timestamp),
                style: TextStyle(fontSize: 13, color: Colors.grey[600], fontFamily: 'Inter', fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // 🛠️ NEW: Compact Vitals List (Row based, not Grid)
  Widget _buildCompactVitalsList() {
    final vitals = _alert!.vitalsSummary!;
    
    return Column(
      children: [
        Row(
          children: [
            if (vitals.bloodPressureFormatted != null)
              Expanded(child: _buildCompactVitalTile('BP', vitals.bloodPressureFormatted!, 'mmHg', Icons.monitor_heart_outlined, Colors.blue)),
            if (vitals.bloodPressureFormatted != null && vitals.heartRate != null)
              const SizedBox(width: 12),
            if (vitals.heartRate != null)
              Expanded(child: _buildCompactVitalTile('HR', vitals.heartRate!.toInt().toString(), 'bpm', Icons.favorite_border, _emergencyRed)),
          ],
        ),
        if (vitals.sugarLevel != null || vitals.temperature != null) ...[
          const SizedBox(height: 12),
          Row(
            children: [
              if (vitals.sugarLevel != null)
                Expanded(child: _buildCompactVitalTile('Glucose', vitals.sugarLevel!.toInt().toString(), 'mg/dL', Icons.water_drop_outlined, Colors.orange)),
              if (vitals.sugarLevel != null && vitals.temperature != null)
                const SizedBox(width: 12),
              if (vitals.temperature != null)
                Expanded(child: _buildCompactVitalTile('Temp', vitals.temperature!.toStringAsFixed(1), '°C', Icons.thermostat_outlined, Colors.teal)),
            ],
          ),
        ]
      ],
    );
  }

  Widget _buildCompactVitalTile(String label, String value, String unit, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14), // Tighter padding
      decoration: BoxDecoration(
        color: _cardWhite,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
            child: Icon(icon, size: 20, color: color),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Colors.grey[500])),
              Row(
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: _textDark, fontFamily: 'Montserrat')),
                  const SizedBox(width: 2),
                  Text(unit, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Colors.grey[500])),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActionBar() {
    if (_alert!.status == 'resolved') return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 30),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, -5))],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (_alert!.status == 'active')
            SizedBox(
              width: double.infinity,
              height: 54,
              child: ElevatedButton.icon(
                onPressed: _acknowledgeAlert,
                icon: const Icon(Icons.check_circle_outline, size: 22),
                label: const Text('ACKNOWLEDGE ALERT', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, fontFamily: 'Montserrat', letterSpacing: 0.5)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue.shade600,
                  foregroundColor: Colors.white,
                  elevation: 4,
                  shadowColor: Colors.blue.withOpacity(0.4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
              ),
            ),
          if (_alert!.status == 'active') const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            height: 54,
            child: _alert!.status == 'active'
                ? OutlinedButton.icon(
                    onPressed: _resolveAlert,
                    icon: const Icon(Icons.check_circle, size: 22),
                    label: const Text('MARK AS RESOLVED', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, fontFamily: 'Montserrat', letterSpacing: 0.5)),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.green.shade600,
                      side: BorderSide(color: Colors.green.shade600, width: 2),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                  )
                : ElevatedButton.icon(
                    onPressed: _resolveAlert,
                    icon: const Icon(Icons.check_circle, size: 22),
                    label: const Text('MARK AS RESOLVED', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, fontFamily: 'Montserrat', letterSpacing: 0.5)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green.shade600,
                      foregroundColor: Colors.white,
                      elevation: 4,
                      shadowColor: Colors.green.withOpacity(0.4),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}
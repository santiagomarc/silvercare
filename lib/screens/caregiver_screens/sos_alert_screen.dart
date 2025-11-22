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

  // Modern Color Palette
  final Color _emergencyRed = const Color(0xFFE53935);
  final Color _bgGrey = const Color(0xFFF5F7FA);
  final Color _cardWhite = Colors.white;
  final Color _textDark = const Color(0xFF2D3748);

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
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Alarm silenced'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  Future<void> _acknowledgeAlert() async {
    if (_alert == null) return;

    await _sosService.acknowledgeAlert(_alert!.id);
    await SOSListenerService.stopSOSAlarm();

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Alert acknowledged'),
          backgroundColor: Colors.green,
          behavior: SnackBarBehavior.floating,
        ),
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
        const SnackBar(
          content: Text('✅ Alert resolved'),
          backgroundColor: Colors.green,
          behavior: SnackBarBehavior.floating,
        ),
      );
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        backgroundColor: _bgGrey,
        body: const Center(child: CircularProgressIndicator(color: Colors.red)),
      );
    }

    if (_alert == null) {
      return Scaffold(
        backgroundColor: _bgGrey,
        appBar: AppBar(title: const Text('SOS Alert'), backgroundColor: _emergencyRed),
        body: const Center(child: Text('Alert details not found')),
      );
    }

    return Scaffold(
      backgroundColor: _bgGrey,
      appBar: AppBar(
        title: const Text(
          'EMERGENCY ALERT',
          style: TextStyle(
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w700,
            letterSpacing: 1.0,
            fontSize: 18,
          ),
        ),
        centerTitle: true,
        backgroundColor: _emergencyRed,
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          if (SOSListenerService.isAlarmPlaying)
            IconButton(
              icon: const Icon(Icons.volume_off),
              onPressed: _stopAlarm,
              tooltip: 'Silence Alarm',
            ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // 1. Status Header
              _buildModernStatusHeader(),

              Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 2. Map Section (Bigger now)
                    if (_alert!.location != null) ...[
                      _buildSectionTitle('LOCATION'),
                      const SizedBox(height: 8),
                      _buildMapCard(),
                      const SizedBox(height: 24),
                    ],

                    // 3. Patient Info
                    _buildSectionTitle('PATIENT DETAILS'),
                    const SizedBox(height: 8),
                    _buildElderInfoCard(),
                    const SizedBox(height: 24),

                    // 4. Vitals Grid (Compact & Tighter)
                    if (_alert!.vitalsSummary?.hasData ?? false) ...[
                      _buildSectionTitle('LIVE VITALS'),
                      const SizedBox(height: 8),
                      _buildCompactVitalsGrid(),
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
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: Colors.grey[600],
          letterSpacing: 1.2,
          fontFamily: 'Montserrat',
        ),
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
        bgColor = Colors.orange.shade700;
        icon = Icons.check_circle_outline;
        title = 'ACKNOWLEDGED';
        subtitle = 'Response in progress';
        break;
      case 'resolved':
        bgColor = Colors.green.shade600;
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
      padding: const EdgeInsets.fromLTRB(20, 15, 20, 25),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
        boxShadow: [
          BoxShadow(
            color: bgColor.withOpacity(0.4),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.25),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: Colors.white, size: 36),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    fontFamily: 'Montserrat',
                    letterSpacing: 0.5,
                  ),
                ),
                Text(
                  subtitle,
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.95),
                    fontSize: 14,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w500,
                  ),
                ),
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

    return Container(
      height: 350, // Increased height as requested
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          children: [
            GoogleMap(
              initialCameraPosition: CameraPosition(
                target: latLng,
                zoom: 16, // Zoomed in slightly more
              ),
              markers: {
                Marker(
                  markerId: const MarkerId('elder_location'),
                  position: latLng,
                  icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
                ),
              },
              onMapCreated: (controller) => _mapController = controller,
              zoomControlsEnabled: false,
              myLocationButtonEnabled: false,
              mapToolbarEnabled: false,
            ),
            // Modern Address Overlay
            Positioned(
              bottom: 16,
              left: 16,
              right: 16,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: _emergencyRed.withOpacity(0.1),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(Icons.location_on, color: _emergencyRed, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            "Reported Location",
                            style: TextStyle(
                              color: Colors.grey[500],
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.5,
                            ),
                          ),
                          Text(
                            location.address ?? 'Unknown Address',
                            style: TextStyle(
                              color: _textDark,
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                              fontFamily: 'Montserrat',
                            ),
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
    final timestamp = DateFormat('hh:mm a • MMM dd').format(_alert!.timestamp);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: _cardWhite,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Hero(
            tag: 'profile_pic',
            child: Container(
              width: 60,
              height: 60,
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.grey.shade200, width: 2),
              ),
              child: const Icon(Icons.person, color: Colors.grey, size: 32),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _alert!.elderName,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: _textDark,
                    fontFamily: 'Montserrat',
                  ),
                ),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.access_time, size: 12, color: Colors.grey[600]),
                      const SizedBox(width: 6),
                      Text(
                        timestamp,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[700],
                          fontWeight: FontWeight.w600,
                          fontFamily: 'Inter',
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // NEW: Tighter, more compact grid
  Widget _buildCompactVitalsGrid() {
    final vitals = _alert!.vitalsSummary!;
    final List<Widget> vitalCards = [];

    if (vitals.bloodPressureFormatted != null) {
      vitalCards.add(_buildCompactVitalTile(
        'BLOOD PRESSURE',
        vitals.bloodPressureFormatted!,
        'mmHg',
        Icons.monitor_heart_outlined,
        Colors.blue,
      ));
    }
    if (vitals.heartRate != null) {
      vitalCards.add(_buildCompactVitalTile(
        'HEARTRATE',
        vitals.heartRate!.toInt().toString(),
        'bpm',
        Icons.favorite_border,
        _emergencyRed,
      ));
    }
    if (vitals.sugarLevel != null) {
      vitalCards.add(_buildCompactVitalTile(
        'SUGAR LEVEL',
        vitals.sugarLevel!.toInt().toString(),
        'mg/dL',
        Icons.water_drop_outlined,
        Colors.orange,
      ));
    }
    if (vitals.temperature != null) {
      vitalCards.add(_buildCompactVitalTile(
        'TEMPERATURE',
        vitals.temperature!.toStringAsFixed(1),
        '°C',
        Icons.thermostat_outlined,
        Colors.teal,
      ));
    }

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 2.2, // WIDER aspect ratio makes them short & compact
      children: vitalCards,
    );
  }

  Widget _buildCompactVitalTile(
    String label,
    String value,
    String unit,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: _cardWhite,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade100),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 20, color: color),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  label.toUpperCase(),
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Colors.grey[500],
                    letterSpacing: 0.5,
                  ),
                ),
                const SizedBox(height: 2),
                RichText(
                  text: TextSpan(
                    children: [
                      TextSpan(
                        text: value,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: _textDark,
                          fontFamily: 'Montserrat',
                        ),
                      ),
                      TextSpan(
                        text: ' $unit',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey[500],
                          fontFamily: 'Inter',
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
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
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 20,
            offset: const Offset(0, -5),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (_alert!.status == 'active')
            _buildActionButton(
              label: 'ACKNOWLEDGE ALERT',
              icon: Icons.check_circle_outline,
              color: Colors.blue.shade600,
              onPressed: _acknowledgeAlert,
            ),
          
          if (_alert!.status == 'active') 
            const SizedBox(height: 12),

          _buildActionButton(
            label: 'MARK AS RESOLVED',
            icon: Icons.check_circle,
            color: Colors.green.shade600,
            onPressed: _resolveAlert,
            isOutlined: _alert!.status == 'active',
          ),
        ],
      ),
    );
  }

  Widget _buildActionButton({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onPressed,
    bool isOutlined = false,
  }) {
    if (isOutlined) {
      return SizedBox(
        width: double.infinity,
        height: 54,
        child: OutlinedButton.icon(
          onPressed: onPressed,
          icon: Icon(icon, size: 20),
          label: Text(
            label,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              fontFamily: 'Montserrat',
              letterSpacing: 0.5,
            ),
          ),
          style: OutlinedButton.styleFrom(
            foregroundColor: color,
            side: BorderSide(color: color, width: 2),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          ),
        ),
      );
    }

    return SizedBox(
      width: double.infinity,
      height: 54,
      child: ElevatedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon, size: 20),
        label: Text(
          label,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            fontFamily: 'Montserrat',
            letterSpacing: 0.5,
          ),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: color,
          foregroundColor: Colors.white,
          elevation: 4,
          shadowColor: color.withOpacity(0.4),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
      ),
    );
  }
}
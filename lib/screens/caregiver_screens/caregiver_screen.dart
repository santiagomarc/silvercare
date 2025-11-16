import 'package:flutter/material.dart';
import 'package:silvercare/services/sos_listener_service.dart';
import 'caregiver_dashboard.dart';
import 'caregiver_profile.dart';

class CaregiverScreen extends StatefulWidget {
  const CaregiverScreen({super.key});

  @override
  State<CaregiverScreen> createState() => _CaregiverScreenState();
}

class _CaregiverScreenState extends State<CaregiverScreen> {

  int _selectedIndex = 0;

  final List<Widget> _pages = [
    CaregiverDashboard(),
    CaregiverProfile()
  ];
  final List<String> _titles = ["CAREGIVER DASHBOARD", "PROFILE"];

  @override
  void initState() {
    super.initState();
    // Start listening for SOS alerts
    SOSListenerService().startListening();
  }

  @override
  void dispose() {
    // Stop listening when screen is disposed
    SOSListenerService().stopListening();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 2,
        titleSpacing: 0,
        automaticallyImplyLeading: false, // Remove back button
        title: Row(
          children: [
            const SizedBox(width: 16), // Add left padding
            CircleAvatar(
              radius: 18,
              backgroundColor: Colors.transparent,
              child: SizedBox(
                width: 32,
                height: 32,
                child: Image.asset('assets/icons/silvercare.png', fit: BoxFit.contain),
              ),
            ),
            const SizedBox(width: 12),
            Text(
              _titles[_selectedIndex],
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Colors.black,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
      body: SafeArea(child: _pages[_selectedIndex]),
      bottomNavigationBar: SafeArea(
        child: BottomNavigationBar(
          currentIndex: _selectedIndex,
          onTap: (index){
            setState(() {
              _selectedIndex = index;
            });
          },
          items: [
            BottomNavigationBarItem(
              icon: Icon(Icons.dashboard),
              label: 'Dashboard',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person),
              label: 'Profile',
            ),
          ],
        ),
      ) 
    );
  }
}
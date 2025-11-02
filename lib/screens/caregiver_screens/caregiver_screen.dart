import 'package:flutter/material.dart';
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
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 2,
        titleSpacing: 0,
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: Colors.transparent,
              child: SizedBox(
                width: 32,
                height: 32,
                child: Image.asset('assets/silvercare.png', fit: BoxFit.contain),
              ),
            ),
            
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
        centerTitle: true,
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
import 'package:flutter/material.dart';

// Defines the data structure for each navigation item
class NavItem {
  final String label;
  // NOTE: Assuming your image assets are located in assets/icons/
  final String assetPath; 
  final Color activeColor;
  final int index; // Index matches the order requested by the user

  const NavItem({required this.label, required this.assetPath, required this.activeColor, required this.index});
}

// Custom Bottom Navigation Bar
class CustomBottomNavBar extends StatelessWidget {
  // Current index is needed to highlight the active tab
  final int currentIndex;
  // Callback function to handle tab presses
  final ValueChanged<int> onTabSelected;

  const CustomBottomNavBar({
    super.key,
    required this.currentIndex,
    required this.onTabSelected,
  });

  // Define the 5 navigation items as requested, matching the user's file names and color themes.
  static const List<NavItem> navItems = [
    NavItem(label: "Notifications", assetPath: 'assets/imgs/bell.png', activeColor: Color(0xFFCD7F32), index: 0), // Bronze
    NavItem(label: "Calendar", assetPath: 'assets/imgs/calendar.png', activeColor: Color(0xFF000080), index: 1),    // Dark Blue
    NavItem(label: "Wellness", assetPath: 'assets/imgs/heart.png', activeColor: Color(0xFFFF73CB), index: 2),     // Pink
    NavItem(label: "Home", assetPath: 'assets/imgs/home.png', activeColor: Color(0xFFFFB300), index: 3),           // Orange
    NavItem(label: "Profile", assetPath: 'assets/imgs/person.png', activeColor: Color(0xFF32C3D2), index: 4),      // Teal
  ];

  @override
  Widget build(BuildContext context) {
    return Container(
      // Padding is added inside the box shadow area for a cleaner look
      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 8), 
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(20),
          topRight: Radius.circular(20),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.15),
            spreadRadius: 2,
            blurRadius: 10,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: navItems.asMap().entries.map((entry) {
          int index = entry.key;
          NavItem item = entry.value;
          bool isActive = index == currentIndex;
          
          final Color iconColor = isActive ? item.activeColor : Colors.grey.shade500;

          return Expanded(
            child: InkWell(
              onTap: () => onTabSelected(index),
              borderRadius: BorderRadius.circular(50),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                curve: Curves.easeInOut,
                padding: const EdgeInsets.symmetric(vertical: 8),
                decoration: BoxDecoration(
                  color: isActive ? item.activeColor.withOpacity(0.1) : Colors.transparent,
                  borderRadius: BorderRadius.circular(50),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    AnimatedScale(
                      scale: isActive ? 1.2 : 1.0, // Pop effect
                      duration: const Duration(milliseconds: 250),
                      child: Image.asset(
                        item.assetPath,
                        width: 28,
                        height: 28,
                        // Use color property to tint the image (since it's a monochrome asset)
                        color: iconColor, 
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item.label,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: isActive ? FontWeight.w800 : FontWeight.w500,
                        color: iconColor,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

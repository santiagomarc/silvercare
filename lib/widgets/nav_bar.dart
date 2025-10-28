import 'package:flutter/material.dart';

class SilverCareNavBar extends StatefulWidget {
  final int currentIndex;
  final Function(int) onTap;

  const SilverCareNavBar({
    super.key,
    required this.currentIndex,
    required this.onTap,
  });

  @override
  State<SilverCareNavBar> createState() => _SilverCareNavBarState();
}

class _SilverCareNavBarState extends State<SilverCareNavBar> {
  // Define colors for each screen
  final List<Color> _navColors = [
    Colors.black87,                    // Home - Black (bold when active)
    const Color(0xFF4CAF50),          // Calendar - Green
    const Color(0xFFFFB300),          // Analytics - Yellow/Amber
    const Color(0xFF9C27B0),          // Health/Heart - Purple
    const Color(0xFF2196F3),          // Profile - Blue
  ];

  // Define icons for each nav item
  final List<IconData> _navIcons = [
    Icons.home_rounded,
    Icons.calendar_today_rounded,
    Icons.analytics_rounded,
    Icons.favorite_rounded,
    Icons.person_rounded,
  ];

  // Define labels for accessibility
  final List<String> _navLabels = [
    'Home',
    'Calendar', 
    'Analytics',
    'Health',
    'Profile',
  ];

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final inactiveColor = Colors.black54;
    
    // Responsive sizing - Made icons bigger for elderly users
    final double navHeight = screenWidth > 600 ? 75 : 65;
    final double iconSize = screenWidth > 600 ? 32 : 28;
    final double containerPadding = screenWidth > 600 ? 12 : 8;

    return Container(
      margin: EdgeInsets.symmetric(
        horizontal: screenWidth > 600 ? 24 : 16, 
        vertical: screenWidth > 600 ? 16 : 12
      ),
      height: navHeight,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(35),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.15),
            blurRadius: 12,
            offset: const Offset(0, 6),
            spreadRadius: 2,
          ),
        ],
        border: Border.all(
          color: Colors.grey.withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Padding(
        padding: EdgeInsets.symmetric(
          vertical: containerPadding, 
          horizontal: containerPadding
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: List.generate(5, (index) => 
            _buildNavItem(
              _navIcons[index], 
              index, 
              _navColors[index], 
              inactiveColor, 
              iconSize,
              _navLabels[index],
            )
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem(
    IconData icon,
    int index,
    Color activeColor,
    Color inactiveColor,
    double iconSize,
    String label,
  ) {
    final bool isActive = widget.currentIndex == index;
    final screenWidth = MediaQuery.of(context).size.width;
    
    return Semantics(
      label: label,
      button: true,
      child: GestureDetector(
        onTap: () {
          // Add haptic feedback
          if (widget.currentIndex != index) {
            widget.onTap(index);
          }
        },
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeInOut,
          width: screenWidth > 600 ? 56 : 48,
          height: screenWidth > 600 ? 56 : 48,
          decoration: BoxDecoration(
            color: isActive 
              ? activeColor.withOpacity(0.12) 
              : Colors.transparent,
            shape: BoxShape.circle,
            border: isActive 
              ? Border.all(
                  color: Colors.black87, // Changed to black outline
                  width: 2
                ) 
              : null,
          ),
          child: Center(
            child: AnimatedScale(
              scale: isActive ? 1.1 : 1.0,
              duration: const Duration(milliseconds: 200),
              child: Icon(
                icon,
                size: iconSize,
                color: isActive ? activeColor : inactiveColor,
                // Make home icon appear bolder with shadow when active
                shadows: (index == 0 && isActive) ? [
                  Shadow(
                    color: activeColor.withOpacity(0.6),
                    blurRadius: 2,
                  ),
                ] : null,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

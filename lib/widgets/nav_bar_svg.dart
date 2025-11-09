import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

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
  final List<Color> _navColors = [
    Colors.black87,                    // Home - Black (bold when active)
    const Color(0xFF1565C0),          // Calendar - Green
    const Color(0xFFFFB300),          // Analytics - Yellow/Amber
    const Color(0xFF9C27B0),          // Health/Heart - Purple
    const Color(0xFF2196F3),          // Profile - Blue
  ];

  final List<String> _svgIcons = [
    'assets/icons/home.svg',
    'assets/icons/calendar.svg',
    'assets/icons/analytics.svg',
    'assets/icons/wellness.svg',
    'assets/icons/profile.svg',
  ];

  // Fallback regular icons (in case SVG doesn't load)
  final List<IconData> _fallbackIcons = [
    Icons.home_rounded,
    Icons.calendar_today_rounded,
    Icons.analytics_rounded,
    Icons.favorite_rounded,
    Icons.person_rounded,
  ];

  final List<String> _navLabels = [
    'Home',
    'Calendar', 
    'Analytics',
    'Wellness',
    'Profile',
  ];

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final inactiveColor = Colors.black54;
    
    final double navHeight = screenWidth > 600 ? 80 : 70;
    final double iconSize = screenWidth > 600 ? 36 : 32; // Bigger icons
    final double containerPadding = screenWidth > 600 ? 14 : 10;

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
            color: Colors.black.withOpacity(0.25),
            blurRadius: 20,
            offset: const Offset(0, -12), // Negative offset for upward shadow
            spreadRadius: 4,
          ),
          BoxShadow(
            color: Colors.black.withOpacity(0.25),
            blurRadius: 20,
            offset: const Offset(0, 8),
            spreadRadius: 3,
          ),
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 6,
            offset: const Offset(0, 2),
            spreadRadius: 1,
          ),
        ],
        border: Border.all(
          color: Colors.grey.withOpacity(0.15),
          width: 0.5,
        ),
      ),
      child: Padding(
        padding: EdgeInsets.symmetric(
          vertical: containerPadding, 
          horizontal: containerPadding
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly, // Better spacing
          children: List.generate(5, (index) => 
            _buildNavItem(
              _svgIcons[index],
              _fallbackIcons[index],
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
    String svgPath,
    IconData fallbackIcon,
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
          if (widget.currentIndex != index) {
            widget.onTap(index);
          }
        },
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeInOut,
          width: screenWidth > 600 ? 60 : 52, // Slightly bigger touch targets
          height: screenWidth > 600 ? 60 : 52,
          decoration: BoxDecoration(
            color: isActive 
              ? activeColor.withOpacity(0.12) 
              : Colors.transparent,
            shape: BoxShape.circle,
            border: isActive 
              ? Border.all(
                  color: activeColor, // Use icon color for better consistency
                  width: 2.5, // Slightly thicker for better visibility
                ) 
              : null,
          ),
          child: Center(
            child: AnimatedScale(
              scale: isActive ? 1.15 : 1.0, // Slightly more scale for better feedback
              duration: const Duration(milliseconds: 200),
              child: _buildIcon(
                svgPath,
                fallbackIcon,
                iconSize,
                isActive ? activeColor : inactiveColor,
                false,
                activeColor,
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildIcon(
    String svgPath,
    IconData fallbackIcon,
    double iconSize,
    Color color,
    bool addShadow,
    Color shadowColor,
  ) {
    return SvgPicture.asset(
      svgPath,
      width: iconSize,
      height: iconSize,
      // This is the key: colorFilter changes the SVG color dynamically!
      colorFilter: ColorFilter.mode(color, BlendMode.srcIn),
      // Fallback to regular icon if SVG fails to load
      placeholderBuilder: (BuildContext context) => Icon(
        fallbackIcon,
        size: iconSize,
        color: color,
      ),
    );
  }
}
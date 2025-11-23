import 'package:flutter/material.dart';

/// Professional responsive design utility
/// Follows Material Design breakpoints and Flutter best practices
class ResponsiveLayout {
  // Material Design Breakpoints
  static const double mobileSmall = 320;   // Small phones (iPhone SE)
  static const double mobile = 375;        // Standard phones
  static const double mobileLarge = 428;   // Large phones (iPhone Pro Max)
  static const double tablet = 768;        // Tablets
  static const double desktop = 1024;      // Desktop/Web
  
  /// Get screen width
  static double width(BuildContext context) {
    return MediaQuery.of(context).size.width;
  }
  
  /// Get screen height
  static double height(BuildContext context) {
    return MediaQuery.of(context).size.height;
  }
  
  /// Check if mobile (< 768)
  static bool isMobile(BuildContext context) {
    return width(context) < tablet;
  }
  
  /// Check if tablet (768-1024)
  static bool isTablet(BuildContext context) {
    final w = width(context);
    return w >= tablet && w < desktop;
  }
  
  /// Check if desktop (>= 1024)
  static bool isDesktop(BuildContext context) {
    return width(context) >= desktop;
  }
  
  /// Get responsive font size with better scaling
  /// Uses non-linear scaling for better readability across devices
  static double fontSize(BuildContext context, double baseSize) {
    final screenWidth = width(context);
    
    // Use different scaling factors for different screen sizes
    if (screenWidth < mobileSmall) {
      // Very small phones - scale down more aggressively
      return baseSize * 0.85;
    } else if (screenWidth < mobile) {
      // Small phones
      return baseSize * 0.92;
    } else if (screenWidth < mobileLarge) {
      // Standard phones - base size
      return baseSize;
    } else if (screenWidth < tablet) {
      // Large phones - slight increase
      return baseSize * 1.05;
    } else if (screenWidth < desktop) {
      // Tablets - moderate increase
      return baseSize * 1.15;
    } else {
      // Desktop - capped increase
      return baseSize * 1.25;
    }
  }
  
  /// Get responsive padding
  static EdgeInsets padding(BuildContext context, {
    double base = 20,
  }) {
    final screenWidth = width(context);
    
    if (screenWidth < mobileSmall) {
      return EdgeInsets.all(base * 0.75);
    } else if (screenWidth < tablet) {
      return EdgeInsets.all(base);
    } else if (screenWidth < desktop) {
      return EdgeInsets.all(base * 1.25);
    } else {
      return EdgeInsets.all(base * 1.5);
    }
  }
  
  /// Get responsive spacing
  static double spacing(BuildContext context, double baseSpacing) {
    final screenWidth = width(context);
    
    if (screenWidth < mobileSmall) {
      return baseSpacing * 0.75;
    } else if (screenWidth < tablet) {
      return baseSpacing;
    } else {
      return baseSpacing * 1.2;
    }
  }
  
  /// Get safe bottom padding (accounts for home indicator on iOS)
  static double bottomSafeArea(BuildContext context) {
    return MediaQuery.of(context).padding.bottom;
  }
  
  /// Get number of columns for grid based on screen size
  static int gridColumns(BuildContext context, {int mobile = 2, int tablet = 3, int desktop = 4}) {
    final screenWidth = width(context);
    
    if (screenWidth < ResponsiveLayout.tablet) {
      return mobile;
    } else if (screenWidth < ResponsiveLayout.desktop) {
      return tablet;
    } else {
      return desktop;
    }
  }
  
  /// Get card aspect ratio based on screen size
  static double cardAspectRatio(BuildContext context) {
    final screenWidth = width(context);
    
    if (screenWidth < mobileSmall) {
      return 1.0; // Square for very small screens
    } else if (screenWidth < tablet) {
      return 1.2; // Slightly rectangular
    } else {
      return 1.3; // More rectangular for larger screens
    }
  }
}

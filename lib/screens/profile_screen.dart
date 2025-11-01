import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart'; 

const String _logoAssetPath = 'assets/icons/silvercare.png'; 

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final Color _blueBgColor = const Color(0xFF32C3D2); 
  final Color _darkGreyText = const Color(0xFF808080);
  final Color _redLogout = const Color(0xFFCD5C5C); 
  static const Color _shadowColor25Percent = Color(0x40000000); 

  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _ageController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  
  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }
  
  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _ageController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    super.dispose();
  }

  Future<void> _loadUserData() async {
    final user = _auth.currentUser;
    if (user == null) {
      setState(() {
        _isLoading = false;
        _emailController.text = 'User not signed in.';
      });
      return;
    }
    
    _emailController.text = user.email ?? 'No Email Provided';
    
    try {
      final userDocRef = _firestore.collection('users').doc(user.uid);
      final elderlyDocRef = _firestore.collection('elderly').doc(user.uid);

      final userDoc = await userDocRef.get();
      final elderlyDoc = await elderlyDocRef.get();

      if (userDoc.exists) {
        _nameController.text = userDoc.get('fullName') ?? 'N/A';
      } else {
        _nameController.text = 'User Data Missing';
      }

      if (elderlyDoc.exists) {
        _ageController.text = elderlyDoc.get('age')?.toString() ?? '';
        _phoneController.text = elderlyDoc.get('phoneNumber') ?? '';
        _addressController.text = elderlyDoc.get('address') ?? ''; 
      }
      
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading data: ${e.toString()}'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _handleSaveProfile() async {
    if (_isLoading) return;
    
    setState(() {
      _isSaving = true;
    });

    final user = _auth.currentUser;
    if (user == null) {
      _isSaving = false;
      return;
    }

    try {
      await _firestore.collection('users').doc(user.uid).update({
        'fullName': _nameController.text.trim(),
      });

      final elderlyUpdateData = {
        'age': int.tryParse(_ageController.text.trim()),
        'phoneNumber': _phoneController.text.trim(),
        'address': _addressController.text.trim(),
      };
      
      await _firestore.collection('elderly').doc(user.uid).update(elderlyUpdateData);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('✅ Profile saved successfully!'), backgroundColor: Colors.green),
        );
      }
      
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('❌ Failed to save profile: ${e.toString()}'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSaving = false;
        });
      }
    }
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }
  
  Future<void> _handleSignOut() async {
    _showSignOutConfirmationDialog();
  }
  
  void _showSignOutConfirmationDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          title: const Text('Sign Out'),
          content: const Text('Are you sure you want to sign out?'),
          actions: [
            TextButton(onPressed: () => Navigator.of(context).pop(), child: const Text('Cancel')),
            ElevatedButton(
              onPressed: () async {
                Navigator.of(context).pop(); 
                try {
                  await _auth.signOut();
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('👋 Signed out successfully!'), backgroundColor: Colors.green, duration: Duration(seconds: 2)),
                    );
                    Navigator.of(context).pushReplacementNamed('/signin');
                  }
                } catch (e) {
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Error signing out: ${e.toString()}'), backgroundColor: Colors.red),
                    );
                  }
                }
              },
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red.shade600),
              child: const Text('Sign Out', style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 20, bottom: 20, left: 20, right: 20),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Container(
            width: 55,
            height: 55,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.blueGrey,
              border: Border.all(color: Colors.white, width: 3),
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 5, offset: const Offset(0, 3))],
            ),
            child: const Icon(Icons.person_outline, color: Colors.white, size: 30),
          ),

          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                width: 55, 
                height: 55, 
                child: Opacity(
                  opacity: 0.0,
                  child: Image.asset(
                    _logoAssetPath,
                    fit: BoxFit.contain,
                    errorBuilder: (context, error, stackTrace) => Container(),
                  ),
                ),
              ),
              const SizedBox(width: 15),
              Text(
                'SILVER CARE',
                style: TextStyle(
                  color: const Color(0xFF000000), 
                  fontSize: _getResponsiveFontSize(context, 21), 
                  fontFamily: 'Montserrat', 
                  fontWeight: FontWeight.w800, 
                  shadows: [Shadow(offset: const Offset(0, 3), blurRadius: 4, color: Colors.black.withOpacity(0.5))],
                ),
              ),
            ],
          ),
          
          InkWell(
            onTap: () => showDialog(context: context, builder: (ctx) => const AlertDialog(title: Text('Settings Coming Soon'))),
            child: Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.8),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 4, offset: const Offset(0, 2))],
              ),
              child: const Icon(Icons.settings_outlined, color: Color(0xFF2C2C2C), size: 24),
            ),
          ),
        ],
      ),
    );
  }

  Widget _ScreenHeaderButton(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(25, 10, 25, 10),
      child: Container(
        width: double.infinity,
        height: 65,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.50), blurRadius: 4, offset: const Offset(0, 4))],
          border: Border.all(color: Colors.black, width: 2),
        ),
        child: Center(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.person_outline, size: 30, color: _darkGreyText),
              const SizedBox(width: 10),
              Text(
                'PROFILE',
                style: TextStyle(
                  color: _darkGreyText,
                  fontSize: _getResponsiveFontSize(context, 32),
                  fontFamily: 'Montserrat', 
                  fontWeight: FontWeight.w800, 
                  shadows: const [Shadow(offset: Offset(0, 3), blurRadius: 4, color: _shadowColor25Percent)],
                  letterSpacing: 1.5,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEditProfileHeader(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.person_pin, size: 24, color: Colors.black),
        const SizedBox(width: 8),
        Text(
          'Edit Your Profile',
          style: TextStyle(
            color: const Color(0xFF000000), 
            fontSize: _getResponsiveFontSize(context, 20), 
            fontFamily: 'Montserrat', 
            fontWeight: FontWeight.w600, 
            shadows: [Shadow(offset: const Offset(0, 4), blurRadius: 4, color: _shadowColor25Percent)],
          ),
        ),
      ],
    );
  }

  Widget _buildProfileDetailRow({
    required BuildContext context,
    required String label,
    required TextEditingController controller,
    bool isReadOnly = false,
    TextInputType keyboardType = TextInputType.text,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 25.0),
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(25),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.50), blurRadius: 8, offset: const Offset(0, 4))],
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 10),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                label,
                style: TextStyle(
                  color: const Color(0xFF000000),
                  fontSize: _getResponsiveFontSize(context, 16),
                  fontFamily: 'Montserrat', 
                  fontWeight: FontWeight.w800, 
                  shadows: [Shadow(offset: const Offset(0, 2), blurRadius: 4, color: Colors.black.withOpacity(0.15))],
                ),
              ),
              const SizedBox(height: 4),
              TextFormField(
                controller: controller,
                readOnly: isReadOnly,
                keyboardType: keyboardType,
                style: TextStyle(
                  color: isReadOnly ? Colors.grey.shade700 : const Color(0xFF000000),
                  fontSize: _getResponsiveFontSize(context, 18),
                  fontFamily: 'Inter', 
                  fontWeight: isReadOnly ? FontWeight.w400 : FontWeight.w600,
                ),
                decoration: InputDecoration(
                  isDense: true,
                  contentPadding: EdgeInsets.zero,
                  border: InputBorder.none,
                  filled: false,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: Column(
          children: [
            _buildHeader(context),
            
            _ScreenHeaderButton(context),
            
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: _blueBgColor,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(30),
                    topRight: Radius.circular(30),
                  ),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.3), blurRadius: 8, offset: const Offset(0, -5))],
                ),
                child: _isLoading 
                  ? const Center(child: CircularProgressIndicator(color: Colors.white))
                  : SingleChildScrollView( 
                      padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          _buildEditProfileHeader(context),
                          const SizedBox(height: 30),
  
                          _buildProfileDetailRow(context: context, label: 'Full Name:', controller: _nameController),
                          _buildProfileDetailRow(context: context, label: 'Email:', controller: _emailController, isReadOnly: true),
                          _buildProfileDetailRow(context: context, label: 'Age:', controller: _ageController, keyboardType: TextInputType.number),
                          _buildProfileDetailRow(context: context, label: 'Phone Number:', controller: _phoneController, keyboardType: TextInputType.phone),
                          _buildProfileDetailRow(context: context, label: 'Address:', controller: _addressController),
                          
                          const SizedBox(height: 20),

                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              ElevatedButton.icon(
                                onPressed: _isSaving ? null : _handleSaveProfile,
                                icon: _isSaving 
                                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Icon(Icons.save, size: 20, color: Colors.white),
                                label: Text(
                                  _isSaving ? 'Saving...' : 'Save Changes',
                                  style: TextStyle(
                                    fontSize: _getResponsiveFontSize(context, 16),
                                    fontFamily: 'Montserrat',
                                    fontWeight: FontWeight.w700,
                                    color: Colors.white,
                                  ),
                                ),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF008000),
                                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  elevation: 5,
                                ),
                              ),
                              
                              TextButton(
                                onPressed: _handleSignOut,
                                style: TextButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                                  backgroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                    side: BorderSide(color: _redLogout, width: 2),
                                  ),
                                  shadowColor: Colors.black.withOpacity(0.5),
                                  elevation: 5,
                                ),
                                child: Text(
                                  'Log Out',
                                  style: TextStyle(
                                    color: _redLogout,
                                    fontSize: _getResponsiveFontSize(context, 16), 
                                    fontFamily: 'Montserrat',
                                    fontWeight: FontWeight.w800,
                                    shadows: [Shadow(offset: const Offset(0, 2), blurRadius: 4, color: Colors.black.withOpacity(0.5))],
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 40), 
                        ],
                      ),
                    ),
              ),
            ),
          ],
        ),
      ),

    );
  }
}


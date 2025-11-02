import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart'; 

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  static const Color _primaryColor = Color(0xFF6C63FF);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _cardColor = Colors.white;
  static const Color _textPrimary = Color(0xFF2D3748);
  static const Color _textSecondary = Color(0xFF718096);
  static const Color _successColor = Color(0xFF48BB78); 

  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _ageController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _heightController = TextEditingController();
  final _weightController = TextEditingController();
  final _sexController = TextEditingController();
  
  // Medical info controllers
  final _conditionsController = TextEditingController();
  final _medicationsController = TextEditingController();
  final _allergiesController = TextEditingController();
  
  // Emergency contact controllers
  final _emergencyNameController = TextEditingController();
  final _emergencyPhoneController = TextEditingController();
  final _emergencyRelationshipController = TextEditingController();
  final _caregiverEmailController = TextEditingController();
  
  bool _isLoading = true;
  bool _isSaving = false;
  bool _isEditMode = false;

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
    _heightController.dispose();
    _weightController.dispose();
    _sexController.dispose();
    _conditionsController.dispose();
    _medicationsController.dispose();
    _allergiesController.dispose();
    _emergencyNameController.dispose();
    _emergencyPhoneController.dispose();
    _emergencyRelationshipController.dispose();
    _caregiverEmailController.dispose();
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
        final data = elderlyDoc.data() as Map<String, dynamic>;
        _ageController.text = data['age']?.toString() ?? '';
        _phoneController.text = data['phoneNumber'] ?? '';
        _addressController.text = data['address'] ?? '';
        _heightController.text = data['height']?.toString() ?? '';
        _weightController.text = data['weight']?.toString() ?? '';
        _sexController.text = data['sex'] ?? '';
        
        // Load medical info
        final medicalInfo = data['medicalInfo'] as Map<String, dynamic>?;
        if (medicalInfo != null) {
          _conditionsController.text = (medicalInfo['conditions'] as List<dynamic>?)?.join(', ') ?? '';
          _medicationsController.text = (medicalInfo['medications'] as List<dynamic>?)?.join(', ') ?? '';
          _allergiesController.text = (medicalInfo['allergies'] as List<dynamic>?)?.join(', ') ?? '';
        }
        
        // Load emergency contact
        final emergencyContact = data['emergencyContact'] as Map<String, dynamic>?;
        if (emergencyContact != null) {
          _emergencyNameController.text = emergencyContact['name'] ?? '';
          _emergencyPhoneController.text = emergencyContact['phone'] ?? '';
          _emergencyRelationshipController.text = emergencyContact['relationship'] ?? '';
        }
        
        // Load caregiver email
        final caregiverId = data['caregiverId'] as String?;
        if (caregiverId != null && caregiverId.isNotEmpty) {
          _loadCaregiverEmail(caregiverId);
        }
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

  Future<void> _loadCaregiverEmail(String caregiverId) async {
    try {
      final caregiverDoc = await _firestore.collection('caregivers').doc(caregiverId).get();
      if (caregiverDoc.exists) {
        final data = caregiverDoc.data() as Map<String, dynamic>;
        _caregiverEmailController.text = data['email'] ?? '';
      }
    } catch (e) {
      print('Error loading caregiver email: $e');
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

      // Prepare medical info
      final medicalInfo = {
        'conditions': _conditionsController.text.trim().split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList(),
        'medications': _medicationsController.text.trim().split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList(),
        'allergies': _allergiesController.text.trim().split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList(),
      };

      // Prepare emergency contact
      final emergencyContact = {
        'name': _emergencyNameController.text.trim(),
        'phone': _emergencyPhoneController.text.trim(),
        'relationship': _emergencyRelationshipController.text.trim(),
      };

      final elderlyUpdateData = {
        'age': int.tryParse(_ageController.text.trim()),
        'phoneNumber': _phoneController.text.trim(),
        'address': _addressController.text.trim(),
        'height': double.tryParse(_heightController.text.trim()),
        'weight': double.tryParse(_weightController.text.trim()),
        'sex': _sexController.text.trim(),
        'medicalInfo': medicalInfo,
        'emergencyContact': emergencyContact,
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
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 20),
      child: Center(
        child: Text(
          'SILVER CARE',
          style: TextStyle(
            color: _textPrimary,
            fontSize: _getResponsiveFontSize(context, 28),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w900,
            letterSpacing: 2.0,
          ),
        ),
      ),
    );
  }

  Widget _ScreenHeaderButton(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 10, 20, 20),
      height: 80,
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(40),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.15),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
        border: Border.all(color: const Color.fromRGBO(108, 99, 255, 0.2), width: 2),
      ),
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.person_outline, size: 28, color: _primaryColor),
            const SizedBox(width: 12),
            Text(
              'PROFILE',
              style: TextStyle(
                color: _textPrimary,
                fontSize: _getResponsiveFontSize(context, 24),
                fontFamily: 'Montserrat',
                fontWeight: FontWeight.w800,
                letterSpacing: 1.2,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEditProfileHeader(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            _isEditMode ? 'Edit Profile' : 'Personal Information',
            style: TextStyle(
              color: Colors.white,
              fontSize: _getResponsiveFontSize(context, 22),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w700,
              letterSpacing: 0.5,
            ),
          ),
          if (!_isEditMode)
            IconButton(
              onPressed: () => setState(() => _isEditMode = true),
              icon: const Icon(Icons.edit, color: Colors.white, size: 24),
              style: IconButton.styleFrom(
                backgroundColor: const Color.fromRGBO(255, 255, 255, 0.2),
                shape: const CircleBorder(),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildProfileDetailRow({
    required BuildContext context,
    required String label,
    required TextEditingController controller,
    bool isReadOnly = false,
    TextInputType keyboardType = TextInputType.text,
    int maxLines = 1,
  }) {
    final bool effectiveReadOnly = isReadOnly || !_isEditMode;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
        border: _isEditMode && !isReadOnly 
          ? Border.all(color: const Color.fromRGBO(108, 99, 255, 0.3), width: 1)
          : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              color: _textSecondary,
              fontSize: _getResponsiveFontSize(context, 13),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w600,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: controller,
            readOnly: effectiveReadOnly,
            keyboardType: keyboardType,
            maxLines: maxLines,
            style: TextStyle(
              color: effectiveReadOnly ? _textSecondary : _textPrimary,
              fontSize: _getResponsiveFontSize(context, 16),
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
            decoration: InputDecoration(
              isDense: true,
              contentPadding: EdgeInsets.zero,
              border: InputBorder.none,
              hintText: effectiveReadOnly ? null : 'Enter $label',
              hintStyle: TextStyle(
                color: const Color.fromRGBO(113, 128, 150, 0.6),
                fontSize: _getResponsiveFontSize(context, 16),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMedicalInfoSection(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
        border: _isEditMode 
          ? Border.all(color: const Color.fromRGBO(108, 99, 255, 0.3), width: 1)
          : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Medical Information',
            style: TextStyle(
              color: _textPrimary,
              fontSize: _getResponsiveFontSize(context, 16),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          _buildMedicalField(context, 'Medical Conditions', _conditionsController),
          _buildMedicalField(context, 'Current Medications', _medicationsController),
          _buildMedicalField(context, 'Allergies', _allergiesController),
        ],
      ),
    );
  }

  Widget _buildMedicalField(BuildContext context, String label, TextEditingController controller) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              color: _textSecondary,
              fontSize: _getResponsiveFontSize(context, 13),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          TextFormField(
            controller: controller,
            readOnly: !_isEditMode,
            maxLines: 2,
            style: TextStyle(
              color: !_isEditMode ? _textSecondary : _textPrimary,
              fontSize: _getResponsiveFontSize(context, 14),
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
            decoration: InputDecoration(
              isDense: true,
              contentPadding: EdgeInsets.zero,
              border: InputBorder.none,
              hintText: _isEditMode ? 'Separate multiple items with commas' : null,
              hintStyle: TextStyle(
                color: const Color.fromRGBO(113, 128, 150, 0.6),
                fontSize: _getResponsiveFontSize(context, 12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmergencyContactSection(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color.fromRGBO(0, 0, 0, 0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
        border: _isEditMode 
          ? Border.all(color: const Color.fromRGBO(108, 99, 255, 0.3), width: 1)
          : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Emergency Contact',
            style: TextStyle(
              color: _textPrimary,
              fontSize: _getResponsiveFontSize(context, 16),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          _buildEmergencyField(context, 'Contact Name', _emergencyNameController),
          _buildEmergencyField(context, 'Phone Number', _emergencyPhoneController, TextInputType.phone),
          _buildEmergencyField(context, 'Relationship', _emergencyRelationshipController),
          if (_caregiverEmailController.text.isNotEmpty)
            _buildEmergencyField(context, 'Caregiver Email', _caregiverEmailController, TextInputType.emailAddress, true),
        ],
      ),
    );
  }

  Widget _buildEmergencyField(BuildContext context, String label, TextEditingController controller, [TextInputType keyboardType = TextInputType.text, bool isReadOnly = false]) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              color: _textSecondary,
              fontSize: _getResponsiveFontSize(context, 13),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          TextFormField(
            controller: controller,
            readOnly: isReadOnly || !_isEditMode,
            keyboardType: keyboardType,
            style: TextStyle(
              color: (isReadOnly || !_isEditMode) ? _textSecondary : _textPrimary,
              fontSize: _getResponsiveFontSize(context, 14),
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
            decoration: InputDecoration(
              isDense: true,
              contentPadding: EdgeInsets.zero,
              border: InputBorder.none,
              hintText: (_isEditMode && !isReadOnly) ? 'Enter $label' : null,
              hintStyle: TextStyle(
                color: const Color.fromRGBO(113, 128, 150, 0.6),
                fontSize: _getResponsiveFontSize(context, 14),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _backgroundColor,
      body: SafeArea(
        child: Column(
          children: [
            _buildHeader(context),
            
            _ScreenHeaderButton(context),
            
            Expanded(
                child: Container(
                width: double.infinity,
                margin: const EdgeInsets.symmetric(horizontal: 20),
                decoration: BoxDecoration(
                  color: _primaryColor,
                  borderRadius: const BorderRadius.all(Radius.circular(25)),
                  boxShadow: [
                    BoxShadow(
                      color: const Color.fromRGBO(0, 0, 0, 0.1),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: _isLoading 
                  ? const Center(child: CircularProgressIndicator(color: Colors.white))
                  : SingleChildScrollView( 
                      padding: const EdgeInsets.all(30),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          _buildEditProfileHeader(context),
                          const SizedBox(height: 25),
  
                          // Basic Information Card
                          Container(
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              color: const Color.fromRGBO(255, 255, 255, 0.1),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: const Color.fromRGBO(255, 255, 255, 0.2), width: 1),
                            ),
                            child: Column(
                              children: [
                                _buildProfileDetailRow(context: context, label: 'Full Name', controller: _nameController),
                                _buildProfileDetailRow(context: context, label: 'Email', controller: _emailController, isReadOnly: true),
                                _buildProfileDetailRow(context: context, label: 'Age', controller: _ageController, keyboardType: TextInputType.number),
                                _buildProfileDetailRow(context: context, label: 'Sex', controller: _sexController),
                                _buildProfileDetailRow(context: context, label: 'Phone Number', controller: _phoneController, keyboardType: TextInputType.phone),
                                _buildProfileDetailRow(context: context, label: 'Address', controller: _addressController),
                                _buildProfileDetailRow(context: context, label: 'Height (cm)', controller: _heightController, keyboardType: TextInputType.number),
                                _buildProfileDetailRow(context: context, label: 'Weight (kg)', controller: _weightController, keyboardType: TextInputType.number),
                              ],
                            ),
                          ),
                          
                          const SizedBox(height: 20),
                          _buildMedicalInfoSection(context),
                          _buildEmergencyContactSection(context),
                          
                          const SizedBox(height: 30),

                          // Edit mode buttons
                          if (_isEditMode) ...[
                            Row(
                              children: [
                                Expanded(
                                  child: TextButton.icon(
                                    onPressed: () => setState(() => _isEditMode = false),
                                    icon: const Icon(Icons.close, size: 20, color: Colors.white),
                                    label: Text(
                                      'Cancel',
                                      style: TextStyle(
                                        color: Colors.white,
                                        fontSize: _getResponsiveFontSize(context, 16),
                                        fontFamily: 'Montserrat',
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    style: TextButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(vertical: 16),
                                      backgroundColor: const Color.fromRGBO(255, 255, 255, 0.2),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(15),
                                        side: const BorderSide(color: Color.fromRGBO(255, 255, 255, 0.3), width: 1),
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 15),
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: _isSaving ? null : () async {
                                      await _handleSaveProfile();
                                      if (mounted && !_isSaving) {
                                        setState(() => _isEditMode = false);
                                      }
                                    },
                                    icon: _isSaving 
                                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                      : const Icon(Icons.save_outlined, size: 22, color: Colors.white),
                                    label: Text(
                                      _isSaving ? 'Saving...' : 'Save',
                                      style: TextStyle(
                                        fontSize: _getResponsiveFontSize(context, 16),
                                        fontFamily: 'Montserrat',
                                        fontWeight: FontWeight.w600,
                                        color: Colors.white,
                                      ),
                                    ),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: _successColor,
                                      padding: const EdgeInsets.symmetric(vertical: 16),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                                      elevation: 2,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ] else ...[
                            // Logout button - when not in edit mode
                            SizedBox(
                              width: double.infinity,
                              child: TextButton.icon(
                                onPressed: _handleSignOut,
                                icon: const Icon(Icons.logout, size: 20, color: Colors.white),
                                label: Text(
                                  'Sign Out',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: _getResponsiveFontSize(context, 16),
                                    fontFamily: 'Montserrat',
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                style: TextButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(vertical: 16),
                                  backgroundColor: const Color.fromRGBO(255, 255, 255, 0.2),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(15),
                                    side: const BorderSide(color: Color.fromRGBO(255, 255, 255, 0.3), width: 1),
                                  ),
                                ),
                              ),
                            ),
                          ],
                          
                          const SizedBox(height: 30), 
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
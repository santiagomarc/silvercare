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
<<<<<<< HEAD

  @override
  void dispose() {
    _usernameController.dispose();
    _phoneController.dispose();
    _ageController.dispose();
    _weightController.dispose();
    _heightController.dispose();
    _emergencyNameController.dispose();
    _emergencyPhoneController.dispose();
    _conditionsController.dispose();
    _medicationsController.dispose();
    _allergiesController.dispose();
=======
  
  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _ageController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
>>>>>>> dev
    super.dispose();
  }

  Future<void> _loadUserData() async {
<<<<<<< HEAD
    setState(() {
      _isLoading = true;
    });

    try {
      final user = _auth.currentUser;
      if (user == null) {
        throw Exception('User not authenticated');
      }

      // Load user and elderly data
      final userModel = await AuthService.getUserById(user.uid);
      final elderlyModel = await AuthService.getElderlyByUserId(user.uid);

      if (mounted) {
        setState(() {
          _userModel = userModel;
          _elderlyModel = elderlyModel;
          _populateControllers();
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to load profile: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _populateControllers() {
    if (_elderlyModel != null) {
      _usernameController.text = _elderlyModel!.username;
      _phoneController.text = _elderlyModel!.phoneNumber;
      _selectedSex = _elderlyModel!.sex;
      _ageController.text = _elderlyModel!.age?.toString() ?? '';
      _weightController.text = _elderlyModel!.weight?.toString() ?? '';
      _heightController.text = _elderlyModel!.height?.toString() ?? '';
      
      if (_elderlyModel!.emergencyContact != null) {
        _emergencyNameController.text = _elderlyModel!.emergencyContact!.name;
        _emergencyPhoneController.text = _elderlyModel!.emergencyContact!.phone;
        _selectedRelationship = _elderlyModel!.emergencyContact!.relationship;
      }
      
      if (_elderlyModel!.medicalInfo != null) {
        _conditionsController.text = _elderlyModel!.medicalInfo!.conditions.join(', ');
        _medicationsController.text = _elderlyModel!.medicalInfo!.medications.join(', ');
        _allergiesController.text = _elderlyModel!.medicalInfo!.allergies.join(', ');
      }
    }
  }

  Future<void> _saveProfile() async {
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }

=======
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
    
>>>>>>> dev
    setState(() {
      _isSaving = true;
    });

<<<<<<< HEAD
    try {
      final user = _auth.currentUser;
      if (user == null) throw Exception('User not authenticated');

      // Parse medical info
      final conditions = _conditionsController.text.trim().isNotEmpty
          ? _conditionsController.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList()
          : <String>[];
      
      final medications = _medicationsController.text.trim().isNotEmpty
          ? _medicationsController.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList()
          : <String>[];
      
      final allergies = _allergiesController.text.trim().isNotEmpty
          ? _allergiesController.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList()
          : <String>[];

      // Create data to update
      final updateData = <String, dynamic>{
        'username': _usernameController.text.trim(),
        'phoneNumber': _phoneController.text.trim(),
        'sex': _selectedSex,
        'profileCompleted': true,
      };

      // Add optional fields if provided
      if (_ageController.text.trim().isNotEmpty) {
        updateData['age'] = int.tryParse(_ageController.text.trim());
      }
      if (_weightController.text.trim().isNotEmpty) {
        updateData['weight'] = double.tryParse(_weightController.text.trim());
      }
      if (_heightController.text.trim().isNotEmpty) {
        updateData['height'] = double.tryParse(_heightController.text.trim());
      }

      // Add emergency contact if provided
      if (_emergencyNameController.text.trim().isNotEmpty && 
          _emergencyPhoneController.text.trim().isNotEmpty &&
          _selectedRelationship != null) {
        updateData['emergencyContact'] = {
          'name': _emergencyNameController.text.trim(),
          'phone': _emergencyPhoneController.text.trim(),
          'relationship': _selectedRelationship!,
        };
      }

      // Add medical info if provided
      updateData['medicalInfo'] = {
        'conditions': conditions,
        'medications': medications,
        'allergies': allergies,
      };

      // Update elderly record
      await _firestore.collection('elderly').doc(user.uid).update(updateData);

      // Reload data
      await _loadUserData();

      setState(() {
        _isEditing = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Profile updated successfully!'),
          backgroundColor: Colors.green,
          duration: Duration(seconds: 2),
        ),
      );

    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Failed to update profile: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 4),
        ),
      );
=======
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
>>>>>>> dev
    } finally {
      if (mounted) {
        setState(() {
          _isSaving = false;
        });
      }
    }
  }

<<<<<<< HEAD
  void _cancelEdit() {
    setState(() {
      _isEditing = false;
      _populateControllers(); // Reset to original values
    });
=======
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
>>>>>>> dev
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
<<<<<<< HEAD
        child: _isLoading
            ? _buildLoadingScreen()
            : RefreshIndicator(
                onRefresh: _loadUserData,
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildHeader(),
                      const SizedBox(height: 30),
                      
                      if (_elderlyModel == null)
                        _buildNoProfileCard()
                      else ...[
                        _buildPersonalInfoCard(),
                        const SizedBox(height: 20),
                        _buildEmergencyContactCard(),
                        const SizedBox(height: 20), 
                        _buildMedicalInfoCard(),
                        const SizedBox(height: 20),
                        _buildAccountInfoCard(),
                      ],
                      
                      const SizedBox(height: 30),
                      _buildActionButtons(),
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Widget _buildLoadingScreen() {
    return const Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(
            color: Color(0xFF2C2C2C),
          ),
          SizedBox(height: 20),
          Text(
            'Loading your profile...',
            style: TextStyle(
              color: Color(0xFF666666),
              fontSize: 16,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      children: [
        Text(
          'SILVERCARE',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.black,
            fontSize: _getResponsiveFontSize(context, 24),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w800,
            shadows: [
              Shadow(
                offset: const Offset(0, 2),
                blurRadius: 4,
                color: Colors.black.withValues(alpha: 0.50),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: const Color(0xFF2C2C2C).withOpacity(0.1),
                borderRadius: BorderRadius.circular(40),
                border: Border.all(
                  color: const Color(0xFF2C2C2C).withOpacity(0.3),
                  width: 2,
                ),
              ),
              child: const Icon(
                Icons.person,
                size: 40,
                color: Color(0xFF2C2C2C),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _elderlyModel?.username ?? _userModel?.fullName ?? 'Unknown User',
                    style: TextStyle(
                      color: Colors.black,
                      fontSize: _getResponsiveFontSize(context, 28),
                      fontFamily: 'Montserrat',
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _userModel?.email ?? 'No email available',
                    style: const TextStyle(
                      color: Color(0xFF666666),
                      fontSize: 16,
                      fontFamily: 'Inter',
                      fontWeight: FontWeight.w400,
                    ),
                  ),
                  if (_elderlyModel?.profileCompleted == true) ...[
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.green.withOpacity(0.3)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.check_circle,
                            size: 14,
                            color: Colors.green.shade600,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            'Profile Complete',
                            style: TextStyle(
                              color: Colors.green.shade700,
                              fontSize: 12,
                              fontFamily: 'Inter',
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
=======
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
>>>>>>> dev
              ),
            ),
          ],
        ),
<<<<<<< HEAD
      ],
    );
  }

  Widget _buildNoProfileCard() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: _cardDecoration(),
      child: Column(
        children: [
          Icon(
            Icons.person_add_outlined,
            size: 64,
            color: const Color(0xFF2C2C2C).withOpacity(0.5),
          ),
          const SizedBox(height: 16),
          const Text(
            'Complete Your Profile',
            style: TextStyle(
              color: Color(0xFF1E1E1E),
              fontSize: 20,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Create your profile to access all SilverCare features and get personalized health recommendations.',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Color(0xFF666666),
              fontSize: 14,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
          ),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const ProfileCompletionScreen(),
                ),
              );
            },
            icon: const Icon(Icons.add, size: 20),
            label: const Text('Complete Profile'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2C2C2C),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPersonalInfoCard() {
    return _buildInfoCard(
      title: 'Personal Information',
      icon: Icons.person_outline,
      child: Form(
        key: _formKey,
        child: Column(
          children: [
            _buildInfoField(
              label: 'Username',
              value: _elderlyModel?.username ?? 'Not set',
              controller: _usernameController,
              isRequired: true,
            ),
            const SizedBox(height: 16),
            _buildInfoField(
              label: 'Phone Number',
              value: _elderlyModel?.phoneNumber ?? 'Not set',
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              prefixText: '+63 ',
              isRequired: true,
            ),
            const SizedBox(height: 16),
            _buildSexDropdown(),
            const SizedBox(height: 16),
            _buildInfoField(
              label: 'Age',
              value: _elderlyModel?.age?.toString() ?? 'Not set',
              controller: _ageController,
              keyboardType: TextInputType.number,
              validator: (value) {
                if (value != null && value.isNotEmpty) {
                  final age = int.tryParse(value);
                  if (age == null || age < 0 || age > 150) {
                    return 'Please enter a valid age';
                  }
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildInfoField(
                    label: 'Weight (kg)',
                    value: _elderlyModel?.weight?.toString() ?? 'Not set',
                    controller: _weightController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    validator: (value) {
                      if (value != null && value.isNotEmpty) {
                        final weight = double.tryParse(value);
                        if (weight == null || weight <= 0 || weight > 500) {
                          return 'Invalid weight';
                        }
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildInfoField(
                    label: 'Height (cm)',
                    value: _elderlyModel?.height?.toString() ?? 'Not set',
                    controller: _heightController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    validator: (value) {
                      if (value != null && value.isNotEmpty) {
                        final height = double.tryParse(value);
                        if (height == null || height <= 0 || height > 300) {
                          return 'Invalid height';
                        }
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            if (_elderlyModel?.weight != null && _elderlyModel?.height != null) ...[
              const SizedBox(height: 16),
              _buildBMIDisplay(),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildEmergencyContactCard() {
    final hasEmergencyContact = _elderlyModel?.emergencyContact != null;
    
    return _buildInfoCard(
      title: 'Emergency Contact',
      icon: Icons.emergency,
      child: hasEmergencyContact || _isEditing
          ? Column(
              children: [
                _buildInfoField(
                  label: 'Contact Name',
                  value: _elderlyModel?.emergencyContact?.name ?? 'Not set',
                  controller: _emergencyNameController,
                  isRequired: _isEditing,
                ),
                const SizedBox(height: 16),
                _buildInfoField(
                  label: 'Phone Number',
                  value: _elderlyModel?.emergencyContact?.phone ?? 'Not set',
                  controller: _emergencyPhoneController,
                  keyboardType: TextInputType.phone,
                  prefixText: '+63 ',
                  isRequired: _isEditing,
                  validator: (value) {
                    if (_isEditing && (value == null || value.isEmpty)) {
                      return 'Please enter emergency contact phone';
                    }
                    if (value != null && value.isNotEmpty && value.length < 10) {
                      return 'Please enter a valid phone number';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                _buildRelationshipDropdown(),
              ],
            )
          : _buildEmptyState(
              icon: Icons.emergency_outlined,
              title: 'No Emergency Contact',
              subtitle: 'Add an emergency contact for safety',
            ),
    );
  }

  Widget _buildMedicalInfoCard() {
    final hasMedicalInfo = _elderlyModel?.medicalInfo != null &&
        (_elderlyModel!.medicalInfo!.conditions.isNotEmpty ||
         _elderlyModel!.medicalInfo!.medications.isNotEmpty ||
         _elderlyModel!.medicalInfo!.allergies.isNotEmpty);
    
    return _buildInfoCard(
      title: 'Medical Information',
      icon: Icons.medical_information_outlined,
      child: hasMedicalInfo || _isEditing
          ? Column(
              children: [
                _buildMultilineInfoField(
                  label: 'Medical Conditions',
                  value: _elderlyModel?.medicalInfo?.conditions.join(', ') ?? 'None',
                  controller: _conditionsController,
                  hintText: 'List medical conditions (e.g., Diabetes, Hypertension)',
                  helperText: 'Separate multiple conditions with commas',
                ),
                const SizedBox(height: 16),
                _buildMultilineInfoField(
                  label: 'Current Medications',
                  value: _elderlyModel?.medicalInfo?.medications.join(', ') ?? 'None',
                  controller: _medicationsController,
                  hintText: 'List current medications and dosages',
                  helperText: 'Include dosage and frequency if known',
                ),
                const SizedBox(height: 16),
                _buildMultilineInfoField(
                  label: 'Allergies',
                  value: _elderlyModel?.medicalInfo?.allergies.join(', ') ?? 'None',
                  controller: _allergiesController,
                  hintText: 'List any known allergies',
                  helperText: 'Include severity if known',
                ),
              ],
            )
          : _buildEmptyState(
              icon: Icons.medical_services_outlined,
              title: 'No Medical Information',
              subtitle: 'Add medical information for better care',
            ),
    );
  }

  Widget _buildAccountInfoCard() {
    return _buildInfoCard(
      title: 'Account Information',
      icon: Icons.account_circle_outlined,
      child: Column(
        children: [
          _buildReadOnlyField('Email', _userModel?.email ?? 'Not available'),
          const SizedBox(height: 16),
          _buildReadOnlyField('User Type', _userModel?.userType.toUpperCase() ?? 'UNKNOWN'),
          const SizedBox(height: 16),
          _buildReadOnlyField('Member Since', _formatDate(_userModel?.createdAt)),
          const SizedBox(height: 16),
          _buildReadOnlyField('Last Login', _formatDate(_userModel?.lastLoginAt)),
        ],
      ),
    );
  }

  Widget _buildActionButtons() {
    if (_isEditing) {
      return Row(
        children: [
          Expanded(
            child: OutlinedButton(
              onPressed: _isSaving ? null : _cancelEdit,
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF666666),
                side: const BorderSide(color: Color(0xFF666666)),
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: const Text(
                'Cancel',
                style: TextStyle(
                  fontSize: 16,
                  fontFamily: 'Inter',
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            flex: 2,
            child: ElevatedButton.icon(
              onPressed: _isSaving ? null : _saveProfile,
              icon: _isSaving
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : const Icon(Icons.save, size: 20),
              label: Text(_isSaving ? 'Saving...' : 'Save Changes'),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2C2C2C),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                elevation: 2,
              ),
            ),
          ),
        ],
      );
    } else {
      return Column(
        children: [
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _elderlyModel == null ? null : () {
                setState(() {
                  _isEditing = true;
                });
              },
              icon: const Icon(Icons.edit, size: 20),
              label: const Text('Edit Profile'),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2C2C2C),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                elevation: 2,
              ),
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _showSignOutDialog,
              icon: const Icon(Icons.logout, size: 20),
              label: const Text('Sign Out'),
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.red,
                side: const BorderSide(color: Colors.red),
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        ],
      );
    }
  }

  Widget _buildInfoCard({
    required String title,
    required IconData icon,
    required Widget child,
  }) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFF2C2C2C).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  icon,
                  color: const Color(0xFF2C2C2C),
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    color: Color(0xFF1E1E1E),
                    fontSize: 20,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          child,
        ],
      ),
    );
  }

  Widget _buildInfoField({
    required String label,
    required String value,
    required TextEditingController controller,
    bool isRequired = false,
    TextInputType? keyboardType,
    String? prefixText,
    String? Function(String?)? validator,
  }) {
    if (!_isEditing) {
      return _buildReadOnlyField(label, value);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              label,
              style: const TextStyle(
                color: Color(0xFF1E1E1E),
                fontSize: 16,
                fontFamily: 'Inter',
                fontWeight: FontWeight.w500,
              ),
            ),
            if (isRequired)
              const Text(
                ' *',
                style: TextStyle(
                  color: Colors.red,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
          ],
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
          decoration: InputDecoration(
            hintText: 'Enter $label',
            prefixText: prefixText,
            hintStyle: const TextStyle(
              color: Color.fromARGB(255, 117, 117, 117),
              fontSize: 16,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 2, color: Color(0xFF2C2C2C)),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
          validator: validator ?? (isRequired ? (value) {
            if (value == null || value.isEmpty) {
              return 'Please enter $label';
            }
            return null;
          } : null),
        ),
      ],
    );
  }

  Widget _buildMultilineInfoField({
    required String label,
    required String value,
    required TextEditingController controller,
    String? hintText,
    String? helperText,
  }) {
    if (!_isEditing) {
      return _buildReadOnlyField(label, value.isEmpty ? 'None' : value);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
        ),
        if (helperText != null) ...[
          const SizedBox(height: 4),
          Text(
            helperText,
            style: const TextStyle(
              color: Color(0xFF666666),
              fontSize: 12,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
          ),
        ],
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          maxLines: 3,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
          decoration: InputDecoration(
            hintText: hintText ?? 'Enter $label',
            hintStyle: const TextStyle(
              color: Color.fromARGB(255, 117, 117, 117),
              fontSize: 16,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 2, color: Color(0xFF2C2C2C)),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
        ),
      ],
    );
  }

  Widget _buildReadOnlyField(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Color(0xFF666666),
            fontSize: 14,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _buildSexDropdown() {
    if (!_isEditing) {
      return _buildReadOnlyField('Sex', _selectedSex);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Sex',
          style: TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 8),
        DropdownButtonFormField<String>(
          value: _selectedSex,
          dropdownColor: Colors.white,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
          decoration: InputDecoration(
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 2, color: Color(0xFF2C2C2C)),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
          icon: const Icon(Icons.arrow_drop_down, color: Color(0xFF383838)),
          items: ['Male', 'Female', 'Other'].map((String value) {
            return DropdownMenuItem<String>(
              value: value,
              child: Text(value),
            );
          }).toList(),
          onChanged: (String? newValue) {
            if (newValue != null) {
              setState(() {
                _selectedSex = newValue;
              });
            }
          },
        ),
      ],
    );
  }

  Widget _buildRelationshipDropdown() {
    if (!_isEditing) {
      return _buildReadOnlyField('Relationship', _selectedRelationship ?? 'Not set');
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Relationship',
          style: TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 8),
        DropdownButtonFormField<String>(
          value: _selectedRelationship,
          dropdownColor: Colors.white,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
          hint: const Text(
            'Select relationship',
            style: TextStyle(
              color: Color.fromARGB(255, 117, 117, 117),
              fontSize: 16,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
          ),
          decoration: InputDecoration(
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 1, color: Color(0xFF383838)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(width: 2, color: Color(0xFF2C2C2C)),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
          icon: const Icon(Icons.arrow_drop_down, color: Color(0xFF383838)),
          items: ['Spouse', 'Child', 'Professional Caregiver'].map((String value) {
            return DropdownMenuItem<String>(
              value: value,
              child: Text(value),
            );
          }).toList(),
          onChanged: (String? newValue) {
            setState(() {
              _selectedRelationship = newValue;
            });
          },
        ),
      ],
    );
  }

  Widget _buildBMIDisplay() {
    final weight = _elderlyModel?.weight;
    final height = _elderlyModel?.height;
    
    if (weight == null || height == null) return const SizedBox.shrink();
    
    final heightInMeters = height / 100;
    final bmi = weight / (heightInMeters * heightInMeters);
    
    String bmiCategory;
    Color bmiColor;
    
    if (bmi < 18.5) {
      bmiCategory = 'Underweight';
      bmiColor = Colors.blue;
    } else if (bmi < 25) {
      bmiCategory = 'Normal';
      bmiColor = Colors.green;
    } else if (bmi < 30) {
      bmiCategory = 'Overweight';
      bmiColor = Colors.orange;
    } else {
      bmiCategory = 'Obese';
      bmiColor = Colors.red;
    }
    
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: bmiColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: bmiColor.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          Icon(
            Icons.monitor_weight_outlined,
            color: bmiColor,
            size: 24,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'BMI: ${bmi.toStringAsFixed(1)}',
                  style: TextStyle(
                    color: bmiColor,
                    fontSize: 16,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  bmiCategory,
                  style: TextStyle(
                    color: bmiColor.withOpacity(0.8),
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

  Widget _buildEmptyState({
    required IconData icon,
    required String title,
    required String subtitle,
  }) {
    return Column(
      children: [
        Icon(
          icon,
          size: 48,
          color: const Color(0xFF666666).withOpacity(0.5),
        ),
        const SizedBox(height: 16),
        Text(
          title,
          style: const TextStyle(
            color: Color(0xFF666666),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          textAlign: TextAlign.center,
          style: const TextStyle(
            color: Color(0xFF999999),
            fontSize: 14,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w400,
          ),
        ),
      ],
    );
  }

  BoxDecoration _cardDecoration() {
    return BoxDecoration(
      color: Colors.white,
      border: Border.all(color: const Color(0xFF383838), width: 1),
      borderRadius: BorderRadius.circular(12),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.1),
          blurRadius: 8,
          offset: const Offset(0, 4),
        ),
      ],
    );
  }

  String _formatDate(DateTime? date) {
    if (date == null) return 'Not available';
    return '${date.day}/${date.month}/${date.year}';
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }

  void _showSignOutDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          title: const Text(
            'Sign Out',
            style: TextStyle(
              fontFamily: 'Inter',
              fontWeight: FontWeight.w600,
              fontSize: 18,
            ),
          ),
          content: const Text(
            'Are you sure you want to sign out?',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 14,
              color: Color(0xFF666666),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text(
                'Cancel',
                style: TextStyle(
                  color: Color(0xFF666666),
                  fontFamily: 'Inter',
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () async {
                Navigator.of(context).pop();
                
                try {
                  await AuthService.signOut();
                  
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('👋 Signed out successfully!'),
                        backgroundColor: Colors.green,
                        duration: Duration(seconds: 2),
                      ),
                    );
                    
                    Navigator.of(context).pushNamedAndRemoveUntil(
                      '/signin',
                      (route) => false,
                    );
                  }
                } catch (e) {
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('Error signing out: ${e.toString()}'),
                        backgroundColor: Colors.red,
                      ),
                    );
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: const Text(
                'Sign Out',
                style: TextStyle(
                  color: Colors.white,
                  fontFamily: 'Inter',
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}
=======
      ),

    );
  }
}

>>>>>>> dev

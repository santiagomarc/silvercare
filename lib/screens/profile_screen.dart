import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import '../widgets/nav_bar_svg.dart'; 
import '../models/elderly_model.dart'; 

const String _logoAssetPath = 'assets/icons/silvercare.png'; 

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final int _currentIndex = 4;
  
  final List<String> _navLabels = const [
    'Notifications', 'Calendar', 'Wellness', 'Home', 'Profile',
  ];
  
  final Color _blueBgColor = const Color(0xFF32C3D2); 
  final Color _darkGreyText = const Color(0xFF808080);

  // FIX: Added missing color constants
  final Color _redLogout = const Color(0xFFCD5C5C); 
  static const Color _shadowColor25Percent = Color(0x40000000); 

  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _usernameController = TextEditingController();
  final _ageController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _weightController = TextEditingController();
  final _heightController = TextEditingController();
  final _medicalController = TextEditingController(); 

  final _ecNameController = TextEditingController();
  final _ecPhoneController = TextEditingController();
  String? _ecRelationship; 

  String? _selectedSex; 
  
  bool _isLoading = true;
  bool _isSaving = false;
  bool _isEditing = false; 
  
  ElderlyModel? _elderlyModel; 

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }
  
  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _usernameController.dispose();
    _ageController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _weightController.dispose();
    _heightController.dispose();
    _medicalController.dispose();
    _ecNameController.dispose();
    _ecPhoneController.dispose();
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
      final userDoc = await _firestore.collection('users').doc(user.uid).get();
      final elderlyDoc = await _firestore.collection('elderly').doc(user.uid).get();

      if (userDoc.exists) {
        _nameController.text = userDoc.get('fullName') ?? 'N/A';
      }

      if (elderlyDoc.exists) {
        final model = ElderlyModel.fromDoc(elderlyDoc);
        _elderlyModel = model;
        
        _usernameController.text = model.username;
        _ageController.text = model.age?.toString() ?? '';
        _phoneController.text = model.phoneNumber;
        _weightController.text = model.weight?.toString() ?? '';
        _heightController.text = model.height?.toString() ?? '';
        
        _selectedSex = model.sex; 
        
        _ecNameController.text = model.emergencyContact?.name ?? '';
        _ecPhoneController.text = model.emergencyContact?.phone ?? '';
        _ecRelationship = model.emergencyContact?.relationship; 

        final conditions = model.medicalInfo?.conditions.join(', ') ?? 'None';
        final medications = model.medicalInfo?.medications.join(', ') ?? 'None';
        final allergies = model.medicalInfo?.allergies.join(', ') ?? 'None';
        
        _medicalController.text = 'Conditions: $conditions\nMedications: $medications\nAllergies: $allergies';
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
    if (_isLoading || !_isEditing) return;
    
    setState(() {
      _isSaving = true;
    });

    final user = _auth.currentUser;
    if (user == null) {
      _isSaving = false;
      return;
    }
    
    final EmergencyContact? updatedEmergencyContact = (_ecNameController.text.isNotEmpty && _ecPhoneController.text.isNotEmpty)
      ? EmergencyContact(
          name: _ecNameController.text.trim(),
          phone: _ecPhoneController.text.trim(),
          relationship: _ecRelationship ?? 'Other',
        )
      : null;

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
        'username': _usernameController.text.trim(),
        'sex': _selectedSex,
        'age': int.tryParse(_ageController.text.trim()),
        'phoneNumber': _phoneController.text.trim(),
        'address': _addressController.text.trim(),
        'weight': double.tryParse(_weightController.text.trim()),
        'height': double.tryParse(_heightController.text.trim()),
        'emergencyContact': updatedEmergencyContact?.toMap(),
      };
      
      await _firestore.collection('elderly').doc(user.uid).update(elderlyUpdateData);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('✅ Profile saved successfully!'), backgroundColor: Colors.green),
        );
        setState(() {
          _isEditing = false;
        });
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

  void _handleTabTap(int index) {
    String destination = _navLabels[index];
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text("Navigation Demo"),
          content: Text("You would navigate to the '$destination' screen now."),
          actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text("Close"))],
        );
      },
    );
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
  
  void _showComingSoonDialog(BuildContext context, String feature) {
     showDialog(
      context: context,
      builder: (BuildContext context) {
        return const AlertDialog(
          title: Text("Feature Coming Soon"),
          content: Text("This feature is under development."),
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
            onTap: () => _showComingSoonDialog(context, 'Settings'),
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

  Widget _buildEditProfileControl() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            const Icon(Icons.person_pin, size: 24, color: Colors.black),
            const SizedBox(width: 8),
            Text(
              _isEditing ? 'Editing Profile' : 'View Profile Details',
              style: TextStyle(
                color: const Color(0xFF000000), 
                fontSize: _getResponsiveFontSize(context, 20), 
                fontFamily: 'Montserrat', 
                fontWeight: FontWeight.w600, 
                shadows: [Shadow(offset: const Offset(0, 4), blurRadius: 4, color: _shadowColor25Percent)],
              ),
            ),
          ],
        ),

        ElevatedButton.icon(
          onPressed: _isLoading ? null : () {
            setState(() {
              _isEditing = !_isEditing;
            });
            if (!_isEditing) {
              _loadUserData();
            }
          },
          icon: Icon(_isEditing ? Icons.close : Icons.edit, size: 18),
          label: Text(_isEditing ? 'Cancel Edit' : 'Edit Details'),
          style: ElevatedButton.styleFrom(
            backgroundColor: _isEditing ? _redLogout.withOpacity(0.8) : const Color(0xFFFFB300), // Amber for Edit, Red for Cancel
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            elevation: 4,
          ),
        ),
      ],
    );
  }

  Widget _buildSexDropdown() {
    final bool readOnly = !_isEditing;

    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          color: readOnly ? Colors.white.withOpacity(0.8) : Colors.white,
          borderRadius: BorderRadius.circular(15), 
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.30), blurRadius: 4, offset: const Offset(0, 2))],
          border: Border.all(
            color: readOnly ? Colors.transparent : _darkGreyText, 
            width: readOnly ? 0 : 1,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Sex:',
                style: TextStyle(
                  color: _darkGreyText.withOpacity(0.9),
                  fontSize: _getResponsiveFontSize(context, 14),
                  fontFamily: 'Montserrat', 
                  fontWeight: FontWeight.w700, 
                ),
              ),
              IgnorePointer(
                ignoring: readOnly,
                child: DropdownButtonFormField<String>(
                  value: _selectedSex,
                  isExpanded: true,
                  dropdownColor: Colors.white,
                  style: TextStyle(
                    color: readOnly ? Colors.black54 : const Color(0xFF000000),
                    fontSize: _getResponsiveFontSize(context, 16),
                    fontFamily: 'Inter', 
                    fontWeight: readOnly ? FontWeight.w500 : FontWeight.w600,
                  ),
                  decoration: const InputDecoration(
                    isDense: true,
                    contentPadding: EdgeInsets.zero,
                    border: InputBorder.none,
                    filled: false,
                  ),
                  items: ['Male', 'Female', 'Other'].map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedSex = newValue;
                    });
                  },
                ),
              ),
            ],
          ),
        ),
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
    final bool readOnly = isReadOnly || !_isEditing;

    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          color: readOnly ? Colors.white.withOpacity(0.8) : Colors.white,
          borderRadius: BorderRadius.circular(15),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.30), blurRadius: 4, offset: const Offset(0, 2))],
          border: Border.all(
            color: readOnly ? Colors.transparent : _darkGreyText, 
            width: readOnly ? 0 : 1,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                label,
                style: TextStyle(
                  color: _darkGreyText.withOpacity(0.9),
                  fontSize: _getResponsiveFontSize(context, 14),
                  fontFamily: 'Montserrat', 
                  fontWeight: FontWeight.w700, 
                ),
              ),
              TextFormField(
                controller: controller,
                readOnly: readOnly,
                keyboardType: keyboardType,
                maxLines: label.contains('Medical') ? 5 : 1,
                style: TextStyle(
                  color: readOnly ? Colors.black54 : const Color(0xFF000000),
                  fontSize: _getResponsiveFontSize(context, 16),
                  fontFamily: 'Inter', 
                  fontWeight: readOnly ? FontWeight.w500 : FontWeight.w600,
                ),
                decoration: const InputDecoration(
                  isDense: true,
                  contentPadding: EdgeInsets.zero,
                  border: InputBorder.none,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildEmergencyContactRow() {
    final bool readOnly = !_isEditing;
    final List<String> relationships = ['Spouse', 'Child', 'Professional Caregiver', 'Other'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 8.0, left: 4),
          child: Text(
            'EMERGENCY CONTACT DETAILS:',
            style: TextStyle(
              color: Colors.black, 
              fontSize: _getResponsiveFontSize(context, 18), 
              fontFamily: 'Montserrat', 
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
        const SizedBox(height: 15),

        Row(
          children: [
            Expanded(child: _buildProfileDetailRow(context: context, label: 'EC Name:', controller: _ecNameController)),
            const SizedBox(width: 15),
            Expanded(child: _buildProfileDetailRow(context: context, label: 'EC Phone:', controller: _ecPhoneController, keyboardType: TextInputType.phone)),
          ],
        ),

        Padding(
          padding: const EdgeInsets.only(bottom: 20.0),
          child: Container(
            width: double.infinity,
            decoration: BoxDecoration(
              color: readOnly ? Colors.white.withOpacity(0.8) : Colors.white,
              borderRadius: BorderRadius.circular(15), 
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.30), blurRadius: 4, offset: const Offset(0, 2))],
              border: Border.all(
                color: readOnly ? Colors.transparent : _darkGreyText, 
                width: readOnly ? 0 : 1,
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'EC Relationship:',
                    style: TextStyle(
                      color: _darkGreyText.withOpacity(0.9),
                      fontSize: _getResponsiveFontSize(context, 14),
                      fontFamily: 'Montserrat', 
                      fontWeight: FontWeight.w700, 
                    ),
                  ),
                  IgnorePointer(
                    ignoring: readOnly,
                    child: DropdownButtonFormField<String>(
                      value: _ecRelationship,
                      isExpanded: true,
                      dropdownColor: Colors.white,
                      style: TextStyle(
                        color: readOnly ? Colors.black54 : const Color(0xFF000000),
                        fontSize: _getResponsiveFontSize(context, 16),
                        fontFamily: 'Inter', 
                        fontWeight: readOnly ? FontWeight.w500 : FontWeight.w600,
                      ),
                      decoration: const InputDecoration(
                        isDense: true,
                        contentPadding: EdgeInsets.zero,
                        border: InputBorder.none,
                        filled: false,
                      ),
                      items: relationships.map((String value) {
                        return DropdownMenuItem<String>(
                          value: value,
                          child: Text(value),
                        );
                      }).toList(),
                      onChanged: (String? newValue) {
                        setState(() {
                          _ecRelationship = newValue;
                        });
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
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
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          _buildEditProfileControl(),
                          const SizedBox(height: 25),
  
                          // --- CORE PERSONAL INFO ---
                          _buildProfileDetailRow(context: context, label: 'Full Name:', controller: _nameController),
                          _buildProfileDetailRow(context: context, label: 'Username:', controller: _usernameController),
                          _buildProfileDetailRow(context: context, label: 'Email:', controller: _emailController, isReadOnly: true),
                          
                          // --- PHYSICAL STATS ROW ---
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(child: _buildProfileDetailRow(context: context, label: 'Age:', controller: _ageController, keyboardType: TextInputType.number)),
                              const SizedBox(width: 15),
                              Expanded(child: _buildProfileDetailRow(context: context, label: 'Weight (kg):', controller: _weightController, keyboardType: TextInputType.number)),
                              const SizedBox(width: 15),
                              Expanded(child: _buildProfileDetailRow(context: context, label: 'Height (cm):', controller: _heightController, keyboardType: TextInputType.number)),
                            ],
                          ),
                          
                          _buildSexDropdown(),

                          _buildProfileDetailRow(context: context, label: 'Phone Number:', controller: _phoneController, keyboardType: TextInputType.phone),
                          _buildProfileDetailRow(context: context, label: 'Address:', controller: _addressController),
                          
                          _buildEmergencyContactRow(),
                          
                          _buildProfileDetailRow(
                            context: context, 
                            label: 'Medical Info (Read Only):', 
                            controller: _medicalController, 
                            isReadOnly: true,
                          ),
                          
                          const SizedBox(height: 20),

                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              ElevatedButton.icon(
                                onPressed: (_isSaving || !_isEditing) ? null : _handleSaveProfile,
                                icon: _isSaving 
                                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Icon(Icons.save, size: 20, color: Colors.white),
                                label: Text(
                                  'Sign Out',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: _getResponsiveFontSize(context, 16),
                                    fontFamily: 'Montserrat',
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF008000), // Green for save
                                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  elevation: 5,
                                ),
                              ),
                              
                              TextButton(
                                onPressed: _handleSignOut,
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
      bottomNavigationBar: SilverCareNavBar(
        currentIndex: _currentIndex,
        onTap: _handleTabTap,
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'home_screen.dart';

class ProfileCompletionScreen extends StatefulWidget {
  final Map<String, String>? caregiverData;
  
  const ProfileCompletionScreen({
    super.key,
    this.caregiverData,
  });

  @override
  State<ProfileCompletionScreen> createState() => _ProfileCompletionScreenState();
}

class _ProfileCompletionScreenState extends State<ProfileCompletionScreen> {
  final PageController _pageController = PageController();
  final GlobalKey<FormState> _personalFormKey = GlobalKey<FormState>();
  final GlobalKey<FormState> _emergencyFormKey = GlobalKey<FormState>();
  final GlobalKey<FormState> _medicalFormKey = GlobalKey<FormState>();
  
  int _currentStep = 0;
  bool _isLoading = false;
  
  // Personal Details Controllers
  final _ageController = TextEditingController();
  final _weightController = TextEditingController();
  final _heightController = TextEditingController();
  
  // Emergency Contact Controllers
  final _emergencyNameController = TextEditingController();
  final _emergencyPhoneController = TextEditingController();
  String? _selectedRelationship;
  bool _useCaregiver = true; // Default to true if caregiver data exists
  
  // Medical Info Controllers
  final _conditionsController = TextEditingController();
  final _medicationsController = TextEditingController();
  final _allergiesController = TextEditingController();
  
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  @override
  void initState() {
    super.initState();
    // Initialize caregiver toggle based on available data
    _useCaregiver = widget.caregiverData != null;
    _initializeCaregiverData();
  }

  void _initializeCaregiverData() {
    if (widget.caregiverData != null && _useCaregiver) {
      _emergencyNameController.text = widget.caregiverData!['name'] ?? '';
      _selectedRelationship = widget.caregiverData!['relationship'];
      // Note: Email is not shown in emergency contact form
      // Phone number is left empty for user to fill
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    _ageController.dispose();
    _weightController.dispose();
    _heightController.dispose();
    _emergencyNameController.dispose();
    _emergencyPhoneController.dispose();
    _conditionsController.dispose();
    _medicationsController.dispose();
    _allergiesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFDEDEDE),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Top section with title
              _buildTopSection(),
              
              const SizedBox(height: 20),
              
              // Progress indicator
              _buildProgressIndicator(),
              
              const SizedBox(height: 30),
              
              // Main content area
              Expanded(
                child: PageView(
                  controller: _pageController,
                  physics: const NeverScrollableScrollPhysics(),
                  children: [
                    _buildPersonalDetailsStep(),
                    _buildEmergencyContactStep(),
                    _buildMedicalInfoStep(),
                  ],
                ),
              ),
              
              const SizedBox(height: 20),
              
              // Navigation buttons
              _buildNavigationButtons(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTopSection() {
    return Column(
      children: [
        // SilverCare title
        Text(
          'SILVERCARE',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.black,
            fontSize: _getResponsiveFontSize(context, 20),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w800,
            shadows: [
              Shadow(
                color: Colors.black.withValues(alpha: 0.25),
                offset: const Offset(0, 2),
                blurRadius: 2,
              )
            ],
          ),
        ),
        
        const SizedBox(height: 10),
        
        // Complete Profile title
        Text(
          'Complete Your Profile',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.black,
            fontSize: _getResponsiveFontSize(context, 28),
            fontFamily: 'Montserrat',
            fontWeight: FontWeight.w700,
          ),
        ),
        
        const SizedBox(height: 8),
        
        // Subtitle
        Text(
          'Help us provide better care by completing your profile',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: const Color(0xFF666666),
            fontSize: _getResponsiveFontSize(context, 14),
            fontFamily: 'Inter',
            fontWeight: FontWeight.w400,
          ),
        ),
      ],
    );
  }

  Widget _buildProgressIndicator() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        children: [
          // Step indicators
          Row(
            children: [
              _buildStepIndicator(0, 'Personal', isActive: _currentStep >= 0),
              Expanded(child: _buildConnector(isActive: _currentStep >= 1)),
              _buildStepIndicator(1, 'Emergency', isActive: _currentStep >= 1),
              Expanded(child: _buildConnector(isActive: _currentStep >= 2)),
              _buildStepIndicator(2, 'Medical', isActive: _currentStep >= 2),
            ],
          ),
          
          const SizedBox(height: 12),
          
          // Progress text
          Text(
            'Step ${_currentStep + 1} of 3',
            style: const TextStyle(
              color: Color(0xFF666666),
              fontSize: 14,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator(int step, String label, {required bool isActive}) {
    return Column(
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: isActive ? const Color(0xFF2C2C2C) : Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isActive ? const Color(0xFF2C2C2C) : const Color(0xFFCCCCCC),
              width: 2,
            ),
          ),
          child: Center(
            child: isActive 
              ? (_currentStep > step 
                  ? const Icon(Icons.check, color: Colors.white, size: 18)
                  : Text(
                      '${step + 1}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                    ))
              : Text(
                  '${step + 1}',
                  style: const TextStyle(
                    color: Color(0xFFCCCCCC),
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: TextStyle(
            color: isActive ? const Color(0xFF2C2C2C) : const Color(0xFFCCCCCC),
            fontSize: 12,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildConnector({required bool isActive}) {
    return Container(
      height: 2,
      margin: const EdgeInsets.only(bottom: 24),
      color: isActive ? const Color(0xFF2C2C2C) : const Color(0xFFCCCCCC),
    );
  }

  Widget _buildPersonalDetailsStep() {
    return SingleChildScrollView(
      child: _buildFormContainer(
        title: 'Personal Details',
        subtitle: 'Tell us a bit more about yourself',
        icon: Icons.person_outline,
        child: Form(
          key: _personalFormKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildTextFormField(
                label: 'Age',
                controller: _ageController,
                hintText: 'Enter your age',
                keyboardType: TextInputType.number,
                customValidator: (value) {
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
              
              _buildTextFormField(
                label: 'Weight (kg)',
                controller: _weightController,
                hintText: 'Enter your weight in kg',
                keyboardType: TextInputType.numberWithOptions(decimal: true),
                customValidator: (value) {
                  if (value != null && value.isNotEmpty) {
                    final weight = double.tryParse(value);
                    if (weight == null || weight <= 0 || weight > 500) {
                      return 'Please enter a valid weight';
                    }
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              _buildTextFormField(
                label: 'Height (cm)',
                controller: _heightController,
                hintText: 'Enter your height in cm',
                keyboardType: TextInputType.numberWithOptions(decimal: true),
                customValidator: (value) {
                  if (value != null && value.isNotEmpty) {
                    final height = double.tryParse(value);
                    if (height == null || height <= 0 || height > 300) {
                      return 'Please enter a valid height';
                    }
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 24),
              
              // Info box
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF0F8FF),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: const Color(0xFF2C2C2C).withOpacity(0.1),
                  ),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      Icons.info_outline,
                      color: const Color(0xFF2C2C2C).withOpacity(0.7),
                      size: 20,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'These details are optional but help us provide personalized health recommendations.',
                        style: TextStyle(
                          color: const Color(0xFF2C2C2C).withOpacity(0.8),
                          fontSize: 13,
                          fontFamily: 'Inter',
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmergencyContactStep() {
    return SingleChildScrollView(
      child: _buildFormContainer(
        title: 'Emergency Contact',
        subtitle: 'Someone we can reach in case of emergency',
        icon: Icons.emergency,
        child: Form(
          key: _emergencyFormKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Caregiver toggle (only show if caregiver data exists)
              if (widget.caregiverData != null) ...[
                _buildCaregiverToggle(),
                const SizedBox(height: 20),
              ],
              
              // Show pre-filled info when using caregiver
              if (_useCaregiver && widget.caregiverData != null) ...[
                _buildCaregiverInfoSection(),
                const SizedBox(height: 16),
                // Only phone number input needed
                _buildTextFormField(
                  label: 'Phone Number',
                  controller: _emergencyPhoneController,
                  hintText: 'Enter phone number',
                  keyboardType: TextInputType.phone,
                  prefixText: '+63 ',
                  isRequired: true,
                  customValidator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter emergency contact phone';
                    }
                    if (value.length < 10) {
                      return 'Please enter a valid phone number';
                    }
                    return null;
                  },
                ),
              ] else ...[
                // Normal emergency contact form
                _buildTextFormField(
                  label: 'Contact Name',
                  controller: _emergencyNameController,
                  hintText: 'Enter emergency contact name',
                  isRequired: true,
                ),
                
                const SizedBox(height: 16),
                
                _buildTextFormField(
                  label: 'Phone Number',
                  controller: _emergencyPhoneController,
                  hintText: 'Enter phone number',
                  keyboardType: TextInputType.phone,
                  prefixText: '+63 ',
                  isRequired: true,
                  customValidator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter emergency contact phone';
                    }
                    if (value.length < 10) {
                      return 'Please enter a valid phone number';
                    }
                    return null;
                  },
                ),
                
                const SizedBox(height: 16),
                
                _buildRelationshipDropdown(),
              ],
              
              const SizedBox(height: 24),
              
              // Info box
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF0F5),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: Colors.red.withOpacity(0.2),
                  ),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      Icons.emergency_outlined,
                      color: Colors.red.withOpacity(0.7),
                      size: 20,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'This contact will be notified in case of medical emergencies or if we cannot reach you.',
                        style: TextStyle(
                          color: Colors.red.withOpacity(0.8),
                          fontSize: 13,
                          fontFamily: 'Inter',
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMedicalInfoStep() {
    return SingleChildScrollView(
      child: _buildFormContainer(
        title: 'Medical Information',
        subtitle: 'Help us understand your health needs',
        icon: Icons.medical_information_outlined,
        child: Form(
          key: _medicalFormKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildMultilineTextFormField(
                label: 'Medical Conditions',
                controller: _conditionsController,
                hintText: 'List any medical conditions (e.g., Diabetes, Hypertension)',
                helperText: 'Separate multiple conditions with commas',
              ),
              
              const SizedBox(height: 16),
              
              _buildMultilineTextFormField(
                label: 'Current Medications',
                controller: _medicationsController,
                hintText: 'List current medications and dosages',
                helperText: 'Include dosage and frequency if known',
              ),
              
              const SizedBox(height: 16),
              
              _buildMultilineTextFormField(
                label: 'Allergies',
                controller: _allergiesController,
                hintText: 'List any known allergies (food, medication, environmental)',
                helperText: 'Include severity if known',
              ),
              
              const SizedBox(height: 24),
              
              // Medical info disclaimer
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF5F9FF),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: Colors.blue.withOpacity(0.2),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.health_and_safety_outlined,
                          color: Colors.blue.withOpacity(0.7),
                          size: 20,
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'Medical Information Privacy',
                          style: TextStyle(
                            color: Colors.blue.withOpacity(0.9),
                            fontSize: 14,
                            fontFamily: 'Inter',
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Your medical information is encrypted and only accessible to you and your designated caregivers. This information helps provide better care recommendations.',
                      style: TextStyle(
                        color: Colors.blue.withOpacity(0.8),
                        fontSize: 13,
                        fontFamily: 'Inter',
                        fontWeight: FontWeight.w400,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFormContainer({
    required String title,
    required String subtitle,
    required IconData icon,
    required Widget child,
  }) {
    return Center(
      child: Container(
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.9,
          minWidth: 280,
        ),
        padding: const EdgeInsets.all(24),
        decoration: ShapeDecoration(
          color: Colors.white,
          shape: RoundedRectangleBorder(
            side: const BorderSide(
              width: 1,
              color: Color(0xFF383838),
            ),
            borderRadius: BorderRadius.circular(25),
          ),
          shadows: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 8,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Section header
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
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          color: Color(0xFF1E1E1E),
                          fontSize: 20,
                          fontFamily: 'Inter',
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          color: Color(0xFF666666),
                          fontSize: 14,
                          fontFamily: 'Inter',
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 24),
            
            child,
          ],
        ),
      ),
    );
  }

  Widget _buildTextFormField({
    required String label,
    required TextEditingController controller,
    required String hintText,
    bool isRequired = false,
    String? prefixText,
    TextInputType? keyboardType,
    String? Function(String?)? customValidator,
  }) {
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
            hintText: hintText,
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
              borderSide: const BorderSide(
                width: 1,
                color: Color(0xFF383838),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(
                width: 1,
                color: Color(0xFF383838),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(
                width: 2,
                color: Color(0xFF2C2C2C),
              ),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
          validator: customValidator ?? (isRequired ? (value) {
            if (value == null || value.isEmpty) {
              return 'Please enter $label';
            }
            return null;
          } : null),
        ),
      ],
    );
  }

  Widget _buildMultilineTextFormField({
    required String label,
    required TextEditingController controller,
    required String hintText,
    String? helperText,
    bool isRequired = false,
  }) {
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
            hintText: hintText,
            hintStyle: const TextStyle(
              color: Color.fromARGB(255, 117, 117, 117),
              fontSize: 16,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(
                width: 1,
                color: Color(0xFF383838),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(
                width: 1,
                color: Color(0xFF383838),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(
                width: 2,
                color: Color(0xFF2C2C2C),
              ),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
          validator: isRequired ? (value) {
            if (value == null || value.isEmpty) {
              return 'Please enter $label';
            }
            return null;
          } : null,
        ),
      ],
    );
  }

  Widget _buildCaregiverToggle() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF0F8FF),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: const Color(0xFF2C2C2C).withOpacity(0.2),
        ),
      ),
      child: Row(
        children: [
          Icon(
            Icons.family_restroom,
            color: const Color(0xFF2C2C2C).withOpacity(0.7),
            size: 24,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Use Caregiver as Emergency Contact',
                  style: TextStyle(
                    color: Color(0xFF1E1E1E),
                    fontSize: 16,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'We can use your caregiver information for emergency contact',
                  style: TextStyle(
                    color: const Color(0xFF666666),
                    fontSize: 13,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w400,
                  ),
                ),
              ],
            ),
          ),
          Switch(
            value: _useCaregiver,
            onChanged: (bool value) {
              setState(() {
                _useCaregiver = value;
                if (value) {
                  _initializeCaregiverData();
                } else {
                  _emergencyNameController.clear();
                  _emergencyPhoneController.clear();
                  _selectedRelationship = null;
                }
              });
            },
            activeColor: const Color(0xFF2C2C2C),
            activeTrackColor: const Color(0xFF2C2C2C).withOpacity(0.3),
          ),
        ],
      ),
    );
  }

  Widget _buildCaregiverInfoSection() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FA),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: const Color(0xFF2C2C2C).withOpacity(0.1),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                Icons.check_circle,
                color: Colors.green.withOpacity(0.7),
                size: 20,
              ),
              const SizedBox(width: 8),
              const Text(
                'Using Caregiver Information',
                style: TextStyle(
                  color: Color(0xFF1E1E1E),
                  fontSize: 14,
                  fontFamily: 'Inter',
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _buildInfoRow('Name', widget.caregiverData!['name']!),
          const SizedBox(height: 8),
          _buildInfoRow('Email', widget.caregiverData!['email']!),
          const SizedBox(height: 8),
          _buildInfoRow('Relationship', widget.caregiverData!['relationship']!),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 80,
          child: Text(
            '$label:',
            style: const TextStyle(
              color: Color(0xFF666666),
              fontSize: 13,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              color: Color(0xFF1E1E1E),
              fontSize: 13,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildRelationshipDropdown() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Row(
          children: [
            Text(
              'Relationship',
              style: TextStyle(
                color: Color(0xFF1E1E1E),
                fontSize: 16,
                fontFamily: 'Inter',
                fontWeight: FontWeight.w500,
              ),
            ),
            Text(
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
          items: [
            'Spouse',
            'Child',
            'Professional Caregiver'
          ].map((String value) {
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
          validator: (value) {
            if (value == null) {
              return 'Please select relationship';
            }
            return null;
          },
        ),
      ],
    );
  }

  Widget _buildNavigationButtons() {
    return Row(
      children: [
        // Skip button (always visible)
        Expanded(
          child: OutlinedButton(
            onPressed: _isLoading ? null : _skipProfile,
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFF666666),
              side: const BorderSide(color: Color(0xFF666666)),
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text(
              'Skip for now',
              style: TextStyle(
                fontSize: 16,
                fontFamily: 'Inter',
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ),
        
        const SizedBox(width: 16),
        
        // Previous/Next/Complete button
        Expanded(
          flex: 2,
          child: ElevatedButton(
            onPressed: _isLoading ? null : () {
              if (_currentStep == 2) {
                _completeProfile();
              } else {
                _nextStep();
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2C2C2C),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              elevation: 2,
            ),
            child: _isLoading 
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 2,
                  ),
                )
              : Text(
                  _currentStep == 2 ? 'Complete Profile' : 'Next',
                  style: const TextStyle(
                    fontSize: 16,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w600,
                  ),
                ),
          ),
        ),
      ],
    );
  }

  void _nextStep() {
    // Validate current step
    bool isValid = false;
    switch (_currentStep) {
      case 0:
        isValid = _personalFormKey.currentState?.validate() ?? false;
        break;
      case 1:
        isValid = _emergencyFormKey.currentState?.validate() ?? false;
        break;
      case 2:
        isValid = _medicalFormKey.currentState?.validate() ?? false;
        break;
    }

    if (isValid && _currentStep < 2) {
      setState(() {
        _currentStep++;
      });
      _pageController.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  void _skipProfile() {
    _showSkipConfirmationDialog();
  }

  void _showSkipConfirmationDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          title: const Row(
            children: [
              Icon(Icons.warning_amber_outlined, color: Colors.orange),
              SizedBox(width: 8),
              Text('Skip Profile Completion?'),
            ],
          ),
          content: const Text(
            'You can complete your profile later in Settings. Some features may be limited until your profile is complete.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                _navigateToHome();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.orange,
                foregroundColor: Colors.white,
              ),
              child: const Text('Skip'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _completeProfile() async {
    if (!(_medicalFormKey.currentState?.validate() ?? false)) {
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final user = _auth.currentUser;
      if (user == null) throw Exception('User not authenticated');

      // Show progress
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('💾 Saving your profile...'),
          backgroundColor: Colors.blue,
          duration: Duration(seconds: 2),
        ),
      );

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
        'profileCompleted': true,
      };

      // Add personal details if provided
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
      if (_emergencyPhoneController.text.trim().isNotEmpty) {
        String contactName;
        String contactRelationship;
        
        if (_useCaregiver && widget.caregiverData != null) {
          // Use caregiver data
          contactName = widget.caregiverData!['name']!;
          contactRelationship = widget.caregiverData!['relationship']!;
        } else {
          // Use manually entered data
          contactName = _emergencyNameController.text.trim();
          contactRelationship = _selectedRelationship ?? 'Other';
        }
        
        updateData['emergencyContact'] = {
          'name': contactName,
          'phone': _emergencyPhoneController.text.trim(),
          'relationship': contactRelationship,
        };
      }

      // Add medical info if provided
      if (conditions.isNotEmpty || medications.isNotEmpty || allergies.isNotEmpty) {
        updateData['medicalInfo'] = {
          'conditions': conditions,
          'medications': medications,
          'allergies': allergies,
        };
      }

      // Update elderly record
      await _firestore.collection('elderly').doc(user.uid).update(updateData);

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Profile completed successfully!'),
          backgroundColor: Colors.green,
          duration: Duration(seconds: 2),
        ),
      );

      // Navigate to home
      await Future.delayed(const Duration(milliseconds: 1500));
      _navigateToHome();

    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Failed to save profile: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 4),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _navigateToHome() {
    // Replace the entire navigation stack with home screen
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (context) => const HomeScreen()),
      (route) => false,
    );
  }

  // Responsive font sizing
  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }
}
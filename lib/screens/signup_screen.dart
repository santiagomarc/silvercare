import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/models.dart';
import 'profile_completion_screen.dart';

class SignUpScreen extends StatefulWidget {
  const SignUpScreen({super.key});

  @override
  State<SignUpScreen> createState() => _SignUpScreenState();
}

class _SignUpScreenState extends State<SignUpScreen> {
  final _formKey = GlobalKey<FormState>();
  final _fullNameController = TextEditingController();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _phoneController = TextEditingController();
  
  // Caregiver fields
  final _caregiverNameController = TextEditingController();
  final _caregiverEmailController = TextEditingController();
  
  String? _selectedSex;
  String? _selectedRelationship;
  bool _addCaregiver = false;
  bool _isLoading = false;
  
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  @override
  void dispose() {
    _fullNameController.dispose();
    _usernameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _phoneController.dispose();
    _caregiverNameController.dispose();
    _caregiverEmailController.dispose();
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
              // Top section with back button and Silver Care text
              _buildTopSection(context),

              const SizedBox(height: 10),
              
              // Sign Up title
              _buildSignUpTitle(),
              
              const SizedBox(height: 20),
              
              // Registration form - scrollable
              Expanded(
                child: SingleChildScrollView(
                  child: _buildRegistrationForm(),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTopSection(BuildContext context) {
    return Row(
      children: [
        // Back button
        _buildBackButton(context),
        
        // SilverCare text - centered in remaining space
        Expanded(
          child: Text(
            'SILVERCARE',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Colors.black,
              fontSize: _getResponsiveFontSize(context, 20),
              fontFamily: 'Montserrat',
              fontWeight: FontWeight.w800,
              shadows: [
                Shadow(
                  offset: const Offset(0, 2),
                  blurRadius: 4, 
                  color: Colors.black.withValues(alpha: 0.50)
                )
              ],
            ),
          ),
        ),
        
        // Invisible container to balance the back button
        const SizedBox(width: 48),
      ],
    );
  }

  Widget _buildBackButton(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.pop(context),
      child: Container(
        width: 48,
        height: 48,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.8),
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: const Icon(
          Icons.arrow_back,
          color: Colors.black,
          size: 24,
        ),
      ),
    );
  }

  Widget _buildSignUpTitle() {
    return Text(
      'SIGN UP',
      textAlign: TextAlign.center,
      style: TextStyle(
        color: Colors.black,
        fontSize: _getResponsiveFontSize(context, 36),
        fontFamily: 'Montserrat',
        fontWeight: FontWeight.w800,
      ),
    );
  }

  Widget _buildRegistrationForm() {
    return Center(
      child: Container(
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.9, // 90% of screen width
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
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Full Name Field
              _buildTextFormField(
                label: 'Full Name',
                controller: _fullNameController,
                hintText: 'Enter your full name',
              ),
              
              const SizedBox(height: 16),
              
              // Username Field
              _buildTextFormField(
                label: 'Username',
                controller: _usernameController,
                hintText: 'Enter your username',
                customValidator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter username';
                  }
                  if (value.length < 3) {
                    return 'Username must be at least 3 characters';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              // Email Field
              _buildTextFormField(
                label: 'Email',
                controller: _emailController,
                hintText: 'Enter your email',
                keyboardType: TextInputType.emailAddress,
                customValidator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter email';
                  }
                  if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                    return 'Please enter a valid email';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              // Password Field
              _buildTextFormField(
                label: 'Password',
                controller: _passwordController,
                hintText: 'Enter your password',
                isPassword: true,
                customValidator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter password';
                  }
                  if (value.length < 6) {
                    return 'Password must be at least 6 characters';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              // Sex Dropdown
              _buildSexDropdown(),
              
              const SizedBox(height: 16),
              
              // Phone Number Field
              _buildTextFormField(
                label: 'Phone Number',
                controller: _phoneController,
                hintText: '9123456789',
                prefixText: '+63 ',
                keyboardType: TextInputType.phone,
              ),
              
              const SizedBox(height: 32),
              
              // Divider
              const Divider(color: Color(0xFF383838), thickness: 1),
              
              const SizedBox(height: 24),
              
              // Caregiver Section
              _buildCaregiverSection(),
              
              const SizedBox(height: 32),
              
              // Register Button
              _buildRegisterButton(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextFormField({
    required String label,
    required TextEditingController controller,
    required String hintText,
    bool isPassword = false,
    String? prefixText,
    TextInputType? keyboardType,
    String? Function(String?)? customValidator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w400,
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          obscureText: isPassword,
          keyboardType: keyboardType,
          style: const TextStyle( // Add this style property
            color: Color(0xFF1E1E1E), // Dark gray for better contrast
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
          validator: customValidator ?? (value) {
            if (value == null || value.isEmpty) {
              return 'Please enter $label';
            }
            return null;
          },
        ),
      ],
    );
  }

  Widget _buildSexDropdown() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Sex',
          style: TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w400,
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
          hint: const Text(
            'Select your sex',
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
          icon: const Icon(
            Icons.arrow_drop_down,
            color: Color(0xFF383838),
          ),
          items: ['Male', 'Female', 'Other'].map((String value) {
            return DropdownMenuItem<String>(
              value: value,
              child: Text(
                value,
                style: const TextStyle(
                  color: Color(0xFF1E1E1E),
                  fontSize: 16,
                  fontFamily: 'Inter',
                  fontWeight: FontWeight.w500,
                ),
              ),
            );
          }).toList(),
          onChanged: (String? newValue) {
            setState(() {
              _selectedSex = newValue;
            });
          },
          validator: (value) {
            if (value == null) {
              return 'Please select your sex';
            }
            return null;
          },
        ),
      ],
    );
  }

  Widget _buildCaregiverSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Section Header
        Row(
          children: [
            const Icon(
              Icons.family_restroom,
              color: Color(0xFF2C2C2C),
              size: 24,
            ),
            const SizedBox(width: 8),
            const Text(
              'Caregiver Information',
              style: TextStyle(
                color: Color(0xFF1E1E1E),
                fontSize: 18,
                fontFamily: 'Inter',
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        
        const SizedBox(height: 12),
        
        // Toggle Checkbox
        InkWell(
          onTap: () {
            setState(() {
              _addCaregiver = !_addCaregiver;
            });
          },
          child: Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF5F5F5),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(
                color: _addCaregiver ? const Color(0xFF2C2C2C) : const Color(0xFF383838),
                width: _addCaregiver ? 2 : 1,
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 24,
                  height: 24,
                  decoration: BoxDecoration(
                    color: _addCaregiver ? const Color(0xFF2C2C2C) : Colors.white,
                    borderRadius: BorderRadius.circular(4),
                    border: Border.all(
                      color: const Color(0xFF383838),
                      width: 2,
                    ),
                  ),
                  child: _addCaregiver
                      ? const Icon(
                          Icons.check,
                          color: Colors.white,
                          size: 16,
                        )
                      : null,
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Add a caregiver to monitor my health',
                    style: TextStyle(
                      color: Color(0xFF1E1E1E),
                      fontSize: 15,
                      fontFamily: 'Inter',
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
        
        // Show caregiver fields if toggled
        if (_addCaregiver) ...[
          const SizedBox(height: 20),
          
          // Info box
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF0F8FF),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(
                color: const Color(0xFF2C2C2C).withOpacity(0.2),
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.info_outline,
                  color: Color(0xFF2C2C2C),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Your caregiver will receive a password setup email. Ask them to check their spam/junk folder if not received within 5 minutes.',
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
          
          const SizedBox(height: 16),
          
          // Caregiver Name
          _buildTextFormField(
            label: 'Caregiver Name',
            controller: _caregiverNameController,
            hintText: 'e.g., Juan Dela Cruz',
            customValidator: (value) {
              if (_addCaregiver && (value == null || value.isEmpty)) {
                return 'Please enter caregiver name';
              }
              return null;
            },
          ),
          
          const SizedBox(height: 16),
          
          // Caregiver Email
          _buildTextFormField(
            label: 'Caregiver Email',
            controller: _caregiverEmailController,
            hintText: 'caregiver@email.com',
            keyboardType: TextInputType.emailAddress,
            customValidator: (value) {
              if (_addCaregiver) {
                if (value == null || value.isEmpty) {
                  return 'Please enter caregiver email';
                }
                if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                  return 'Please enter a valid email';
                }
              }
              return null;
            },
          ),
          
          const SizedBox(height: 16),
          
          // Relationship Dropdown
          _buildRelationshipDropdown(),
        ],
      ],
    );
  }

  Widget _buildRelationshipDropdown() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Relationship',
          style: TextStyle(
            color: Color(0xFF1E1E1E),
            fontSize: 16,
            fontFamily: 'Inter',
            fontWeight: FontWeight.w400,
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
          items: ['Spouse', 'Child', 'Professional Caregiver']
              .map((String value) {
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
            if (_addCaregiver && value == null) {
              return 'Please select relationship';
            }
            return null;
          },
        ),
      ],
    );
  }

  Widget _buildRegisterButton() {
    return ElevatedButton(
      onPressed: _isLoading ? null : () async {
        if (_formKey.currentState!.validate()) {
          await _registerUser();
        }
      },
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF2C2C2C),
        foregroundColor: const Color(0xFFF5F5F5),
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
        : const Text(
            'Register',
            style: TextStyle(
              fontSize: 16,
              fontFamily: 'Inter',
              fontWeight: FontWeight.w400,
            ),
          ),
    );
  }



  Future<void> _registerUser() async {
    setState(() {
      _isLoading = true;
    });

    // Show progress snackbar
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('� Starting registration process...'),
        backgroundColor: Colors.blue,
        duration: Duration(seconds: 2),
      ),
    );

    try {
      // Create elderly user account
      UserCredential elderlyCredential = await _auth.createUserWithEmailAndPassword(
        email: _emailController.text.trim(),
        password: _passwordController.text.trim(),
      );
      
      // Show progress snackbar
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Account created! Saving to database...'),
          backgroundColor: Colors.blue,
          duration: Duration(seconds: 2),
        ),
      );

      String? caregiverId;
      
      // Create caregiver if provided
      if (_addCaregiver && 
          _caregiverEmailController.text.trim().isNotEmpty && 
          _caregiverNameController.text.trim().isNotEmpty) {
        
        try {
          caregiverId = await _createCaregiverAccount(
            elderlyId: elderlyCredential.user!.uid,
            caregiverName: _caregiverNameController.text.trim(),
            caregiverEmail: _caregiverEmailController.text.trim(),
            relationship: _selectedRelationship ?? 'Professional Caregiver',
          );
          
          // Sign back in as elderly user
          await _auth.signInWithEmailAndPassword(
            email: _emailController.text.trim(),
            password: _passwordController.text.trim(),
          );
        } catch (e) {
          // If caregiver creation fails, continue with elderly account only
          caregiverId = null;
        }
      }

      // Create user and elderly records
      await _createElderlyRecords(
        userId: elderlyCredential.user!.uid,
        email: _emailController.text.trim(),
        fullName: _fullNameController.text.trim(),
        username: _usernameController.text.trim(),
        phoneNumber: _phoneController.text.trim(),
        sex: _selectedSex!,
        caregiverId: caregiverId,
      );
      
      // Show final progress
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Registration complete! Preparing success dialog...'),
          backgroundColor: Colors.green,
          duration: Duration(seconds: 1),
        ),
      );
      
      // Small delay to let user see the message
      await Future.delayed(const Duration(milliseconds: 1500));

      if (mounted) {
        // Show success dialog instead of just snackbar for better visibility
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (BuildContext context) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              title: const Row(
                children: [
                  Icon(Icons.check_circle, color: Colors.green, size: 24),
                  SizedBox(width: 8),
                  Text('Success!', style: TextStyle(color: Colors.green)),
                ],
              ),
              content: Text(
                caregiverId != null 
                  ? 'Your account has been created successfully!\n\n📧 Your caregiver has been notified and will receive setup instructions via email.\n\n✨ Let\'s complete your profile to personalize your care experience!'
                  : 'Your account has been created successfully!\n\n✨ Let\'s complete your profile to personalize your care experience!',
              ),
              actions: [
                ElevatedButton(
                  onPressed: () {
                    Navigator.of(context).pop(); // Close dialog
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(
                        builder: (context) => ProfileCompletionScreen(
                          caregiverData: _addCaregiver ? {
                            'name': _caregiverNameController.text.trim(),
                            'email': _caregiverEmailController.text.trim(),
                            'relationship': _selectedRelationship ?? 'Professional Caregiver',
                          } : null,
                        ),
                      ),
                    );
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                  ),
                  child: const Text('Complete Profile'),
                ),
              ],
            );
          },
        );
      }
    } on FirebaseAuthException catch (e) {
      String errorMessage = 'Registration failed. Please try again.';
      
      switch (e.code) {
        case 'weak-password':
          errorMessage = 'Password is too weak. Please choose a stronger password.';
          break;
        case 'email-already-in-use':
          errorMessage = 'Email is already registered. Please use a different email.';
          break;
        case 'invalid-email':
          errorMessage = 'Please enter a valid email address.';
          break;
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 4),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Registration failed: ${e.toString()}'),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 4),
          ),
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

  // Create caregiver account and send password reset email
  Future<String> _createCaregiverAccount({
    required String elderlyId,
    required String caregiverName,
    required String caregiverEmail,
    required String relationship,
  }) async {
    // Generate temporary password
    final tempPassword = _generateSecurePassword();
    
    // Sign out current user temporarily
    await _auth.signOut();
    
    // Create caregiver account
    UserCredential caregiverCredential = await _auth.createUserWithEmailAndPassword(
      email: caregiverEmail,
      password: tempPassword,
    );
    
    final caregiverId = caregiverCredential.user!.uid;
    
    // Create caregiver records
    final caregiverUser = UserModel(
      id: caregiverId,
      email: caregiverEmail,
      fullName: caregiverName,
      userType: 'caregiver',
      createdAt: DateTime.now(),
    );
    
    final caregiverDetails = CaregiverModel(
      id: caregiverId,
      userId: caregiverId,
      email: caregiverEmail,
      fullName: caregiverName,
      elderlyId: elderlyId,
      relationship: relationship,
      createdAt: DateTime.now(),
    );

    // Create caregiver records in Firestore
    await _firestore.collection('users').doc(caregiverId).set(caregiverUser.toMap());
    await _firestore.collection('caregivers').doc(caregiverId).set(caregiverDetails.toMap());
    
    // Send password reset email
    try {
      await _auth.sendPasswordResetEmail(email: caregiverEmail);
    } catch (e) {
      // Email sending failed, but don't fail the entire registration
      // Caregiver can use "Forgot Password" later
    }
    
    return caregiverId;
  }

  // Create elderly user and elderly records
  Future<void> _createElderlyRecords({
    required String userId,
    required String email,
    required String fullName,
    required String username,
    required String phoneNumber,
    required String sex,
    String? caregiverId,
  }) async {
    try {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('📝 Creating user record...'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 1),
        ),
      );

      final elderlyUser = UserModel(
        id: userId,
        email: email,
        fullName: fullName,
        userType: 'elderly',
        createdAt: DateTime.now(),
      );
      
      // Create user record first
      await _firestore.collection('users').doc(userId).set(elderlyUser.toMap());
      
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ User record created! Creating elderly record...'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 1),
        ),
      );
      
      final elderlyDetails = ElderlyModel(
        id: userId,
        userId: userId,
        username: username,
        phoneNumber: phoneNumber,
        sex: sex,
        caregiverId: caregiverId,
        profileCompleted: false,
        createdAt: DateTime.now(),
      );

      // Create elderly record
      await _firestore.collection('elderly').doc(userId).set(elderlyDetails.toMap());
      
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Both records created successfully!'),
          backgroundColor: Colors.green,
          duration: Duration(seconds: 1),
        ),
      );
      
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Database error: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 5),
        ),
      );
      rethrow; // Re-throw so the main function catches it
    }
  }

  // Generate secure password for caregiver
  String _generateSecurePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#\$%';
    final random = DateTime.now().millisecondsSinceEpoch;
    return List.generate(12, (i) => chars[(random + i) % chars.length]).join();
  }


  // Responsive font sizing based on screen width
  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    final clampedScaleFactor = scaleFactor.clamp(0.8, 1.4);
    return baseSize * clampedScaleFactor;
  }
}
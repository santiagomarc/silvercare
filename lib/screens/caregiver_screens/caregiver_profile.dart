import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:silvercare/models/caregiver_model.dart';
import 'package:silvercare/models/elderly_model.dart';

class CaregiverProfile extends StatefulWidget {
  const CaregiverProfile({super.key});

  @override
  State<CaregiverProfile> createState() => _CaregiverProfileState();
}

class _CaregiverProfileState extends State<CaregiverProfile> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  
  bool _isLoading = true;
  CaregiverModel? _caregiverData;
  ElderlyModel? _elderlyData;
  Map<String, dynamic>? _userProfile; // For caregiver's email from users collection
  Map<String, dynamic>? _elderlyUserProfile; // For elderly's email from users collection

  @override
  void initState() {
    super.initState();
    _fetchCaregiverProfile();
  }

  Future<void> _fetchCaregiverProfile() async {
    setState(() => _isLoading = true);
    
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) {
        throw Exception('User not authenticated');
      }

      // Fetch caregiver's email from users collection
      final userDoc = await _firestore.collection('users').doc(userId).get();
      if (userDoc.exists) {
        _userProfile = userDoc.data();
      }

      // Fetch caregiver document
      final caregiverDoc = await _firestore.collection('caregivers').doc(userId).get();
      if (caregiverDoc.exists) {
        _caregiverData = CaregiverModel.fromDoc(caregiverDoc);

        // If caregiver has an elderly assigned, fetch elderly data
        if (_caregiverData!.elderlyId != null && _caregiverData!.elderlyId!.isNotEmpty) {
          final elderlyDoc = await _firestore
              .collection('elderly')
              .doc(_caregiverData!.elderlyId)
              .get();
          if (elderlyDoc.exists) {
            _elderlyData = ElderlyModel.fromDoc(elderlyDoc);
            
            // Fetch elderly's email from users collection
            final elderlyUserDoc = await _firestore.collection('users').doc(_elderlyData!.userId).get();
            if (elderlyUserDoc.exists) {
              _elderlyUserProfile = elderlyUserDoc.data();
            }
          }
        }
      }
    } catch (e) {
      print('Error fetching caregiver profile: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error loading profile: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _showEditCaregiverDialog() async {
    if (_caregiverData == null) return;

    final nameController = TextEditingController(text: _caregiverData!.fullName ?? '');
    final emailController = TextEditingController(text: _userProfile?['email'] ?? _caregiverData!.email);
    final relationshipController = TextEditingController(text: _caregiverData!.relationship);
    final phoneController = TextEditingController(text: _caregiverData!.phoneNumber ?? '');

    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Edit Caregiver Details'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameController,
                decoration: const InputDecoration(
                  labelText: 'Full Name',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: emailController,
                decoration: const InputDecoration(
                  labelText: 'Email',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: relationshipController,
                decoration: const InputDecoration(
                  labelText: 'Relationship',
                  border: OutlineInputBorder(),
                  hintText: 'e.g., Spouse, Child, Professional Caregiver',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: phoneController,
                decoration: const InputDecoration(
                  labelText: 'Phone Number',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Save'),
          ),
        ],
      ),
    );

    if (result == true) {
      await _saveCaregiverChanges(
        nameController.text,
        emailController.text,
        relationshipController.text,
        phoneController.text,
      );
    }

    nameController.dispose();
    emailController.dispose();
    relationshipController.dispose();
    phoneController.dispose();
  }

  Future<void> _saveCaregiverChanges(
    String fullName,
    String email,
    String relationship,
    String phoneNumber,
  ) async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) return;

      // Validate input
      if (fullName.trim().isEmpty) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('❌ Full name cannot be empty'),
              backgroundColor: Colors.red,
            ),
          );
        }
        return;
      }

      // Update users collection (email and phone)
      await _firestore.collection('users').doc(userId).set({
        'fullName': fullName.trim(),
        'email': email.trim(),
        'phoneNumber': phoneNumber.trim(),
      }, SetOptions(merge: true));

      // Update caregivers collection (include phone number)
      await _firestore.collection('caregivers').doc(userId).update({
        'fullName': fullName.trim(),
        'email': email.trim(),
        'phoneNumber': phoneNumber.trim(),
        'relationship': relationship.trim(),
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✅ Caregiver details updated successfully!'),
            backgroundColor: Colors.green,
          ),
        );
      }

      // Refresh data
      await _fetchCaregiverProfile();
    } catch (e) {
      print('Error saving caregiver changes: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error updating details: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _handleSignOut() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Sign Out'),
        content: const Text('Are you sure you want to sign out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Sign Out', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      try {
        await FirebaseAuth.instance.signOut();
        if (mounted) {
          Navigator.of(context).pushNamedAndRemoveUntil(
            '/welcome',
            (route) => false,
          );
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('👋 Signed out successfully!'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Error signing out: $e'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_caregiverData == null) {
      return const Center(
        child: Text('Unable to load profile data'),
      );
    }

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header with profile picture and sign out button
            Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                CircleAvatar(
                  radius: 48,
                  backgroundColor: Colors.deepPurple.shade50,
                  child: const Icon(
                    Icons.person,
                    size: 48,
                    color: Colors.deepPurple,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _caregiverData!.fullName ?? 'Caregiver',
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w700,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        '${_caregiverData!.relationship} • Caregiver',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[700],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                OutlinedButton.icon(
                  onPressed: _handleSignOut,
                  icon: const Icon(Icons.logout, color: Colors.red, size: 20),
                  label: const Text('Sign Out', style: TextStyle(color: Colors.red)),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: Colors.red),
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Caregiver Details Card (Editable)
            _buildCaregiverCard(),

            const SizedBox(height: 16),

            if (_elderlyData != null) _buildElderCard(),
          ],
        ),
      ),
    );
  }

  Widget _buildCaregiverCard() {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 3,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.person_outline, color: Colors.deepPurple, size: 24),
                    SizedBox(width: 8),
                    Text(
                      'Caregiver Details',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: Colors.deepPurple,
                      ),
                    ),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.edit, color: Colors.deepPurple),
                  onPressed: _showEditCaregiverDialog,
                  tooltip: 'Edit Caregiver Details',
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 8),
            _buildDetailRow('Name', _caregiverData!.fullName ?? 'N/A'),
            const SizedBox(height: 12),
            _buildDetailRow('Email', _userProfile?['email'] ?? _caregiverData!.email),
            const SizedBox(height: 12),
            _buildDetailRow('Relationship', _caregiverData!.relationship),
            const SizedBox(height: 12),
            _buildDetailRow('Phone', _caregiverData!.phoneNumber ?? 'Not provided'),
          ],
        ),
      ),
    );
  }

  Widget _buildElderCard() {
    // Calculate BMI if height and weight are available
    String bmiText = 'N/A';
    String bmiCategory = '';
    if (_elderlyData!.height != null && 
        _elderlyData!.weight != null && 
        _elderlyData!.height! > 0) {
      // BMI = weight (kg) / (height (m))^2
      final heightInMeters = _elderlyData!.height! / 100; // Convert cm to m
      final bmi = _elderlyData!.weight! / (heightInMeters * heightInMeters);
      bmiText = bmi.toStringAsFixed(1);
      
      // Determine BMI category
      if (bmi < 18.5) {
        bmiCategory = ' (Underweight)';
      } else if (bmi < 25) {
        bmiCategory = ' (Normal)';
      } else if (bmi < 30) {
        bmiCategory = ' (Overweight)';
      } else {
        bmiCategory = ' (Obese)';
      }
    }

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 3,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.elderly, color: Colors.teal, size: 24),
                SizedBox(width: 8),
                Text(
                  'Elderly Details',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: Colors.teal,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 8),
            
            // Basic Information
            _buildDetailRow('Name', _elderlyData!.username),
            const SizedBox(height: 12),
            _buildDetailRow('Email', _elderlyUserProfile?['email'] ?? 'Not available'),
            const SizedBox(height: 12),
            _buildDetailRow('Phone', _elderlyData!.phoneNumber),
            const SizedBox(height: 12),
            _buildDetailRow('Sex', _elderlyData!.sex),
            const SizedBox(height: 12),
            _buildDetailRow('Age', _elderlyData!.age?.toString() ?? 'Not provided'),
            
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 8),
            
            // Physical Information
            const Text(
              'Physical Information',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: Colors.teal,
              ),
            ),
            const SizedBox(height: 12),
            _buildDetailRow('Height', _elderlyData!.height != null ? '${_elderlyData!.height!.toStringAsFixed(1)} cm' : 'Not provided'),
            const SizedBox(height: 12),
            _buildDetailRow('Weight', _elderlyData!.weight != null ? '${_elderlyData!.weight!.toStringAsFixed(1)} kg' : 'Not provided'),
            const SizedBox(height: 12),
            _buildDetailRow('BMI', bmiText + bmiCategory),
            
            // Medical Information
            if (_elderlyData!.medicalInfo != null) ...[
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 8),
              const Text(
                'Medical Information',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.teal,
                ),
              ),
              const SizedBox(height: 12),
              _buildMedicalInfoRow(
                'Conditions',
                _elderlyData!.medicalInfo!.conditions.isEmpty
                    ? 'None'
                    : _elderlyData!.medicalInfo!.conditions.join(', '),
              ),
              const SizedBox(height: 12),
              _buildMedicalInfoRow(
                'Medications',
                _elderlyData!.medicalInfo!.medications.isEmpty
                    ? 'None'
                    : _elderlyData!.medicalInfo!.medications.join(', '),
              ),
              const SizedBox(height: 12),
              _buildMedicalInfoRow(
                'Allergies',
                _elderlyData!.medicalInfo!.allergies.isEmpty
                    ? 'None'
                    : _elderlyData!.medicalInfo!.allergies.join(', '),
              ),
            ],
            
            // Emergency Contact
            if (_elderlyData!.emergencyContact != null) ...[
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 8),
              const Text(
                'Emergency Contact',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.teal,
                ),
              ),
              const SizedBox(height: 12),
              _buildDetailRow('Name', _elderlyData!.emergencyContact!.name),
              const SizedBox(height: 12),
              _buildDetailRow('Phone', _elderlyData!.emergencyContact!.phone),
              const SizedBox(height: 12),
              _buildDetailRow('Relationship', _elderlyData!.emergencyContact!.relationship),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 120,
          child: Text(
            label,
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              color: Colors.black87,
              fontSize: 15,
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              color: Colors.black87,
              fontSize: 15,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildMedicalInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 120,
          child: Text(
            label,
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              color: Colors.black87,
              fontSize: 15,
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              color: Colors.black87,
              fontSize: 15,
            ),
            softWrap: true,
          ),
        ),
      ],
    );
  }
}

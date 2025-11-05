import 'dart:math';

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:intl/intl.dart';
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
  Map<String, dynamic>? _userProfile;

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

      // Fetch fullName from users collection
      final userDoc = await _firestore.collection('users').doc(userId).get();
      if (userDoc.exists) {
        _userProfile = userDoc.data();
        print('User profile data: $_userProfile');
      } else {
        print('No user document found, creating empty profile map');
        _userProfile = {};
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

  Future<void> _showEditDialog() async {
    if (_caregiverData == null) return;

    final fullNameController = TextEditingController(text: _userProfile?['fullName'] ?? '');
    final relationshipController = TextEditingController(text: _caregiverData!.relationship);

    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Edit Profile'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: fullNameController,
                decoration: const InputDecoration(
                  labelText: 'Full Name',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: relationshipController,
                decoration: const InputDecoration(
                  labelText: 'Relationship',
                  border: OutlineInputBorder(),
                  hintText: 'e.g., Spouse, Child, Professional Caregiver',
                ),
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
      await _saveProfileChanges(
        fullNameController.text,
        relationshipController.text,
      );
    }

    fullNameController.dispose();
    relationshipController.dispose();
  }

  Future<void> _saveProfileChanges(String fullName, String relationship) async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) {
        print('Error: User ID is null');
        return;
      }

      print('Saving profile changes for user: $userId');
      print('Full Name: $fullName');
      print('Relationship: $relationship');

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

      // Update users collection (use set with merge to create field if it doesn't exist)
      print('Updating users collection...');
      await _firestore.collection('users').doc(userId).set({
        'fullName': fullName.trim(),
      }, SetOptions(merge: true));
      print('Users collection updated successfully');

      // Update caregivers collection
      print('Updating caregivers collection...');
      await _firestore.collection('caregivers').doc(userId).update({
        'relationship': relationship.trim(),
      });
      print('Caregivers collection updated successfully');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✅ Profile updated successfully!'),
            backgroundColor: Colors.green,
          ),
        );
      }

      // Refresh data
      print('Refreshing profile data...');
      await _fetchCaregiverProfile();
      print('Profile refresh complete');
    } catch (e) {
      print('Error saving profile changes: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error updating profile: $e'),
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

    final created = DateFormat.yMMMMd().add_jm().format(_caregiverData!.createdAt.toLocal());

    return SafeArea(
      child: LayoutBuilder(builder: (context, constraints) {
        final isNarrow = constraints.maxWidth < 420;
        final maxContentWidth = min(900.0, constraints.maxWidth);
        final labelWidth = min(160.0, maxContentWidth * 0.36);

        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Center(
            child: ConstrainedBox(
              constraints: BoxConstraints(maxWidth: maxContentWidth),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Header
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      CircleAvatar(
                        radius: isNarrow ? 36 : 48,
                        backgroundColor: Colors.deepPurple.shade50,
                        child: Icon(
                          Icons.person,
                          size: isNarrow ? 36 : 48,
                          color: Colors.deepPurple,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _userProfile?['fullName'] ?? _caregiverData!.email,
                              style: TextStyle(
                                fontSize: isNarrow ? 16 : 20,
                                fontWeight: FontWeight.w700,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${_caregiverData!.relationship} • Caregiver',
                              style: TextStyle(
                                fontSize: isNarrow ? 12 : 14,
                                color: Colors.grey[700],
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      Column(
                        children: [
                          ElevatedButton.icon(
                            onPressed: _showEditDialog,
                            icon: const Icon(Icons.edit),
                            label: const Text('Edit'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                          const SizedBox(height: 8),
                          OutlinedButton.icon(
                            onPressed: _handleSignOut,
                            icon: const Icon(Icons.logout, color: Colors.red),
                            label: const Text('Sign Out', style: TextStyle(color: Colors.red)),
                            style: OutlinedButton.styleFrom(
                              side: const BorderSide(color: Colors.red),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),

                  const SizedBox(height: 20),

                  // Card with details
                  Card(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 2,
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Caregiver Details',
                            style: TextStyle(fontSize: isNarrow ? 16 : 18, fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 12),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Caregiver ID', value: _caregiverData!.id),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'User ID', value: _caregiverData!.userId),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Full Name', value: _userProfile?['fullName'] ?? 'N/A'),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Email', value: _caregiverData!.email),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Relationship', value: _caregiverData!.relationship),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Created', value: created),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Elderly ID', value: _caregiverData!.elderlyId ?? 'Not assigned'),
                          if (_elderlyData != null) ...[
                            const Divider(),
                            _buildRow(context, labelWidth: labelWidth, label: 'Elderly Name', value: _elderlyData!.username),
                            const Divider(),
                            _buildRow(context, labelWidth: labelWidth, label: 'Elderly Phone', value: _elderlyData!.phoneNumber),
                          ],
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Actions / quick info
                  
                ],
              ),
            ),
          ),
        );
      }),
    );
  }

  Widget _buildRow(BuildContext context, {required double labelWidth, required String label, required String value}) {
    final isNarrow = MediaQuery.of(context).size.width < 420;
    return Padding(
      padding: EdgeInsets.symmetric(vertical: isNarrow ? 6.0 : 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: labelWidth,
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.black87),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(color: Colors.black87),
            ),
          ),
        ],
      ),
    );
  }
}
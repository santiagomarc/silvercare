import 'dart:math';

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:intl/intl.dart';


class Caregiver {
  final String id; 
  final String userId; 
  final String email;
  final String? elderlyId; 
  final String relationship; 
  final DateTime createdAt;


  final String? fullName;
  final String? elderlyName;

  Caregiver({
    required this.id,
    required this.userId,
    required this.email,
    this.elderlyId,
    required this.relationship,
    required this.createdAt,
    this.fullName,
    this.elderlyName,
  });
}
class CaregiverProfile extends StatelessWidget {
  final Caregiver? caregiver;

  const CaregiverProfile({super.key, this.caregiver});

  // Mock data
  Caregiver get _mock => Caregiver(
        id: 'cg_12345',
        userId: 'user_XYZ789',
        fullName: 'Juan Dela Cruz',
        email: 'caregiver@example.com',
        elderlyId: 'test-user-123',
        elderlyName: 'Lola Granny',
        relationship: 'Spouse',
        createdAt: DateTime.now().subtract(const Duration(days: 365)),
      );

  @override
  Widget build(BuildContext context) {
    final c = caregiver ?? _mock;
    final created = DateFormat.yMMMMd().add_jm().format(c.createdAt.toLocal());

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
                              c.fullName ?? c.email,
                              style: TextStyle(
                                fontSize: isNarrow ? 16 : 20,
                                fontWeight: FontWeight.w700,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${c.relationship} • ${c.userId}',
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
                            onPressed: () {
                              // Placeholder action
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Edit profile (mock)')),
                              );
                            },
                            icon: const Icon(Icons.edit),
                            label: const Text('Edit'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                          const SizedBox(height: 8),
                          // Mock Logout button
                          OutlinedButton.icon(
                            onPressed: () async {
                              final confirmed = await showDialog<bool>(
                                context: context,
                                builder: (ctx) => AlertDialog(
                                  title: const Text('Sign Out'),
                                  content: const Text('Are you sure you want to sign out?'),
                                  actions: [
                                    TextButton(onPressed: () => Navigator.of(ctx).pop(false), child: const Text('Cancel')),
                                    TextButton(
                                      onPressed: () => Navigator.of(ctx).pop(true), 
                                      child: const Text('Sign Out', style: TextStyle(color: Colors.red)),
                                    ),
                                  ],
                                ),
                              );
                              if (confirmed == true && context.mounted) {
                                try {
                                  await FirebaseAuth.instance.signOut();
                                  if (context.mounted) {
                                    // Navigate to welcome screen and clear all routes
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
                                  if (context.mounted) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text('Error signing out: ${e.toString()}'), 
                                        backgroundColor: Colors.red,
                                      ),
                                    );
                                  }
                                }
                              }
                            },
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
                          _buildRow(context, labelWidth: labelWidth, label: 'ID', value: c.id),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'User ID', value: c.userId),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Full Name', value: c.fullName ?? 'N/A'),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Email', value: c.email),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Elderly ID', value: c.elderlyId ?? 'Not assigned'),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Elderly Name', value: c.elderlyName ?? 'N/A'),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Relationship', value: c.relationship),
                          const Divider(),
                          _buildRow(context, labelWidth: labelWidth, label: 'Created', value: created),
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
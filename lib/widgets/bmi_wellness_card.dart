import 'package:flutter/material.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../models/elderly_model.dart';
import '../services/user_service.dart';

class BMIWellnessCard extends StatefulWidget {
  const BMIWellnessCard({Key? key}) : super(key: key);

  @override
  State<BMIWellnessCard> createState() => _BMIWellnessCardState();
}

class _BMIWellnessCardState extends State<BMIWellnessCard> {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  
  bool _isLoading = true;
  double? _bmi;
  double? _height;
  double? _weight;
  int? _age;
  String? _sex;

  @override
  void initState() {
    super.initState();
    _loadElderlyData();
  }

  Future<void> _loadElderlyData() async {
    try {
      final userId = _auth.currentUser?.uid;
      if (userId == null) {
        setState(() => _isLoading = false);
        return;
      }

      // Get user profile to determine user type and elderly ID
      final profile = await UserService.getUserProfile(userId);
      if (profile == null) {
        setState(() => _isLoading = false);
        return;
      }

      String? elderlyId;
      final userType = profile['userType'] as String?;

      if (userType == 'elderly') {
        elderlyId = userId;
      } else if (userType == 'caregiver') {
        elderlyId = profile['elderlyId'] as String?;
      }

      if (elderlyId == null || elderlyId.isEmpty) {
        setState(() => _isLoading = false);
        return;
      }

      // Fetch elderly data
      final elderlyDoc = await _firestore.collection('elderly').doc(elderlyId).get();
      
      if (elderlyDoc.exists) {
        final elderlyData = ElderlyModel.fromDoc(elderlyDoc);
        
        setState(() {
          _height = elderlyData.height;
          _weight = elderlyData.weight;
          _age = elderlyData.age;
          _sex = elderlyData.sex;
          
          if (_height != null && _weight != null && _height! > 0) {
            final heightInMeters = _height! / 100;
            _bmi = _weight! / (heightInMeters * heightInMeters);
          }
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (e) {
      print('Error loading elderly data: $e');
      setState(() => _isLoading = false);
    }
  }

  String _getBMICategory(double bmi) {
    if (bmi < 18.5) return 'Underweight';
    if (bmi < 25) return 'Normal';
    if (bmi < 30) return 'Overweight';
    return 'Obese';
  }

  Color _getBMIColor(double bmi) {
    if (bmi < 18.5) return Colors.blue;
    if (bmi < 25) return Colors.green;
    if (bmi < 30) return Colors.orange;
    return Colors.red;
  }

  String _getBMIAdvice(double bmi) {
    if (bmi < 18.5) {
      return 'Consider increasing caloric intake with nutrient-dense foods.';
    } else if (bmi < 25) {
      return 'Maintain your healthy weight with balanced diet and exercise.';
    } else if (bmi < 30) {
      return 'Gentle exercise and portion control can help achieve ideal weight.';
    } else {
      return 'Consult with healthcare provider for personalized weight management.';
    }
  }

  double _getIdealWeightMin() {
    if (_height == null) return 0;
    final heightInMeters = _height! / 100;
    return 18.5 * (heightInMeters * heightInMeters);
  }

  double _getIdealWeightMax() {
    if (_height == null) return 0;
    final heightInMeters = _height! / 100;
    return 24.9 * (heightInMeters * heightInMeters);
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Card(
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(16))),
        child: Padding(
          padding: EdgeInsets.all(40.0),
          child: Center(child: CircularProgressIndicator()),
        ),
      );
    }

    if (_bmi == null) {
      return Card(
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            children: [
              Icon(Icons.monitor_weight_outlined, size: 48, color: Colors.grey.shade400),
              const SizedBox(height: 12),
              Text(
                'BMI Data Not Available',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Complete profile with height and weight to track BMI',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      );
    }

    final category = _getBMICategory(_bmi!);
    final color = _getBMIColor(_bmi!);
    final advice = _getBMIAdvice(_bmi!);
    final idealMin = _getIdealWeightMin();
    final idealMax = _getIdealWeightMax();

    return Card(
      elevation: 3,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              color.withOpacity(0.08),
              Colors.white,
            ],
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.monitor_weight, color: color, size: 24),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Body Mass Index',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        Text(
                          'Wellness Indicator',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: color, width: 1.5),
                    ),
                    child: Text(
                      category,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: color,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // BMI Value Display
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    _bmi!.toStringAsFixed(1),
                    style: TextStyle(
                      fontSize: 56,
                      fontWeight: FontWeight.bold,
                      color: color,
                      height: 1,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      'BMI',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // BMI Range Indicator
              _buildBMIRangeIndicator(_bmi!),
              const SizedBox(height: 20),

              // Current Stats
              Row(
                children: [
                  Expanded(
                    child: _buildStatBox(
                      'Weight',
                      '${_weight!.toStringAsFixed(1)} kg',
                      Icons.fitness_center,
                      Colors.blue.shade700,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildStatBox(
                      'Height',
                      '${_height!.toStringAsFixed(0)} cm',
                      Icons.height,
                      Colors.purple.shade700,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              const Divider(),
              const SizedBox(height: 12),

              // Ideal Weight Range
              Row(
                children: [
                  Icon(Icons.track_changes, size: 16, color: Colors.green.shade700),
                  const SizedBox(width: 8),
                  Text(
                    'Ideal Weight Range',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey.shade700,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                '${idealMin.toStringAsFixed(1)} - ${idealMax.toStringAsFixed(1)} kg',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.green.shade700,
                ),
              ),
              const SizedBox(height: 12),

              // Advice
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: color.withOpacity(0.3)),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, size: 18, color: color),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        advice,
                        style: TextStyle(
                          fontSize: 12,
                          height: 1.4,
                          color: Colors.grey.shade800,
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

  Widget _buildBMIRangeIndicator(double bmi) {
    return Column(
      children: [
        Row(
          children: [
            _buildRangeSegment('Under', Colors.blue, bmi < 18.5),
            _buildRangeSegment('Normal', Colors.green, bmi >= 18.5 && bmi < 25),
            _buildRangeSegment('Over', Colors.orange, bmi >= 25 && bmi < 30),
            _buildRangeSegment('Obese', Colors.red, bmi >= 30),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('<18.5', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
            Text('18.5-25', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
            Text('25-30', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
            Text('>30', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
          ],
        ),
      ],
    );
  }

  Widget _buildRangeSegment(String label, Color color, bool isActive) {
    return Expanded(
      child: Container(
        height: 32,
        margin: const EdgeInsets.symmetric(horizontal: 2),
        decoration: BoxDecoration(
          color: isActive ? color : color.withOpacity(0.2),
          borderRadius: BorderRadius.circular(4),
          border: isActive ? Border.all(color: color, width: 2) : null,
        ),
        child: Center(
          child: Text(
            label,
            style: TextStyle(
              fontSize: 10,
              fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
              color: isActive ? Colors.white : color,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatBox(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.grey.shade600,
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

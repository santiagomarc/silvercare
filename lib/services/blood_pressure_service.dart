import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/health_data_model.dart';

class BloodPressureService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static String? _mockUserId;

 //temporary mock user
  static void setMockUser(String id) {
    _mockUserId = id;
  }

  
  static Future<void> saveBloodPressureData({
    required double systolic,
    required double diastolic,
    required DateTime measuredAt,
    String source = 'manual',
  }) async {
    
    final userId = _mockUserId;
    if (userId == null) {
      throw Exception(
        'User not authenticated. Please sign in first or set a mock user with BloodPressureService.setMockUser("<id>").',
      );
    }

    
    final bpData = HealthDataModel(
      id: '', 
      elderlyId: userId,
      type: 'blood_pressure',
      measuredAt: measuredAt,
      createdAt: DateTime.now(),
      systolic: systolic,
      diastolic: diastolic,
    );

    
    await _firestore.collection('health_data').add(bpData.toMap());
  }

  
  static Future<List<HealthDataModel>> getBloodPressureData({int days = 7}) async {
    final userId = _mockUserId;
    if (userId == null) {
      throw Exception(
        'User not authenticated. Please sign in first or set a mock user with BloodPressureService.setMockUser("<id>").',
      );
    }

    final now = DateTime.now();
    final startDate = now.subtract(Duration(days: days));

    final querySnapshot = await _firestore
        .collection('health_data')
        .where('elderlyId', isEqualTo: userId)
        .where('type', isEqualTo: 'blood_pressure')
        .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
        .orderBy('measuredAt', descending: true)
        .get();

    return querySnapshot.docs.map((doc) => HealthDataModel.fromDoc(doc)).toList();
  }
}

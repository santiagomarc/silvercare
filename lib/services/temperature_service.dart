import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/health_data_model.dart';

class TemperatureService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static String? _mockUserId;

 //temporary mock user
  static void setMockUser(String id) {
    _mockUserId = id;
  }

  
  static Future<void> saveTemperatureData({
    required double value,
    required DateTime measuredAt,
    String? unit,
    String source = 'manual',
  }) async {
    
    final userId = _mockUserId;
    if (userId == null) {
      throw Exception(
        'User not authenticated. Please sign in first or set a mock user with TemperatureService.setMockUser("<id>").',
      );
    }

    
    final temperatureData = HealthDataModel(
      id: '', 
      elderlyId: userId,
      type: 'temperature',
      measuredAt: measuredAt,
      createdAt: DateTime.now(),
      value: value,
      unit: unit
    );

    
    await _firestore.collection('health_data').add(temperatureData.toMap());
  }

  
  static Future<List<HealthDataModel>> getTemperatureData({int days = 7}) async {
    final userId = _mockUserId;
    if (userId == null) {
      throw Exception(
        'User not authenticated. Please sign in first or set a mock user with TemperatureService.setMockUser("<id>").',
      );
    }

    final now = DateTime.now();
    final startDate = now.subtract(Duration(days: days));

    final querySnapshot = await _firestore
        .collection('health_data')
        .where('elderlyId', isEqualTo: userId)
        .where('type', isEqualTo: 'temperature')
        .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
        .orderBy('measuredAt', descending: true)
        .get();

    return querySnapshot.docs.map((doc) => HealthDataModel.fromDoc(doc)).toList();
  }
}

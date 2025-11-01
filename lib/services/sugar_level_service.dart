import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/health_data_model.dart';

class SugarLevelService {
  static final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  static String? _mockUserId;

 //temporary mock user
  static void setMockUser(String id) {
    _mockUserId = id;
  }

  
  static Future<void> saveSugarLevelData({
    required double value,
    required DateTime measuredAt,
    String source = 'manual',
  }) async {
    
    final userId = _mockUserId;
    if (userId == null) {
      throw Exception(
        'User not authenticated. Please sign in first or set a mock user with SugarLevelService.setMockUser("<id>").',
      );
    }

    
    final sugarData = HealthDataModel(
      id: '', 
      elderlyId: userId,
      type: 'sugar_level',
      measuredAt: measuredAt,
      createdAt: DateTime.now(),
      value: value,
    );

    
    await _firestore.collection('health_data').add(sugarData.toMap());
  }

  
  static Future<List<HealthDataModel>> getSugarLevelData({int days = 7}) async {
    final userId = _mockUserId;
    if (userId == null) {
      throw Exception(
        'User not authenticated. Please sign in first or set a mock user with SugarLevelService.setMockUser("<id>").',
      );
    }

    final now = DateTime.now();
    final startDate = now.subtract(Duration(days: days));

    final querySnapshot = await _firestore
        .collection('health_data')
        .where('elderlyId', isEqualTo: userId)
        .where('type', isEqualTo: 'sugar_level')
        .where('measuredAt', isGreaterThanOrEqualTo: Timestamp.fromDate(startDate))
        .orderBy('measuredAt', descending: true)
        .get();

    return querySnapshot.docs.map((doc) => HealthDataModel.fromDoc(doc)).toList();
  }
}

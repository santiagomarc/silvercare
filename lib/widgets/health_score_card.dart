import 'package:flutter/material.dart';
import 'dart:math' as math;

class HealthScoreCard extends StatelessWidget {
  final Map<String, dynamic> bpData;
  final Map<String, dynamic> sugarData;
  final Map<String, dynamic> tempData;
  final Map<String, dynamic> hrData;

  const HealthScoreCard({
    required this.bpData,
    required this.sugarData,
    required this.tempData,
    required this.hrData,
    Key? key,
  }) : super(key: key);

  int _calculateHealthScore() {
    int score = 100; // Start with perfect score
    int factors = 0;

    // Blood Pressure scoring (0-25 points deduction)
    if (bpData['count'] > 0 && bpData['status'] != 'No Data') {
      factors++;
      if (bpData['status'] == 'High') {
        score -= 25;
      } else if (bpData['status'] == 'Low') {
        score -= 15;
      } else {
        score -= 0; // Normal
      }
    }

    // Sugar Level scoring (0-25 points deduction)
    if (sugarData['count'] > 0 && sugarData['status'] != 'No Data') {
      factors++;
      if (sugarData['status'] == 'High') {
        score -= 25;
      } else if (sugarData['status'] == 'Low') {
        score -= 15;
      } else {
        score -= 0; // Normal
      }
    }

    // Temperature scoring (0-25 points deduction)
    if (tempData['count'] > 0 && tempData['status'] != 'No Data') {
      factors++;
      if (tempData['status'] == 'Fever') {
        score -= 25;
      } else if (tempData['status'] == 'Low') {
        score -= 15;
      } else {
        score -= 0; // Normal
      }
    }

    // Heart Rate scoring (0-25 points deduction)
    if (hrData['count'] > 0 && hrData['status'] != 'No Data') {
      factors++;
      if (hrData['status'] == 'High') {
        score -= 20;
      } else if (hrData['status'] == 'Low') {
        score -= 15;
      } else {
        score -= 0; // Normal
      }
    }

    // If no data available, return 50 (neutral)
    if (factors == 0) return 50;

    return score.clamp(0, 100);
  }

  String _getScoreCategory(int score) {
    if (score >= 85) return 'Excellent';
    if (score >= 70) return 'Good';
    if (score >= 55) return 'Fair';
    if (score >= 40) return 'Poor';
    return 'Critical';
  }

  Color _getScoreColor(int score) {
    if (score >= 85) return Colors.green;
    if (score >= 70) return Colors.lightGreen;
    if (score >= 55) return Colors.orange;
    if (score >= 40) return Colors.deepOrange;
    return Colors.red;
  }

  IconData _getScoreIcon(int score) {
    if (score >= 85) return Icons.sentiment_very_satisfied;
    if (score >= 70) return Icons.sentiment_satisfied;
    if (score >= 55) return Icons.sentiment_neutral;
    if (score >= 40) return Icons.sentiment_dissatisfied;
    return Icons.sentiment_very_dissatisfied;
  }

  List<String> _getRecommendations(int score) {
    List<String> recommendations = [];

    if (bpData['status'] == 'High') {
      recommendations.add('🩸 Reduce sodium intake and monitor BP regularly');
    }
    if (sugarData['status'] == 'High') {
      recommendations.add('🍬 Watch carbohydrate intake and stay active');
    }
    if (tempData['status'] == 'Fever') {
      recommendations.add('🌡️ Stay hydrated and rest. Consult doctor if persists');
    }
    if (hrData['status'] == 'High') {
      recommendations.add('❤️ Practice relaxation techniques and avoid stress');
    }

    if (recommendations.isEmpty) {
      recommendations.add('✅ All vitals are normal. Keep up the good work!');
      recommendations.add('💪 Maintain regular exercise and healthy diet');
    }

    return recommendations;
  }

  @override
  Widget build(BuildContext context) {
    final score = _calculateHealthScore();
    final category = _getScoreCategory(score);
    final scoreColor = _getScoreColor(score);
    final icon = _getScoreIcon(score);
    final recommendations = _getRecommendations(score);

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              scoreColor.withOpacity(0.1),
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
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: scoreColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.favorite, color: scoreColor, size: 24),
                  ),
                  const SizedBox(width: 12),
                  const Text(
                    'Health Score',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: scoreColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: scoreColor, width: 1.5),
                    ),
                    child: Text(
                      category,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: scoreColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Score Circle
              Center(
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 160,
                      height: 160,
                      child: CustomPaint(
                        painter: _CircularScorePainter(
                          score: score,
                          color: scoreColor,
                        ),
                      ),
                    ),
                    Column(
                      children: [
                        Icon(icon, size: 40, color: scoreColor),
                        const SizedBox(height: 8),
                        Text(
                          '$score',
                          style: TextStyle(
                            fontSize: 48,
                            fontWeight: FontWeight.bold,
                            color: scoreColor,
                          ),
                        ),
                        Text(
                          'out of 100',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Breakdown
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildMetricChip('BP', bpData['status'], bpData['statusColor']),
                  _buildMetricChip('Sugar', sugarData['status'], sugarData['statusColor']),
                  _buildMetricChip('Temp', tempData['status'], tempData['statusColor']),
                  _buildMetricChip('HR', hrData['status'], hrData['statusColor']),
                ],
              ),
              const SizedBox(height: 20),

              const Divider(),
              const SizedBox(height: 12),

              // Recommendations
              const Row(
                children: [
                  Icon(Icons.lightbulb_outline, size: 18, color: Colors.amber),
                  SizedBox(width: 8),
                  Text(
                    'Personalized Recommendations',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              ...recommendations.map((rec) => Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          margin: const EdgeInsets.only(top: 4),
                          width: 4,
                          height: 4,
                          decoration: BoxDecoration(
                            color: scoreColor,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            rec,
                            style: TextStyle(
                              fontSize: 12,
                              height: 1.4,
                              color: Colors.grey.shade700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  )),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMetricChip(String label, String status, Color color) {
    return Column(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
        ),
        Text(
          status,
          style: TextStyle(
            fontSize: 9,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _CircularScorePainter extends CustomPainter {
  final int score;
  final Color color;

  _CircularScorePainter({required this.score, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;

    // Background circle
    final bgPaint = Paint()
      ..color = Colors.grey.shade200
      ..style = PaintingStyle.stroke
      ..strokeWidth = 12
      ..strokeCap = StrokeCap.round;

    canvas.drawCircle(center, radius - 6, bgPaint);

    // Score arc
    final scorePaint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 12
      ..strokeCap = StrokeCap.round;

    final sweepAngle = (score / 100) * 2 * math.pi;
    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius - 6),
      -math.pi / 2,
      sweepAngle,
      false,
      scorePaint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}

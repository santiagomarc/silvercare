import 'package:flutter/material.dart';
import 'package:intl/intl.dart'; // make sure intl: ^0.19.0 is in pubspec.yaml

class TemperatureRecord {
  final double value;
  final String unit;
  final String status;
  final DateTime dateTime;

  TemperatureRecord({
    required this.value,
    required this.unit,
    required this.status,
    required this.dateTime,
  });
}

class TemperatureScreen extends StatefulWidget {
  const TemperatureScreen({super.key});

  @override
  State<TemperatureScreen> createState() => _TemperatureScreenState();
}

class _TemperatureScreenState extends State<TemperatureScreen> {
  final TextEditingController tempController = TextEditingController();
  DateTime? selectedDate;
  TimeOfDay? selectedTime;
  String selectedUnit = 'Celsius';
  List<TemperatureRecord> records = [];

  Future<void> _pickDate(BuildContext context) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked != null) setState(() => selectedDate = picked);
  }

  Future<void> _pickTime(BuildContext context) async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked != null) setState(() => selectedTime = picked);
  }

  String _evaluateTemperature(double temp, String unit) {
    if (unit == 'Celsius') {
      if (temp < 36.0) return 'Low';
      if (temp > 37.5) return 'High';
      return 'Normal';
    } else {
      if (temp < 96.8) return 'Low';
      if (temp > 99.5) return 'High';
      return 'Normal';
    }
  }

  void _saveRecord() {
    if (tempController.text.isEmpty || selectedDate == null || selectedTime == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please fill all fields')),
      );
      return;
    }

    final temp = double.tryParse(tempController.text);
    if (temp == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Enter a valid temperature')),
      );
      return;
    }

    final status = _evaluateTemperature(temp, selectedUnit);

    final dateTime = DateTime(
      selectedDate!.year,
      selectedDate!.month,
      selectedDate!.day,
      selectedTime!.hour,
      selectedTime!.minute,
    );

    setState(() {
      records.insert(
        0,
        TemperatureRecord(
          value: temp,
          unit: selectedUnit,
          status: status,
          dateTime: dateTime,
        ),
      );
    });

    tempController.clear();
    selectedDate = null;
    selectedTime = null;
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'Low':
        return Colors.blue;
      case 'Normal':
        return Colors.green;
      case 'High':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFB0B0B0),
      body: Center(
        child: Container(
          width: 390,
          height: 844,
          clipBehavior: Clip.antiAlias,
          decoration: BoxDecoration(
            color: const Color(0xFFDEDEDE),
            borderRadius: BorderRadius.circular(20),
            boxShadow: const [
              BoxShadow(
                color: Colors.black38,
                blurRadius: 10,
                offset: Offset(0, 5),
              ),
            ],
          ),
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 40),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // HEADER
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 55,
                          height: 55,
                          decoration: const BoxDecoration(
                            image: DecorationImage(
                              image: NetworkImage("https://via.placeholder.com/55x55.png"),
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        const Text(
                          'SILVER CARE',
                          style: TextStyle(
                            fontFamily: 'Montserrat',
                            fontWeight: FontWeight.w800,
                            fontSize: 20,
                            color: Colors.black,
                            shadows: [
                              Shadow(offset: Offset(0, 4), blurRadius: 4, color: Colors.black26),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const Icon(Icons.settings, size: 28, color: Colors.black),
                  ],
                ),

                const SizedBox(height: 20),

                Row(
                  children: [
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: const Icon(Icons.arrow_back, size: 28),
                    ),
                    const SizedBox(width: 10),
                    const Text(
                      'TEMPERATURE',
                      style: TextStyle(
                        fontFamily: 'Montserrat',
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                        shadows: [
                          Shadow(offset: Offset(0, 4), blurRadius: 4, color: Colors.black26),
                        ],
                      ),
                    ),
                    const Spacer(),
                    const CircleAvatar(
                      radius: 20,
                      backgroundColor: Colors.white,
                      child: Icon(Icons.thermostat, color: Colors.red),
                    ),
                  ],
                ),

                const SizedBox(height: 20),

                // INPUT PANEL
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 30),
                  decoration: BoxDecoration(
                    color: const Color(0xFF9AD4E5),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text("TEMPERATURE VALUE",
                          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              height: 50,
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(30),
                                boxShadow: const [
                                  BoxShadow(color: Colors.black26, offset: Offset(0, 4), blurRadius: 4),
                                ],
                              ),
                              child: TextField(
                                controller: tempController,
                                textAlign: TextAlign.center,
                                keyboardType: TextInputType.number,
                                decoration: const InputDecoration(
                                  border: InputBorder.none,
                                  hintText: "Enter value",
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          DropdownButton<String>(
                            value: selectedUnit,
                            items: ['Celsius', 'Fahrenheit']
                                .map((e) => DropdownMenuItem(value: e, child: Text(e)))
                                .toList(),
                            onChanged: (val) => setState(() => selectedUnit = val!),
                          ),
                        ],
                      ),
                      const SizedBox(height: 25),

                      // DATE & TIME
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30),
                                ),
                                elevation: 6,
                              ),
                              onPressed: () => _pickDate(context),
                              icon: const Icon(Icons.calendar_today, color: Colors.black),
                              label: Text(
                                selectedDate == null
                                    ? "DATE"
                                    : "${selectedDate!.day}/${selectedDate!.month}/${selectedDate!.year}",
                                style: const TextStyle(color: Colors.black),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30),
                                ),
                                elevation: 6,
                              ),
                              onPressed: () => _pickTime(context),
                              icon: const Icon(Icons.access_time, color: Colors.black),
                              label: Text(
                                selectedTime == null ? "TIME" : selectedTime!.format(context),
                                style: const TextStyle(color: Colors.black),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 25),

                      Center(
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFD9D9D9),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(30),
                            ),
                            elevation: 8,
                            padding: const EdgeInsets.symmetric(horizontal: 80, vertical: 15),
                          ),
                          onPressed: _saveRecord,
                          child: const Text(
                            "SAVE",
                            style: TextStyle(
                              fontFamily: 'Montserrat',
                              fontWeight: FontWeight.w900,
                              fontSize: 18,
                              color: Colors.black,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 40),
                const Text(
                  "RECORDS:",
                  style: TextStyle(
                    fontFamily: 'Montserrat',
                    fontWeight: FontWeight.w900,
                    fontSize: 26,
                  ),
                ),
                const SizedBox(height: 15),

                // RECORD LIST UI UPDATED HERE 👇
                Column(
                  children: records.map((record) {
                    final formattedDate =
                        DateFormat('EEE, dd MMM yyyy • hh:mm a').format(record.dateTime);
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(25),
                        boxShadow: const [
                          BoxShadow(
                            color: Colors.black26,
                            blurRadius: 6,
                            offset: Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            formattedDate,
                            style: const TextStyle(
                              fontSize: 14,
                              color: Colors.black54,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Icon(Icons.thermostat, color: _statusColor(record.status), size: 24),
                                  const SizedBox(width: 8),
                                  Text(
                                    "${record.value.toStringAsFixed(1)}°${record.unit == 'Celsius' ? 'C' : 'F'}",
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 18,
                                    ),
                                  ),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                                decoration: BoxDecoration(
                                  color: _statusColor(record.status).withOpacity(0.15),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(color: _statusColor(record.status), width: 1.5),
                                ),
                                child: Text(
                                  record.status,
                                  style: TextStyle(
                                    color: _statusColor(record.status),
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

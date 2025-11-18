import 'package:flutter/material.dart';
import 'dart:async';

class MemoryMatchScreen extends StatefulWidget {
  const MemoryMatchScreen({super.key});

  @override
  State<MemoryMatchScreen> createState() => _MemoryMatchScreenState();
}

class _MemoryMatchScreenState extends State<MemoryMatchScreen> {
  static const Color _themeColor = Color(0xFF283593);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _textPrimary = Color(0xFF2D3748);

  // Game State
  List<MemoryCard> _cards = [];
  int _score = 0;
  bool _isProcessing = false;
  int? _selectedCardIndex;
  int _currentLevel = 0; 

  // --- DEFINING THE 3 ICON SETS ---
  final List<List<GameItem>> _iconSets = [
    // Set 1: Classic Objects
    [
      GameItem(Icons.wb_sunny_rounded, Colors.orange),     // Sun
      GameItem(Icons.favorite_rounded, Colors.red),        // Heart
      GameItem(Icons.home_rounded, Colors.blue),           // Home
      GameItem(Icons.local_florist_rounded, Colors.pink),  // Flower
      GameItem(Icons.music_note_rounded, Colors.purple),   // Music
      GameItem(Icons.pets_rounded, Colors.brown),          // Pet
    ],
    // Set 2: Nature & Weather
    [
      GameItem(Icons.park_rounded, Colors.green),          // Tree
      GameItem(Icons.star_rounded, Colors.amber),          // Star
      GameItem(Icons.nightlight_round, Colors.indigo),     // Moon
      GameItem(Icons.water_drop_rounded, Colors.cyan),     // Water
      GameItem(Icons.local_fire_department_rounded, Colors.deepOrange), // Fire
      GameItem(Icons.cloud_rounded, Colors.lightBlue),     // Cloud
    ],
    // Set 3: Food & Leisure
    [
      GameItem(Icons.cake_rounded, Colors.pinkAccent),     // Cake
      GameItem(Icons.local_cafe_rounded, Colors.brown),    // Coffee
      GameItem(Icons.directions_car_rounded, Colors.blueGrey), // Car
      GameItem(Icons.local_pizza_rounded, Colors.orange),  // Pizza
      GameItem(Icons.shopping_bag_rounded, Colors.teal),   // Shopping
      GameItem(Icons.flight_rounded, Colors.blueAccent),   // Travel
    ],
  ];

  @override
  void initState() {
    super.initState();
    _initializeGame();
  }

  void _initializeGame() {
    List<GameItem> currentSet = _iconSets[_currentLevel % _iconSets.length];

    List<MemoryCard> gameCards = [];
    for (var item in currentSet) {
      gameCards.add(MemoryCard(icon: item.icon, color: item.color));
      gameCards.add(MemoryCard(icon: item.icon, color: item.color));
    }

    gameCards.shuffle();

    setState(() {
      _cards = gameCards;
      _score = 0;
      _selectedCardIndex = null;
      _isProcessing = false;
    });
  }

  void _onCardTap(int index) {
    if (_cards[index].isMatched || _cards[index].isRevealed || _isProcessing) return;

    setState(() {
      _cards[index].isRevealed = true;
    });

    if (_selectedCardIndex == null) {
      _selectedCardIndex = index;
    } else {
      _checkForMatch(index);
    }
  }

  void _checkForMatch(int secondIndex) {
    int firstIndex = _selectedCardIndex!;
    
    if (_cards[firstIndex].icon == _cards[secondIndex].icon) {
      setState(() {
        _cards[firstIndex].isMatched = true;
        _cards[secondIndex].isMatched = true;
        _score++;
        _selectedCardIndex = null;
      });

      if (_score == 6) {
        _showWinDialog();
      }
    } else {
      _isProcessing = true;
      Timer(const Duration(milliseconds: 1000), () {
        if (mounted) {
          setState(() {
            _cards[firstIndex].isRevealed = false;
            _cards[secondIndex].isRevealed = false;
            _selectedCardIndex = null;
            _isProcessing = false;
          });
        }
      });
    }
  }

  void _showWinDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Excellent Work!', style: TextStyle(fontFamily: 'Montserrat', fontWeight: FontWeight.bold)),
        content: const Text('You matched all the pairs! Ready to try a new set of cards?'),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              setState(() {
                _currentLevel++;
                _initializeGame();
              });
            },
            child: const Text('Play Next Level', style: TextStyle(color: _themeColor, fontSize: 18, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  double _getResponsiveFontSize(BuildContext context, double baseSize) {
    final screenWidth = MediaQuery.of(context).size.width;
    final scaleFactor = screenWidth / 375;
    return baseSize * scaleFactor.clamp(0.8, 1.4);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _backgroundColor,
      appBar: AppBar(
        title: const Text('Memory Match', style: TextStyle(fontFamily: 'Montserrat', fontWeight: FontWeight.bold)),
        backgroundColor: _themeColor,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _initializeGame,
            tooltip: 'Restart Game',
          )
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: const BorderRadius.vertical(bottom: Radius.circular(25)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 15,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Text(
                    'Round ${_currentLevel + 1}',
                    style: TextStyle(
                      color: _themeColor,
                      fontFamily: 'Montserrat',
                      fontWeight: FontWeight.w800,
                      fontSize: 14,
                      letterSpacing: 1.0,
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    'Exercise Your Mind',
                    style: TextStyle(
                      color: _textPrimary,
                      fontFamily: 'Montserrat',
                      fontWeight: FontWeight.w700,
                      fontSize: _getResponsiveFontSize(context, 20),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Tap the cards to flip them and find the matching pairs.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontFamily: 'Inter',
                      fontSize: 15,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Grid
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: GridView.builder(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    crossAxisSpacing: 15,
                    mainAxisSpacing: 15,
                    childAspectRatio: 0.85,
                  ),
                  itemCount: _cards.length,
                  itemBuilder: (context, index) {
                    final card = _cards[index];
                    return GestureDetector(
                      onTap: () => _onCardTap(index),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        decoration: BoxDecoration(
                          color: card.isRevealed || card.isMatched ? Colors.white : _themeColor.withOpacity(0.85),
                          borderRadius: BorderRadius.circular(15),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.1),
                              blurRadius: 5,
                              offset: const Offset(0, 3),
                            ),
                          ],
                          border: card.isRevealed || card.isMatched 
                              ? Border.all(color: card.color.withOpacity(0.5), width: 3) 
                              : null,
                        ),
                        child: Center(
                          child: card.isRevealed || card.isMatched
                              // UPDATED: Used FittedBox and Padding to make icon fill the card
                              ? Padding(
                                  padding: const EdgeInsets.all(8.0),
                                  child: FittedBox(
                                    fit: BoxFit.contain,
                                    child: Icon(
                                      card.icon,
                                      color: card.color,
                                      // Giving it a large size, FittedBox will scale it down to fit
                                      size: 100, 
                                    ),
                                  ),
                                )
                              : Icon(
                                  Icons.question_mark_rounded,
                                  size: 35,
                                  color: Colors.white.withOpacity(0.5),
                                ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }
}

class GameItem {
  final IconData icon;
  final Color color;
  GameItem(this.icon, this.color);
}

class MemoryCard {
  final IconData icon;
  final Color color;
  bool isRevealed;
  bool isMatched;

  MemoryCard({
    required this.icon,
    required this.color,
    this.isRevealed = false,
    this.isMatched = false,
  });
}
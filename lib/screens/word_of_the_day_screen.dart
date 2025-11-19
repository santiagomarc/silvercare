import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';

class WordOfTheDayScreen extends StatefulWidget {
  const WordOfTheDayScreen({super.key});

  @override
  State<WordOfTheDayScreen> createState() => _WordOfTheDayScreenState();
}

class _WordOfTheDayScreenState extends State<WordOfTheDayScreen> with SingleTickerProviderStateMixin {
  static const Color _primaryColor = Color(0xFF7B1FA2);
  static const Color _accentColor = Color(0xFFFFD54F);
  static const Color _backgroundColor = Color(0xFFF8F9FA);
  static const Color _textPrimary = Color(0xFF2D3748);

  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  int _currentIndex = 0;

  // Curated daily quotes - encouraging, uplifting, and easy to understand
  final List<Map<String, String>> _dailyQuotes = [
    {
      'quote': 'Every day is a new beginning. Take a deep breath, smile, and start again.',
      'author': 'Unknown',
      'action': 'Start your day with a smile! 😊'
    },
    {
      'quote': 'Age is just a number. It\'s never too late to learn something new.',
      'author': 'Unknown',
      'action': 'Try something new today! 🌟'
    },
    {
      'quote': 'The best time to plant a tree was 20 years ago. The second best time is now.',
      'author': 'Chinese Proverb',
      'action': 'Take a small step today! 🌱'
    },
    {
      'quote': 'Happiness is not by chance, but by choice.',
      'author': 'Jim Rohn',
      'action': 'Choose joy today! ✨'
    },
    {
      'quote': 'Every sunset brings the promise of a new dawn.',
      'author': 'Ralph Waldo Emerson',
      'action': 'Look forward to tomorrow! 🌅'
    },
    {
      'quote': 'The greatest wealth is health.',
      'author': 'Virgil',
      'action': 'Take care of yourself today! 💪'
    },
    {
      'quote': 'A smile is a curve that sets everything straight.',
      'author': 'Phyllis Diller',
      'action': 'Share a smile with someone! 😄'
    },
    {
      'quote': 'Do not count the days, make the days count.',
      'author': 'Muhammad Ali',
      'action': 'Make today meaningful! 🎯'
    },
    {
      'quote': 'You are never too old to set another goal or dream a new dream.',
      'author': 'C.S. Lewis',
      'action': 'Dream big today! 💫'
    },
    {
      'quote': 'The secret of getting ahead is getting started.',
      'author': 'Mark Twain',
      'action': 'Start now, no matter how small! 🚀'
    },
    {
      'quote': 'Kind words are short and easy to speak, but their echoes are endless.',
      'author': 'Mother Teresa',
      'action': 'Say something kind today! 💝'
    },
    {
      'quote': 'Life is 10% what happens to you and 90% how you react to it.',
      'author': 'Charles R. Swindoll',
      'action': 'Choose your response wisely! 🧠'
    },
    {
      'quote': 'The only way to do great work is to love what you do.',
      'author': 'Steve Jobs',
      'action': 'Find joy in what you do! ❤️'
    },
    {
      'quote': 'Believe you can and you\'re halfway there.',
      'author': 'Theodore Roosevelt',
      'action': 'Believe in yourself today! 💪'
    },
    {
      'quote': 'A journey of a thousand miles begins with a single step.',
      'author': 'Lao Tzu',
      'action': 'Take that first step! 👣'
    },
    {
      'quote': 'The best preparation for tomorrow is doing your best today.',
      'author': 'H. Jackson Brown Jr.',
      'action': 'Do your best right now! 🌟'
    },
    {
      'quote': 'In every day, there are 1,440 minutes. That means we have 1,440 daily opportunities to make a positive impact.',
      'author': 'Les Brown',
      'action': 'Make each moment count! ⏰'
    },
    {
      'quote': 'Keep your face always toward the sunshine and shadows will fall behind you.',
      'author': 'Walt Whitman',
      'action': 'Focus on the positive! ☀️'
    },
    {
      'quote': 'The more you praise and celebrate your life, the more there is in life to celebrate.',
      'author': 'Oprah Winfrey',
      'action': 'Celebrate small wins today! 🎉'
    },
    {
      'quote': 'It does not matter how slowly you go as long as you do not stop.',
      'author': 'Confucius',
      'action': 'Keep moving forward! 🚶'
    },
    {
      'quote': 'Laughter is the best medicine.',
      'author': 'Proverb',
      'action': 'Find something to laugh about! 😂'
    },
    {
      'quote': 'Be yourself; everyone else is already taken.',
      'author': 'Oscar Wilde',
      'action': 'Embrace who you are! 🦋'
    },
    {
      'quote': 'The purpose of our lives is to be happy.',
      'author': 'Dalai Lama',
      'action': 'Choose happiness today! 😊'
    },
    {
      'quote': 'Live as if you were to die tomorrow. Learn as if you were to live forever.',
      'author': 'Mahatma Gandhi',
      'action': 'Learn something new today! 📚'
    },
    {
      'quote': 'Today is a gift, that\'s why we call it the present.',
      'author': 'Bill Keane',
      'action': 'Be present in this moment! 🎁'
    },
    {
      'quote': 'The only impossible journey is the one you never begin.',
      'author': 'Tony Robbins',
      'action': 'Begin something today! 🌈'
    },
    {
      'quote': 'Gratitude turns what we have into enough.',
      'author': 'Melody Beattie',
      'action': 'Be grateful for today! 🙏'
    },
    {
      'quote': 'You don\'t have to be great to start, but you have to start to be great.',
      'author': 'Zig Ziglar',
      'action': 'Just start! 💪'
    },
    {
      'quote': 'The sun himself is weak when he first rises, and gathers strength and courage as the day gets on.',
      'author': 'Charles Dickens',
      'action': 'You grow stronger each day! 🌄'
    },
    {
      'quote': 'Small acts, when multiplied by millions of people, can transform the world.',
      'author': 'Howard Zinn',
      'action': 'Your actions matter! 🌍'
    },
  ];

  @override
  void initState() {
    super.initState();
    // Always start at quote #1
    _currentIndex = 0;

    _animationController = AnimationController(
      duration: const Duration(milliseconds: 1200),
      vsync: this,
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeIn),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _animationController, curve: Curves.easeOut));

    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  void _changeQuote(int direction) {
    _animationController.reverse().then((_) {
      setState(() {
        _currentIndex = (_currentIndex + direction) % _dailyQuotes.length;
        if (_currentIndex < 0) _currentIndex = _dailyQuotes.length - 1;
      });
      _animationController.forward();
    });
  }

  void _shareQuote() {
    final quote = _dailyQuotes[_currentIndex];
    Share.share(
      '"${quote['quote']}"\n\n- ${quote['author']}\n\n${quote['action']}\n\n✨ Shared from SilverCare',
      subject: 'Daily Inspiration',
    );
  }

  void _copyQuote() {
    final quote = _dailyQuotes[_currentIndex];
    Clipboard.setData(ClipboardData(
      text: '"${quote['quote']}"\n\n- ${quote['author']}\n\n${quote['action']}',
    ));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text('Quote copied to clipboard! 📋', style: TextStyle(fontFamily: 'Inter')),
        backgroundColor: _primaryColor,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final quote = _dailyQuotes[_currentIndex];
    final screenWidth = MediaQuery.of(context).size.width;

    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              _accentColor.withOpacity(0.3),
              _primaryColor.withOpacity(0.8),
              _primaryColor,
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              // Custom App Bar
              Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
                      onPressed: () => Navigator.pop(context),
                    ),
                    const Spacer(),
                    Text(
                      'WORDS OF THE DAY',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: screenWidth < 360 ? 16 : 18,
                        fontFamily: 'Montserrat',
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.2,
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.share_rounded, color: Colors.white),
                      onPressed: _shareQuote,
                    ),
                  ],
                ),
              ),

              // Date Display
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.white.withOpacity(0.3), width: 1),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.calendar_today_rounded, color: Colors.white, size: 18),
                    const SizedBox(width: 8),
                    Text(
                      _formatDate(DateTime.now()),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontFamily: 'Inter',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Main Quote Card
              Expanded(
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 20),
                  child: FadeTransition(
                    opacity: _fadeAnimation,
                    child: SlideTransition(
                      position: _slideAnimation,
                      child: Card(
                        elevation: 20,
                        shadowColor: Colors.black45,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(30),
                        ),
                        child: Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(30),
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [
                                Colors.white,
                                _accentColor.withOpacity(0.05),
                              ],
                            ),
                          ),
                          padding: const EdgeInsets.all(35),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              // Decorative Quote Icon
                              Container(
                                padding: const EdgeInsets.all(16),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [_accentColor, _primaryColor],
                                  ),
                                  shape: BoxShape.circle,
                                  boxShadow: [
                                    BoxShadow(
                                      color: _primaryColor.withOpacity(0.3),
                                      blurRadius: 15,
                                      offset: const Offset(0, 5),
                                    ),
                                  ],
                                ),
                                child: const Icon(
                                  Icons.format_quote_rounded,
                                  color: Colors.white,
                                  size: 40,
                                ),
                              ),

                              const SizedBox(height: 35),

                              // Quote Text
                              Text(
                                quote['quote']!,
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: screenWidth < 360 ? 20 : 24,
                                  fontFamily: 'Montserrat',
                                  fontWeight: FontWeight.w600,
                                  color: _textPrimary,
                                  height: 1.5,
                                  letterSpacing: 0.3,
                                ),
                              ),

                              const SizedBox(height: 30),

                              // Author
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      _primaryColor.withOpacity(0.1),
                                      _accentColor.withOpacity(0.1),
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  '— ${quote['author']}',
                                  style: TextStyle(
                                    fontSize: screenWidth < 360 ? 15 : 17,
                                    fontFamily: 'Inter',
                                    fontWeight: FontWeight.w500,
                                    color: _primaryColor,
                                    fontStyle: FontStyle.italic,
                                  ),
                                ),
                              ),

                              const SizedBox(height: 40),

                              // Action Call
                              Container(
                                padding: const EdgeInsets.all(20),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [_accentColor, _primaryColor],
                                  ),
                                  borderRadius: BorderRadius.circular(20),
                                  boxShadow: [
                                    BoxShadow(
                                      color: _primaryColor.withOpacity(0.3),
                                      blurRadius: 10,
                                      offset: const Offset(0, 5),
                                    ),
                                  ],
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.wb_sunny_rounded, color: Colors.white, size: 22),
                                    const SizedBox(width: 12),
                                    Flexible(
                                      child: Text(
                                        quote['action']!,
                                        textAlign: TextAlign.center,
                                        style: TextStyle(
                                          fontSize: screenWidth < 360 ? 15 : 17,
                                          fontFamily: 'Montserrat',
                                          fontWeight: FontWeight.w700,
                                          color: Colors.white,
                                          letterSpacing: 0.5,
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
                    ),
                  ),
                ),
              ),

              // Navigation & Action Buttons
              Padding(
                padding: const EdgeInsets.all(25.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    // Previous Button
                    _buildNavButton(
                      icon: Icons.arrow_back_ios_new_rounded,
                      onPressed: () => _changeQuote(-1),
                      tooltip: 'Previous Quote',
                    ),

                    // Copy Button
                    _buildActionButton(
                      icon: Icons.content_copy_rounded,
                      onPressed: _copyQuote,
                      tooltip: 'Copy Quote',
                    ),

                    // Favorite Button (placeholder for future feature)
                    _buildActionButton(
                      icon: Icons.favorite_border_rounded,
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: const Text('Quote saved to favorites! ❤️', style: TextStyle(fontFamily: 'Inter')),
                            backgroundColor: _primaryColor,
                            behavior: SnackBarBehavior.floating,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            duration: const Duration(seconds: 2),
                          ),
                        );
                      },
                      tooltip: 'Save to Favorites',
                    ),

                    // Next Button
                    _buildNavButton(
                      icon: Icons.arrow_forward_ios_rounded,
                      onPressed: () => _changeQuote(1),
                      tooltip: 'Next Quote',
                    ),
                  ],
                ),
              ),

              // Quote Counter
              Padding(
                padding: const EdgeInsets.only(bottom: 20),
                child: Text(
                  '${_currentIndex + 1} of ${_dailyQuotes.length}',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.8),
                    fontSize: 14,
                    fontFamily: 'Inter',
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNavButton({
    required IconData icon,
    required VoidCallback onPressed,
    required String tooltip,
  }) {
    return Container(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Material(
        color: Colors.white,
        shape: const CircleBorder(),
        child: InkWell(
          onTap: onPressed,
          customBorder: const CircleBorder(),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Icon(icon, color: _primaryColor, size: 24),
          ),
        ),
      ),
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required VoidCallback onPressed,
    required String tooltip,
  }) {
    return Container(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Material(
        color: Colors.white.withOpacity(0.9),
        shape: const CircleBorder(),
        child: InkWell(
          onTap: onPressed,
          customBorder: const CircleBorder(),
          child: Padding(
            padding: const EdgeInsets.all(14.0),
            child: Icon(icon, color: _primaryColor, size: 22),
          ),
        ),
      ),
    );
  }

  String _formatDate(DateTime date) {
    final months = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    final days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    return '${days[date.weekday % 7]}, ${months[date.month - 1]} ${date.day}, ${date.year}';
  }
}

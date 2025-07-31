# Choose Your Path - Text-Based RPG

A compelling text-based role-playing game built with PHP, HTML5, and CSS3. Navigate through mystical forests, make crucial decisions, and forge your destiny in this immersive adventure.

## 🎮 Features

- **Branching Storylines**: Your choices shape the narrative and determine multiple endings
- **Character Progression**: Manage health points and collect magical items
- **User Authentication**: Secure registration and login with "remember me" functionality
- **Game Persistence**: Save and continue your adventure across sessions
- **Leaderboard System**: Compete with other players and track achievements
- **Responsive Design**: Optimized for desktop and mobile devices
- **Accessibility**: ARIA labels, semantic HTML, and keyboard navigation support
- **CSS Animations**: Smooth transitions and engaging visual effects

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (procedural and lightweight OOP)
- **Database**: SQLite (lightweight, no server required)
- **Frontend**: HTML5 & CSS3 only (zero JavaScript)
- **Styling**: Custom CSS with Google Fonts and CSS Grid/Flexbox
- **Architecture**: MVC-inspired structure with namespace organization

## 📁 Project Structure

```
choose-your-path/
├── public/              # Web-accessible files
│   ├── index.php        # Homepage and game launcher
│   ├── login.php        # User authentication
│   ├── register.php     # Account creation
│   ├── story.php        # Main gameplay interface
│   ├── end.php          # Game completion screen
│   ├── leaderboard.php  # Player rankings
│   └── css/
│       └── style.css    # Main stylesheet
├── src/                 # PHP classes
│   ├── Database.php     # Database connection singleton
│   ├── Auth.php         # Authentication handler
│   └── GameEngine.php   # Core game logic
├── scenes/              # Story content files
│   ├── start.php        # Opening scene
│   ├── dark_woods.php   # Forest path storyline
│   ├── sunny_meadow.php # Meadow path storyline
│   ├── riverside.php    # River path storyline
│   ├── victory.php      # Victory ending
│   └── defeat.php       # Defeat ending
├── tests/               # Testing framework
│   └── GameEngineTest.php
├── data/                # Database storage (created by setup)
├── setup.php            # Database initialization
├── schema.sql           # Database schema reference
├── composer.json        # PHP dependencies
└── README.md           # This file
```

## 🚀 Quick Setup

### Prerequisites
- PHP 8.0 or higher
- SQLite support (usually included with PHP)
- Web server or PHP built-in server

### Installation Steps

1. **Clone or Download the Project**
   ```bash
   # If using git
   git clone <repository-url> choose-your-path
   cd choose-your-path
   
   # Or download and extract the ZIP file
   ```

2. **Initialize the Database**
   ```bash
   php setup.php
   ```
   This creates the SQLite database at `data/game.db` with all required tables.

3. **Start the Development Server**
   ```bash
   php -S localhost:8000 -t public/
   ```

4. **Open Your Browser**
   Navigate to: `http://localhost:8000`

### Alternative Setup (Production)

For production deployment:

1. Upload files to your web server
2. Run `php setup.php` once to initialize the database
3. Ensure the `data/` directory is writable by the web server
4. Point your domain to the `public/` directory

## 🎯 How to Play

1. **Create an Account**: Register with a username and password
2. **Start Your Adventure**: Choose to begin a new game or continue an existing one
3. **Make Choices**: Read the story text and select from available options
4. **Manage Resources**: Keep track of your health points and inventory items
5. **Reach an Ending**: Navigate through the story to achieve victory, defeat, or other outcomes
6. **Compete**: View the leaderboard to see how you compare with other players

## 🎨 Game Mechanics

### Health System
- Start with 100 HP
- Choices can increase or decrease health
- Game ends if health reaches 0
- Health affects final ranking

### Inventory System
- Collect special items through story choices
- Items are remembered throughout your journey
- Some items may influence available choices

### Save System
- Game automatically saves after each choice
- Continue from where you left off
- Option to start fresh at any time

### Scoring
- Final HP determines primary ranking
- Playtime affects tiebreakers (faster is better)
- Number of choices made provides additional scoring

## 🧪 Testing

Run the included test suite:

```bash
php tests/GameEngineTest.php
```

The test suite validates:
- Scene loading functionality
- Game state management
- HP boundary checking
- Error handling

## 🔧 Development

### Adding New Scenes

1. Create a new PHP file in the `scenes/` directory
2. Follow the existing scene structure:

```php
<?php
return [
    'title' => 'Scene Title',
    'text' => 'Narrative text describing the scene...',
    'image_alt' => 'Alt text for accessibility',
    'choices' => [
        'choice_key' => [
            'text' => 'Choice description',
            'next_scene' => 'target_scene',
            'hp_change' => 10,  // Optional
            'add_item' => 'Item Name'  // Optional
        ]
    ]
];
?>
```

### Customizing Styles

Edit `public/css/style.css` to modify:
- Color schemes
- Typography
- Animations
- Layout adjustments

### Database Modifications

To add new tables or modify existing ones:
1. Update `setup.php` with your changes
2. Delete `data/game.db` 
3. Run `php setup.php` again

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` and `password_verify()`
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: All output properly escaped with `htmlspecialchars()`
- **Session Security**: Secure session handling and CSRF protection
- **Remember Me Tokens**: Cryptographically secure token generation

## 📱 Browser Compatibility

- **Modern Browsers**: Chrome 60+, Firefox 60+, Safari 12+, Edge 79+
- **Mobile Support**: iOS Safari, Chrome Mobile, Samsung Internet
- **Accessibility**: WCAG 2.1 AA compliant
- **No JavaScript Required**: Works with JavaScript disabled

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🎭 Game Lore

Enter the Enchanted Forest where ancient magic flows through every tree and stream. As a brave adventurer, you must navigate through mystical paths, each decision shaping your destiny. Will you show compassion to forest creatures, seek wisdom from ancient sages, or boldly face unknown dangers?

Your journey through three distinct paths - the Dark Woods, Sunny Meadow, and Mystical Riverside - will test your courage, wisdom, and determination. Each choice carries weight, affecting not only your immediate survival but also the final outcome of your grand adventure.

## 🏆 Achievements

- **First Steps**: Complete your first game
- **Survivor**: Finish with 80+ HP
- **Speed Runner**: Complete in under 5 minutes
- **Collector**: Gather 3+ items in a single playthrough
- **Legend**: Achieve victory ending with maximum HP

---

**Happy Adventuring!** 🌟

*Built with ❤️ using PHP, SQLite, and pure CSS*
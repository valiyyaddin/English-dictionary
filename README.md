# English Dictionary Platform

A modern, interactive web-based English Dictionary platform with advanced search capabilities, word statistics tracking, and beautiful UI/UX.

## Features

### 🔍 Core Features
- **Advanced Word Search**: Real-time search suggestions with autocomplete
- **Full-Text Search**: Search through 66,000+ words and definitions
- **Random Word Discovery**: Explore new words randomly to expand vocabulary
- **Word Statistics**: Track how many times each word has been searched
- **Search History**: Keep track of recently searched words
- **Favorites System**: Save your favorite words for quick access

### 📊 Statistics Dashboard
- Total words in database
- Total searches performed
- Today's search count
- Personal favorites count

### 🎨 User Experience
- Modern, gradient-based UI design
- Responsive layout (mobile-friendly)
- Smooth animations and transitions
- Real-time search suggestions
- Text-to-speech pronunciation
- Share words functionality
- Session-based favorites

### 🚀 Technical Features
- PHP backend with MySQL database
- AJAX-powered API endpoints
- Automatic database initialization
- Indexed database tables for fast queries
- Full-text search support
- Session management
- Statistics tracking with timestamps

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Python 3.7+ (for importing data)
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Configure Database

1. Update the `.env` file with your MySQL credentials:

```
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=english_dictionary
DB_PORT=3306
```

### Step 2: Import Dictionary Data

1. Install Python dependencies:
```bash
pip install mysql-connector-python python-dotenv
```

2. Run the import script:
```bash
python import_dictionary.py
```

This will:
- Create the database if it doesn't exist
- Create all necessary tables with proper indexes
- Import all words from dictionary.csv
- Set up the statistics tracking tables

### Step 3: Run the Website

**Option A: Using PHP Built-in Server**
```bash
cd website
php -S localhost:8000
```

Then open your browser and go to: `http://localhost:8000`

**Option B: Using Apache/Nginx**
- Configure your web server to point to the `website` directory
- Ensure PHP is enabled
- Access through your configured domain/localhost

## Database Schema

### Tables

1. **words**
   - `id` (PRIMARY KEY)
   - `word` (VARCHAR, INDEXED)
   - `definition` (TEXT, FULLTEXT INDEXED)
   - `created_at` (TIMESTAMP)

2. **search_stats**
   - `id` (PRIMARY KEY)
   - `word_id` (FOREIGN KEY)
   - `search_count` (INT)
   - `last_searched` (TIMESTAMP)

3. **search_history**
   - `id` (PRIMARY KEY)
   - `word_id` (FOREIGN KEY)
   - `searched_at` (TIMESTAMP)
   - `ip_address` (VARCHAR)

4. **favorites**
   - `id` (PRIMARY KEY)
   - `word_id` (FOREIGN KEY)
   - `session_id` (VARCHAR)
   - `created_at` (TIMESTAMP)

## File Structure

```
English-dictionary/
├── .env                      # Database configuration
├── dictionary.csv            # Source dictionary data
├── import_dictionary.py      # Python import script
├── README.md                 # This file
└── website/
    ├── config.php           # Database connection & initialization
    ├── index.php            # Main application page
    ├── api.php              # REST API endpoints
    ├── styles.css           # Beautiful CSS styling
    └── script.js            # Interactive JavaScript
```

## API Endpoints

All endpoints are accessed via `api.php?action=<action_name>`

### GET Endpoints
- `search` - Get word suggestions (param: `q`)
- `getWord` - Get word details (param: `word`)
- `randomWord` - Get a random word
- `randomWords` - Get multiple random words (param: `count`)
- `recentSearches` - Get recent searches
- `stats` - Get statistics dashboard data
- `checkFavorite` - Check if word is favorited (param: `wordId`)

### POST Endpoints
- `addFavorite` - Add word to favorites (param: `wordId`)
- `removeFavorite` - Remove word from favorites (param: `wordId`)
- `getFavorites` - Get all favorites

## Usage

### Search for Words
1. Type in the search box to see suggestions
2. Click a suggestion or press Enter to view full definition
3. Use the random button (🔀) for surprise discoveries

### Interactive Features
- **❤️ Favorite**: Save words you want to remember
- **🔊 Speak**: Hear the pronunciation
- **📤 Share**: Share words with others

### Explore Tabs
- **Popular Words**: See most searched words
- **Recent Searches**: Your search history
- **Favorites**: Your saved words
- **Random Discovery**: Explore new vocabulary

## Performance Optimizations

- Database indexes on frequently queried columns
- Full-text search indexes for fast word lookup
- AJAX requests with debouncing (300ms delay)
- Efficient batch inserts during data import
- Session-based favorites (no user account required)

## Browser Compatibility

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Security Features

- Prepared SQL statements (prevents SQL injection)
- Input sanitization and escaping
- Session-based authentication
- IP address logging for analytics

## Customization

### Change Colors
Edit CSS variables in `website/styles.css`:
```css
:root {
    --primary-color: #4F46E5;
    --secondary-color: #EC4899;
    /* ... more variables */
}
```

### Modify Database Settings
Update `.env` file with your preferences

### Add More Features
- Edit `website/api.php` for new API endpoints
- Modify `website/script.js` for client-side features
- Update `website/index.php` for UI changes

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check `.env` credentials
- Ensure MySQL user has proper permissions

### Import Script Errors
- Install required Python packages: `pip install mysql-connector-python python-dotenv`
- Check file encoding (should be UTF-8)
- Verify dictionary.csv exists in root directory

### Empty Search Results
- Ensure import script completed successfully
- Check if words table is populated: `SELECT COUNT(*) FROM words;`
- Verify full-text indexes were created

## Credits

Dictionary data contains 66,000+ English words with comprehensive definitions.

Built with modern web technologies:
- PHP for backend processing
- MySQL for data storage
- Vanilla JavaScript for interactivity
- CSS3 for beautiful styling

## License

This project is open source and available for educational purposes.

## Support

For issues or questions, please check the troubleshooting section or review the code comments for detailed implementation notes.

---

**Happy Learning! 📚✨**


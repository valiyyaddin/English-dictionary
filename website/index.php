<?php
session_start();
require_once 'config.php';

$conn = getDBConnection();

// Get total word count
$stmt = $conn->query("SELECT COUNT(*) as total FROM words");
$totalWords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total searches today
$stmt = $conn->query("SELECT COUNT(*) as total FROM search_history WHERE DATE(searched_at) = CURDATE()");
$todaySearches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get most popular words
$popularWords = $conn->query("
    SELECT w.word, s.search_count 
    FROM words w 
    INNER JOIN search_stats s ON w.id = s.word_id 
    ORDER BY s.search_count DESC 
    LIMIT 10
");

// PDO doesn't need explicit close
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Modern English Dictionary with 66,000+ words. Search, discover, and explore word meanings with our beautiful dark-themed interface.">
    <meta name="theme-color" content="#0a0e27">
    <title>Lexicon - Modern English Dictionary</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Modern Navbar -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="#" class="logo">
                    <div class="logo-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10 8h6M10 12h6M10 16h4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title">Lexicon</span>
                        <span class="logo-subtitle">Dictionary</span>
                    </div>
                </a>
                
                <div class="navbar-stats">
                    <div class="nav-stat">
                        <span class="nav-stat-value"><?php echo number_format($totalWords); ?></span>
                        <span class="nav-stat-label">Words</span>
                    </div>
                    <div class="nav-stat">
                        <span class="nav-stat-value"><?php echo number_format($todaySearches); ?></span>
                        <span class="nav-stat-label">Today</span>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero-section">
            <h1 class="hero-title">Discover Words, Expand Knowledge</h1>
            <p class="hero-subtitle">Explore over <?php echo number_format($totalWords); ?> English words with detailed definitions and meanings</p>
        </section>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-container">
                <div class="search-wrapper">
                    <svg class="search-icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search for any word..." autocomplete="off">
                    <button id="randomWordBtn" class="random-btn" title="Random Word">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M17 3l4 4-4 4M7 13l-4-4 4-4M21 7H3M21 17H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                <div id="suggestions" class="suggestions"></div>
            </div>
        </section>

        <!-- Results Section -->
        <section id="resultsSection" class="results-section" style="display: none;">
            <div class="result-card">
                <div class="result-header">
                    <h3 id="resultWord" class="result-word"></h3>
                    <div class="result-actions">
                        <button id="favoriteBtn" class="action-btn" title="Add to Favorites">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                        </button>
                        <button id="speakBtn" class="action-btn" title="Pronounce">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <button id="shareBtn" class="action-btn" title="Share">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/>
                                <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/>
                                <path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="resultDefinition" class="result-definition"></div>
                <div class="result-stats">
                    <span id="searchCount" class="stat-badge"></span>
                    <span id="lastSearched" class="stat-badge"></span>
                </div>
            </div>
        </section>

        <!-- Tabs Section -->
        <section class="tabs-section">
            <div class="tabs">
                <button class="tab-btn active" data-tab="popular">Popular Words</button>
                <button class="tab-btn" data-tab="recent">Recent Searches</button>
                <button class="tab-btn" data-tab="favorites">Favorites</button>
                <button class="tab-btn" data-tab="random">Random Discovery</button>
            </div>

            <!-- Tab Contents -->
            <div id="popularTab" class="tab-content active">
                <div class="word-grid">
                    <?php while($row = $popularWords->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="word-card" data-word="<?php echo htmlspecialchars($row['word']); ?>">
                            <span class="word-text"><?php echo htmlspecialchars($row['word']); ?></span>
                            <span class="search-badge"><?php echo number_format($row['search_count']); ?> searches</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="recentTab" class="tab-content">
                <div class="word-grid" id="recentWords">
                    <p class="empty-state">Your recent searches will appear here</p>
                </div>
            </div>

            <div id="favoritesTab" class="tab-content">
                <div class="word-grid" id="favoriteWords">
                    <p class="empty-state">Your favorite words will appear here</p>
                </div>
            </div>

            <div id="randomTab" class="tab-content">
                <div class="random-section">
                    <p class="random-description">Expand your vocabulary with random word discoveries!</p>
                    <button id="randomDiscoveryBtn" class="primary-btn">Discover Random Word</button>
                    <div id="randomWordsGrid" class="word-grid"></div>
                </div>
            </div>
        </section>

        <!-- Statistics Dashboard -->
        <section class="stats-dashboard">
            <h2 class="section-title">Statistics Dashboard</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalWordsCount"><?php echo number_format($totalWords); ?></h3>
                        <p>Total Words</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="8" stroke="white" stroke-width="2"/>
                            <path d="M21 21l-4.35-4.35" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalSearchesCount">0</h3>
                        <p>Total Searches</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 id="todaySearchesCount"><?php echo number_format($todaySearches); ?></h3>
                        <p>Searches Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="white" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 id="favoritesCount">0</h3>
                        <p>Favorites</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p><strong>English Dictionary Platform</strong></p>
                <p>&copy; 2025 Empowering language learners worldwide</p>
                <p style="margin-top: 1.5rem; font-size: 0.875rem;">
                    <span style="display: inline-block; margin: 0 0.5rem;">📚 <?php echo number_format($totalWords); ?> Words</span>
                    <span style="display: inline-block; margin: 0 0.5rem;">•</span>
                    <span style="display: inline-block; margin: 0 0.5rem;">🔍 Search & Discover</span>
                    <span style="display: inline-block; margin: 0 0.5rem;">•</span>
                    <span style="display: inline-block; margin: 0 0.5rem;">❤️ Save Favorites</span>
                </p>
                <p style="margin-top: 1rem; font-size: 0.875rem; opacity: 0.7;">
                    Built with passion for word enthusiasts
                </p>
            </div>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>


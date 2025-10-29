// Global state
let currentWordId = null;
let searchTimeout = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    addWelcomeAnimation();
});

function initializeApp() {
    setupEventListeners();
    loadRecentSearches();
    loadFavorites();
    updateStats();
    
    // Load recent searches from localStorage
    displayLocalRecentSearches();
}

function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    const randomWordBtn = document.getElementById('randomWordBtn');
    const favoriteBtn = document.getElementById('favoriteBtn');
    const speakBtn = document.getElementById('speakBtn');
    const shareBtn = document.getElementById('shareBtn');
    const randomDiscoveryBtn = document.getElementById('randomDiscoveryBtn');
    
    // Search input with debounce
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length > 0) {
            searchTimeout = setTimeout(() => {
                getSuggestions(query);
            }, 300);
        } else {
            hideSuggestions();
        }
    });
    
    // Search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = e.target.value.trim();
            if (query) {
                searchWord(query);
                hideSuggestions();
            }
        }
    });
    
    // Random word button
    randomWordBtn.addEventListener('click', getRandomWord);
    
    // Favorite button
    favoriteBtn.addEventListener('click', toggleFavorite);
    
    // Speak button
    speakBtn.addEventListener('click', speakWord);
    
    // Share button
    shareBtn.addEventListener('click', shareWord);
    
    // Random discovery button
    randomDiscoveryBtn.addEventListener('click', loadRandomWords);
    
    // Tab buttons
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });
    
    // Click outside to close suggestions
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            hideSuggestions();
        }
    });
}

// Search suggestions
async function getSuggestions(query) {
    try {
        const response = await fetch(`api.php?action=search&q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success && data.suggestions.length > 0) {
            displaySuggestions(data.suggestions);
        } else {
            hideSuggestions();
        }
    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}

function displaySuggestions(suggestions) {
    const suggestionsDiv = document.getElementById('suggestions');
    
    suggestionsDiv.innerHTML = suggestions.map(item => `
        <div class="suggestion-item" onclick="searchWord('${escapeHtml(item.word)}')">
            <span class="suggestion-word">${escapeHtml(item.word)}</span>
            <span class="suggestion-preview">${escapeHtml(item.definition)}</span>
        </div>
    `).join('');
    
    suggestionsDiv.classList.add('active');
}

function hideSuggestions() {
    const suggestionsDiv = document.getElementById('suggestions');
    suggestionsDiv.classList.remove('active');
}

// Search word with loading state
async function searchWord(word) {
    // Show loading state
    const resultsSection = document.getElementById('resultsSection');
    resultsSection.style.display = 'block';
    resultsSection.innerHTML = '<div style="text-align: center; padding: 4rem;"><div class="loading"></div><p style="margin-top: 1rem; color: var(--text-secondary);">Loading...</p></div>';
    resultsSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    try {
        const response = await fetch(`api.php?action=getWord&word=${encodeURIComponent(word)}`);
        const data = await response.json();
        
        if (data.success) {
            // Add a slight delay for smooth transition
            setTimeout(() => {
                resultsSection.innerHTML = `
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
                `;
                
                displayWordResult(data);
                saveToLocalRecent(word);
                updateStats();
                
                // Re-attach event listeners
                document.getElementById('favoriteBtn').addEventListener('click', toggleFavorite);
                document.getElementById('speakBtn').addEventListener('click', speakWord);
                document.getElementById('shareBtn').addEventListener('click', shareWord);
            }, 300);
        } else {
            resultsSection.style.display = 'none';
            showNotification('Word not found', 'error');
        }
    } catch (error) {
        console.error('Error searching word:', error);
        resultsSection.style.display = 'none';
        showNotification('Error searching word', 'error');
    }
}

function displayWordResult(data) {
    currentWordId = data.id;
    
    document.getElementById('resultWord').textContent = data.word;
    document.getElementById('resultDefinition').textContent = data.definition;
    document.getElementById('searchCount').textContent = `${data.searchCount} searches`;
    document.getElementById('lastSearched').textContent = `Last searched: ${formatDate(data.lastSearched)}`;
    
    document.getElementById('resultsSection').style.display = 'block';
    document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Update search input
    document.getElementById('searchInput').value = data.word;
    
    // Check if word is in favorites
    checkFavoriteStatus(data.id);
    
    // Update recent searches
    loadRecentSearches();
}

// Random word
async function getRandomWord() {
    try {
        const response = await fetch('api.php?action=randomWord');
        const data = await response.json();
        
        if (data.success) {
            searchWord(data.word);
        }
    } catch (error) {
        console.error('Error getting random word:', error);
    }
}

// Load random words for discovery
async function loadRandomWords() {
    try {
        const response = await fetch('api.php?action=randomWords&count=12');
        const data = await response.json();
        
        if (data.success) {
            const grid = document.getElementById('randomWordsGrid');
            grid.innerHTML = data.words.map(word => `
                <div class="word-card" data-word="${escapeHtml(word)}">
                    <span class="word-text">${escapeHtml(word)}</span>
                </div>
            `).join('');
            
            // Add click event to each card
            grid.querySelectorAll('.word-card').forEach(card => {
                card.addEventListener('click', function() {
                    searchWord(this.dataset.word);
                });
            });
        }
    } catch (error) {
        console.error('Error loading random words:', error);
    }
}

// Favorites functionality
async function toggleFavorite() {
    if (!currentWordId) return;
    
    const favoriteBtn = document.getElementById('favoriteBtn');
    const isFavorite = favoriteBtn.classList.contains('active');
    
    try {
        const action = isFavorite ? 'removeFavorite' : 'addFavorite';
        const formData = new FormData();
        formData.append('action', action);
        formData.append('wordId', currentWordId);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            favoriteBtn.classList.toggle('active');
            const svg = favoriteBtn.querySelector('svg path');
            if (!isFavorite) {
                svg.setAttribute('fill', 'currentColor');
                showNotification('Added to favorites', 'success');
            } else {
                svg.setAttribute('fill', 'none');
                showNotification('Removed from favorites', 'success');
            }
            loadFavorites();
            updateStats();
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

async function checkFavoriteStatus(wordId) {
    try {
        const response = await fetch(`api.php?action=checkFavorite&wordId=${wordId}`);
        const data = await response.json();
        
        const favoriteBtn = document.getElementById('favoriteBtn');
        const svg = favoriteBtn.querySelector('svg path');
        
        if (data.isFavorite) {
            favoriteBtn.classList.add('active');
            svg.setAttribute('fill', 'currentColor');
        } else {
            favoriteBtn.classList.remove('active');
            svg.setAttribute('fill', 'none');
        }
    } catch (error) {
        console.error('Error checking favorite status:', error);
    }
}

async function loadFavorites() {
    try {
        const response = await fetch('api.php?action=getFavorites');
        const data = await response.json();
        
        const favoritesGrid = document.getElementById('favoriteWords');
        
        if (data.success && data.favorites.length > 0) {
            favoritesGrid.innerHTML = data.favorites.map(item => `
                <div class="word-card" data-word="${escapeHtml(item.word)}">
                    <span class="word-text">${escapeHtml(item.word)}</span>
                </div>
            `).join('');
            
            // Add click events
            favoritesGrid.querySelectorAll('.word-card').forEach(card => {
                card.addEventListener('click', function() {
                    searchWord(this.dataset.word);
                });
            });
        } else {
            favoritesGrid.innerHTML = '<p class="empty-state">Your favorite words will appear here</p>';
        }
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
}

// Recent searches
async function loadRecentSearches() {
    try {
        const response = await fetch('api.php?action=recentSearches');
        const data = await response.json();
        
        const recentGrid = document.getElementById('recentWords');
        
        if (data.success && data.words.length > 0) {
            recentGrid.innerHTML = data.words.map(word => `
                <div class="word-card" data-word="${escapeHtml(word)}">
                    <span class="word-text">${escapeHtml(word)}</span>
                </div>
            `).join('');
            
            // Add click events
            recentGrid.querySelectorAll('.word-card').forEach(card => {
                card.addEventListener('click', function() {
                    searchWord(this.dataset.word);
                });
            });
        } else {
            recentGrid.innerHTML = '<p class="empty-state">Your recent searches will appear here</p>';
        }
    } catch (error) {
        console.error('Error loading recent searches:', error);
    }
}

// Local storage for recent searches
function saveToLocalRecent(word) {
    let recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
    recent = recent.filter(w => w !== word);
    recent.unshift(word);
    recent = recent.slice(0, 20);
    localStorage.setItem('recentSearches', JSON.stringify(recent));
}

function displayLocalRecentSearches() {
    const recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
    if (recent.length === 0) return;
    
    // This will be overwritten by loadRecentSearches, but provides immediate feedback
}

// Text-to-speech
function speakWord() {
    const word = document.getElementById('resultWord').textContent;
    
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(word);
        utterance.lang = 'en-US';
        utterance.rate = 0.8;
        speechSynthesis.speak(utterance);
        
        showNotification('Speaking word...', 'success');
    } else {
        showNotification('Speech synthesis not supported', 'error');
    }
}

// Share word
function shareWord() {
    const word = document.getElementById('resultWord').textContent;
    const definition = document.getElementById('resultDefinition').textContent;
    const text = `${word}: ${definition.substring(0, 100)}...`;
    
    if (navigator.share) {
        navigator.share({
            title: `Word: ${word}`,
            text: text,
            url: window.location.href
        }).then(() => {
            showNotification('Shared successfully', 'success');
        }).catch(() => {
            copyToClipboard(text);
        });
    } else {
        copyToClipboard(text);
    }
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard', 'success');
        });
    } else {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('Copied to clipboard', 'success');
    }
}

// Update statistics
async function updateStats() {
    try {
        const response = await fetch('api.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalWordsCount').textContent = formatNumber(data.totalWords);
            document.getElementById('totalSearchesCount').textContent = formatNumber(data.totalSearches);
            document.getElementById('todaySearchesCount').textContent = formatNumber(data.todaySearches);
            document.getElementById('favoritesCount').textContent = formatNumber(data.favoritesCount);
        }
    } catch (error) {
        console.error('Error updating stats:', error);
    }
}

// Tab switching
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tabName}Tab`).classList.add('active');
    
    // Load content based on tab
    if (tabName === 'recent') {
        loadRecentSearches();
    } else if (tabName === 'favorites') {
        loadFavorites();
    } else if (tabName === 'random') {
        if (document.getElementById('randomWordsGrid').children.length === 0) {
            loadRandomWords();
        }
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
    const diffMinutes = Math.floor(diffTime / (1000 * 60));
    
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString();
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    
    const colors = {
        success: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        error: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        info: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    };
    
    const icons = {
        success: '✓',
        error: '✕',
        info: 'ℹ'
    };
    
    notification.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        padding: 1.25rem 1.75rem;
        background: ${colors[type]};
        color: white;
        border-radius: 16px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25);
        z-index: 10000;
        animation: slideInNotification 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        max-width: 400px;
        backdrop-filter: blur(10px);
    `;
    
    notification.innerHTML = `
        <span style="
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            font-size: 0.875rem;
            flex-shrink: 0;
        ">${icons[type]}</span>
        <span>${message}</span>
    `;
    
    // Add animation
    if (!document.getElementById('notificationStyle')) {
        const style = document.createElement('style');
        style.id = 'notificationStyle';
        style.textContent = `
            @keyframes slideInNotification {
                from {
                    transform: translateX(450px) scale(0.8);
                    opacity: 0;
                }
                to {
                    transform: translateX(0) scale(1);
                    opacity: 1;
                }
            }
            @keyframes slideOutNotification {
                to {
                    transform: translateX(450px) scale(0.8);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Remove after 3.5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutNotification 0.3s cubic-bezier(0.4, 0, 1, 1)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3500);
}

// Popular word cards click handler
document.addEventListener('click', function(e) {
    const wordCard = e.target.closest('.word-card');
    if (wordCard && wordCard.dataset.word) {
        searchWord(wordCard.dataset.word);
    }
});

// Auto-load random words on page load
setTimeout(() => {
    loadRandomWords();
}, 1000);

// Welcome animation on first visit
function addWelcomeAnimation() {
    // Check if this is the first visit
    const hasVisited = localStorage.getItem('hasVisitedDictionary');
    
    if (!hasVisited) {
        // Add subtle welcome effect
        const searchInput = document.getElementById('searchInput');
        setTimeout(() => {
            searchInput.focus();
            searchInput.placeholder = 'Try searching for "serendipity"...';
            
            setTimeout(() => {
                searchInput.placeholder = 'Type a word to search...';
            }, 3000);
        }, 500);
        
        localStorage.setItem('hasVisitedDictionary', 'true');
    }
    
    // Add parallax effect to cards on scroll
    if (window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const cards = document.querySelectorAll('.word-card, .stat-card');
            cards.forEach((card, index) => {
                const speed = 0.5 + (index % 3) * 0.1;
                const offset = scrolled * speed * 0.05;
                if (scrolled < window.innerHeight * 2) {
                    card.style.transform = `translateY(${offset}px)`;
                }
            });
        });
    }
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    
    // Escape to clear search and hide suggestions
    if (e.key === 'Escape') {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = '';
        searchInput.blur();
        hideSuggestions();
    }
    
    // Ctrl/Cmd + R for random word
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        getRandomWord();
    }
});

// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Performance optimization: Lazy load images if any are added later
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}


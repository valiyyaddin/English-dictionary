<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$conn = getDBConnection();

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

switch ($action) {
    
    case 'search':
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 1) {
            echo json_encode(['success' => false, 'message' => 'Query too short']);
            break;
        }
        
        $stmt = $conn->prepare("
            SELECT id, word, definition 
            FROM words 
            WHERE word LIKE :searchTerm 
            ORDER BY word ASC 
            LIMIT 10
        ");
        $searchTerm = $query . '%';
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        
        $suggestions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $suggestions[] = [
                'id' => $row['id'],
                'word' => $row['word'],
                'definition' => substr($row['definition'], 0, 100) . '...'
            ];
        }
        
        echo json_encode(['success' => true, 'suggestions' => $suggestions]);
        break;
    
    case 'getWord':
        $word = $_GET['word'] ?? '';
        
        if (empty($word)) {
            echo json_encode(['success' => false, 'message' => 'Word not specified']);
            break;
        }
        
        $stmt = $conn->prepare("SELECT id, word, definition FROM words WHERE word = :word LIMIT 1");
        $stmt->bindParam(':word', $word, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $wordId = $row['id'];
            
            // Update search statistics
            $conn->exec("
                INSERT INTO search_stats (word_id, search_count) 
                VALUES ($wordId, 1) 
                ON DUPLICATE KEY UPDATE 
                    search_count = search_count + 1,
                    last_searched = CURRENT_TIMESTAMP
            ");
            
            // Add to search history
            $ip = getUserIP();
            $stmtHistory = $conn->prepare("INSERT INTO search_history (word_id, ip_address) VALUES (:wordId, :ip)");
            $stmtHistory->bindParam(':wordId', $wordId, PDO::PARAM_INT);
            $stmtHistory->bindParam(':ip', $ip, PDO::PARAM_STR);
            $stmtHistory->execute();
            
            // Get search stats
            $statsStmt = $conn->prepare("SELECT search_count, last_searched FROM search_stats WHERE word_id = :wordId");
            $statsStmt->bindParam(':wordId', $wordId, PDO::PARAM_INT);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'word' => $row['word'],
                'definition' => $row['definition'],
                'id' => $row['id'],
                'searchCount' => $stats['search_count'],
                'lastSearched' => $stats['last_searched']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Word not found']);
        }
        break;
    
    case 'randomWord':
        $stmt = $conn->query("SELECT id, word, definition FROM words ORDER BY RAND() LIMIT 1");
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => true,
                'word' => $row['word'],
                'definition' => $row['definition'],
                'id' => $row['id']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No words found']);
        }
        break;
    
    case 'randomWords':
        $count = intval($_GET['count'] ?? 6);
        $stmt = $conn->query("SELECT word FROM words ORDER BY RAND() LIMIT $count");
        
        $words = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $words[] = $row['word'];
        }
        
        echo json_encode(['success' => true, 'words' => $words]);
        break;
    
    case 'addFavorite':
        $wordId = $_POST['wordId'] ?? 0;
        $sessionId = session_id();
        
        if (empty($wordId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid word ID']);
            break;
        }
        
        $stmt = $conn->prepare("
            INSERT IGNORE INTO favorites (word_id, session_id) 
            VALUES (:wordId, :sessionId)
        ");
        $stmt->bindParam(':wordId', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':sessionId', $sessionId, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Added to favorites']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Already in favorites']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add favorite']);
        }
        break;
    
    case 'removeFavorite':
        $wordId = $_POST['wordId'] ?? 0;
        $sessionId = session_id();
        
        if (empty($wordId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid word ID']);
            break;
        }
        
        $stmt = $conn->prepare("DELETE FROM favorites WHERE word_id = :wordId AND session_id = :sessionId");
        $stmt->bindParam(':wordId', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':sessionId', $sessionId, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove favorite']);
        }
        break;
    
    case 'getFavorites':
        $sessionId = session_id();
        
        $stmt = $conn->prepare("
            SELECT w.id, w.word, w.definition 
            FROM words w
            INNER JOIN favorites f ON w.id = f.word_id
            WHERE f.session_id = :sessionId
            ORDER BY f.created_at DESC
        ");
        $stmt->bindParam(':sessionId', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        
        $favorites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $favorites[] = [
                'id' => $row['id'],
                'word' => $row['word'],
                'definition' => substr($row['definition'], 0, 100) . '...'
            ];
        }
        
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        break;
    
    case 'checkFavorite':
        $wordId = $_GET['wordId'] ?? 0;
        $sessionId = session_id();
        
        $stmt = $conn->prepare("SELECT id FROM favorites WHERE word_id = :wordId AND session_id = :sessionId");
        $stmt->bindParam(':wordId', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':sessionId', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'isFavorite' => $stmt->rowCount() > 0]);
        break;
    
    case 'recentSearches':
        $stmt = $conn->query("
            SELECT DISTINCT w.word, MAX(h.searched_at) as last_search
            FROM words w
            INNER JOIN search_history h ON w.id = h.word_id
            GROUP BY w.word
            ORDER BY last_search DESC
            LIMIT 20
        ");
        
        $recent = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent[] = $row['word'];
        }
        
        echo json_encode(['success' => true, 'words' => $recent]);
        break;
    
    case 'stats':
        // Total searches
        $stmt = $conn->query("SELECT COUNT(*) as total FROM search_history");
        $totalSearches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Today's searches
        $stmt = $conn->query("SELECT COUNT(*) as total FROM search_history WHERE DATE(searched_at) = CURDATE()");
        $todaySearches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total favorites for this session
        $sessionId = session_id();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM favorites WHERE session_id = :sessionId");
        $stmt->bindParam(':sessionId', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        $favoritesCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total words
        $stmt = $conn->query("SELECT COUNT(*) as total FROM words");
        $totalWords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'totalSearches' => $totalSearches,
            'todaySearches' => $todaySearches,
            'favoritesCount' => $favoritesCount,
            'totalWords' => $totalWords
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// PDO doesn't need explicit close
$conn = null;
?>

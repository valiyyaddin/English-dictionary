import csv
import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def create_database_connection():
    """Create a database connection to MySQL server"""
    try:
        connection = mysql.connector.connect(
            host=os.getenv('DB_HOST', 'localhost'),
            user=os.getenv('DB_USER', 'root'),
            password=os.getenv('DB_PASSWORD', ''),
            port=os.getenv('DB_PORT', '3306')
        )
        
        if connection.is_connected():
            cursor = connection.cursor()
            
            # Create database if it doesn't exist
            db_name = os.getenv('DB_NAME', 'english_dictionary')
            cursor.execute(f"CREATE DATABASE IF NOT EXISTS {db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
            cursor.execute(f"USE {db_name}")
            
            print(f"Successfully connected to MySQL and using database: {db_name}")
            return connection
            
    except Error as e:
        print(f"Error while connecting to MySQL: {e}")
        return None

def create_tables(connection):
    """Create necessary tables with indexes"""
    try:
        cursor = connection.cursor()
        
        # Create words table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS words (
                id INT AUTO_INCREMENT PRIMARY KEY,
                word VARCHAR(255) NOT NULL,
                definition TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_word (word),
                FULLTEXT INDEX idx_fulltext_word (word),
                FULLTEXT INDEX idx_fulltext_definition (definition)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)
        
        # Create search statistics table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS search_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                word_id INT,
                search_count INT DEFAULT 0,
                last_searched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
                UNIQUE INDEX idx_word_id (word_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)
        
        # Create search history table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS search_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                word_id INT,
                searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
                INDEX idx_searched_at (searched_at),
                INDEX idx_word_id (word_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)
        
        connection.commit()
        print("Tables created successfully")
        
    except Error as e:
        print(f"Error creating tables: {e}")

def import_csv_data(connection, csv_file):
    """Import data from CSV file to database"""
    try:
        cursor = connection.cursor()
        
        # Check if data already exists
        cursor.execute("SELECT COUNT(*) FROM words")
        count = cursor.fetchone()[0]
        
        if count > 0:
            print(f"Database already contains {count} words. Skipping import.")
            response = input("Do you want to clear existing data and re-import? (yes/no): ")
            if response.lower() != 'yes':
                return
            cursor.execute("DELETE FROM words")
            connection.commit()
            print("Existing data cleared.")
        
        print(f"Starting import from {csv_file}...")
        
        with open(csv_file, 'r', encoding='utf-8') as file:
            csv_reader = csv.DictReader(file)
            
            batch_size = 1000
            batch = []
            total_imported = 0
            
            for row in csv_reader:
                word = row['word'].strip()
                definition = row['definition'].strip()
                
                if word and definition:
                    batch.append((word, definition))
                    
                    if len(batch) >= batch_size:
                        cursor.executemany(
                            "INSERT INTO words (word, definition) VALUES (%s, %s)",
                            batch
                        )
                        connection.commit()
                        total_imported += len(batch)
                        print(f"Imported {total_imported} words...")
                        batch = []
            
            # Insert remaining records
            if batch:
                cursor.executemany(
                    "INSERT INTO words (word, definition) VALUES (%s, %s)",
                    batch
                )
                connection.commit()
                total_imported += len(batch)
            
            print(f"\nImport completed! Total words imported: {total_imported}")
            
    except Error as e:
        print(f"Error importing data: {e}")
    except FileNotFoundError:
        print(f"Error: File '{csv_file}' not found")

def main():
    # Connect to database
    connection = create_database_connection()
    
    if connection:
        # Create tables
        create_tables(connection)
        
        # Import CSV data
        csv_file = 'dictionary.csv'
        import_csv_data(connection, csv_file)
        
        # Close connection
        if connection.is_connected():
            connection.close()
            print("\nMySQL connection closed")

if __name__ == "__main__":
    main()


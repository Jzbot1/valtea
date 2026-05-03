  <?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    
    echo "Fixing database schema...<br>";
    
    // Disable foreign key checks temporarily
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // If there's a category with ID 0, update it to 1 (if 1 doesn't exist) or just delete it
    // since this is likely a failed sync.
    $db->exec("DELETE FROM categories WHERE id = 0");
    echo "Removed invalid category entries with ID 0.<br>";
    
    // Fix categories table
    $db->exec("ALTER TABLE categories MODIFY id INT AUTO_INCREMENT");
    echo "Successfully added AUTO_INCREMENT to categories table.<br>";
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<br><b>Database fix complete!</b> You can now delete this file and try syncing again.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

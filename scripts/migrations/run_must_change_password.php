<?php
require_once __DIR__ . '/../../api/config/connection-pdo.php';

echo "Running Must_Change_Password migration...\n\n";

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tbl_user LIKE 'Must_Change_Password'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'Must_Change_Password' already exists. Skipping migration.\n";
        exit(0);
    }

    // Add the column
    echo "Adding Must_Change_Password column...\n";
    $pdo->exec("ALTER TABLE `tbl_user` 
                ADD COLUMN `Must_Change_Password` tinyint(1) NOT NULL DEFAULT 0 
                AFTER `Academic_Number`");
    echo "✓ Column added successfully\n\n";

    // Set Must_Change_Password = 1 for all Examinee accounts
    echo "Setting Must_Change_Password = 1 for Examinee accounts...\n";
    $stmt = $pdo->exec("UPDATE `tbl_user` u
                        INNER JOIN `tbl_role` r ON u.Role_ID = r.Role_ID
                        SET u.Must_Change_Password = 1
                        WHERE r.Role_Name = 'Examinee'");
    echo "✓ Updated $stmt Examinee account(s)\n\n";

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

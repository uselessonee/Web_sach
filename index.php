<?php

$databaseFile = './DB/user.sqlite';
try {
    // Create a new PDO instance for SQLite
    $pdo = new PDO("sqlite:" . $databaseFile);

    // Set error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to the SQLite database successfully!<br>";

        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL
        );
    ";
    $pdo->exec($createTableSQL);
    echo "Table 'users' checked/created successfully!<br>";

    // 4. Insert Sample User Data
    // We'll insert a few sample users.
    // Always hash passwords before storing them! password_hash() is recommended.
    $usersToInsert = [
        ['username' => 'admin', 'password' => password_hash('password123', PASSWORD_DEFAULT)],
        ['username' => 'john.doe', 'password' => password_hash('securepass', PASSWORD_DEFAULT)],
        ['username' => 'jane.smith', 'password' => password_hash('mysecret', PASSWORD_DEFAULT)]
    ];

    $insertSQL = "INSERT INTO users (username, password) VALUES (:username, :password)";
    $stmt = $pdo->prepare($insertSQL);

    $insertedCount = 0;
    foreach ($usersToInsert as $user) {
        try {
            $stmt->bindParam(':username', $user['username']);
            $stmt->bindParam(':password', $user['password']);
            $stmt->execute();
            $insertedCount++;
        } catch (PDOException $e) {
            // Catch unique constraint violations for username
            if ($e->getCode() == '23000') { // SQLite's unique constraint violation code
                echo "Warning: User '{$user['username']}' already exists.<br>";
            } else {
                throw $e; // Re-throw other unexpected errors
            }
        }
    }
    echo "Attempted to insert {$insertedCount} new users (or found existing ones).<br>";

    // 5. Verify Data (Optional: Select and display data)
    echo "<br>Current Users in Database:<br>";
    $selectSQL = "SELECT id, username FROM users"; // Don't select passwords for display!
    $stmt = $pdo->query($selectSQL);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($users)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No users found in the database.";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
// Close the PDO connection
$pdo = null;

?>
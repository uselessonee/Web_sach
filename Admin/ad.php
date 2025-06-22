<?php

$databaseFile = '../DB/Database.db'; 

$message = ''; 

try {
    $pdo = new PDO("sqlite:$databaseFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['genres'])) {
        $genresToUpdate = $_POST['genres'];
        $updatedCount = 0;

        $pdo->beginTransaction(); 
        try {
            $stmt = $pdo->prepare("UPDATE books SET genre = :genre WHERE id = :id");

            foreach ($genresToUpdate as $bookId => $genreValue) {
                $cleanedGenre = trim($genreValue);

                $stmt->bindValue(':genre', $cleanedGenre, PDO::PARAM_STR);
                $stmt->bindValue(':id', $bookId, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $updatedCount++;
                }
            }

            $pdo->commit(); 
            $message = "<div class='success-message'>Successfully updated $updatedCount genre(s)!</div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='error-message'>Error updating genres: " . $e->getMessage() . "</div>";
        }
    }
    $stmt = $pdo->query("SELECT id, title, author, genre FROM books ORDER BY title ASC");
    $books = $stmt->fetchAll();

} catch (PDOException $e) {

    $message = "<div class='error-message'>Database error: " . $e->getMessage() . "<br>Please ensure 'books.sqlite' exists and is writable.</div>";
    $books = []; 
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Book Genres</title>
    <link rel="icon" href="../images/logo/book.png" type="image/x-icon">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0056b3;
            text-align: center;
            margin-bottom: 30px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        input[type="text"] {
            width: calc(100% - 16px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in the element's total width and height */
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Book Genres</h1>

        <?php echo $message; // Display messages here ?>

        <form method="POST" action="">
            <?php if (!empty($books)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Genre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['id']); ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td>
                                    <input
                                        type="text"
                                        name="genres[<?php echo htmlspecialchars($book['id']); ?>]"
                                        value="<?php echo htmlspecialchars($book['genre'] ?? ''); ?>"
                                        placeholder="Enter genre"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="form-actions">
                    <button type="submit">Update Genres</button>
                </div>
            <?php else: ?>
                <p style="text-align: center; margin-top: 40px;">No books found in the database. Please add some books first.</p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
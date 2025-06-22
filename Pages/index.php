<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> Đọc sách online</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" href="../images/book.png" type="image/x-icon">

</head>

<body>
  <header id="header-placeholder">
    <?php include '../Elements/header.php'; ?>
  </header>

  <section class="trangchu" id="trangchu">
    <div class="gth-container">
      <div class="gth">
        <h3>KHÔNG GIAN SÁCH</h3>
        <p>Chào mừng bạn đến với thế giới của tri thức và tưởng tượng, nơi những trang sách mở ra hành trình không giới
          hạn!</p>
      </div>
    </div>
    <div class="book-slider">
      <button class="prev">&#10094;</button>

<div class="book-track">
    <?php
    // Bao gồm file kết nối database
    require_once '../Elements/connect.php'; // Điều chỉnh đường dẫn nếu cần

    $pdo = connect_db();

    try {
        // Truy vấn để lấy 3 cuốn sách có published_date mới nhất
        // Lưu ý: published_date được lưu dưới dạng TEXT, nên cần định dạng chuẩn (YYYY-MM-DD)
        // để ORDER BY hoạt động chính xác.
        $stmt = $pdo->prepare("SELECT title, cover_image_url FROM books ORDER BY published_date DESC LIMIT 3");
        $stmt->execute();
        $latest_books = $stmt->fetchAll();

        if (count($latest_books) > 0) {
            foreach ($latest_books as $book) {
                // Đảm bảo đường dẫn hình ảnh và alt text an toàn
                echo '<img src="../Admin/' . htmlspecialchars($book['cover_image_url']) . '" alt="' . htmlspecialchars($book['title']) . '">';
            }
        } else {
            // Hiển thị hình ảnh mặc định hoặc thông báo nếu không có sách
            echo '<p>Không tìm thấy sách mới nhất.</p>';
        }
    } catch (PDOException $e) {
        echo "<p>Lỗi khi tải sách mới nhất: " . $e->getMessage() . "</p>";
    }
    //SẴN LÀM LUÔN PHẦN NÀY

    // --- Lấy 8 cuốn sách "Nổi bật" (ngẫu nhiên) ---
    $featured_books = [];
    try {
        // SQLite sử dụng RANDOM(), MySQL sử dụng RAND()
        $stmt_featured = $pdo->prepare("SELECT * FROM books ORDER BY RANDOM() LIMIT 8");
        $stmt_featured->execute();
        $featured_books = $stmt_featured->fetchAll();
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn sách nổi bật: " . $e->getMessage());
        // Có thể hiển thị thông báo lỗi thân thiện với người dùng hoặc log lỗi
    }

    // --- Lấy 8 cuốn sách "Mới nhất" ---
    $latest_books = [];
    try {
        // Sắp xếp theo published_date giảm dần để lấy các ngày gần nhất
        $stmt_latest = $pdo->prepare("SELECT * FROM books ORDER BY published_date DESC LIMIT 8");
        $stmt_latest->execute();
        $latest_books = $stmt_latest->fetchAll();
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn sách mới nhất: " . $e->getMessage());
        // Có thể hiển thị thông báo lỗi thân thiện với người dùng hoặc log lỗi
    }


    ?>
</div>
<button class="next">&#10095;</button>
    </div>
  </section>
  <script>
    // JavaScript cho book slider
    // Lấy các phần tử cần thiết
    const track = document.querySelector('.book-track');
    const prevBtn = document.querySelector('.prev');
    const nextBtn = document.querySelector('.next');
    let index = 0;
    // eventlistener cho next button
    nextBtn.onclick = () => {
      if (index < track.children.length - 1) {
        index++; // tăng phần tử slider
        track.style.transform = `translateX(-${index * 100}%)`;
      }
    };
    // eventlistener cho previous button
    prevBtn.onclick = () => {
      if (index > 0) {
        index--;
        track.style.transform = `translateX(-${index * 100}%)`;
      }
    };
  </script>

  <p style="font-size: 2.5rem; margin-left: 5rem; font-weight: bold; color: #000000;">Nổi bật</p>
 <div class="featured-books" id="featuredBooksContainer">
        <?php
        if (count($featured_books) > 0) {
            foreach ($featured_books as $book) {
                ?>
                <div class="book-item">
                    <a href="../Pages/Read.php?id=<?php echo htmlspecialchars($book['id']); ?>">
                        <img src="../Admin/<?php echo htmlspecialchars($book['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </a>
                    <a href="<?php echo htmlspecialchars($book['read_link']); ?>">
                        <div class="book-info">
                            <div class="book-title">Tác phẩm: <?php echo htmlspecialchars($book['title']); ?></div>
                            <div class="book-title">Tác giả: <?php echo htmlspecialchars($book['author']); ?></div>
                            <div class="book-title">Năm xuất bản: <?php echo htmlspecialchars($book['published_date']); ?></div>
                            </div>
                    </a>
                </div>
                <?php
            }
        } else {
            echo "<p style='font-size: 2rem; margin-left: 5rem; color: #ccc;'>Không tìm thấy sách nổi bật nào.</p>";
        }
        ?>
    </div>

    <p style="font-size: 2.5rem; margin-left: 5rem; font-weight: bold; color: #000000;">Mới nhất</p>

    <div class="featured-books" id="latestBooksContainer">
        <?php
        if (count($latest_books) > 0) {
            foreach ($latest_books as $book) {
                ?>
                <div class="book-item">
                    <a href="<?php echo htmlspecialchars($book['read_link']); ?>">
                        <img src="../Admin/<?php echo htmlspecialchars($book['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </a>
                    <a href="<?php echo htmlspecialchars($book['read_link']); ?>">
                        <div class="book-info">
                            <div class="book-title">Tác phẩm: <?php echo htmlspecialchars($book['title']); ?></div>
                            <div class="book-title">Tác giả: <?php echo htmlspecialchars($book['author']); ?></div>
                            <div class="book-title">Năm xuất bản: <?php echo htmlspecialchars($book['published_date']); ?></div>
                            <div class="book-title">Thể loại: <?php echo htmlspecialchars($book['genre']); ?></div>
                            </div>
                    </a>
                </div>
                <?php
            }
        } else {
            echo "<p style='font-size: 2rem; margin-left: 5rem; color: #ccc;'>Không tìm thấy sách mới nhất nào.</p>";
        }
        ?>
    </div>
  
</body>
<footer id="footer-placeholder">
  <?php include '../Elements/footer.html'; ?>
</footer>

</html>
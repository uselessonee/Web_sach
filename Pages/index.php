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
        <img src="../images/tatden.jpeg" alt="Sách 1">
        <img src="../images/Trong Họa Có Phúc.jpeg" alt="Sách 2">
        <img src="../images/Tiếng Chim Hót Trong Bụi Mận Gai.jpg" alt="Sách 3">
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

  </div>

  <p style="font-size: 2.5rem; margin-left: 5rem; font-weight: bold; color: #000000;">Mới nhất</p>

  <div class="featured-books" id="latestBooksContainer">

  </div>
  
</body>
<footer id="footer-placeholder">
  <?php include '../Elements/footer.html'; ?>
</footer>

</html>
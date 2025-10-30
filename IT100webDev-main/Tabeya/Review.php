<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only approved reviews
$sql = "SELECT * FROM review_table WHERE status = 'Approved' ORDER BY datetime DESC";
$reviews_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews</title>
    <link rel="stylesheet" href="CSS/ReviewDesign.css">
    <script></script>
</head>
<body>
    <!-- Header section -->
    <header>
        <div class="logo">
            <img src="Photo/Tabeya Name.png" alt="Logo">
        </div>
        <nav>
            <a href="index.html">HOME</a>
            <a href="Menu.html">MENU</a>
            <a href="CaterReservation.html">CATER RESERVATION</a>
            <a href="GALLERY.html">GALLERY</a>
            <a href="Review.php"class="active">TESTIMONY</a>
            <a href="About.html">ABOUT</a>
        </nav>
    </header>
    <!-- Main content section -->
    <section class="background">
    <img src="Photo/background.jpg" alt="Background">
        <div class="rectangle-panel">
            <div class="review-header">
                <h1>Customer Reviews</h1>
                <button id="write-review-btn" class="btn">Write a Review</button>
            </div>

            <div id="overall-rating-section" class="overall-rating">
                <h2 id="overall-rating-value">0.0</h2>
                <div class="star-rating" id="overall-star-rating">
                    <span>☆</span>
                    <span>☆</span>
                    <span>☆</span>
                    <span>☆</span>
                    <span>☆</span>
                </div>
                <div class="total-reviews">
                    Based on <span id="total-reviews-count">0</span> Reviews
                </div>
            </div>
            <!-- Review progress section -->
            <div id="review-progress-section" class="review-progress">
                <div class="progress-row">
                    <div class="progress-label">5 Stars</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="five-star-progress" style="width: 0%"></div>
                    </div>
                </div>
                <div class="progress-row">
                    <div class="progress-label">4 Stars</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="four-star-progress" style="width: 0%"></div>
                    </div>
                </div>
                <div class="progress-row">
                    <div class="progress-label">3 Stars</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="three-star-progress" style="width: 0%"></div>
                    </div>
                </div>
                <div class="progress-row">
                    <div class="progress-label">2 Stars</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="two-star-progress" style="width: 0%"></div>
                    </div>
                </div>
                <div class="progress-row">
                    <div class="progress-label">1 Star</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="one-star-progress" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- User reviews section -->
            <div id="reviews-container" class="user-reviews">
                <?php 
                if ($reviews_result->num_rows > 0) {
                    while ($review = $reviews_result->fetch_assoc()) {
                        echo "<div class='user-review'>";
                        echo "<div class='review-header'>";
                        echo "<h3>" . htmlspecialchars($review['user_name']) . "</h3>";
                        echo "<div class='review-rating'>";
                        
                        // Generate star rating
                        for ($i = 1; $i <= 5; $i++) {
                            $active = ($i <= $review['user_rating']) ? 'active' : '';
                            echo "<span class='star $active'>★</span>";
                        }
                        
                        echo "</div>";
                        echo "</div>";
                        echo "<p>" . htmlspecialchars($review['user_review']) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No reviews yet. Be the first to write a review!</p>";
                }
                ?>
            </div>
        </div>
    </section>
    <!-- Review modal -->
    <div id="review-modal" class="review-modal">
        <div class="review-modal-content">
            <h2>Write a Review</h2>
            <div class="rating-stars">
                <span class="rating-star">★</span>
                <span class="rating-star">★</span>
                <span class="rating-star">★</span>
                <span class="rating-star">★</span>
                <span class="rating-star">★</span>
            </div>
            <input type="text" id="username-input" class="review-input" placeholder="Your Name">
            <textarea id="review-text-input" class="review-input" placeholder="Write your review"></textarea>
            <div>
                <button id="submit-review-btn" class="submit-review-btn">Submit Review</button>
                <button id="close-modal-btn">Cancel</button>
            </div>
        </div>
    </div>
    <!-- Footer section -->
    <footer>
            <div class="contact-section">
                <div class="container">
                    <div class="contact-info">
                        <h2>Contact Us</h2>
                        <p>Have any questions? We'd love to hear from you.</p>
                        <div class="info-group">
                            <div class="info-item visit-us">
                                <img src="Photo/VisitUs.png" alt="Location Icon" class="icon">
                                <div>
                                    <strong>Visit us</strong>
                                    <p>Poblacion 2, Vinzons Avenue,<br>Vinzons, Camarines Norte</p>
                                </div>
                            </div>
                            <div class="info-item call-us">
                                <img src="Photo/Selpon.png" alt="Phone Icon" class="icon">
                                <div>
                                    <strong>Call us</strong>
                                    <p>09380839641</p>
                                </div>
                            </div>
                            <div class="info-item connect-us">
                                <img src="Photo/Facebook.png" alt="Facebook Icon" class="icon">
                                <div>
                                    <strong>Connect to us</strong>
                                    <a href="https://www.facebook.com/profile.php?id=100063540027038" target="_blank" class="no-underline">Tabeya, VCN</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

    <script src="rating.js"></script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?>
document.addEventListener('DOMContentLoaded', () => {
    const writeReviewBtn = document.getElementById('write-review-btn');
    const reviewModal = document.getElementById('review-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const submitReviewBtn = document.getElementById('submit-review-btn');
    const reviewsContainer = document.getElementById('reviews-container');
    const ratingStars = document.querySelectorAll('.rating-star');
    const overallRatingSection = document.getElementById('overall-rating-section');
    const reviewProgressSection = document.getElementById('review-progress-section');
    const overallStarRating = document.getElementById('overall-star-rating');
    const overallRatingValue = document.getElementById('overall-rating-value');
    const totalReviewsCount = document.getElementById('total-reviews-count');
    let selectedRating = 0;
    // Fetch and update approved reviews
    function fetchApprovedReviews() {
        fetch('fetch_reviews.php')
            .then(response => response.json())
            .then(reviews => {
                // Update reviews container
                updateReviewsContainer(reviews);
                
                // Calculate and update overall rating
                updateOverallRating(reviews);
            })
            .catch(error => {
                console.error('Error fetching reviews:', error);
            });
    }

    function updateReviewsContainer(reviews) {
        const reviewsContainer = document.getElementById('reviews-container');
        
        // Clear existing reviews
        reviewsContainer.innerHTML = reviews.length > 0 
            ? '' 
            : '<p>No reviews yet. Be the first to write a review!</p>';

        // Add approved reviews
        reviews.forEach(review => {
            const reviewElement = document.createElement('div');
            reviewElement.classList.add('user-review');
            
            // Generate star rating HTML
            const starRatingHTML = Array(5).fill().map((_, index) => 
                `<span class="star ${index < review.user_rating ? 'active' : ''}">★</span>`
            ).join('');

            reviewElement.innerHTML = `
                <div class="review-header">
                    <h3>${escapeHtml(review.user_name)}</h3>
                    <div class="review-rating">
                        ${starRatingHTML}
                    </div>
                </div>
                <p>${escapeHtml(review.user_review)}</p>
            `;

            reviewsContainer.appendChild(reviewElement);
        });
    }

    // Escape HTML to prevent XSS
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function updateOverallRating(reviews) {
        // Handle empty reviews
        if (reviews.length === 0) {
            overallRatingValue.textContent = '0.0';
            totalReviewsCount.textContent = '0';
            overallStarRating.innerHTML = '☆☆☆☆☆';
            resetProgressBars();
            return;
        }
    
        // Calculate weighted sum with proper type conversion
        const weightedSum = reviews.reduce((sum, review) => sum + Number(review.user_rating), 0);
        const overallRating = weightedSum / reviews.length;
    
        // Format the rating to one decimal place
        const formattedRating = overallRating.toFixed(1);
    
        // Update UI with overall rating
        overallRatingValue.textContent = formattedRating;
        totalReviewsCount.textContent = reviews.length;
    
        // Update stars
        overallStarRating.innerHTML = generateOverallStarRating(overallRating);
    
        // Star distribution and progress bars
        const starDistribution = calculateStarDistribution(reviews);
        updateProgressBars(starDistribution, reviews.length);
    }
    
    function generateOverallStarRating(rating) {
        let starHtml = '';
        
        for (let i = 1; i <= 5; i++) {
            if (rating >= i) {
                // Full star
                starHtml += '<span class="star active">★</span>';
            } else if (rating > i - 1) {
                // Half star - add both active and half-star classes
                starHtml += '<span class="star active half-star">★</span>';
            } else {
                // Empty star
                starHtml += '<span class="star">★</span>';
            }
        }
        
        return starHtml;
    }

    // Calculate star distribution and update progress bars
    function calculateStarDistribution(reviews) {
        const distribution = {
            5: 0, 4: 0, 3: 0, 2: 0, 1: 0
        };

        reviews.forEach(review => {
            distribution[review.user_rating]++;
        });

        return distribution;
    }
    // Update progress bars
    function updateProgressBars(distribution, totalReviews) {
        const progressBars = {
            5: document.getElementById('five-star-progress'),
            4: document.getElementById('four-star-progress'),
            3: document.getElementById('three-star-progress'),
            2: document.getElementById('two-star-progress'),
            1: document.getElementById('one-star-progress')
        };

        Object.keys(distribution).forEach(stars => {
            const percentage = (distribution[stars] / totalReviews) * 100;
            progressBars[stars].style.width = `${percentage}%`;
        });
    }
    // Reset progress bars
    function resetProgressBars() {
        const progressBars = {
            5: document.getElementById('five-star-progress'),
            4: document.getElementById('four-star-progress'),
            3: document.getElementById('three-star-progress'),
            2: document.getElementById('two-star-progress'),
            1: document.getElementById('one-star-progress')
        };

        Object.values(progressBars).forEach(bar => {
            bar.style.width = '0%';
        });
    }

    // Existing star rating functionality
    ratingStars.forEach((star, index) => {
        star.addEventListener('click', () => {
            selectedRating = index + 1;
            updateStarRating(selectedRating);
        });
    });
    // Update star rating
    function updateStarRating(rating) {
        ratingStars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    // Open Review Modal
    writeReviewBtn.addEventListener('click', () => {
        reviewModal.style.display = 'block';
    });

    // Close Review Modal
    closeModalBtn.addEventListener('click', () => {
        reviewModal.style.display = 'none';
    });

    // Submit Review
    submitReviewBtn.addEventListener('click', () => {
        const usernameInput = document.getElementById('username-input');
        const reviewTextInput = document.getElementById('review-text-input');

        if (usernameInput.value.trim() === '' || reviewTextInput.value.trim() === '' || selectedRating === 0) {
            alert('Please fill all fields and select a rating');
            return;
        }

        // Send review to server via AJAX
        fetch('submit_review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `username=${encodeURIComponent(usernameInput.value)}&review=${encodeURIComponent(reviewTextInput.value)}&rating=${selectedRating}`
        })
        .then(response => response.text())
        .then(result => {
            alert('Review submitted successfully! It will be reviewed by the admin.');

            // Reset modal
            usernameInput.value = '';
            reviewTextInput.value = '';
            updateStarRating(0);
            selectedRating = 0;
            reviewModal.style.display = 'none';

            // Refresh reviews after submission
            fetchApprovedReviews();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to submit review. Please try again.');
        });
    });

    // Fetch and display approved reviews on page load
    fetchApprovedReviews();
});
document.addEventListener('DOMContentLoaded', () => {
    const productGroups = document.getElementById('product-groups');
    const totalPriceElement = document.getElementById('total-price');
    const categoryNavItems = document.querySelectorAll('.product-category-nav a');
    let allProducts = [];// Global variable to store all products
    let selectedProducts = {}; // Tracks selected products across categories
    let totalAmount = 0; // Global total amount variable
    // Fetch products from the server
    async function fetchProducts() {
        try {
            const response = await fetch('fetch_products.php');
            const products = await response.json();
            return products;
        } catch (error) {
            console.error('FETCH ERROR:', error);
            return [];
        }
    }
    // Function to filter products by category range
    function filterProductsByCategory(categoryRange) {
        const [start, end] = categoryRange.split('-').map(Number);
        return allProducts.filter(product => {
            const productId = parseInt(product.id);
            return productId >= start && productId <= end;
        });
    }
    // Function to display products
    function displayProducts(products) {
        productGroups.innerHTML = '';

        products.forEach(product => {
            const productDiv = document.createElement('div');
            productDiv.classList.add('product-item');

            // Retrieve previously selected quantity for this product
            const savedQuantity = selectedProducts[product.id]?.quantity || 0;
            // Update total amount based on saved quantity
            productDiv.innerHTML = `
                <div class="product-details">
                    <h3>${product.name}</h3>
                    <p>${product.description}</p>
                    <p>Price: ₱${parseFloat(product.price).toFixed(2)}</p>
                </div>
                <div class="product-quantity">
                    <input type="number" min="0" value="${savedQuantity}" 
                           data-price="${product.price}" 
                           data-id="${product.id}"
                           onchange="updateTotal(this)">
                </div>
            `;
            productGroups.appendChild(productDiv);
        });

        updateDisplayedTotal();
    }
    // Function to show product selection panel
    // and fetch products from the server
    async function showProductSelection() {
        try {
            if (allProducts.length === 0) {
                allProducts = await fetchProducts();
            }

            const bilaoProducts = filterProductsByCategory('1-32');
            displayProducts(bilaoProducts);

            document.body.classList.add('blur-background');
            document.getElementById('product-selection-panel').style.display = 'block';
        } catch (error) {
            console.error('Error loading products:', error);
            alert('Failed to load products. Please try again.');
        }
    }
    // Function to close product selection panel
    categoryNavItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();

            categoryNavItems.forEach(nav => nav.classList.remove('active'));
            e.target.classList.add('active');

            const categoryRange = e.target.dataset.category;
            const filteredProducts = filterProductsByCategory(categoryRange);
            displayProducts(filteredProducts);
        });
    });
    // Function to update total price based on selected products
    window.updateTotal = function(input) {
        const productId = input.dataset.id;
        const price = parseFloat(input.dataset.price);
        const quantity = parseInt(input.value) || 0;
        const productName = input.closest('.product-item').querySelector('h3').textContent;
    
        if (quantity > 0) {
            selectedProducts[productId] = {
                name: productName,  
                price: price,
                quantity: quantity
            };
        } else {
            delete selectedProducts[productId];
        }
    
        updateDisplayedTotal();
    };
    // Function to update displayed total price
    function updateDisplayedTotal() {
        totalAmount = 0;

        // Calculate total amount from selected products
        for (const productId in selectedProducts) {
            const product = selectedProducts[productId];
            totalAmount += product.quantity * product.price;
        }

        // Update total price display
        totalPriceElement.textContent = totalAmount.toFixed(2);
    }

    window.closeProductSelection = function() {
        document.body.classList.remove('blur-background');
        document.getElementById('product-selection-panel').style.display = 'none';
    };
    // Function to confirm reservation and save selected products
    window.confirmReservation = function() {
        const selectedProductsList = [];

        for (const productId in selectedProducts) {
            const product = selectedProducts[productId];
            selectedProductsList.push({
                id: productId,
                name: product.name,
                quantity: product.quantity,
                price: product.price
            });
        }
        // Send selected products and total price to the server
        const formData = new FormData();
        formData.append('selected_products', JSON.stringify(selectedProductsList));
        formData.append('total_price', totalAmount);

        fetch('save_product_reservation.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('confirmation-popup').style.display = 'none';
                document.getElementById('success-popup').style.display = 'flex';
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error saving reservation:', error);
            alert('An error occurred while saving products.');
        });
    };
    // Initialize product selection panel
    document.querySelector('.continue-btn').addEventListener('click', showProductSelection);
});

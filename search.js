document.addEventListener('DOMContentLoaded', function() {
    // Get the search elements
    const searchInput = document.getElementById('search');
    const searchButton = document.querySelector('.search_button');
    const searchResults = document.querySelector('.search-results');
    const resultsGrid = document.querySelector('.search-results-grid');

    // Check if all required elements exist
    if (!searchInput || !searchButton || !searchResults || !resultsGrid) {
        console.error('Required DOM elements not found:', {
            searchInput: !!searchInput,
            searchButton: !!searchButton,
            searchResults: !!searchResults,
            resultsGrid: !!resultsGrid
        });
        return;
    }

    // Function to handle the search
    async function handleSearch() {
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length === 0) {
            searchResults.style.display = 'none';
            return;
        }

        try {
            // Show loading state
            resultsGrid.innerHTML = '<p>Searching...</p>';
            searchResults.style.display = 'block';

            // Send the search request to your server
            const response = await fetch('search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ searchTerm })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Search failed');
            }

            if (data.status === 'success') {
                displaySearchResults(data.results);
            } else {
                throw new Error(data.message);
            }

        } catch (error) {
            console.error('Error during search:', error);
            resultsGrid.innerHTML = `
                <div class="search-error">
                    <p>Error: ${error.message}</p>
                </div>
            `;
        }
    }

    // Function to display search results
    function displaySearchResults(results) {
        if (!resultsGrid) {
            console.error('Results grid element not found');
            return;
        }

        resultsGrid.innerHTML = ''; // Clear previous results

        if (results.length === 0) {
            resultsGrid.innerHTML = '<p>No results found</p>';
            return;
        }

        results.forEach(item => {
            const resultItem = document.createElement('div');
            resultItem.className = 'search-result-item';
            resultItem.style.cursor = 'pointer';
            resultItem.onclick = function() {
                const params = new URLSearchParams({
                    name: item.name,
                    description: item.description,
                    price: item.price,
                    image: item.image,
                    manufacture_date: item.manufacture_date,
                    expiry_date: item.expiry_date
                });
                window.location.href = `shopping.html?${params.toString()}`;
            };
            resultItem.innerHTML = `
                <img src="${sanitizeInput(item.image)}" 
                     alt="${sanitizeInput(item.name)}" 
                     class="search-result-image"
                     onerror="this.src='images/placeholder.jpg'">
                <div class="search-result-details">
                    <h3 class="search-result-name">${sanitizeInput(item.name)}</h3>
                    <p class="search-result-description">${sanitizeInput(item.description)}</p>
                    <p class="search-result-price">₹${sanitizeInput(item.price)}</p>
                    <p class="search-result-dates">
                        Manufactured: ${sanitizeInput(item.manufacture_date)}<br>
                        Expires: ${sanitizeInput(item.expiry_date)}
                    </p>
                </div>
            `;
            resultsGrid.appendChild(resultItem);
        });

        searchResults.style.display = 'block';
    }

    // Event listeners
    searchButton.addEventListener('click', handleSearch);
    
    // Add search on Enter key press
    searchInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            handleSearch();
        }
    });

    // Add debounced search as user types
    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(handleSearch, 500);
    });
});

// Helper function to sanitize user input
function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
} 
/**
 * Monster Actions - Global JavaScript for Monster Interactions
 * ==============================================================
 * 
 * PURPOSE:
 * Provides reusable functions for monster interactions across all pages.
 * Loaded globally via footer.php so functions work everywhere monster-card-mini.php is used.
 * 
 * FEATURES:
 * - Like/Unlike monsters via AJAX (asynchronous, no page reload)
 * - Add to collection dropdown functionality
 * - Real-time UI updates for better user experience
 * 
 * DEPENDENCIES:
 * - Bootstrap 5+ (for icons: bi-heart, bi-heart-fill)
 * - User must be logged in for actions (checked server-side)
 * 
 * USED ON PAGES:
 * - monster-card-mini.php template (all monster listings)
 * - Monster detail pages (show.php)
 * - Collections, dashboard, browse pages
 */

/**
 * TOGGLE LIKE - Like/Unlike Monster via AJAX
 * ===========================================
 * 
 * WHAT IS AJAX?
 * AJAX (Asynchronous JavaScript And XML) lets you send requests to the server
 * and update the page WITHOUT reloading. User clicks heart, database updates,
 * UI changes instantly - all without leaving the page.
 * 
 * HOW IT WORKS (Step-by-Step):
 * 
 * 1. USER ACTION: User clicks heart icon on monster card
 * 
 * 2. PREVENT DEFAULT: Stop normal link click (which would reload page)
 * 
 * 3. DISABLE BUTTON: Prevent double-clicks while request is in progress
 * 
 * 4. SEND REQUEST: JavaScript sends HTTP request to server
 *    URL: index.php?url=monster-like&id=123
 *    Method: GET
 *    Includes: Session cookie (for user authentication)
 * 
 * 5. SERVER PROCESSES:
 *    - MonsterController->toggleLike() receives request
 *    - Checks if user is logged in (session)
 *    - Checks if monster exists and is public/owned
 *    - MonsterLike->toggleLike() adds or removes like from database
 *    - MonsterLike->countLikes() gets updated count
 *    - Returns JSON response: { success: true, action: "added", count: 5, liked: true }
 * 
 * 6. RECEIVE RESPONSE: JavaScript receives JSON from server
 * 
 * 7. UPDATE UI: Change heart icon and counter based on response
 *    - If liked: Show filled heart (bi-heart-fill), set data-liked="1"
 *    - If unliked: Show empty heart (bi-heart), set data-liked="0"
 *    - Update counter number
 * 
 * 8. RE-ENABLE BUTTON: Allow user to click again
 * 
 * 9. ERROR HANDLING: If something fails, show alert message
 * 
 * PARAMETERS:
 * @param {Event} event - Click event (needed to prevent page reload)
 * @param {number} monsterId - Database ID of monster (from PHP: $monster['monster_id'])
 * 
 * RETURNS:
 * @returns {boolean} false - Prevents default link behavior
 * 
 * RELATED FILES:
 * - Backend: src/controllers/MonsterController.php -> toggleLike()
 * - Model: src/models/MonsterLike.php -> toggleLike(), countLikes()
 * - Template: src/views/templates/monster-card-mini.php (like button HTML)
 * - Database: monster_likes table (u_id, monster_id, created_at)
 */
function toggleLike(event, monsterId) {
    // STEP 1: Prevent default behavior
    // Without this, clicking the button would follow the href link and reload the page
    if (event) {
        event.preventDefault();      // Stop link navigation
        event.stopPropagation();     // Stop click from bubbling to parent elements
    }
    
    // STEP 2: Find the button element
    // User might click the icon, span, or button itself - closest() finds the button parent
    let btn = null;
    if (event && event.target) {
        btn = event.target.closest('button.like-btn');
    }
    
    // Exit if button not found (shouldn't happen, but safety check)
    if (!btn) {
        console.error('toggleLike: Button element not found');
        return false;
    }
    
    // STEP 3: Find the icon and counter elements inside the button
    const icon = btn.querySelector('i');                 // <i class="bi bi-heart">
    const countSpan = btn.querySelector('.like-count');   // <span class="like-count">5</span>
    
    // Exit if elements missing (shouldn't happen, but safety check)
    if (!icon || !countSpan) {
        console.error('toggleLike: Icon or count element not found');
        return false;
    }
    
    // STEP 4: Disable button during request
    // Prevents user from clicking multiple times while request is processing
    btn.disabled = true;
    
    // STEP 5: Build URL for AJAX request
    const url = 'index.php?url=monster-like&id=' + monsterId;
    
    // STEP 6: Send AJAX request using fetch() API
    // fetch() is modern JavaScript way to make HTTP requests
    // Returns a Promise - code continues executing, .then() runs when response received
    fetch(url, {
        method: 'GET',                    // HTTP method
        credentials: 'same-origin'        // Send cookies (needed for PHP session)
    })
    // STEP 7: Convert response to JSON
    .then(response => {
        // Check if HTTP status is OK (200-299)
        if (!response.ok) {
            throw new Error('Network response error: ' + response.status);
        }
        // Parse response body as JSON
        return response.json();
    })
    // STEP 8: Update UI based on server response
    .then(data => {
        // data = { success: true, action: "added", count: 5, liked: true }
        
        if (data.success) {
            // Update heart icon based on liked state
            if (data.liked) {
                // User just LIKED the monster
                icon.classList.remove('bi-heart');       // Remove empty heart
                icon.classList.add('bi-heart-fill');     // Add filled heart
                btn.dataset.liked = '1';                 // Update data attribute for next load
            } else {
                // User just UNLIKED the monster
                icon.classList.remove('bi-heart-fill');  // Remove filled heart
                icon.classList.add('bi-heart');          // Add empty heart
                btn.dataset.liked = '0';                 // Update data attribute
            }
            
            // Update the like counter number
            countSpan.textContent = parseInt(data.count) || 0;
        } else {
            // Server returned error
            alert(data.error || 'Failed to update like');
        }
    })
    // STEP 9: Handle errors (network failure, invalid JSON, etc.)
    .catch(error => {
        console.error('Like error:', error);
        alert('An error occurred. Please try again.');
    })
    // STEP 10: Re-enable button (runs whether success or error)
    // finally() always executes after then() or catch()
    .finally(() => {
        btn.disabled = false;
    });
    
    return false;
}

/**
 * Add monster to collection (AJAX)
 * 
 * Called from add-to-collection dropdown in monster-card-mini.php
 * Shows success/error feedback without page reload
 * 
 * @param {Event} event - Click event
 * @param {HTMLElement} element - Clicked dropdown item with data attributes
 */
async function addToCollection(event, element) {
    event.preventDefault();
    event.stopPropagation();
    
    const monsterId = element.dataset.monsterId;
    const collectionId = element.dataset.collectionId;
    const collectionName = element.dataset.collectionName;
    
    console.log('addToCollection called:', {
        monsterId: monsterId,
        collectionId: collectionId,
        collectionName: collectionName
    });
    
    try {
        const response = await fetch('index.php?url=collection-add-monster', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `collection_id=${collectionId}&monster_id=${monsterId}`
        });
        
        const result = await response.json();
        console.log('Add to collection result:', result);
        
        if (result.success) {
            // Dynamic success alert (auto-dismiss after 3 seconds)
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            successMsg.style.zIndex = '9999';
            successMsg.innerHTML = `
                <i class="bi bi-check-circle"></i> Added to ${collectionName}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        } else {
            // Show error message to user
            alert(result.message || 'Failed to add to collection');
        }
    } catch (error) {
        console.error('Error adding to collection:', error);
        alert('An error occurred. Please try again.');
    }
}

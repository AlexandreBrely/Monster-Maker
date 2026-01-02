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
 * ADD TO COLLECTION - Add Monster to User's Collection via AJAX
 * ===============================================================
 * 
 * WHAT IS THIS?
 * Lets users organize monsters into collections (like favorites/bookmarks)
 * without reloading the page. Uses async/await for clean asynchronous code.
 * 
 * WHEN IS IT CALLED?
 * User clicks "Add to Collection" dropdown menu on monster card:
 * 
 *   <div class="dropdown">
 *     <button class="dropdown-toggle">Add to Collection ▼</button>
 *     <div class="dropdown-menu">
 *       <a onclick="addToCollection(event, this)" 
 *          data-monster-id="42" 
 *          data-collection-id="5" 
 *          data-collection-name="Dragons">
 *         Dragons
 *       </a>
 *     </div>
 *   </div>
 * 
 * FLOW (Step-by-Step):
 * 
 * 1. USER ACTION: Click collection name in dropdown
 * 
 * 2. PREVENT DEFAULT: Stop normal link behavior (page reload)
 *    event.preventDefault() - Stop link navigation
 *    event.stopPropagation() - Stop click from bubbling to parent
 * 
 * 3. EXTRACT DATA: Get values from clicked element's data attributes
 *    - monsterId: Which monster to add (from element.dataset.monsterId)
 *    - collectionId: Which collection to add to
 *    - collectionName: Display name for success message
 * 
 * 4. SEND AJAX REQUEST:
 *    Uses Fetch API to send POST request (submits data to server)
 *    URL: index.php?url=collection-add-monster
 *    Method: POST (more secure than GET for data changes)
 *    Body: collection_id=5&monster_id=42
 *    Headers: Content-Type tells server format is URL-encoded
 * 
 *    WHAT HAPPENS ON SERVER:
 *    a) PHP CollectionController receives request
 *    b) Validates user logged in and owns the collection
 *    c) Validates monster exists and is public/accessible
 *    d) Inserts record into collection_monsters junction table
 *    e) Returns JSON: { success: true, message: "Added" }
 * 
 * 5. RECEIVE RESPONSE:
 *    - Parse response as JSON
 *    - Check if success flag is true
 * 
 * 6. SHOW SUCCESS MESSAGE:
 *    Dynamic popup alert (not the old ugly alert() box)
 *    - Creates new Bootstrap alert div
 *    - Positioned at top-center of page
 *    - Shows collection name: "Added to Dragons"
 *    - Auto-dismisses after 3 seconds
 *    - User can click X button to close manually
 * 
 * 7. ERROR HANDLING:
 *    - If response.success is false: Show user-friendly error message
 *    - If fetch fails (network error): Show generic error
 *    - Errors don't crash the page, just show alert
 * 
 * WHY ASYNC/AWAIT?
 * Makes asynchronous code (waiting for server response) look synchronous:
 * 
 * const response = await fetch(...)    // Wait for response
 * const result = await response.json() // Wait for parsing
 * // Now we can use result directly without nested .then() calls
 * 
 * PARAMETERS:
 * @param {Event} event - Click event (from onclick handler)
 * @param {HTMLElement} element - The <a> or <button> that was clicked
 *                               Contains data-* attributes with IDs
 * 
 * RETURNS:
 * @returns {void} - Asynchronous function, no return value
 * 
 * RELATED FILES:
 * - Backend: src/controllers/CollectionController.php -> addMonster()
 * - Model: src/models/Collection.php -> addMonster()
 * - Template: src/views/templates/monster-card-mini.php (dropdown HTML)
 * - Database: collection_monsters table (collection_id, monster_id)
 * 
 * DEBUGGING:
 * Open browser DevTools (F12) → Console tab:
 * - Messages logged: collection_id, monster_id, response from server
 * - Errors show with full stack trace
 */
async function addToCollection(event, element) {
    // STEP 1: Prevent page reload
    event.preventDefault();
    event.stopPropagation();
    
    // STEP 2: Extract data from the clicked dropdown item
    // Data attributes are stored on the HTML element
    // <a data-monster-id="42" data-collection-id="5" ...>
    // JavaScript accesses via: element.dataset.propertyName
    const monsterId = element.dataset.monsterId;
    const collectionId = element.dataset.collectionId;
    const collectionName = element.dataset.collectionName;
    
    // STEP 3: Log for debugging (visible in browser console)
    console.log('addToCollection called:', {
        monsterId: monsterId,
        collectionId: collectionId,
        collectionName: collectionName
    });
    
    try {
        // STEP 4: Send AJAX request to server
        // fetch() = modern way to send HTTP requests
        // Method: POST (more secure for data-changing operations)
        // Body: Form data with collection and monster IDs
        const response = await fetch('index.php?url=collection-add-monster', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `collection_id=${collectionId}&monster_id=${monsterId}`
        });
        
        // STEP 5: Parse server response
        // Server responds with JSON like: { success: true, message: "Added" }
        const result = await response.json();
        console.log('Add to collection result:', result);
        
        // STEP 6: Check if request was successful
        if (result.success) {
            // CREATE SUCCESS MESSAGE
            // Instead of ugly alert() box, create a nice Bootstrap alert
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            successMsg.style.zIndex = '9999';  // Appear above everything
            
            // Build alert HTML with collection name
            successMsg.innerHTML = `
                <i class="bi bi-check-circle"></i> Added to ${collectionName}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Add alert to page
            document.body.appendChild(successMsg);
            
            // STEP 7: Auto-dismiss after 3 seconds
            // setTimeout() runs code after delay (3000ms = 3 seconds)
            // Remove the alert element from DOM
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        } else {
            // SERVER RETURNED ERROR (success: false)
            // Show user the error message from server
            alert(result.message || 'Failed to add to collection');
        }
    } catch (error) {
        // STEP 8: Error handling (network failure, JSON parse error, etc)
        console.error('Error adding to collection:', error);
        alert('An error occurred. Please try again.');
    }
}

/**
 * Download Monster Card as PDF via Puppeteer Service
 * ====================================================
 * 
 * WHAT IS THIS FUNCTION?
 * Handles downloading a monster card as a print-ready PDF file to the user's computer.
 * Uses async/await for clean asynchronous code flow.
 * 
 * TECHNOLOGY STACK:
 * - Frontend: JavaScript Fetch API (modern way to send HTTP requests)
 * - Backend: PHP MonsterController::generatePdf()
 * - PDF Service: Node.js Puppeteer microservice (renders PDFs via headless Chrome)
 * 
 * DETAILED FLOW (Step-by-Step):
 * 
 * 1. USER ACTION: Click "Download for Print" button on monster card
 * 
 * 2. PREVENT DEFAULT: Stop normal button behavior (page reload)
 * 
 * 3. GET MONSTER ID: Extract ID from page (stored in data-monster-id attribute)
 *    Example: <div data-monster-id="42"> ... </div>
 *    JavaScript: document.querySelector('[data-monster-id]').dataset.monsterId
 * 
 * 4. SHOW LOADING STATE:
 *    - Disable button (prevent double-clicks)
 *    - Show spinner icon with "Generating PDF..." text
 *    - Visual feedback so user knows something is happening
 * 
 * 5. FETCH REQUEST (AJAX):
 *    Uses JavaScript Fetch API to send HTTP request to backend
 *    URL: index.php?url=monster-pdf&id=42
 *    Method: GET (no body needed, ID in URL)
 *    Response Type: Blob (binary data - the PDF file)
 *    
 *    WHAT HAPPENS ON SERVER:
 *    a) PHP MonsterController receives request
 *    b) Validates user has permission to see this monster
 *    c) Renders clean HTML template (no header/footer/nav)
 *    d) Calls PrintService->generatePdf()
 *    e) PrintService POSTs HTML URL to Puppeteer service
 *    f) Puppeteer service (Node.js):
 *       - Fetches the HTML from web server
 *       - Opens headless Chrome browser
 *       - Renders HTML using real browser engine
 *       - Converts rendered page to PDF
 *       - Returns PDF binary data
 *    g) PHP streams PDF to JavaScript with headers:
 *       Content-Type: application/pdf
 *       Content-Disposition: attachment; filename="MonsterMaker_MonsterName.pdf"
 * 
 * 6. RECEIVE RESPONSE:
 *    - Check if request was successful (response.ok)
 *    - If error: Extract error message from JSON, throw exception
 *    - If success: Convert response stream to Blob (binary data)
 * 
 * 7. EXTRACT FILENAME:
 *    HTTP headers can contain filename: Content-Disposition: attachment; filename="..."
 *    JavaScript parses this header to get proper filename
 *    Fallback if no header: monster_{id}.pdf
 *    Example: MonsterMaker_Ancient_Red_Dragon.pdf
 * 
 * 8. TRIGGER BROWSER DOWNLOAD:
 *    JavaScript can't directly save files (security), but can trigger downloads:
 *    
 *    a) Create Blob URL (temporary URL pointing to the PDF data in memory):
 *       const url = window.URL.createObjectURL(pdfBlob)
 *       Example: blob:http://localhost:8000/a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6
 *    
 *    b) Create temporary <a> link element:
 *       const link = document.createElement('a')
 *    
 *    c) Set download properties:
 *       link.href = url (point to PDF blob)
 *       link.download = filename (tells browser to download, not open)
 *    
 *    d) Simulate click (triggers browser download):
 *       link.click()
 *    
 *    e) Clean up (remove temporary link and release memory):
 *       document.body.removeChild(link)
 *       window.URL.revokeObjectURL(url)
 * 
 * 9. RESTORE BUTTON:
 *    - Re-enable button (allow next click)
 *    - Show original text/icon
 *    - User can download again if needed
 * 
 * 10. ERROR HANDLING:
 *     If anything fails:
 *     - Catch the error
 *     - Log to browser console (for debugging)
 *     - Show user-friendly alert
 *     - Restore button state
 * 
 * WHY ASYNC/AWAIT?
 * Traditional callbacks (promise.then()) get messy with nested calls.
 * async/await makes it look like synchronous code but still non-blocking:
 * 
 * Traditional way:
 *   fetch().then(response => response.blob()).then(blob => { ... })
 * 
 * With async/await:
 *   const response = await fetch()
 *   const blob = await response.blob()
 *   // Use blob directly
 * 
 * PARAMETERS:
 * @param {Event} event - Click event from button (needed to prevent page reload)
 * 
 * RETURNS:
 * @returns {Promise<void>} - Asynchronous operation (no return value)
 * 
 * RELATED FILES:
 * - Backend API: src/controllers/MonsterController.php -> generatePdf()
 * - PDF Service: src/services/PrintService.php
 * - Template: src/views/templates/action-buttons.php (button HTML)
 * - Button HTML: <button onclick="downloadCardPuppeteer(event)">Download for Print</button>
 * 
 * BROWSER COMPATIBILITY:
 * - Fetch API: All modern browsers (IE not supported)
 * - async/await: All modern browsers
 * - Blob: All modern browsers
 * 
 * DEBUGGING TIPS:
 * If download fails:
 * 1. Open browser DevTools (F12)
 * 2. Click Network tab
 * 3. Click "Download for Print" button
 * 4. Look for request to "index.php?url=monster-pdf"
 * 5. Check response status (200 = success, 404 = not found, 500 = server error)
 * 6. Check Console tab for error messages
 */
async function downloadCardPuppeteer(event) {
    event.preventDefault();
    
    const button = event.target.closest('button');
    const container = document.querySelector('[data-monster-id]');
    const monsterId = container?.dataset.monsterId;
    
    if (!monsterId) {
        alert('Monster ID not found');
        return;
    }
    
    try {
        // STEP 1: Show loading state
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating PDF...';
        
        // STEP 2: Call PDF generation endpoint (AJAX request)
        // fetch() sends HTTP GET request, returns Promise
        // await waits for response before continuing
        const response = await fetch(`index.php?url=monster-pdf&id=${monsterId}`);
        
        // STEP 3: Check for errors
        // response.ok is true if status is 200-299
        if (!response.ok) {
            // Server returned error response (400, 403, 404, 500, etc)
            const error = await response.json();
            throw new Error(error.error || 'Failed to generate PDF');
        }
        
        // STEP 4: Get PDF blob from response
        // .blob() converts the response stream into binary data
        // Blob = Binary Large Object (for images, PDFs, files, etc)
        const pdfBlob = await response.blob();
        
        // STEP 5: Extract filename from HTTP header
        // Server sends: Content-Disposition: attachment; filename="MonsterMaker_DragonName.pdf"
        // JavaScript parses this to get the proper filename
        const contentDisposition = response.headers.get('Content-Disposition');
        let filename = `monster_${monsterId}.pdf`; // fallback if no header
        
        if (contentDisposition) {
            // Regex extracts filename from header
            // Pattern: filename="something.pdf" or filename=something.pdf
            const filenameMatch = contentDisposition.match(/filename="?(.+?)"?$/);
            if (filenameMatch) {
                filename = filenameMatch[1];
            }
        }
        
        // STEP 6: Create temporary download link
        // Browsers can't directly save files, but can simulate <a> tag clicks
        // We create a fake link pointing to our PDF blob
        const url = window.URL.createObjectURL(pdfBlob);  // Create blob URL
        const link = document.createElement('a');         // Create <a> element
        link.href = url;                                  // Point to PDF data
        link.download = filename;                         // Filename for save dialog
        
        // STEP 7: Trigger download
        // Append link to page (required by some browsers)
        document.body.appendChild(link);
        link.click();  // Simulate user clicking the download link
        
        // STEP 8: Clean up
        // Remove temporary link from page
        document.body.removeChild(link);
        // Release memory used by blob URL
        window.URL.revokeObjectURL(url);
        
        // STEP 9: Restore button
        button.disabled = false;
        button.innerHTML = originalText;
        
    } catch (error) {
        // Error handler (runs if anything fails)
        console.error('PDF download error:', error);
        alert('Error generating PDF: ' + error.message);
        
        // Restore button even if error occurred
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

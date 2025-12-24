/**
 * CARD DOWNLOAD FUNCTIONALITY - Print-Ready PDF Generation
 * ==========================================================
 * 
 * PURPOSE:
 * Generates high-quality PDF files from monster card HTML for printing.
 * Converts DOM elements to images at 300 DPI equivalent resolution.
 * 
 * WORKFLOW:
 * 1. User clicks "Download PDF" button
 * 2. Script captures card HTML as canvas (html2canvas library)
 * 3. Canvas is scaled to print dimensions (2.5"×3.5" or 5.8"×4.1")
 * 4. Image is embedded in PDF (jsPDF library)
 * 5. PDF is downloaded to user's device
 * 
 * CARD TYPES SUPPORTED:
 * - Small cards: 2.5" × 3.5" (750×1050px at 300 DPI) - Playing card size
 * - Boss cards: 5.8" × 4.1" (1740×1230px at 300 DPI) - A6 landscape
 * 
 * DEPENDENCIES:
 * - html2canvas: DOM to canvas conversion (loaded via CDN in footer.php)
 * - jsPDF: PDF generation (loaded via CDN with dynamic fallback)
 * 
 * TECHNICAL DETAILS:
 * - Captures both front and back cards if present
 * - Maintains exact aspect ratios for print accuracy
 * - Uses high DPI scaling (300 DPI) for print quality
 * - Handles cross-origin images via CORS settings
 * - Generates sanitized filenames from monster names
 * 
 * BROWSER COMPATIBILITY:
 * - Modern browsers (Chrome, Firefox, Edge, Safari)
 * - Requires canvas and Blob support
 * - Works offline after initial library load
 */

/**
 * CAPTURE CARD CANVASES - Convert HTML Cards to High-Quality Images
 * =================================================================
 * 
 * Converts monster card DOM elements into canvas images at print resolution.
 * 
 * PROCESS:
 * 1. Locate card elements in DOM (front statblock, back image)
 * 2. Calculate target dimensions based on card type
 * 3. Capture DOM as canvas using html2canvas at high scale
 * 4. Resize canvas to exact print dimensions (300 DPI)
 * 5. Return canvas objects for PDF embedding
 * 
 * CARD DETECTION:
 * - Tries multiple selectors for compatibility with different card types
 * - Small cards: .small-statblock or .statblock
 * - Boss cards: .boss-card-front
 * - Back cards: .card-back (optional)
 * 
 * DIMENSIONS:
 * - Small cards: 750×1050px (2.5" × 3.5" at 300 DPI)
 * - Boss cards: 1740×1230px (5.8" × 4.1" at 300 DPI)
 * 
 * SCALING LOGIC:
 * - Calculates scale factor to achieve 300 DPI equivalent
 * - Uses Math.max() to ensure no dimension is undersized
 * - Maintains aspect ratio during resize
 * 
 * HTML2CANVAS OPTIONS:
 * - scale: High multiplier for resolution (calculated dynamically)
 * - backgroundColor: White (#ffffff) for print compatibility
 * - logging: Disabled for cleaner console
 * - useCORS: Enable cross-origin image loading
 * - allowTaint: Allow rendering of cross-origin images
 * - scrollY/scrollX: Compensate for page scroll position
 * 
 * RETURN VALUE:
 * {
 *   frontCanvas: Canvas,      // Front card as canvas (always present)
 *   backCanvas: Canvas|null,  // Back card as canvas (if exists)
 *   monsterName: string,      // Display name
 *   baseFilename: string      // Sanitized filename
 * }
 * 
 * ERROR HANDLING:
 * - Throws error if no card elements found
 * - Provides helpful error message for debugging
 * 
 * @returns {Promise<Object>} Promise resolving to card canvas data
 * @throws {Error} If card elements not found in DOM
 */
async function captureCardCanvases() {
    // Try to find the card - try multiple selectors for compatibility
    let frontCard = document.querySelector('.small-statblock, .statblock, .boss-card-front');
    
    // Fallback: if not found, try alternate selectors
    if (!frontCard) {
        frontCard = document.querySelector('[class*="statblock"], [class*="boss-card"]');
    }
    
    // Back face selectors (support boss card back as well)
    let backCard = document.querySelector('.card-back, .boss-card-back');

    // Ensure images inside the cards are fully loaded before capturing
    await waitForImages(frontCard, backCard);

    if (!frontCard) {
        throw new Error('Card not found. Please make sure you are viewing a monster card page (not the list or form).');
    }

    const titleElement = frontCard.querySelector('.statblock-title, .boss-name');
    const monsterName = titleElement ? titleElement.textContent.trim() : 'monster';
    const baseFilename = monsterName.replace(/[^a-z0-9]/gi, '_').toLowerCase();

    const isSmallCard = frontCard.classList.contains('small-statblock') || frontCard.classList.contains('statblock');
    const targetDimensions = isSmallCard
        ? { width: 750, height: 1050 }    // 2.5" × 3.5" at 300 DPI
        : { width: 1740, height: 1230 };   // 5.8" × 4.1" at 300 DPI

    const rect = frontCard.getBoundingClientRect();
    const scaleX = targetDimensions.width / rect.width;
    const scaleY = targetDimensions.height / rect.height;
    const scale = Math.max(scaleX, scaleY);

    // Capture front card
    const frontCanvasRaw = await html2canvas(frontCard, {
        scale: scale,
        backgroundColor: '#ffffff',
        logging: false,
        useCORS: true,
        allowTaint: true,
        scrollY: -window.scrollY,
        scrollX: -window.scrollX,
        width: rect.width,
        height: rect.height
    });

    const finalFrontCanvas = document.createElement('canvas');
    finalFrontCanvas.width = targetDimensions.width;
    finalFrontCanvas.height = targetDimensions.height;
    finalFrontCanvas.getContext('2d').drawImage(frontCanvasRaw, 0, 0, targetDimensions.width, targetDimensions.height);

    // Capture back card if present
    let finalBackCanvas = null;
    if (backCard) {
        const backRect = backCard.getBoundingClientRect();
        const backScaleX = targetDimensions.width / backRect.width;
        const backScaleY = targetDimensions.height / backRect.height;
        const backScale = Math.max(backScaleX, backScaleY);

        const backCanvasRaw = await html2canvas(backCard, {
            scale: backScale,
            backgroundColor: '#ffffff',
            logging: false,
            useCORS: true,
            allowTaint: true,
            scrollY: -window.scrollY,
            scrollX: -window.scrollX,
            width: backRect.width,
            height: backRect.height
        });

        finalBackCanvas = document.createElement('canvas');
        finalBackCanvas.width = targetDimensions.width;
        finalBackCanvas.height = targetDimensions.height;
        finalBackCanvas.getContext('2d').drawImage(backCanvasRaw, 0, 0, targetDimensions.width, targetDimensions.height);
    }

    return { frontCanvas: finalFrontCanvas, backCanvas: finalBackCanvas, monsterName, baseFilename };
}

/**
 * Ensure jsPDF is available - tries footer CDN first, then dynamic fallback
 * Returns the jsPDF constructor function
 */
async function ensureJsPdf() {
    if (window.jspdf && window.jspdf.jsPDF) {
        return window.jspdf.jsPDF;
    }

    // Attempt to load jsPDF dynamically if not available
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/jspdf@3.0.4/dist/jspdf.umd.min.js';
        script.async = true;
        script.onload = () => {
            if (window.jspdf && window.jspdf.jsPDF) {
                resolve(window.jspdf.jsPDF);
            } else {
                reject(new Error('jsPDF not exposed on window after dynamic load.'));
            }
        };
        script.onerror = () => reject(new Error('Failed to load jsPDF from CDN.'));
        document.head.appendChild(script);
    });
}

/**
 * Wait for all images inside the given elements to finish loading
 * This avoids blank canvases when html2canvas runs before images are ready.
 * @param {...HTMLElement|null} elements
 */
async function waitForImages(...elements) {
    const imgs = [];
    elements.forEach(el => {
        if (!el) return;
        imgs.push(...el.querySelectorAll('img'));
    });
    if (imgs.length === 0) return;

    await Promise.all(imgs.map(img => {
        if (img.complete && img.naturalWidth) {
            return Promise.resolve();
        }
        return new Promise(resolve => {
            img.addEventListener('load', resolve, { once: true });
            img.addEventListener('error', resolve, { once: true });
        });
    }));
}

/**
 * Hint viewers to avoid automatic print scaling
 * Adds /ViewerPreferences << /PrintScaling /None >> when supported by jsPDF events
 */
function applyNoPrintScaling(pdf) {
    const events = pdf && pdf.internal && pdf.internal.events;
    if (!events || typeof events.subscribe !== 'function') return;

    let applied = false;
    events.subscribe('putCatalog', () => {
        if (applied) return;
        pdf.internal.write('/ViewerPreferences << /PrintScaling /None >>');
        applied = true;
    });
}

/**
 * Main function: Download monster cards as a print-ready PDF
 * 
 * The PDF includes:
 * - Front card image at correct size
 * - Back card image at correct size (if present, on separate page)
 * - Embedded page dimensions (users can print directly without adjustments)
 * 
 * Small cards: 2.5" × 3.5" (portrait)
 * Boss cards: 5.8" × 4.1" (landscape)
 */
async function downloadCardForPrint(evt) {
    const button = evt ? evt.target : null;
    const originalButtonText = button ? button.innerHTML : '';
    
    // Show loading state
    if (button) {
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
        button.disabled = true;
    }

    try {
        // Ensure jsPDF is available
        const jsPDF = await ensureJsPdf();

        // Capture cards at high quality
        const { frontCanvas, backCanvas, baseFilename } = await captureCardCanvases();
        
        // Determine card type from DOM classes
        const frontCard = document.querySelector('.small-statblock, .statblock, .boss-card-front');
        const isBossCard = frontCard.classList.contains('boss-card-front');
        
        // Set exact dimensions based on card type (in inches)
        const pageWidth = isBossCard ? 5.8 : 2.5;
        const pageHeight = isBossCard ? 4.1 : 3.5;
        
        // Create PDF with exact page dimensions
        const pdf = new jsPDF({
            orientation: isBossCard ? 'landscape' : 'portrait',
            unit: 'in',
            format: [pageWidth, pageHeight],
            compress: true
        });

        // Ask compatible viewers to keep actual size when printing
        applyNoPrintScaling(pdf);

        // Add front card image filling entire page
        const frontImageData = frontCanvas.toDataURL('image/png');
        pdf.addImage(
            frontImageData, 
            'PNG', 
            0, 0, 
            pdf.internal.pageSize.getWidth(), 
            pdf.internal.pageSize.getHeight()
        );

        // Add back card on new page if present
        if (backCanvas) {
            pdf.addPage();
            const backImageData = backCanvas.toDataURL('image/png');
            pdf.addImage(
                backImageData, 
                'PNG', 
                0, 0, 
                pdf.internal.pageSize.getWidth(), 
                pdf.internal.pageSize.getHeight()
            );
        }

        // Download the PDF file
        pdf.save(`${baseFilename}_print-ready.pdf`);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Error: ' + error.message);
    } finally {
        // Restore button state
        if (button) {
            button.innerHTML = originalButtonText || '<i class="fa-solid fa-file-pdf"></i> Download for Print';
            button.disabled = false;
        }
    }
}


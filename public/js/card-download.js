/**
 * Card Download Functionality
 * Captures monster cards and downloads them as high-quality PNG images
 * Uses html2canvas to convert card DOM to images.
 * How it works:
 * 1. Find the card element in the DOM (Document Object Model)
 * 2. Use html2canvas to "screenshot" the card with high quality settings
 * 3. Convert the canvas to a downloadable image file
 * 4. Trigger browser download
 * 
 * Dependency: html2canvas (CDN available)
 */

/**
 * Download both front and back monster cards as high-quality PNG images
 * 
 * For small monsters: 2.5in × 3.5in cards at 300 DPI = 750px × 1050px
 * For boss monsters: 5.8in × 4.1in cards at 300 DPI = 1740px × 1230px
 * 
 * The images are scaled to exact print dimensions to ensure accurate printing.
 */
async function downloadCard() {
    // Find the front and back card elements
    const frontCard = document.querySelector('.small-statblock, .statblock, .boss-card-front');
    const backCard = document.querySelector('.card-back');
    
    if (!frontCard) {
        alert('Card not found. Please make sure you are on a monster card page.');
        return;
    }
    
    // Get monster name for filename
    const titleElement = frontCard.querySelector('.statblock-title, .boss-name');
    const monsterName = titleElement ? titleElement.textContent.trim() : 'monster';
    const baseFilename = monsterName.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    
    // Determine card type and dimensions
    const isSmallCard = frontCard.classList.contains('small-statblock') || frontCard.classList.contains('statblock');
    const isBossCard = frontCard.classList.contains('boss-card-front');
    
    // Physical card dimensions at 300 DPI (print quality)
    // Small cards: 2.5in × 3.5in = 750px × 1050px at 300 DPI
    // Boss cards: 5.8in × 4.1in = 1740px × 1230px at 300 DPI
    const targetDimensions = isSmallCard 
        ? { width: 750, height: 1050 }  // Small monster card
        : { width: 1740, height: 1230 }; // Boss monster card
    
    try {
        // Show loading indicator
        const originalButtonText = event.target.innerHTML;
        event.target.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
        event.target.disabled = true;
        
        // Calculate scale factor to achieve exact print dimensions
        // Get the current rendered size of the card
        const rect = frontCard.getBoundingClientRect();
        const scaleX = targetDimensions.width / rect.width;
        const scaleY = targetDimensions.height / rect.height;
        const scale = Math.max(scaleX, scaleY); // Use larger scale to ensure quality
        
        // Capture FRONT card
        const frontCanvas = await html2canvas(frontCard, {
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
        
        // Resize to exact target dimensions
        const finalFrontCanvas = document.createElement('canvas');
        finalFrontCanvas.width = targetDimensions.width;
        finalFrontCanvas.height = targetDimensions.height;
        const ctx = finalFrontCanvas.getContext('2d');
        ctx.drawImage(frontCanvas, 0, 0, targetDimensions.width, targetDimensions.height);
        
        // Download front card
        await downloadCanvasAsImage(finalFrontCanvas, `${baseFilename}_front.png`);
        
        // Capture BACK card if it exists
        if (backCard) {
            const backRect = backCard.getBoundingClientRect();
            const backScaleX = targetDimensions.width / backRect.width;
            const backScaleY = targetDimensions.height / backRect.height;
            const backScale = Math.max(backScaleX, backScaleY);
            
            const backCanvas = await html2canvas(backCard, {
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
            
            // Resize back card to exact target dimensions
            const finalBackCanvas = document.createElement('canvas');
            finalBackCanvas.width = targetDimensions.width;
            finalBackCanvas.height = targetDimensions.height;
            const backCtx = finalBackCanvas.getContext('2d');
            backCtx.drawImage(backCanvas, 0, 0, targetDimensions.width, targetDimensions.height);
            
            // Download back card
            await downloadCanvasAsImage(finalBackCanvas, `${baseFilename}_back.png`);
        }
        
        // Restore button
        event.target.innerHTML = originalButtonText;
        event.target.disabled = false;
        
    } catch (error) {
        console.error('Error generating card image:', error);
        alert('Failed to generate card image. Please try again.');
        
        // Restore button on error
        event.target.innerHTML = '<i class="fa-solid fa-download"></i> Download Card';
        event.target.disabled = false;
    }
}

/**
 * Helper function to download a canvas as a PNG image
 * 
 * @param {HTMLCanvasElement} canvas - The canvas to download
 * @param {string} filename - The filename for the download
 */
function downloadCanvasAsImage(canvas, filename) {
    return new Promise((resolve) => {
        canvas.toBlob(function(blob) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            // Small delay between downloads to prevent browser blocking
            setTimeout(resolve, 100);
        }, 'image/png');
    });
}

/**
 * Alternative: Download both front and back cards (for cards with back images)
 * This creates a ZIP file with both card sides.
 * 
 * Note: Requires JSZip library for creating ZIP files
 * Future enhancement: Uncomment and test when needed
 */
/*
async function downloadBothSides() {
    const frontCard = document.querySelector('.small-statblock, .boss-card-front');
    const backCard = document.querySelector('.card-back');
    
    if (!frontCard) {
        alert('Front card not found.');
        return;
    }
    
    try {
        const zip = new JSZip();
        
        // Capture front card
        const frontCanvas = await html2canvas(frontCard, {
            scale: 3,
            backgroundColor: '#ffffff',
            logging: false
        });
        
        const frontBlob = await new Promise(resolve => {
            frontCanvas.toBlob(resolve, 'image/png');
        });
        
        zip.file('card-front.png', frontBlob);
        
        // Capture back card if exists
        if (backCard) {
            const backCanvas = await html2canvas(backCard, {
                scale: 3,
                backgroundColor: '#ffffff',
                logging: false
            });
            
            const backBlob = await new Promise(resolve => {
                backCanvas.toBlob(resolve, 'image/png');
            });
            
            zip.file('card-back.png', backBlob);
        }
        
        // Generate and download ZIP
        const zipBlob = await zip.generateAsync({type: 'blob'});
        const url = URL.createObjectURL(zipBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'monster-card.zip';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
    } catch (error) {
        console.error('Error generating cards:', error);
        alert('Failed to generate card images.');
    }
}
*/

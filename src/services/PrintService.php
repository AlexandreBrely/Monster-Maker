<?php

namespace App\Services;

/**
 * PrintService - PDF Generation via Puppeteer Microservice
 * ==========================================================
 * 
 * WHAT IS THIS SERVICE?
 * Handles all PDF generation for Monster Maker cards. Instead of using a PHP library
 * to generate PDFs directly, we delegate to a separate Node.js service running Puppeteer.
 * This is a "microservice architecture" - breaking concerns into separate services.
 * 
 * WHAT IS PUPPETEER?
 * Puppeteer is a Node.js library that controls headless Chrome (Chrome without GUI).
 * It automates browser tasks like:
 * - Opening web pages
 * - Taking screenshots
 * - Generating PDFs (rendering HTML to PDF exactly as it appears in browser)
 * - Filling forms
 * - Testing websites
 * 
 * WHY USE PUPPETEER INSTEAD OF PHP PDF LIBRARIES?
 * ================================================
 * 
 * We tested 3 approaches before choosing Puppeteer:
 * 
 * APPROACH 1: MPDF (PHP Library) - FAILED ❌
 * What: PHP library that generates PDFs server-side
 * Why we tried: Built into PHP, no extra service needed, seems simple
 * Problems encountered:
 *   - CSS Support: Doesn't understand modern CSS3 (Grid, Flexbox, @page rules)
 *   - Fonts: Limited font support, had to embed fonts manually
 *   - Layout Issues: Cards rendering incorrectly, text overlapping
 *   - Images: Trouble loading remote images from server
 *   - No @page CSS: Can't define page sizes via CSS (had to hardcode in PHP)
 *   - PDF Quality: Looked pixelated, unprofessional
 *   - Result: Cards looked completely different in PDF vs web browser
 * 
 * APPROACH 2: TCPDF (Another PHP Library) - FAILED ❌
 * What: Another PHP PDF library, similar to mPDF
 * Why we tried: Alternative to mPDF, maybe better CSS support
 * Problems encountered:
 *   - Same issues as mPDF: Poor CSS/font support
 *   - Even slower than mPDF
 *   - Generated huge file sizes
 *   - Result: Same failure
 * 
 * APPROACH 3: PUPPETEER (Headless Chrome) - SUCCESS ✅
 * What: Node.js library controlling real Chrome browser
 * Why we tried: Realized the real solution is using a real browser
 * Advantages:
 *   - PERFECT CSS Support: Uses actual Chrome rendering engine (same as browser)
 *   - Font Rendering: Loads Google Fonts beautifully, no configuration needed
 *   - Pixel-Perfect: PDF looks EXACTLY like it does in web browser
 *   - Modern CSS: Supports Grid, Flexbox, CSS variables, @page rules
 *   - Image Handling: Loads images from any source automatically
 *   - Page Setup: @page CSS rules work perfectly
 *   - PDF Quality: Professional-looking output
 *   - Scalable: Can run multiple instances for load balancing
 *   - Result: Cards look perfect in PDF, exactly matching web display
 * 
 * THE PUPPETEER ARCHITECTURE
 * ==========================
 * 
 * Why not put Puppeteer directly in PHP?
 * - Puppeteer requires Node.js (not available in PHP)
 * - Solution: Run Puppeteer in a separate service (microservice pattern)
 * - PHP communicates with Puppeteer service via HTTP
 * 
 * System Flow:
 * 
 * [Browser]
 *    ↓ Click "Download PDF"
 * [PHP Web Server] 
 *    ↓ User clicks button
 * [MonsterController] 
 *    ↓ Renders HTML template
 * [PrintService] 
 *    ↓ Sends HTTP request to Puppeteer service
 * [Docker Network]
 *    ↓ Service-to-service communication
 * [Puppeteer Service - Node.js]
 *    ↓ Fetch HTML from PHP web server
 * [Headless Chrome]
 *    ↓ Render HTML page exactly as browser would
 * [Puppeteer PDF Renderer]
 *    ↓ Convert rendered page to PDF
 * [PDF Binary Data]
 *    ↓ Return to PHP via HTTP
 * [PrintService]
 *    ↓ Stream to browser
 * [Browser]
 *    ↓ Download PDF file
 * [User's Computer]
 * 
 * WHY MICROSERVICE ARCHITECTURE?
 * ==============================
 * Separates concerns (responsibility):
 * - PHP handles: Web routing, permissions, data retrieval
 * - Node.js handles: PDF rendering (CPU-intensive task)
 * 
 * Benefits:
 * 1. Scalability: Can run multiple Puppeteer instances if needed
 * 2. Maintainability: Each service has single responsibility
 * 3. Flexibility: Could replace Puppeteer with different PDF service without changing PHP
 * 4. Performance: PDF rendering doesn't block main PHP process
 * 5. Technology Agnostic: PHP doesn't need to know about Node.js/Chrome
 * 
 * HOW DOCKER ENABLES THIS
 * =======================
 * Docker containers allow us to:
 * 1. Run PHP in one container (php:8.4-apache)
 * 2. Run Node.js/Puppeteer in separate container (node:20)
 * 3. Connect them via Docker network (app-network)
 * 4. Services can reference each other by name: http://pdf-renderer:3000
 * 5. Everything in Docker, so no system dependencies required
 * 
 * CONFIGURATION
 * ==============
 * - Puppeteer Service URL: http://pdf-renderer:3000/render-pdf
 * - Timeout: 30 seconds per request
 * - Docker Network: app-network
 * - Port mapping: 3000:3000 (internal Docker port to host port)
 * - Restart policy: unless-stopped (auto-restart if crashes)
 * 
 * SECURITY CONSIDERATIONS
 * =======================
 * - Puppeteer only accessible via internal Docker network (not internet)
 * - Input validation on monster ID (user can't request private monsters)
 * - Permission checks before rendering
 * - Timeouts prevent long-running attacks
 * - Service runs with minimal privileges in container
 */
class PrintService
{
    private $puppeteerUrl = 'http://pdf-renderer:3000/render-pdf';
    private $timeout = 30;

    /**
     * Generate PDF from an internal URL using Puppeteer microservice
     * 
     * HOW IT WORKS:
     * ============
     * 1. Takes a URL to clean HTML (no layout/nav)
     * 2. Sends the URL to Puppeteer service (Node.js running in Docker)
     * 3. Puppeteer fetches the URL and renders it with headless Chrome
     * 4. Chrome converts the rendered page to PDF using @page CSS rules
     * 5. Returns binary PDF data
     * 
     * EXAMPLE USAGE:
     * ============
     * // In MonsterController.php:
     * $printService = new \App\Services\PrintService();
     * $url = 'http://web/index.php?url=monster-print&id=1';
     * $pdf = $printService->generatePdf($url, [
     *     'format' => 'A4',
     *     'printBackground' => true
     * ]);
     * // Now $pdf contains the binary PDF data ready to stream
     * 
     * WHY THIS APPROACH?
     * ================
     * Question: Why not call Puppeteer directly from PHP?
     * Answer: Puppeteer requires Node.js, not available in PHP process
     * 
     * Question: Why not use PHP PDF libraries (mPDF)?
     * Answer: They don't support modern CSS/fonts. Puppeteer uses real Chrome.
     * 
     * Question: Why not embed Puppeteer inside Docker PHP container?
     * Answer: Would need Node.js AND PHP in same container (bloat).
     *         Separate containers follow Unix philosophy: "do one thing well"
     * 
     * WHAT HAPPENS ON PUPPETEER SERVICE:
     * ==================================
     * When you call this function:
     * 
     * 1. PHP builds JSON request: { "url": "http://web/...", "pdfOptions": {...} }
     * 2. PHP POSTs JSON to: http://pdf-renderer:3000/render-pdf
     * 3. Puppeteer service receives request in Node.js Express app
     * 4. Puppeteer service:
     *    a) Launches headless Chrome browser (or reuses from pool)
     *    b) Creates new browser page
     *    c) Navigates to the provided URL (fetches from web server)
     *    d) Waits for page to load completely
     *    e) Applies PDF options (page size, margins, scale)
     *    f) Renders the page as PDF
     *    g) Returns PDF binary data as HTTP response
     * 5. PHP receives PDF binary in response body
     * 6. This function returns the raw PDF bytes
     * 
     * THE COMPLETE REQUEST/RESPONSE CYCLE:
     * ===================================
     * 
     * REQUEST (PHP → Puppeteer):
     * POST http://pdf-renderer:3000/render-pdf
     * Content-Type: application/json
     * 
     * {
     *   "url": "http://web/index.php?url=monster-print&id=1",
     *   "pdfOptions": {
     *     "format": "A4",
     *     "printBackground": true,
     *     "preferCSSPageSize": true,
     *     "scale": 1,
     *     "margin": {
     *       "top": 0,
     *       "right": 0,
     *       "bottom": 0,
     *       "left": 0
     *     }
     *   }
     * }
     * 
     * RESPONSE (Puppeteer → PHP):
     * HTTP 200 OK
     * Content-Type: application/pdf
     * 
     * [Binary PDF data - thousands of bytes of PDF code]
     * %PDF-1.4
     * 1 0 obj
     * << /Type /Catalog /Pages 2 0 R >>
     * ... etc ...
     * 
     * PARAMETERS:
     * @param string $url Internal URL to render (e.g., http://web/index.php?url=monster-print&id=1)
     *                    Should point to clean HTML without header/navbar/footer
     *                    Puppeteer will fetch this URL and render it
     * 
     * @param array $pdfOptions Puppeteer PDF generation options (optional)
     *                          Override defaults like page size, margins, scale
     *                          Merged with default options below
     * 
     * AVAILABLE PDF OPTIONS:
     * =====================
     * [Passed to Puppeteer's page.pdf() method]
     * 
     * format: string          - Page format: 'A4' (default), 'Letter', 'A3', etc.
     * scale: number          - Zoom level: 1 = 100% (default)
     * printBackground: bool  - Include background colors/images (default: false)
     * preferCSSPageSize: bool - Use @page CSS rules for page size (default: false)
     * margin: object         - Margins in inches:
     *   - top, right, bottom, left (default: 0 for all)
     * 
     * RETURN VALUE:
     * @return string Raw binary PDF data (looks like garbage if displayed as text)
     *               Safe to stream to browser or save to file
     * 
     * THROWS:
     * @throws Exception If Puppeteer service unavailable or rendering fails
     *                   Error messages include details from Puppeteer
     * 
     * EXAMPLE ERROR MESSAGES:
     * - "PDF generation failed: connect ECONNREFUSED 172.20.0.4:3000"
     *   (Puppeteer service not running)
     * - "PDF generation failed: Timeout waiting for page to load"
     *   (URL not responding or page infinite loop)
     * - "PDF generation failed: Navigation failed: net::ERR_NAME_NOT_RESOLVED"
     *   (URL doesn't exist or DNS error)
     */
    public function generatePdf($url, $pdfOptions = [])
    {
        // Default PDF options
        $defaultOptions = [
            'format' => 'A4',
            'printBackground' => true,
            'preferCSSPageSize' => true,
            'scale' => 1,
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]
        ];

        $pdfOptions = array_merge($defaultOptions, $pdfOptions);

        // Build request payload
        $payload = json_encode([
            'url' => $url,
            'pdfOptions' => $pdfOptions
        ]);

        // Call Puppeteer microservice
        $ch = curl_init($this->puppeteerUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // Check for errors
        if ($httpCode !== 200) {
            if ($error) {
                throw new \Exception("PDF generation failed: {$error}");
            }
            $response_data = json_decode($response, true);
            $msg = $response_data['error'] ?? 'Unknown error';
            throw new \Exception("PDF generation failed: {$msg}");
        }

        return $response;
    }

    /**
     * Stream PDF to browser as download
     * 
     * @param string $pdfContent PDF binary content
     * @param string $filename Filename for download (without .pdf)
     */
    public function downloadPdf($pdfContent, $filename = 'document')
    {
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filename = substr($filename, 0, 100); // Max 100 chars

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $pdfContent;
    }

    /**
     * Stream PDF to browser inline (display)
     * 
     * @param string $pdfContent PDF binary content
     * @param string $filename Filename (without .pdf)
     */
    public function viewPdf($pdfContent, $filename = 'document')
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filename = substr($filename, 0, 100);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $pdfContent;
    }

    /**
     * Test if Puppeteer service is available
     * 
     * @return bool True if service is reachable
     */
    public function isAvailable()
    {
        $ch = curl_init('http://pdf-renderer:3000/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $httpCode === 200;
    }
}

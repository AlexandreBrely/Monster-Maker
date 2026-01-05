<?php

namespace App\Services;

/**
 * PrintService
 * Delegates HTML→PDF rendering to Puppeteer microservice.
 *
 * ORGANIZATION:
 * 1) Config
 * 2) PDF generation
 * 3) Streaming helpers
 * 4) Health check
 */
class PrintService
{
    private $puppeteerUrl = 'http://pdf-renderer:3000/render-pdf';
    private $timeout = 30;

    // ===================================================================
    // SECTION 1: PDF GENERATION
    // ===================================================================

    public function generatePdf($url, $pdfOptions = [])
    {
        $defaultOptions = [
            'format' => 'A4',
            'printBackground' => true,
            'preferCSSPageSize' => true,
            'scale' => 1,
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]
        ];

        $payload = json_encode([
            'url' => $url,
            'pdfOptions' => array_merge($defaultOptions, $pdfOptions)
        ]);

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

        if ($httpCode !== 200) {
            if ($error) {
                throw new \Exception('PDF generation failed: ' . $error);
            }
            $responseData = json_decode($response, true);
            $message = $responseData['error'] ?? 'Unknown error';
            throw new \Exception('PDF generation failed: ' . $message);
        }

        return $response;
    }

    // ===================================================================
    // SECTION 2: STREAMING HELPERS
    // ===================================================================

    public function downloadPdf($pdfContent, $filename = 'document')
    {
        $filename = $this->sanitizeFilename($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $pdfContent;
    }

    public function viewPdf($pdfContent, $filename = 'document')
    {
        $filename = $this->sanitizeFilename($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $pdfContent;
    }

    // ===================================================================
    // SECTION 3: HEALTH CHECK
    // ===================================================================

    public function isAvailable()
    {
        $ch = curl_init('http://pdf-renderer:3000/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $httpCode === 200;
    }

    // ===================================================================
    // SECTION 4: HELPERS
    // ===================================================================

    private function sanitizeFilename($filename): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        return substr($clean, 0, 100);
    }
}

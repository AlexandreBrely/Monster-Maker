const express = require('express');
const puppeteer = require('puppeteer');
const app = express();

app.use(express.json({ limit: '10mb' }));

// Cache browser instance to avoid restart overhead
let browser = null;

// Initialize browser on startup
async function initBrowser() {
    if (!browser) {
        browser = await puppeteer.launch({
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
            headless: 'new'
        });
    }
    return browser;
}

// POST /render-pdf - Generate PDF from HTML
app.post('/render-pdf', async (req, res) => {
    const { url, pdfOptions = {} } = req.body;

    if (!url) {
        return res.status(400).json({ error: 'Missing url parameter' });
    }

    let page = null;
    try {
        const browser = await initBrowser();
        page = await browser.newPage();

        // Set viewport for consistent rendering
        await page.setViewport({ width: 1200, height: 1600 });

        // Navigate to URL and wait for network to be idle
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

        // Wait for fonts to be ready
        await page.evaluate(() => {
            return document.fonts.ready;
        });

        // Generate PDF with default options merged with request options
        const pdfConfig = Object.assign({
            printBackground: true,
            preferCSSPageSize: true,
            scale: 1,
            margin: { top: 0, right: 0, bottom: 0, left: 0 }
        }, pdfOptions);

        const pdf = await page.pdf(pdfConfig);

        res.set('Content-Type', 'application/pdf');
        res.set('Content-Length', pdf.length);
        res.send(pdf);

    } catch (error) {
        console.error('PDF rendering error:', error);
        res.status(500).json({
            error: 'PDF rendering failed',
            message: error.message
        });
    } finally {
        if (page) {
            await page.close();
        }
    }
});

// GET /health - Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', service: 'puppeteer-pdf-renderer' });
});

// Graceful shutdown
process.on('SIGTERM', async () => {
    console.log('SIGTERM received, shutting down...');
    if (browser) {
        await browser.close();
    }
    process.exit(0);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Puppeteer PDF Renderer listening on port ${PORT}`);
    initBrowser().then(() => {
        console.log('Browser instance initialized');
    }).catch(err => {
        console.error('Failed to initialize browser:', err);
        process.exit(1);
    });
});

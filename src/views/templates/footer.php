<!-- Footer: Sticky footer using Bootstrap grid and flexbox utilities -->
<!-- mt-auto: Margin-top auto (pushes footer to bottom when content is short) -->
<!-- py-4: Padding top/bottom (4 units = 1.5rem) -->
<footer class="mt-auto bg-dark text-white py-4">
    <div class="container-fluid">
        <!-- Bootstrap grid row with vertical alignment -->
        <div class="row align-items-center">
            <!-- Left column: Links -->
            <div class="col-md-4 text-center text-md-start">
                <!-- Terms of Service link -->
                <a href="index.php?url=terms" class="text-white text-decoration-none">Terms of Service</a>
            </div>
            
            <!-- Center column: Branding -->
            <div class="col-md-4 text-center">
                <p class="mb-0 fw-bold">Monster Maker !</p>
            </div>
            
            <!-- Right column: Copyright -->
            <div class="col-md-4 text-center text-md-end">
                <!-- &copy; is HTML entity for © symbol -->
                <p class="mb-0">&copy; 2025 Alex, LaKobolderie</p>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript: Load shared monster form behaviors before Bootstrap -->
<script src="/js/monster-form.js"></script> <!-- Form behavior: dynamic sections & ability mods -->

<!-- html2canvas: Library for converting HTML elements to images (used for card downloads) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Card Download: Custom script for downloading monster cards as images -->
<script src="/js/card-download.js"></script>

<!-- Bootstrap 5.3.8 JavaScript bundle includes Popper.js for dropdowns/tooltips -->
<!-- integrity attribute ensures CDN file hasn't been tampered with (security) -->
<!-- crossorigin="anonymous" allows loading from different origin (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>

</html>

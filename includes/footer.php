    <!-- PDF.js library via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        // Set workerSrc PDF.js agar bisa melakukan rendering di sisi klien
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    </script>
    
    <!-- Main Script Kontroller Aplikasi -->
    <script src="assets/js/main.js?v=<?= time(); ?>"></script>
</body>
</html>

<?php
// Tailwind + fonts live here so index.php stays clean.
// Production tip: replace the CDN <script> with a compiled stylesheet,
// e.g. <link rel="stylesheet" href="assets/css/app.css">
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { theme: { extend: {
        fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], serif: ['"Playfair Display"', 'Georgia', 'serif'] },
        colors: {
            gold: '#e8b04a',
            navy: { 800: '#10214a', 900: '#0a1633', 950: '#050b1a' }
        }
    } } };
</script>
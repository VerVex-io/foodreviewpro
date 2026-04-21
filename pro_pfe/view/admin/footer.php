    </div> <!-- Close .admin-content or admin-view-standalone -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script>
        // Initialisation universelle pour l'admin
        if (window.VanillaTilt) {
            VanillaTilt.init(document.querySelectorAll(".stat-card"), {
                max: 10,
                speed: 400,
                glare: true,
                "max-glare": 0.1,
            });
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>

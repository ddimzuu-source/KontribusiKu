<?php
/**
 * includes/footer.php
 * Menutup halaman: footer, script Bootstrap, dan JS proyek.
 */
if (!defined('BASE_URL')) {
    define('BASE_URL', '/KontribusiKu');
}
?>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container text-center">
            <p class="text-secondary mb-1">
                &copy; <?= date('Y') ?> KontribusiKu
            </p>
            <small class="text-secondary">
                Kerja sama yang baik dimulai dari kontribusi yang jelas.
            </small>
        </div>
    </footer>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>

</body>
</html>
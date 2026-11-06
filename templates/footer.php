<?php
/**
 * Footer Admin Layout
 * Sistem Informasi Queen Laundry
 */
?>
    </main>

    <!-- Footer Copyright -->
    <footer class="py-3 px-4 bg-white border-top text-center text-muted small no-print">
        &copy; <?= date('Y') ?> Queen Laundry. Sistem Informasi Manajemen Laundry Berbasis Web - Skripsi Denia.
    </footer>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Responsive sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose  = document.getElementById('sidebarClose');
    const adminSidebar  = document.getElementById('adminSidebar');

    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('show');
        });
    }

    if (sidebarClose && adminSidebar) {
        sidebarClose.addEventListener('click', () => {
            adminSidebar.classList.remove('show');
        });
    }

    // Auto dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        });
    }, 5000);
</script>
</body>
</html>


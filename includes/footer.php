        </div>
    </div>

    <script>
        // Update page title dynamically
        document.addEventListener('DOMContentLoaded', function() {
            const pageTitle = document.getElementById('page-title');
            if (pageTitle) {
                document.title = pageTitle.textContent + ' - Sistem Penilaian Kinerja';
            }
            
            // Highlight active menu
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href === currentPage) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
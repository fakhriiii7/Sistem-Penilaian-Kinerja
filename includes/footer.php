        </div>
    </div>

    <style>
       /* ===== FOOTER ===== */
.app-footer {
    background-color: #f8f9fa;
    padding: 15px 0;
    margin-top: 30px;
    margin-left: 250px;
    border-top: 1px solid #e9ecef;
    color: #6c757d;
    font-size: 14px;
    width: calc(100% - 250px);
    border-radius: 10px 10px 0 0;
}


/* ISI FOOTER */
.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-content div:first-child {
    font-weight: 500;
}

.footer-content div:last-child {
    text-align: right;
    line-height: 1.4;
}

/* RESPONSIVE (SIDEBAR HILANG) */
@media (max-width: 992px) {
    .app-footer {
        margin-left: 0;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }

    .footer-content div:last-child {
        text-align: center;
    }
}

    </style>

    <footer class="app-footer">
        <div class="footer-content">
            <div>
                © 2025 Sistem Penilaian Kinerja  
07TPLP018 | Kelompok 2  
Alda Ailsa Alvita · Muhammad Fakhri Azmar · Muhamad Dimas Aditiya ·  
Martuah Pardamean Wijaya Siahaan · Indika Saputra

            </div>
        </div>
    </footer>

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
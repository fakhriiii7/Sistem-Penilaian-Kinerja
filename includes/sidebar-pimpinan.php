<div class="sidebar">
    <div class="sidebar-header">
        <h2>Sistem Penilaian Kinerja</h2>
        <p><?php echo $_SESSION['nama_lengkap']; ?></p>
        <p style="font-size: 11px; opacity: 0.7;">Pimpinan</p>
    </div>
    
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="penilaian.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'penilaian.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i>
            <span>Penilaian</span>
        </a>
        
        <a href="laporan.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Laporan</span>
        </a>
    </nav>
</div>
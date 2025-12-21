<div class="sidebar">
    <div class="sidebar-header">
        <h2>Sistem Penilaian Kinerja</h2>
        <p><?php echo $_SESSION['nama_lengkap']; ?></p>
        <p style="font-size: 11px; opacity: 0.7;">Pegawai</p>
    </div>
    
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="laporan.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Saya</span>
        </a>
        
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>
</div>


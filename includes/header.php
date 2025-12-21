<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistem Penilaian Kinerja Pegawai'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== HEADER UNIVERSAL UNTUK SEMUA AKTOR ===== */
        .main-content .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .main-content .header-left h1 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-content .header-right .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .main-content .user-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .main-content .btn-logout {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .main-content .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Style untuk statistik grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.blue { background: #3498db; }
        .stat-icon.green { background: #2ecc71; }
        .stat-icon.orange { background: #e67e22; }
        .stat-icon.purple { background: #9b59b6; }
        
        .stat-content h3 {
            font-size: 28px;
            margin: 0;
            color: #2c3e50;
        }
        
        .stat-content p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .content-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .main-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .section-header h2 {
            font-size: 18px;
            color: #2c3e50;
            margin: 0;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-aktif {
            background: #d4edda;
            color: #155724;
        }
        
        .status-selesai {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: #f8f9fa;
        }
        
        .data-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .quick-actions-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-align: left;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .quick-action-btn:hover {
            background: #3498db;
            color: white;
            transform: translateX(5px);
        }
        
        .quick-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .quick-action-btn:hover .quick-icon {
            background: white;
            color: #3498db;
        }
        
        .period-info {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .period-info h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .period-dates {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .date-item {
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .main-content .header-left h1 {
                justify-content: center;
            }
            
            .main-content .header-right .user-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR BERDASARKAN ROLE -->
        <?php 
        if (isset($_SESSION['role'])) {
            $role = $_SESSION['role'];
            if ($role == 'admin') {
                include 'sidebar-admin.php';
            } elseif ($role == 'pimpinan') {
                include 'sidebar-pimpinan.php';
            } elseif ($role == 'pegawai') {
                include 'sidebar-pegawai.php';
            }
        } else {
            // Default ke admin jika tidak ada session
            include 'sidebar-admin.php';
        }
        ?>
        
        <div class="main-content">
            <!-- HEADER UNIVERSAL UNTUK SEMUA AKTOR -->
            <div class="header">
                <div class="header-left">
                    <h1 id="page-title">
                        <?php 
                        // Menentukan icon berdasarkan role
                        if (isset($_SESSION['role'])) {
                            $role = $_SESSION['role'];
                            if ($role == 'admin') {
                                echo '<i class="fas fa-user-shield"></i> ';
                            } elseif ($role == 'pimpinan') {
                                echo '<i class="fas fa-user-check"></i> ';
                            } elseif ($role == 'pegawai') {
                                echo '<i class="fas fa-user-tie"></i> ';
                            }
                        } else {
                            echo '<i class="fas fa-chart-line"></i> ';
                        }
                        ?>
                        <?php echo $page_title ?? 'Dashboard'; ?>
                    </h1>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <!-- Badge role dengan warna berbeda -->
                        <?php if (isset($_SESSION['role'])): ?>
                            <div class="user-badge" style="
                                <?php 
                                $role = $_SESSION['role'];
                                if ($role == 'admin') {
                                    echo 'background: rgba(220, 53, 69, 0.2);';
                                } elseif ($role == 'pimpinan') {
                                    echo 'background: rgba(13, 110, 253, 0.2);';
                                } elseif ($role == 'pegawai') {
                                    echo 'background: rgba(25, 135, 84, 0.2);';
                                }
                                ?>
                            ">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo ucfirst($role); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <span class="user-name"><?php echo $_SESSION['nama_lengkap'] ?? 'Pengguna'; ?></span>
                        <a href="../logout.php" class="btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?>">
                    <?php echo $_SESSION['flash_message']['message']; ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
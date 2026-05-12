<?php
// includes/auth.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/activity_logger.php';

// SISTEM MERGER ROLE (Jalankan setiap load untuk sinkronisasi)
try {
    $final_roles = [
        'Super Admin', 
        'Pranata Humas', 
        'Operator Kliping', 
        'Operator Berita Online', 
        'Operator Foto/Video'
    ];

    // 1. Pastikan Role Final Ada
    foreach ($final_roles as $r) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
        $stmt->execute([$r]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)")->execute([$r]);
        }
    }

    // 2. Mapping & Merger (Old -> New)
    $merger_map = [
        'Admin' => 'Super Admin',
        'Petugas Kliping' => 'Operator Kliping',
        'Petugas Link' => 'Operator Berita Online',
        'Petugas Foto' => 'Operator Foto/Video'
    ];

    foreach ($merger_map as $old_name => $new_name) {
        // Cari ID Baru
        $stmt_new = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
        $stmt_new->execute([$new_name]);
        $new_id = $stmt_new->fetchColumn();

        if ($new_id) {
            // Update User yang masih pakai Role Lama (berdasarkan nama role lama)
            $pdo->prepare("UPDATE users u 
                           JOIN roles r ON u.role_id = r.id 
                           SET u.role_id = ? 
                           WHERE r.role_name = ?")->execute([$new_id, $old_name]);

            // Hapus Role Lama jika sudah tidak ada user yang memakai
            // Tapi hati-hati, kita hapus saja yang namanya memang nama lama
            if ($old_name !== $new_name) {
                $pdo->prepare("DELETE FROM roles WHERE role_name = ? AND id != ?")->execute([$old_name, $new_id]);
            }
        }
    }
} catch (Exception $e) {
    // Silently fail if DB not ready
}

function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') return "Akun Anda dinonaktifkan.";
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['role_id'] = $user['role_id'];
        return true;
    }
    return "Username atau password salah.";
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function checkAccess($allowed_roles = []) {
    global $pdo;
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }

    // Selalu refresh role dari DB untuk memastikan sinkronisasi merger
    $stmt = $pdo->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_role = $stmt->fetchColumn();
    $_SESSION['role'] = $current_role;
    
    // Check Maintenance Mode
    require_once __DIR__ . '/settings_helper.php';
    if (get_setting('maintenance_mode', '0') === '1' && $_SESSION['role'] !== 'Super Admin') {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px; background:#fff; height:100vh; display:flex; flex-direction:column; justify-content:center;'>
                <i class='fas fa-tools fa-5x' style='color:#f39c12; margin-bottom:20px;'></i>
                <h1 style='color:#333; margin-bottom:10px;'>Sistem Dalam Pemeliharaan</h1>
                <p style='color:#666; font-size:18px;'>Maaf, aplikasi saat ini sedang dalam proses pemeliharaan/upgrade rutin.<br>Silakan kembali beberapa saat lagi.</p>
                <div style='margin-top:30px;'><a href='logout.php' style='padding:10px 20px; background:#e74c3c; color:white; text-decoration:none; border-radius:5px;'>Keluar</a></div>
            </div>");
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h1 style='color:#ef4444;'>Akses Ditolak</h1>
                <p>Maaf, level akun Anda (<b>{$_SESSION['role']}</b>) tidak diizinkan mengakses halaman ini.</p>
                <a href='index.php' style='color:#3b82f6;'>Kembali ke Dashboard</a>
            </div>");
    }
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

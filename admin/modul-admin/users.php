<?php
// admin/users.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas']);

$message = "";
$edit_mode = false;
$edit_data = null;

$roles_list = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

if (isset($_POST['save_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $role_id = $_POST['role_id'];
    $status = $_POST['status'];
    
    // Cegah Pranata Humas membuat/mengedit Super Admin
    $stmt_r = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt_r->execute([$role_id]);
    $r_name = $stmt_r->fetchColumn();
    
    if ($_SESSION['role'] === 'Pranata Humas' && ($r_name === 'Super Admin' || $r_name === 'Pranata Humas')) {
        $message = "Gagal! Anda tidak memiliki izin untuk mengelola level ini.";
    } else {
        // Check for duplicate username
        if (isset($_POST['id'])) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $_POST['id']]);
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
        }

        if ($check->fetch()) {
            $message = "Gagal! Username '$username' sudah digunakan oleh akun lain.";
        } else {
            if (isset($_POST['id'])) {
                $id = $_POST['id'];
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, password = ?, role_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $password, $role_id, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $role_id, $status, $id]);
                }
                write_log($pdo, "Memperbarui user: $username", "Manajemen User", $id);
                header("Location: users.php?success=Updated");
            } else {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role_id, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $username, $password, $role_id, $status]);
                $new_id = $pdo->lastInsertId();
                write_log($pdo, "Menambah user baru: $username", "Manajemen User", $new_id);
                header("Location: users.php?success=Added");
            }
            exit();
        }
    }
}

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

$query = "SELECT u.*, r.role_name 
          FROM users u 
          LEFT JOIN roles r ON u.role_id = r.id 
          ORDER BY r.id ASC, u.full_name ASC";
$users = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Manajemen Akun Pengguna</h1>
            <p class="sub-title">Kelola hak akses dan akun operator Humas Hub.</p>
        </header>

        <?php if ($message): ?>
            <div style="background: #FFF5F5; color: #C53030; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #FEB2B2; font-weight: 600;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #dcfce7; color: #15803d; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> User berhasil <?php echo htmlspecialchars($_GET['success']); ?>!
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
            <!-- Form Area -->
            <div class="card" style="height: fit-content;">
                <h3 style="margin-bottom: 20px; font-size: 16px; color: var(--navy);">
                    <?php echo $edit_mode ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?>
                </h3>
                <form method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">NAMA LENGKAP</label>
                        <input type="text" name="full_name" class="stitch-select" value="<?php echo $edit_mode ? $edit_data['full_name'] : ''; ?>" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">USERNAME</label>
                        <input type="text" name="username" class="stitch-select" value="<?php echo $edit_mode ? $edit_data['username'] : ''; ?>" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">PASSWORD <?php echo $edit_mode ? '(Kosongkan jika tetap)' : ''; ?></label>
                        <input type="password" name="password" class="stitch-select" <?php echo $edit_mode ? '' : 'required'; ?>>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">LEVEL / HAK AKSES</label>
                        <select name="role_id" class="stitch-select" required>
                            <?php foreach ($roles_list as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo ($edit_mode && $edit_data['role_id'] == $r['id']) ? 'selected' : ''; ?>><?php echo $r['role_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">STATUS AKUN</label>
                        <select name="status" class="stitch-select">
                            <option value="active" <?php echo ($edit_mode && $edit_data['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo ($edit_mode && $edit_data['status'] == 'inactive') ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>

                    <button type="submit" name="save_user" class="btn btn-primary" style="width: 100%; justify-content: center; height: 45px;">
                        <i class="fas fa-save"></i> SIMPAN PENGGUNA
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="users.php" class="btn" style="width: 100%; justify-content: center; background: #f1f5f9; color: #64748b; margin-top: 10px;">BATAL</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Area -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <div class="table-wrapper" style="margin-top: 0; border: none; border-radius: 0;">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Pengguna</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $idx => $u): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--navy);"><?php echo $u['full_name']; ?></div>
                                    <div style="font-size: 12px; color: #94a3b8;">@<?php echo $u['username']; ?></div>
                                </td>
                                <td>
                                    <span style="font-size: 12px; font-weight: 700; color: var(--primary); text-transform: uppercase;"><?php echo $u['role_name']; ?></span>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge" style="background: #ecfdf5; color: #10b981;">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #fef2f2; color: #ef4444;">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $u['id']; ?>" class="btn-circle btn-circle-edit"><i class="fas fa-pen"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// admin/modul-fotovideo/detail.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Foto/Video']);

if (!isset($_GET['id'])) {
    header("Location: daftar.php");
    exit();
}

$id = $_GET['id'];

// Fetch main documentation with author name and categories
$query = "SELECT d.*, u.full_name as author_name, GROUP_CONCAT(cat.name SEPARATOR ', ') as category_names
          FROM documentation d 
          LEFT JOIN users u ON d.created_by = u.id 
          LEFT JOIN documentation_category_rel rel ON d.id = rel.documentation_id
          LEFT JOIN categories cat ON rel.category_id = cat.id
          WHERE d.id = ?
          GROUP BY d.id";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) die("Data tidak ditemukan.");

// Fetch categories for badges
$stmt_cats = $pdo->prepare("SELECT cat.name FROM documentation_category_rel rel JOIN categories cat ON rel.category_id = cat.id WHERE rel.documentation_id = ?");
$stmt_cats->execute([$id]);
$categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

// Fetch attendance with custom hierarchy order
$stmt_att = $pdo->prepare("SELECT * FROM documentation_attendance 
                           WHERE documentation_id = ? 
                           ORDER BY 
                            CASE 
                                WHEN level = 'Rektorat' THEN 1 
                                WHEN level = 'Fakultas' THEN 2 
                                WHEN level = 'Lainnya' THEN 3 
                                ELSE 4 
                            END, id ASC");
$stmt_att->execute([$id]);
$attendance = $stmt_att->fetchAll();

// Fungsi Helper Konversi GDrive URL untuk Thumbnail
function getDirectImageUrl($url) {
    if (!$url) return '';
    if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    if (preg_match('/drive\.google\.com\/open\?id=([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    return $url;
}

// Convert GDrive Folder URL to Embed URL
function getEmbedUrl($url) {
    if (!$url) return '';
    $folderMatch = preg_match('/\/folders\/([a-zA-Z0-9-_]+)/', $url, $matches);
    if ($folderMatch && isset($matches[1])) {
        return "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#grid";
    }
    $fileMatch = preg_match('/\/file\/d\/([a-zA-Z0-9-_]+)/', $url, $matches);
    if ($fileMatch && isset($matches[1])) {
        return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
    }
    return $url;
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Detail Dokumentasi | Humas Hub</title>
    
    <!-- Existing Admin Theme -->
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind Config -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class", 
            theme: {
                extend: {
                    colors: {
                        primary: "#27ae60", 
                        background: "#F4F7F6",
                        surface: "#FFFFFF",
                        "on-surface": "#2E3E5C",
                        "on-surface-variant": "#7f8c8d",
                        outline: "#cbd5e0",
                        "outline-variant": "#eee",
                        "primary-container": "#E1F5E3",
                        "on-primary-container": "#27ae60",
                        "secondary-container": "#F4F7F6",
                        "tertiary-container": "#F9FAFB",
                        "on-tertiary-container": "#2E3E5C",
                        "surface-container-lowest": "#FFFFFF",
                        "surface-container-low": "#F9FAFB",
                        "surface-container": "#F4F7F6",
                        "surface-container-high": "#EAECF0",
                        "surface-container-highest": "#E2E8F0"
                    }, 
                    borderRadius: {
                        DEFAULT: "7px", 
                        sm: "7px",
                        md: "7px",
                        lg: "7px", 
                        xl: "7px", 
                        "2xl": "7px",
                        "3xl": "7px",
                        full: "9999px"
                    }, 
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                        display: ['Inter', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined {
          font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #bec9be; border-radius: 7px; }
        ::-webkit-scrollbar-thumb:hover { background: #6f7a70; }

        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #F4F7F6 !important;
        }

        .main-content {
            padding: 30px;
            background: #F4F7F6;
            min-height: 100vh;
        }
        
        .main-title {
            font-family: 'Inter', sans-serif !important;
            font-weight: 800;
        }
        
        /* Ensure Tailwind components don't clash with sidebar */
        aside, .sidebar { z-index: 1000; }
        
        /* Typography overrides to match design system */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Inter', sans-serif !important;
            color: #2E3E5C;
        }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">

    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <h1 class="main-title">Detail Dokumentasi</h1>
                <p class="sub-title"><?php echo htmlspecialchars($doc['event_name']); ?></p>
            </div>
            <a href="edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary" style="text-decoration: none;">
                <i class="fas fa-edit"></i> Edit Dokumentasi
            </a>
        </header>

        <div class="max-w-[1200px] w-full flex flex-col gap-8">
            
            <!-- Back Action -->
            <div class="w-full">
                <a class="inline-flex items-center gap-1 text-primary font-bold hover:underline transition-all" href="daftar.php" style="text-decoration: none; font-size: 14px;">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Kembali ke Daftar
                </a>
            </div>

            <!-- Hero Header Section (Thumbnail) -->
            <section class="w-full rounded-lg overflow-hidden shadow-sm border border-outline-variant bg-white relative group">
                <div class="aspect-[16/9] md:aspect-[21/9] lg:aspect-[3/1] w-full bg-slate-100 relative">
                    <?php if ($doc['thumbnail_url']): ?>
                        <img class="w-full h-full object-cover" src="<?php echo getDirectImageUrl($doc['thumbnail_url']); ?>" alt="<?php echo htmlspecialchars($doc['event_name']); ?>"/>
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-slate-100">
                            <span class="material-symbols-outlined text-slate-300 text-6xl">image</span>
                        </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
            </section>

            <!-- Information Bento Grid -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 w-full">
                <!-- Main Content Column -->
                <div class="lg:col-span-8 flex flex-col gap-8">
                    <!-- Card: Info & Deskripsi -->
                    <div class="bg-white p-8 rounded-lg shadow-sm border border-outline-variant flex flex-col gap-6">
                        <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800 leading-tight">
                            <?php echo htmlspecialchars($doc['event_name']); ?>
                        </h2>
                        <div class="w-16 h-1.5 bg-primary rounded-full"></div>
                        <p class="text-base text-slate-600 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($doc['description'] ?: 'Tidak ada deskripsi tersedia.')); ?>
                        </p>
                        
                        <?php if ($doc['news_link']): ?>
                            <div class="mt-2 pt-6 border-t border-slate-100">
                                <a href="<?php echo htmlspecialchars($doc['news_link']); ?>" target="_blank" class="inline-flex items-center gap-2 text-primary font-bold hover:underline" style="text-decoration: none; font-size: 14px;">
                                    <span class="material-symbols-outlined text-sm">link</span>
                                    Baca berita terkait di Web Resmi
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card: Kehadiran -->
                    <?php if (!empty($attendance)): ?>
                    <div class="bg-white p-8 rounded-lg shadow-sm border border-outline-variant">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">groups</span>
                            Pimpinan & Tokoh yang Hadir
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <?php 
                            $current_level = '';
                            foreach ($attendance as $att): 
                                if ($current_level != $att['level']):
                                    $current_level = $att['level'];
                                    $display_label = 'Lainnya'; // Fallback label
                                    if ($current_level == 'Rektorat') $display_label = 'Pimpinan Rektorat';
                                    elseif ($current_level == 'Fakultas') $display_label = 'Pimpinan Fakultas/Pasca';
                                    elseif ($current_level == 'Lainnya') $display_label = 'Tokoh/Pejabat Lainnya';
                                    else $display_label = !empty($current_level) ? $current_level : 'Tokoh/Pejabat Lainnya';
                            ?>
                                <div class="col-span-full">
                                    <p class="text-[10px] font-black uppercase tracking-[2px] text-primary mt-4 first:mt-0"><?php echo $display_label; ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="flex flex-col">
                                <p class="text-base font-bold text-slate-700 mb-0.5"><?php echo htmlspecialchars($att['person_name']); ?></p>
                                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($att['position']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Detail Card Column -->
                <div class="lg:col-span-4 flex flex-col bg-white p-8 rounded-lg shadow-sm border border-outline-variant">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">info</span>
                        Detail Kegiatan
                    </h3>
                    <ul class="flex flex-col gap-6" style="list-style: none; padding: 0; margin: 0;">
                        <li class="flex items-start gap-4">
                            <div class="p-2 bg-slate-50 rounded text-slate-500">
                                <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tanggal</p>
                                <p class="text-sm font-semibold text-slate-700"><?php echo tgl_indo($doc['event_date']); ?></p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="p-2 bg-slate-50 rounded text-slate-500">
                                <span class="material-symbols-outlined text-[20px]">category</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kategori</p>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <?php foreach ($categories as $cat): ?>
                                        <span class="px-2 py-0.5 bg-green-50 text-green-600 rounded text-[10px] font-bold uppercase border border-green-100"><?php echo htmlspecialchars($cat); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="p-2 bg-slate-50 rounded text-slate-500">
                                <span class="material-symbols-outlined text-[20px]">location_on</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Lokasi</p>
                                <p class="text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($doc['location_name']); ?></p>
                                <span class="inline-block mt-1 px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] font-bold uppercase"><?php echo $doc['location_type']; ?></span>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="p-2 bg-slate-50 rounded text-slate-500">
                                <span class="material-symbols-outlined text-[20px]">person_pin</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kredit Media</p>
                                <p class="text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($doc['creator_name'] ?: '-'); ?></p>
                                <p class="text-xs text-slate-400 mt-1">Dibuat oleh: <?php echo htmlspecialchars($doc['author_name']); ?></p>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Google Drive Gallery Section -->
            <?php 
            $has_photo = !empty($doc['photo_folder_link']);
            $has_video = !empty($doc['video_folder_link']);
            $grid_cols = ($has_photo && $has_video) ? 'md:grid-cols-2' : 'grid-cols-1';
            ?>
            <section class="w-full mt-4 bg-white p-8 rounded-lg shadow-sm border border-outline-variant flex flex-col gap-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-50 rounded-lg text-primary">
                            <span class="material-symbols-outlined text-[28px]">folder_shared</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-800" style="margin:0;">Galeri Dokumentasi</h2>
                            <p class="text-sm text-slate-400" style="margin:0;">Akses folder foto dan video via Google Drive</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($has_photo): ?>
                            <a href="<?php echo $doc['photo_folder_link']; ?>" target="_blank" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg font-bold hover:bg-blue-100 transition-all text-xs" style="text-decoration: none;">
                                <span class="material-symbols-outlined text-[18px]">image</span>
                                Folder Foto
                            </a>
                        <?php endif; ?>
                        <?php if ($has_video): ?>
                            <a href="<?php echo $doc['video_folder_link']; ?>" target="_blank" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-50 text-purple-600 border border-purple-100 rounded-lg font-bold hover:bg-purple-100 transition-all text-xs" style="text-decoration: none;">
                                <span class="material-symbols-outlined text-[18px]">video_library</span>
                                Folder Video
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Google Drive Embeds -->
                <div class="grid <?php echo $grid_cols; ?> gap-8">
                    <?php if ($has_photo): ?>
                    <div class="flex flex-col gap-3">
                        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2" style="margin:0;"><span class="material-symbols-outlined text-blue-500">photo_library</span> Pratinjau Foto</h4>
                        <div class="w-full h-[500px] rounded-lg overflow-hidden border border-slate-100 bg-slate-50">
                            <iframe src="<?php echo getEmbedUrl($doc['photo_folder_link']); ?>" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_video): ?>
                    <div class="flex flex-col gap-3">
                        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2" style="margin:0;"><span class="material-symbols-outlined text-purple-500">video_camera_back</span> Pratinjau Video</h4>
                        <div class="w-full h-[500px] rounded-lg overflow-hidden border border-slate-100 bg-slate-50">
                            <iframe src="<?php echo getEmbedUrl($doc['video_folder_link']); ?>" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$has_photo && !$has_video): ?>
                        <div class="col-span-full py-20 flex flex-col items-center justify-center text-slate-300">
                            <span class="material-symbols-outlined text-6xl mb-4 opacity-20">folder_off</span>
                            <p class="font-bold text-lg">Foto/Video Belum ditambahkan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <footer style="margin-top: 60px; padding-top: 30px; border-top: 1px solid #EAECF0; text-align: center; color: #94a3b8; font-size: 13px;">
            <p>© <?php echo date('Y'); ?> <?php echo get_setting('app_name', 'Humas Hub'); ?>. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

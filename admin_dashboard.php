<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// Periksa sesi admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Konfigurasi koneksi ke database
$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil statistik absensi hari ini
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as total_absensi FROM absences WHERE DATE(waktu) = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error prepare SELECT: " . $conn->error);
}
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$total_absensi = $result->fetch_assoc()['total_absensi'];
$stmt->close();

// Ambil jumlah pengguna
$sql = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($sql);
if ($result === false) {
    die("Error query users: " . $conn->error);
}
$total_users = $result->fetch_assoc()['total_users'];

$conn->close();

// Ambil nama admin dari sesi (asumsi nama disimpan saat login)
$admin_name = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Admin';
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PT INDO TEKHNO PLUS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                    },
                    boxShadow: {
                        card: '0 0 20px rgba(0, 0, 0, 0.08)',
                        'card-hover': '0 10px 25px rgba(0, 0, 0, 0.12)',
                    },
                },
                fontFamily: {
                    sans: ['Poppins', 'sans-serif'],
                }
            }
        }
    </script>
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .action-card {
            background: linear-gradient(145deg, #0ea5e9, #0369a1);
            color: white;
            text-align: center;
            border-radius: 12px;
            padding: 1.5rem;
            transition: transform 0.3s ease, background 0.3s ease;
        }
        
        .action-card:hover {
            transform: scale(1.05);
            background: linear-gradient(145deg, #0369a1, #0ea5e9);
        }
        
        .action-card.logout {
            background: linear-gradient(145deg, #dc2626, #b91c1c);
        }
        
        .action-card.logout:hover {
            background: linear-gradient(145deg, #b91c1c, #dc2626);
        }
        
        .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        /* Text Animation Styles */
        .animated-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0369a1;
            text-align: center;
            margin: 2rem 0;
            letter-spacing: 2px;
        }
        
        .animated-title span {
            display: inline-block;
            opacity: 0;
            transform: translateY(20px);
            animation: letterBounce 0.5s ease forwards;
        }
        
        @keyframes letterBounce {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            60% {
                transform: translateY(-5px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Staggered delay for each letter */
        .animated-title span:nth-child(1) { animation-delay: 0.05s; }
        .animated-title span:nth-child(2) { animation-delay: 0.1s; }
        .animated-title span:nth-child(3) { animation-delay: 0.15s; }
        .animated-title span:nth-child(4) { animation-delay: 0.2s; }
        .animated-title span:nth-child(5) { animation-delay: 0.25s; }
        .animated-title span:nth-child(6) { animation-delay: 0.3s; }
        .animated-title span:nth-child(7) { animation-delay: 0.35s; }
        .animated-title span:nth-child(8) { animation-delay: 0.4s; }
        .animated-title span:nth-child(9) { animation-delay: 0.45s; }
        .animated-title span:nth-child(10) { animation-delay: 0.5s; }
        .animated-title span:nth-child(11) { animation-delay: 0.55s; }
        .animated-title span:nth-child(12) { animation-delay: 0.6s; }
        .animated-title span:nth-child(13) { animation-delay: 0.65s; }
        .animated-title span:nth-child(14) { animation-delay: 0.7s; }
        .animated-title span:nth-child(15) { animation-delay: 0.75s; }
        .animated-title span:nth-child(16) { animation-delay: 0.8s; }
        .animated-title span:nth-child(17) { animation-delay: 0.85s; }
        .animated-title span:nth-child(18) { animation-delay: 0.9s; }
        .animated-title span:nth-child(19) { animation-delay: 0.95s; }
        .animated-title span:nth-child(20) { animation-delay: 1s; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Animation utilities */
        .animate-fadeIn {
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .container {
                padding: 0.5rem;
            }
            .animated-title {
                font-size: 1.8rem;
            }
            .card, .action-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="header-gradient sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white p-2 rounded-lg shadow-md">
                        <i class="fas fa-building text-primary-600 text-xl"></i>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-white">PT. INDO TEKHNO PLUS</h1>
                </div>
              
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        <!-- Animated Title -->
        <div class="animated-title">
            <?php
            $title = "PT. INDO TEKHNO PLUS";
            // Split title into individual characters, preserving spaces
            for ($i = 0; $i < strlen($title); $i++) {
                $char = $title[$i];
                echo "<span>" . htmlspecialchars($char) . "</span>";
            }
            ?>
        </div>

        <!-- Welcome Message -->
        <div class="text-center mb-8 animate-fadeIn">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard Admin</h2>
            <p class="text-lg text-gray-600 mt-2">Selamat datang, <?php echo $admin_name; ?>!</p>
        </div>

        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-fadeIn">
            <div class="card flex items-center">
                <div class="flex-shrink-0 mr-4">
                    <i class="fas fa-calendar-check icon text-primary-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700">Absensi Hari Ini</h3>
                    <p class="text-3xl font-bold text-primary-600"><?php echo $total_absensi; ?></p>
                    <p class="text-sm text-gray-500 mt-1">Jumlah karyawan yang absen hari ini.</p>
                </div>
            </div>
            <div class="card flex items-center">
                <div class="flex-shrink-0 mr-4">
                    <i class="fas fa-users icon text-primary-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700">Total Pengguna</h3>
                    <p class="text-3xl font-bold text-primary-600"><?php echo $total_users; ?></p>
                    <p class="text-sm text-gray-500 mt-1">Jumlah karyawan terdaftar.</p>
                </div>
            </div>
        </div>

        <!-- Tautan Aksi -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-fadeIn">
            <a href="absensi_lengkap.php" class="action-card">
                <i class="fas fa-table icon"></i>
                <h3 class="text-lg font-semibold mb-2">Lihat Absensi</h3>
                <p class="text-sm">Tampilkan semua data absensi dan peta lokasi.</p>
            </a>
            <a href="set_work_hours.php" class="action-card">
                <i class="fas fa-clock icon"></i>
                <h3 class="text-lg font-semibold mb-2">Pengaturan Jam Kerja</h3>
                <p class="text-sm">Atur jam kerja karyawan.</p>
            </a>
            <a href="manage_users.php" class="action-card">
                <i class="fas fa-user-cog icon"></i>
                <h3 class="text-lg font-semibold mb-2">Kelola Pengguna</h3>
                <p class="text-sm">Tambah, edit, atau hapus akun karyawan.</p>
            </a>
            <a href="logout.php" class="action-card logout">
                <i class="fas fa-sign-out-alt icon"></i>
                <h3 class="text-lg font-semibold mb-2">Logout</h3>
                <p class="text-sm">Keluar dari sesi admin.</p>
            </a>
        </div>
    </main>
</body>
</html>
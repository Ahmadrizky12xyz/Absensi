<?php
// session_start(); // Removed to avoid duplicate calls
$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nama, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$nama = $user['nama'];
$role = $user['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi PT Indotekhnoplus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .btn { transition: transform 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-2px); }
        .dropdown { display: none; }
        .dropdown.active { display: block; }
        @media (max-width: 640px) {
            .nav-links { display: none; }
            .dropdown.active { display: block; }
        }
    </style>
    <script>
        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('active');
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">Absensi PT Indotekhnoplus</h1>
            <nav class="nav-links flex items-center space-x-4">
                <a href="index.php" class="hover:underline">Beranda</a>
                <a href="absensi_lengkap.php" class="hover:underline">Absensi</a>
                <?php if ($role === 'admin') { ?>
                    <a href="set_work_hours.php" class="hover:underline">Pengaturan Jam Kerja</a>
                    <a href="manage_users.php" class="hover:underline">Kelola Pengguna</a>
                <?php } ?>
                <div class="relative group">
                    <span class="cursor-pointer hover:underline"><?php echo htmlspecialchars($nama); ?></span>
                    <div class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-lg shadow-lg hidden group-hover:block">
                        <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </nav>
            <button onclick="toggleDropdown()" class="sm:hidden focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
        </div>
        <div id="dropdown" class="dropdown bg-blue-600 text-white sm:hidden">
            <a href="index.php" class="block px-4 py-2 hover:bg-blue-700">Beranda</a>
            <a href="absensi_lengkap.php" class="block px-4 py-2 hover:bg-blue-700">Absensi</a>
            <?php if ($role === 'admin') { ?>
                <a href="set_work_hours.php" class="block px-4 py-2 hover:bg-blue-700">Pengaturan Jam Kerja</a>
                <a href="manage_users.php" class="block px-4 py-2 hover:bg-blue-700">Kelola Pengguna</a>
            <?php } ?>
            <a href="logout.php" class="block px-4 py-2 hover:bg-blue-700">Logout</a>
        </div>
    </header>
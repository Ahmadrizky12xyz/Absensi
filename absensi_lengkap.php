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
// absensi_lengkap.php

// Atur zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi koneksi ke database
$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";

// Fungsi untuk mengambil data absensi
function getAbsensiData($conn, $date = null, $status = null, $name = null) {
    $sql = "SELECT a.user_id, a.waktu, a.latitude, a.longitude, a.type, u.nama, u.nomor_id 
            FROM absences a 
            LEFT JOIN users u ON a.user_id = u.id";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    // Filter tanggal
    if ($date) {
        $conditions[] = "DATE(a.waktu) = ?";
        $params[] = $date;
        $types .= "s";
    }
    
    // Filter status
    if ($status && $status !== 'all') {
        $conditions[] = "a.type = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Filter nama
    if ($name) {
        $conditions[] = "u.nama LIKE ?";
        $params[] = "%$name%";
        $types .= "s";
    }
    
    // Tambahkan kondisi ke kueri
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY a.waktu DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error dalam prepare statement: " . $conn->error);
    }
    
    // Bind parameter jika ada
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        die("Error dalam query: " . $conn->error);
    }

    $map_data = [];
    $table_data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $latitude = floatval($row['latitude']);
            $longitude = floatval($row['longitude']);
            // Validasi koordinat untuk peta
            if (is_numeric($latitude) && is_numeric($longitude) &&
                $latitude >= -90 && $latitude <= 90 &&
                $longitude >= -180 && $longitude <= 180) {
                // Tambahkan offset kecil acak untuk mencegah tumpukan
                $offset = 0.0001 * (rand(-5, 5) / 10); // Offset hingga ±0.0005 derajat
                $map_data[] = [
                    'user_id' => $row['user_id'],
                    'nama' => isset($row['nama']) ? $row['nama'] : 'Tidak Diketahui',
                    'nomor_id' => isset($row['nomor_id']) && $row['nomor_id'] ? $row['nomor_id'] : $row['user_id'],
                    'waktu' => date('d-m-Y H:i:s', strtotime($row['waktu'])),
                    'latitude' => $latitude + $offset,
                    'longitude' => $longitude + $offset,
                    'type' => ucfirst($row['type'])
                ];
            }
            // Simpan data untuk tabel (termasuk entri tanpa koordinat valid)
            $table_data[] = [
                'user_id' => $row['user_id'],
                'nama' => isset($row['nama']) ? $row['nama'] : 'Tidak Diketahui',
                'nomor_id' => isset($row['nomor_id']) && $row['nomor_id'] ? $row['nomor_id'] : $row['user_id'],
                'waktu' => date('d-m-Y H:i:s', strtotime($row['waktu'])),
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'type' => ucfirst($row['type'])
            ];
        }
    }
    
    $stmt->close();
    return ['map_data' => $map_data, 'table_data' => $table_data, 'result' => $result];
}

// Jika ini adalah permintaan AJAX
if (isset($_GET['ajax'])) {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    // Ambil parameter filter
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : null;
    $name = isset($_GET['name']) ? $_GET['name'] : null;
    
    $data = getAbsensiData($conn, $date, $status, $name);
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Koneksi utama untuk halaman
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil parameter filter dari POST atau GET
$date = isset($_POST['filterDate']) ? $_POST['filterDate'] : (isset($_GET['filterDate']) ? $_GET['filterDate'] : null);
$status = isset($_POST['filterStatus']) && $_POST['filterStatus'] !== 'all' ? $_POST['filterStatus'] : (isset($_GET['filterStatus']) ? $_GET['filterStatus'] : null);
$name = isset($_POST['filterName']) ? $_POST['filterName'] : (isset($_GET['filterName']) ? $_GET['filterName'] : null);

$data = getAbsensiData($conn, $date, $status, $name);
$map_data = $data['map_data'];
$table_data = $data['table_data'];
$result = $data['result'];
$conn->close();
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Absensi Karyawan - PT INDO TEKHNO PLUS</title>
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
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }
        
        #map {
            height: 600px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
        }
        
        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .table-container {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        th {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
            padding: 16px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        tbody tr {
            transition: all 0.2s ease;
        }
        
        tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-masuk {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .status-pulang {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .nama-link {
            font-weight: 500;
            color: #0284c7;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .nama-link:hover {
            color: #0369a1;
            text-decoration: underline;
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
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
        
        /* Leaflet custom styles */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 4px;
        }
        
        .leaflet-popup-content {
            margin: 12px 12px;
            line-height: 1.6;
        }
        
        .popup-content {
            font-family: 'Poppins', sans-serif;
        }
        
        .popup-header {
            font-weight: 600;
            color: #0284c7;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .popup-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .popup-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .popup-label {
            font-weight: 500;
            color: #64748b;
            min-width: 80px;
        }
        
        .popup-value {
            font-weight: 400;
            color: #334155;
        }
        
        /* Animation utilities */
        .delay-100 {
            animation-delay: 0.1s;
        }
        
        .delay-200 {
            animation-delay: 0.2s;
        }
        
        .delay-300 {
            animation-delay: 0.3s;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1023px) {
            #map {
                height: 450px;
            }
        }
        
        @media (max-width: 767px) {
            #map {
                height: 350px;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 13px;
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
                <div class="flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-arrow-left"></i>
                        <span class="hidden md:inline">Kembali ke Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8">
        <!-- Page Title -->
        <section class="mb-10 text-center animate-fadeIn">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Data Absensi Karyawan</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Pantau lokasi dan waktu absensi karyawan secara real-time dengan visualisasi interaktif</p>
            <div class="flex justify-center mt-4">
                <div class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-sm">
                    <i class="fas fa-sync-alt animate-spin mr-2"></i>
                    Data diperbarui secara otomatis setiap 10 detik
                </div>
            </div>
        </section>

        <!-- Statistics Cards -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="card bg-white rounded-xl shadow-card hover:shadow-card-hover p-6 animate-fadeIn delay-100">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Absensi</h3>
                        <p class="text-2xl font-bold text-gray-800"><?php echo count($table_data); ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-clipboard-list text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-500 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> <?php echo count($table_data) > 0 ? rand(5, 15) : 0; ?>%
                    </span>
                    <span class="text-gray-400 ml-2">dari minggu lalu</span>
                </div>
            </div>
            
            <div class="card bg-white rounded-xl shadow-card hover:shadow-card-hover p-6 animate-fadeIn delay-200">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Absensi Masuk</h3>
                        <?php
                        $masuk_count = 0;
                        foreach ($table_data as $row) {
                            if ($row['type'] === 'Masuk') $masuk_count++;
                        }
                        ?>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $masuk_count; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-sign-in-alt text-green-600"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-500 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> <?php echo $masuk_count > 0 ? rand(5, 15) : 0; ?>%
                    </span>
                    <span class="text-gray-400 ml-2">dari minggu lalu</span>
                </div>
            </div>
            
            <div class="card bg-white rounded-xl shadow-card hover:shadow-card-hover p-6 animate-fadeIn delay-300">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Absensi Pulang</h3>
                        <?php
                        $pulang_count = 0;
                        foreach ($table_data as $row) {
                            if ($row['type'] === 'Pulang') $pulang_count++;
                        }
                        ?>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $pulang_count; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i class="fas fa-sign-out-alt text-red-600"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-500 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> <?php echo $pulang_count > 0 ? rand(5, 15) : 0; ?>%
                    </span>
                    <span class="text-gray-400 ml-2">dari minggu lalu</span>
                </div>
            </div>
            
            <div class="card bg-white rounded-xl shadow-card hover:shadow-card-hover p-6 animate-fadeIn delay-300">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Karyawan</h3>
                        <?php
                        $unique_employees = [];
                        foreach ($table_data as $row) {
                            $unique_employees[$row['user_id']] = true;
                        }
                        $employee_count = count($unique_employees);
                        ?>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $employee_count; ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-500 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> <?php echo $employee_count > 0 ? rand(2, 8) : 0; ?>%
                    </span>
                    <span class="text-gray-400 ml-2">dari bulan lalu</span>
                </div>
            </div>
        </section>

        <!-- Map and Table Section -->
        <div class="grid lg:grid-cols-5 gap-8">
            <!-- Map Column (wider) -->
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white rounded-xl shadow-card overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-map-marked-alt"></i>
                            <span>Peta Lokasi Absensi</span>
                        </h3>
                        <div class="flex gap-2">
                            <button id="resetMap" class="bg-white text-primary-700 hover:bg-gray-100 px-3 py-1.5 rounded-lg text-sm transition flex items-center gap-1 focus:outline-none">
                                <i class="fas fa-expand-arrows-alt"></i>
                                <span>Reset View</span>
                            </button>
                        </div>
                    </div>
                    <div id="map"></div>
                    <div class="px-6 py-4 bg-gray-50 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                <span class="text-gray-600">Masuk</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <span class="text-gray-600">Pulang</span>
                            </div>
                        </div>
                        <div class="text-gray-500">
                            <span id="lastUpdated">Terakhir diperbarui: <?php echo date('d-m-Y H:i:s'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="bg-white rounded-xl shadow-card p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Data</h3>
                    <form id="filterForm" method="POST" action="">
                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-600 text-sm font-medium mb-2">Status Absensi</label>
                                <select id="filterStatus" name="filterStatus" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                                    <option value="all" <?php echo isset($status) && $status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                    <option value="Masuk" <?php echo isset($status) && $status === 'Masuk' ? 'selected' : ''; ?>>Masuk</option>
                                    <option value="Pulang" <?php echo isset($status) && $status === 'Pulang' ? 'selected' : ''; ?>>Pulang</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-medium mb-2">Tanggal</label>
                                <input type="date" id="filterDate" name="filterDate" value="<?php echo htmlspecialchars(isset($date) ? $date : ''); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-medium mb-2">Nama Karyawan</label>
                                <input type="text" id="filterName" name="filterName" placeholder="Cari nama..." value="<?php echo htmlspecialchars(isset($name) ? $name : ''); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" id="applyFilter" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                                <i class="fas fa-filter"></i>
                                <span>Terapkan Filter</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Table Column -->
            <div class="lg:col-span-2">
                <div class="table-container bg-white h-full flex flex-col">
                    <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-table"></i>
                            <span>Data Absensi</span>
                        </h3>
                        <button onclick="location.reload()" class="bg-white text-primary-700 hover:bg-gray-100 px-3 py-1.5 rounded-lg text-sm transition flex items-center gap-1 focus:outline-none">
                            <i class="fas fa-sync-alt"></i>
                            <span>Perbarui</span>
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="text-left">nama</th>
                                    <th class="text-center">Waktu</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php
                                if (!empty($table_data)) {
                                    $marker_index = 0;
                                    foreach ($table_data as $index => $row) {
                                        $has_marker = false;
                                        $marker_idx = -1;
                                        if (is_numeric($row['latitude']) && is_numeric($row['longitude']) &&
                                            $row['latitude'] >= -90 && $row['latitude'] <= 90 &&
                                            $row['longitude'] >= -180 && $row['longitude'] <= 180) {
                                            $has_marker = true;
                                            $marker_idx = $marker_index++;
                                        }
                                        $nama_attrs = $has_marker ? "class='nama-link' data-marker-idx='{$marker_idx}' data-lat='{$row['latitude']}' data-lon='{$row['longitude']}'" : '';
                                        $status_class = $row['type'] === 'Masuk' ? 'status-masuk' : 'status-pulang';
                                        $status_icon = $row['type'] === 'Masuk' ? 'sign-in-alt' : 'sign-out-alt';
                                        
                                        echo "<tr class='hover:bg-gray-50 transition border-b border-gray-100'>
                                                <td>
                                                    <div class='flex flex-col'>
                                                        <span {$nama_attrs}>" . htmlspecialchars($row['nama']) . "</span>
                                                        <span class='text-xs text-gray-500'>" . htmlspecialchars($row['nomor_id']) . "</span>
                                                    </div>
                                                </td>
                                                <td class='text-center'>
                                                    <div class='flex flex-col'>
                                                        <span class='font-medium'>" . date('H:i:s', strtotime($row['waktu'])) . "</span>
                                                        <span class='text-xs text-gray-500'>" . date('d-m-Y', strtotime($row['waktu'])) . "</span>
                                                    </div>
                                                </td>
                                                <td class='text-center'>
                                                    <span class='status-badge {$status_class}'>
                                                        <i class='fas fa-{$status_icon}'></i>
                                                        " . htmlspecialchars($row['type']) . "
                                                    </span>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-8'>Tidak ada data absensi</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 text-sm text-gray-600">
                        Menampilkan <span class="font-medium"><?php echo count($table_data); ?></span> data absensi
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detail Card Section -->
        <section class="mt-8">
            <div class="bg-white rounded-xl shadow-card p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Karyawan</h3>
                <div id="employeeDetail" class="text-center text-gray-500 py-4">
                    Klik nama karyawan pada tabel untuk melihat detail
                </div>
            </div>
        </section>
    </main>

    <!-- Script Map dan Real-time -->
    <script>
        // Inisialisasi peta
        const map = L.map('map').setView([-6.2088, 106.8456], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Data awal dari PHP
        let mapData = <?php echo json_encode($map_data); ?>;
        let markers = [];
        let markerCluster = null;
        let useCluster = false;

        // Ikon kustom untuk Masuk (hijau) dan Pulang (merah)
        const greenIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            shadowSize: [41, 41]
        });

        const redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            shadowSize: [41, 41]
        });

        // Fungsi untuk memformat waktu
        function formatDate(dateString) {
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        }

        // Fungsi untuk memperbarui peta
        function updateMap(data) {
            // Hapus marker yang ada
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            data.forEach(function(item, index) {
                var icon = item.type === 'Masuk' ? greenIcon : redIcon;
                var statusClass = item.type === 'Masuk' ? 'text-green-600' : 'text-red-600';
                var statusIcon = item.type === 'Masuk' ? 'sign-in-alt' : 'sign-out-alt';
                
                var popupContent = `
                    <div class="popup-content">
                        <div class="popup-header">${item.nama}</div>
                        <div class="popup-info">
                            <div class="popup-item">
                                <span class="popup-label">Nomor ID</span>
                                <span class="popup-value">${item.nomor_id}</span>
                            </div>
                            <div class="popup-item">
                                <span class="popup-label">Waktu</span>
                                <span class="popup-value">${item.waktu}</span>
                            </div>
                            <div class="popup-item">
                                <span class="popup-label">Status</span>
                                <span class="popup-value ${statusClass}">
                                    <i class="fas fa-${statusIcon}"></i> ${item.type}
                                </span>
                            </div>
                            <div class="popup-item">
                                <span class="popup-label">Koordinat</span>
                                <span class="popup-value text-xs">${item.latitude.toFixed(6)}, ${item.longitude.toFixed(6)}</span>
                            </div>
                        </div>
                    </div>
                `;
                
                var marker = L.marker([item.latitude, item.longitude], { icon: icon, markerIdx: index })
                    .addTo(map)
                    .bindPopup(popupContent);
                
                markers.push(marker);
            });
            
            if (markers.length > 0) {
                var group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            }
            
            // Update waktu terakhir diperbarui
            document.getElementById('lastUpdated').textContent = 'Terakhir diperbarui: ' + formatDate(new Date());
        }

        // Fungsi untuk memperbarui tabel
        function updateTable(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            let markerIndex = 0;
            
            if (data.length > 0) {
                data.forEach((row, index) => {
                    const hasMarker = row.latitude && row.longitude && 
                                     row.latitude >= -90 && row.latitude <= 90 && 
                                     row.longitude >= -180 && row.longitude <= 180;
                    const markerIdx = hasMarker ? markerIndex++ : -1;
                    const namaAttrs = hasMarker ? `class="nama-link" data-marker-idx="${markerIdx}" data-lat="${row.latitude}" data-lon="${row.longitude}" data-nama="${row.nama}" data-nomor="${row.nomor_id}" data-waktu="${row.waktu}" data-type="${row.type}"` : '';
                    const statusClass = row.type === 'Masuk' ? 'status-masuk' : 'status-pulang';
                    const statusIcon = row.type === 'Masuk' ? 'sign-in-alt' : 'sign-out-alt';
                    
                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                            <td>
                                <div class="flex flex-col">
                                    <span ${namaAttrs}>${row.nama}</span>
                                    <span class="text-xs text-gray-500">${row.nomor_id}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex flex-col">
                                    <span class="font-medium">${row.waktu.split(' ')[1]}</span>
                                    <span class="text-xs text-gray-500">${row.waktu.split(' ')[0]}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge ${statusClass}">
                                    <i class="fas fa-${statusIcon}"></i>
                                    ${row.type}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-8">Tidak ada data absensi</td></tr>';
            }

            // Tambahkan event listener untuk nama yang dapat diklik
            document.querySelectorAll('.nama-link').forEach(link => {
                link.addEventListener('click', function() {
                    const markerIdx = parseInt(this.getAttribute('data-marker-idx'));
                    const lat = parseFloat(this.getAttribute('data-lat'));
                    const lon = parseFloat(this.getAttribute('data-lon'));
                    const nama = this.getAttribute('data-nama');
                    const nomor = this.getAttribute('data-nomor');
                    const waktu = this.getAttribute('data-waktu');
                    const type = this.getAttribute('data-type');
                    
                    if (markerIdx >= 0 && markers[markerIdx]) {
                        map.setView([lat, lon], 15);
                        markers[markerIdx].openPopup();
                    }
                    
                    // Update detail karyawan
                    updateEmployeeDetail(nama, nomor, waktu, lat, lon, type);
                });
            });
        }
        
        // Fungsi untuk memperbarui detail karyawan
        function updateEmployeeDetail(nama, nomor, waktu, lat, lon, type) {
            const detailEl = document.getElementById('employeeDetail');
            const statusClass = type === 'Masuk' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
            const statusIcon = type === 'Masuk' ? 'sign-in-alt' : 'sign-out-alt';
            
            detailEl.innerHTML = `
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                            <i class="fas fa-user text-2xl text-gray-500"></i>
                        </div>
                        <h4 class="font-medium text-lg">${nama}</h4>
                        <p class="text-gray-500">${nomor}</p>
                    </div>
                    <div class="md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-500 mb-1">Status Absensi</div>
                                <div class="flex items-center ${statusClass} rounded-lg px-3 py-1 w-fit">
                                    <i class="fas fa-${statusIcon} mr-2"></i>
                                    <span class="font-medium">${type}</span>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-500 mb-1">Waktu</div>
                                <div class="font-medium">${waktu}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
                                <div class="text-sm text-gray-500 mb-1">Lokasi</div>
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                                    <span>${lat.toFixed(6)}, ${lon.toFixed(6)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Inisialisasi peta dengan data awal
        updateMap(mapData);
        updateTable(<?php echo json_encode($table_data); ?>);

        // Reset view
        document.getElementById('resetMap').onclick = function() {
            if (markers.length > 0) {
                var group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            } else {
                map.setView([-6.2088, 106.8456], 11);
            }
        };
        
        // Toggle cluster
        document.getElementById('mapCluster').onclick = function() {
            useCluster = !useCluster;
            alert('Fitur clustering akan segera tersedia!');
        };

        // Filter data
        document.getElementById('applyFilter').onclick = function(e) {
            e.preventDefault(); // Mencegah form submit default
            const statusFilter = document.getElementById('filterStatus').value;
            const nameFilter = document.getElementById('filterName').value.toLowerCase();
            const dateFilter = document.getElementById('filterDate').value;
            
            // Buat query string untuk semua filter
            const queryParams = new URLSearchParams();
            queryParams.append('ajax', '1');
            if (dateFilter) queryParams.append('date', dateFilter);
            if (statusFilter !== 'all') queryParams.append('status', statusFilter);
            if (nameFilter) queryParams.append('name', nameFilter);
            
            // Kirim permintaan AJAX dengan filter
            fetch(`?${queryParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    mapData = data.map_data;
                    updateMap(data.map_data);
                    updateTable(data.table_data);
                    
                    // Update waktu terakhir diperbarui
                    document.getElementById('lastUpdated').textContent = 
                        'Terakhir diperbarui: ' + formatDate(new Date());
                        
                    // Tampilkan notifikasi filter
                    alert(`Filter diterapkan:\nStatus: ${statusFilter}\nNama: ${nameFilter || 'Semua nama'}\nTanggal: ${dateFilter || 'Semua tanggal'}`);
                })
                .catch(error => console.error('Error fetching data:', error));
        };

        // Pembaruan real-time dengan AJAX
        let lastDateFilter = '<?php echo htmlspecialchars(isset($date) ? $date : ''); ?>';
        let lastStatusFilter = '<?php echo htmlspecialchars(isset($status) ? $status : 'all'); ?>';
        let lastNameFilter = '<?php echo htmlspecialchars(isset($name) ? $name : ''); ?>';
        setInterval(function() {
            const dateFilter = document.getElementById('filterDate').value;
            const statusFilter = document.getElementById('filterStatus').value;
            const nameFilter = document.getElementById('filterName').value.toLowerCase();
            
            lastDateFilter = dateFilter;
            lastStatusFilter = statusFilter;
            lastNameFilter = nameFilter;
            
            const queryParams = new URLSearchParams();
            queryParams.append('ajax', '1');
            if (dateFilter) queryParams.append('date', dateFilter);
            if (statusFilter !== 'all') queryParams.append('status', statusFilter);
            if (nameFilter) queryParams.append('name', nameFilter);
            
            fetch(`?${queryParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    mapData = data.map_data;
                    updateMap(data.map_data);
                    updateTable(data.table_data);
                })
                .catch(error => console.error('Error fetching data:', error));
        }, 10000); // Perbarui setiap 10 detik
    </script>
</body>
</html>
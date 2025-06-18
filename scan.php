<?php
session_start();

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Regenerasi session ID untuk mencegah session fixation
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

// Periksa library phpqrcode
$qr_lib_path = 'libs/phpqrcode/qrlib.php';
if (!file_exists($qr_lib_path)) {
    die("Error: Library phpqrcode tidak ditemukan. Silakan instal library phpqrcode.");
}
require $qr_lib_path;

// Periksa apakah kelas QRcode ada
if (!class_exists('QRcode')) {
    die("Error: Kelas QRcode tidak ditemukan. Pastikan phpqrcode diinstal dengan benar.");
}

$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil nama pengguna dari database
$user_sql = "SELECT username FROM users WHERE id = ?"; // Ganti 'username' dengan nama kolom yang benar
$stmt = $conn->prepare($user_sql);
if ($stmt === false) {
    die("Error prepare SELECT user: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_name = "Pengguna";
if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_name = isset($user_data['username']) ? htmlspecialchars($user_data['username']) : "Pengguna";
}
$stmt->close();

// Hapus kode QR kadaluarsa
$expiration_time = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$stmt = $conn->prepare("DELETE FROM qr_codes WHERE created_at < ?");
if ($stmt === false) {
    die("Error prepare DELETE: " . $conn->error);
}
$stmt->bind_param("s", $expiration_time);
$stmt->execute();
$stmt->close();

// Proses pembuatan kode QR berdasarkan jenis absensi
$qr_code = null;
$qr_file = null;
$message = null;
$message_type = null;

if (isset($_POST['type']) && in_array($_POST['type'], ['masuk', 'pulang'])) {
    $type = $_POST['type'];
    $qr_code = uniqid('absen_');
    $created_at = date('Y-m-d H:i:s');

    // Gunakan localhost secara eksplisit
    $server_ip = 'localhost';
    $validate_url = "http://$server_ip/absensi_ptindotekhnoplus/validate.php?code=" . urlencode($qr_code);

    // Simpan kode QR ke database
    $sql = "INSERT INTO qr_codes (code, user_id, created_at, type) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error prepare INSERT: " . $conn->error . " | Query: $sql");
    }
    $stmt->bind_param("siss", $qr_code, $user_id, $created_at, $type);
    if (!$stmt->execute()) {
        $message = "Gagal membuat kode QR: " . $stmt->error;
        $message_type = "error";
    } else {
        // Hasilkan QR code
        $qr_file = 'qrcodes/' . $qr_code . '.png';
        if (!is_dir('qrcodes')) {
            mkdir('qrcodes', 0755, true);
        }
        QRcode::png($validate_url, $qr_file, QR_ECLEVEL_L, 10);
    }
    $stmt->close();
}

// Ambil riwayat absensi karyawan
$absensi_sql = "SELECT waktu, type, latitude, longitude FROM absences WHERE user_id = ? ORDER BY waktu DESC LIMIT 10";
$stmt = $conn->prepare($absensi_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$absensi_result = $stmt->get_result();
$absensi_data = [];
while ($row = $absensi_result->fetch_assoc()) {
    $absensi_data[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Absensi QR Code - Absensi PT Indotekhnoplus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; }
        .btn { transition: transform 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-2px); }
        .qr-container { max-width: 300px; margin: 0 auto; }
        img { width: 100%; height: auto; }
        @media (max-width: 640px) {
            .container { padding: 0; }
            .bg-white { border-radius: 0; box-shadow: none; }
        }
        /* Styling untuk tabel riwayat absensi */
        .absensi-table-container {
            max-width: 100%;
            overflow-x: auto;
        }
        .absensi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: linear-gradient(145deg, #ffffff, #f0f4f8);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        .absensi-table th {
            background: #3b82f6;
            color: white;
            font-weight: 600;
            padding: 16px;
            text-align: left;
            font-size: 16px;
        }
        .absensi-table td {
            padding: 16px;
            font-size: 14px;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
        }
        .absensi-table tr:hover {
            background: #f1f5f9;
            transition: background 0.2s ease;
        }
        .absensi-table .type-masuk::before {
            content: '↪';
            display: inline-block;
            margin-right: 8px;
            color: #16a34a;
            font-weight: bold;
        }
        .absensi-table .type-pulang::before {
            content: '↩';
            display: inline-block;
            margin-right: 8px;
            color: #dc2626;
            font-weight: bold;
        }
        @media (max-width: 640px) {
            .absensi-table th, .absensi-table td {
                font-size: 12px;
                padding: 12px;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-8 flex-grow">
        <!-- Tampilkan nama pengguna -->
        <div class="mb-6 text-center">
            <p class="text-lg md:text-xl font-semibold text-gray-800">Selamat datang, <?php echo $user_name; ?>!</p>
        </div>

        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 text-center">Absensi QR Code</h2>

        <?php if (isset($message)): ?>
            <div class="mb-4 p-4 rounded-lg text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php elseif ($qr_file): ?>
            <div class="bg-white p-4 md:p-6 rounded-2xl shadow-lg mx-auto w-full max-w-md">
                <p class="text-center text-gray-600 mb-4">Pindai QR code ini untuk absensi <strong><?php echo htmlspecialchars($type); ?></strong>.</p>
                <div class="qr-container mb-4">
                    <img src="<?php echo htmlspecialchars($qr_file); ?>" alt="QR Code Absensi">
                </div>
                <p class="text-center text-sm text-gray-500">Kode ini berlaku selama 5 menit.</p>
            </div>
        <?php else: ?>
            <div class="bg-white p-4 md:p-6 rounded-2xl shadow-lg mx-auto w-full max-w-md">
                <p class="text-center text-gray-600 mb-4">Pilih jenis absensi untuk membuat QR code:</p>
                <form method="POST" class="flex flex-col space-y-4">
                    <button type="submit" name="type" value="masuk" class="btn bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700">Absen Masuk</button>
                    <button type="submit" name="type" value="pulang" class="btn bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">Absen Pulang</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Riwayat Absensi -->
        <div class="mt-8 bg-white p-4 md:p-6 rounded-2xl shadow-lg mx-auto w-full max-w-4xl">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 text-center">Riwayat Absensi</h3>
            <?php if (count($absensi_data) > 0): ?>
                <div class="absensi-table-container">
                    <table class="absensi-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Jenis</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absensi_data as $absensi): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i:s', strtotime($absensi['waktu']))); ?></td>
                                    <td class="type-<?php echo htmlspecialchars($absensi['type']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($absensi['type'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(isset($absensi['latitude']) ? $absensi['latitude'] : 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(isset($absensi['longitude']) ? $absensi['longitude'] : 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-600 text-lg">Belum ada riwayat absensi.</p>
            <?php endif; ?>
        </div>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-blue-600 hover:underline text-lg">Keluar</a>
        </div>
    </div>
</body>
</html>
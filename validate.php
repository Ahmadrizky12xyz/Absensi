<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu ke WIB

$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (!isset($_GET['code'])) {
    $message = "Kode QR tidak valid.";
    $message_type = "error";
} else {
    $qr_code = $_GET['code'];
    $device_fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

    // Validasi kode QR dan ambil jenis absensi
    $sql = "SELECT user_id, type FROM qr_codes WHERE code = ? AND created_at >= ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error prepare SELECT: " . $conn->error . " | Query: $sql");
    }
    $expiration_time = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $stmt->bind_param("ss", $qr_code, $expiration_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $type = $row['type'];

        // Cek perangkat terdaftar
        $sql = "SELECT id FROM user_devices WHERE user_id = ? AND device_fingerprint = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error prepare SELECT user_devices: " . $conn->error . " | Query: $sql");
        }
        $stmt->bind_param("is", $user_id, $device_fingerprint);
        $stmt->execute();
        $device_result = $stmt->get_result();

        if ($device_result->num_rows === 0) {
            $device_name = substr($_SERVER['HTTP_USER_AGENT'], 0, 100);
            $sql = "INSERT INTO user_devices (user_id, device_fingerprint, device_name) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Error prepare INSERT user_devices: " . $conn->error . " | Query: $sql");
            }
            $stmt->bind_param("iss", $user_id, $device_fingerprint, $device_name);
            $stmt->execute();
            $message = "Perangkat baru terdeteksi. Menunggu verifikasi admin.";
            $message_type = "warning";
        } else {
            // Catat absensi
            $waktu = date('Y-m-d H:i:s');
            $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : null;
            $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;

            if (!$latitude || !$longitude) {
                $message = "Koordinat lokasi tidak valid. Izinkan akses lokasi di pengaturan ponsel.";
                $message_type = "error";
            } else {
                $sql = "INSERT INTO absences (user_id, waktu, latitude, longitude, device_fingerprint, type) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    die("Error prepare INSERT absences: " . $conn->error . " | Query: $sql");
                }
                $stmt->bind_param("isssss", $user_id, $waktu, $latitude, $longitude, $device_fingerprint, $type);
                if ($stmt->execute()) {
                    $message = "Absensi $type berhasil dicatat pada " . date('d-m-Y H:i:s') . ".";
                    $message_type = "success";
                    // Hapus kode QR setelah digunakan
                    $sql = "DELETE FROM qr_codes WHERE code = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        die("Error prepare DELETE qr_codes: " . $conn->error . " | Query: $sql");
                    }
                    $stmt->bind_param("s", $qr_code);
                    $stmt->execute();
                } else {
                    $message = "Gagal menyimpan absensi: " . $stmt->error;
                    $message_type = "error";
                }
            }
        }
    } else {
        $message = "Kode QR tidak valid atau telah kadaluarsa.";
        $message_type = "error";
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Validasi Absensi - Absensi PT Indotekhnoplus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; }
        .btn { transition: transform 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-2px); }
        @media (max-width: 640px) {
            .container { padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-8 flex-grow">
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 text-center">Validasi Absensi</h2>

        <div class="bg-white p-4 md:p-6 rounded-2xl shadow-lg mx-auto w-full max-w-md">
            <div class="mb-4 p-4 rounded-lg text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if ($message_type !== 'success' && $message_type !== 'warning') { ?>
                <div class="mb-4 text-sm text-gray-600">
                    <p>Jika akses lokasi ditolak, silakan:</p>
                    <ul class="list-disc pl-5">
                        <li>Aktifkan GPS di pengaturan ponsel.</li>
                        <li>Izinkan akses lokasi untuk browser Anda.</li>
                        <li>Klik tombol di bawah untuk mencoba lagi.</li>
                    </ul>
                </div>
                <button onclick="getLocation()" class="btn w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Coba Lagi
                </button>
            <?php } ?>
        </div>

        <div class="mt-6 text-center">
            <a href="http://localhost/absensi_ptindotekhnoplus/scan.php" class="text-blue-600 hover:underline">Kembali ke QR Code</a>
        </div>
    </div>

    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(setPosition, showError, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                alert("Geolocation tidak didukung oleh browser Anda.");
            }
        }

        function setPosition(position) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'http://localhost/absensi_ptindotekhnoplus/validate.php?code=<?php echo htmlspecialchars($qr_code); ?>';

            var latInput = document.createElement('input');
            latInput.type = 'hidden';
            latInput.name = 'latitude';
            latInput.value = position.coords.latitude;
            form.appendChild(latInput);

            var lonInput = document.createElement('input');
            lonInput.type = 'hidden';
            lonInput.name = 'longitude';
            lonInput.value = position.coords.longitude;
            form.appendChild(lonInput);

            document.body.appendChild(form);
            form.submit();
        }

        function showError(error) {
            let message;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = "Akses lokasi ditolak. Silakan izinkan akses lokasi di pengaturan ponsel.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = "Informasi lokasi tidak tersedia. Pastikan GPS aktif.";
                    break;
                case error.TIMEOUT:
                    message = "Permintaan lokasi timeout. Coba lagi.";
                    break;
                default:
                    message = "Terjadi kesalahan: " + error.message;
            }
            alert(message);
        }

        // Panggil getLocation saat halaman dimuat
        window.onload = getLocation;
    </script>
</body>
</html>
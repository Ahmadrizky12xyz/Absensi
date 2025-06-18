<?php
// Koneksi ke database (sesuaikan dengan konfigurasi Anda)
$conn = mysqli_connect("localhost", "username", "password", "nama_database");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Mendapatkan kode QR dari URL
$code = isset($_GET['code']) ? $_GET['code'] : '';

$qr = null;
if ($code != '') {
    // Ambil data QR code dari database
    $result_qr = mysqli_query($conn, "SELECT * FROM qr_codes WHERE code = '$code'");
    if (mysqli_num_rows($result_qr) > 0) {
        $qr = mysqli_fetch_assoc($result_qr);
    } else {
        die("QR Code tidak ditemukan.");
    }
} else {
    die("Kode QR tidak diberikan.");
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $qr_code_id = $_POST['qr_code_id'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $action = $_POST['action']; // 'masuk' atau 'keluar'

    // Validasi input
    if (empty($user_id) || empty($qr_code_id) || empty($action)) {
        echo "<script>alert('Data tidak lengkap.');</script>";
    } else {
        // Simpan data absensi
        $time_field = ($action == 'masuk') ? 'check_in_time' : 'check_out_time';

        // Cek apakah user sudah melakukan absensi masuk/keluar hari ini
        $today = date('Y-m-d');
        $cek = mysqli_query($conn, "SELECT * FROM absences WHERE user_id = '$user_id' AND DATE($time_field) = '$today'");

        if (mysqli_num_rows($cek) > 0) {
            // Kalau sudah ada, update waktu keluar/masuk
            if ($action == 'masuk') {
                // Jika sudah ada masuk, tidak perlu update
                echo "<script>alert('User sudah melakukan absensi masuk hari ini.');</script>";
            } else {
                // Update waktu keluar
                mysqli_query($conn, "UPDATE absences SET check_out_time = NOW(), latitude_keluar='$latitude', longitude_keluar='$longitude' WHERE user_id = '$user_id' AND DATE(check_in_time) = '$today'");
                echo "<script>alert('Absensi keluar berhasil disimpan.');</script>";
            }
        } else {
            // Belum ada, insert data baru
            if ($action == 'masuk') {
                mysqli_query($conn, "INSERT INTO absences (user_id, qr_code_id, check_in_time, latitude_masuk, longitude_masuk) VALUES ('$user_id', '$qr_code_id', NOW(), '$latitude', '$longitude')");
                echo "<script>alert('Absensi masuk berhasil disimpan.');</script>";
            } else {
                // Kalau keluar tanpa masuk, bisa diatur sesuai kebijakan
                mysqli_query($conn, "INSERT INTO absences (user_id, qr_code_id, check_out_time, latitude_keluar, longitude_keluar) VALUES ('$user_id', '$qr_code_id', NOW(), '$latitude', '$longitude')");
                echo "<script>alert('Absensi keluar berhasil disimpan.');</script>";
            }
        }
    }
}

// Ambil data user dari database
$result_users = mysqli_query($conn, "SELECT * FROM users ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Absensi QR Code</title>
<script>
    // Mendapatkan lokasi dari perangkat
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(setPosition, showError);
        } else {
            alert("Geolocation tidak didukung oleh browser ini.");
        }
    }

    function setPosition(position) {
        document.getElementById('latitude').value = position.coords.latitude;
        document.getElementById('longitude').value = position.coords.longitude;
    }

    function showError(error) {
        alert("Gagal mendapatkan lokasi: " + error.message);
    }

    window.onload = function() {
        getLocation();
    }
</script>
</head>
<body>
<h2>Absensi QR Code: <?php echo htmlspecialchars($qr['code']); ?></h2>
<p>Pilih User:</p>

<form method="post" action="">
    <input type="hidden" name="qr_code_id" value="<?php echo $qr['id']; ?>">
    <input type="hidden" name="latitude" id="latitude" value="">
    <input type="hidden" name="longitude" id="longitude" value="">

    <select name="user_id" required>
        <option value="">-- Pilih User --</option>
        <?php while($user = mysqli_fetch_assoc($result_users)) { ?>
            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
        <?php } ?>
    </select>
    <br><br>
    <button type="submit" name="action" value="masuk">Masuk</button>
    <button type="submit" name="action" value="keluar">Keluar</button>
</form>
</body>
</html>
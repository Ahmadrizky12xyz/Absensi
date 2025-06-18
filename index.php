<?php include 'header.php'; ?>

<div class="container mx-auto px-4 py-8 flex-grow">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Selamat Datang, <?php echo htmlspecialchars($nama); ?>!</h2>
    <p class="text-center text-gray-600 mb-8">Sistem Absensi PT Indotekhnoplus</p>

    <!-- Konten index.php seperti sebelumnya -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-4xl mx-auto">
        <a href="absensi.php" class="btn bg-blue-600 text-white p-6 rounded-lg shadow-lg text-center hover:bg-blue-700">
            <h3 class="text-lg font-semibold">Absensi</h3>
            <p class="text-sm">Lakukan absensi harian Anda</p>
        </a>
        <?php if ($role === 'admin') { ?>
            <a href="set_work_hours.php" class="btn bg-green-600 text-white p-6 rounded-lg shadow-lg text-center hover:bg-green-700">
                <h3 class="text-lg font-semibold">Pengaturan Jam Kerja</h3>
                <p class="text-sm">Atur jam masuk kerja</p>
            </a>
            <a href="manage_users.php" class="btn bg-yellow-600 text-white p-6 rounded-lg shadow-lg text-center hover:bg-yellow-700">
                <h3 class="text-lg font-semibold">Kelola Pengguna</h3>
                <p class="text-sm">Tambah, edit, atau hapus pengguna</p>
            </a>
        <?php } ?>
    </div>

    <!-- Ringkasan Absensi Terbaru -->
    <div class="mt-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Absensi Terbaru Anda</h3>
        <div class="table-container">
            <table class="w-full bg-white rounded-lg shadow-lg">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-center">No</th>
                        <th class="py-3 px-6 text-center">Tanggal & Waktu</th>
                        <th class="py-3 px-6 text-center">Latitude</th>
                        <th class="py-3 px-6 text-center">Longitude</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php
                    $sql = "SELECT waktu, latitude, longitude FROM absences WHERE user_id = ? ORDER BY waktu DESC LIMIT 5";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $absensi_result = $stmt->get_result();

                    if ($absensi_result->num_rows > 0) {
                        $no = 1;
                        while ($row = $absensi_result->fetch_assoc()) {
                            $waktu = date('d-m-Y H:i:s', strtotime($row['waktu']));
                            echo "<tr class='border-b border-gray-200 hover:bg-gray-50'>
                                    <td class='py-3 px-6 text-center'>{$no}</td>
                                    <td class='py-3 px-6 text-center'>" . htmlspecialchars($waktu) . "</td>
                                    <td class='py-3 px-6 text-center'>" . htmlspecialchars($row['latitude'] ?: '-') . "</td>
                                    <td class='py-3 px-6 text-center'>" . htmlspecialchars($row['longitude'] ?: '-') . "</td>
                                  </tr>";
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='4' class='py-3 px-6 text-center'>Belum ada data absensi</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="logout.php" class="btn bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700">Logout</a>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
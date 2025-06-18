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

// Membuat koneksi ke database
$conn = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel pesan
$message = '';
$message_type = '';

// Proses tambah jam masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_work_hour'])) {
    $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validasi input
    if (!$start_time) {
        $message = "Jam masuk harus diisi.";
        $message_type = "error";
    } else {
        // Siapkan query
        $stmt = $conn->prepare("INSERT INTO work_hours (start_time, description) VALUES (?, ?)");
        if ($stmt === false) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        // Bind parameter
        $stmt->bind_param("ss", $start_time, $description);

        // Eksekusi statement
        if ($stmt->execute()) {
            $message = "Jam masuk kerja berhasil disimpan.";
            $message_type = "success";
        } else {
            $message = "Gagal menyimpan data: " . $stmt->error;
            $message_type = "error";
        }

        $stmt->close();
    }
}

// Proses edit jam masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_work_hour'])) {
    $id = intval($_POST['id']);
    $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validasi input
    if (!$start_time) {
        $message = "Jam masuk harus diisi.";
        $message_type = "error";
    } else {
        // Siapkan query
        $stmt = $conn->prepare("UPDATE work_hours SET start_time = ?, description = ? WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        // Bind parameter
        $stmt->bind_param("ssi", $start_time, $description, $id);

        // Eksekusi statement
        if ($stmt->execute()) {
            $message = "Jam masuk kerja berhasil diperbarui.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui data: " . $stmt->error;
            $message_type = "error";
        }

        $stmt->close();
    }
}

// Proses hapus jam masuk
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM work_hours WHERE id = ?");
    if ($stmt === false) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Jam masuk kerja berhasil dihapus.";
        $message_type = "success";
    } else {
        $message = "Gagal menghapus data: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Query untuk mengambil semua data jam masuk
$sql = "SELECT * FROM work_hours ORDER BY created_at DESC";
$result = $conn->query($sql);
if (!$result) {
    die("Error dalam query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Jam Masuk Kerja - PT INDO TEKHNO PLUS</title>
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
            background-color: #f8fafc;
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
            overflow-x: auto;
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
        
        .btn-submit {
            transition: transform 0.2s ease-in-out, background-color 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        /* Modal Styles */
        .modal {
            animation: fadeIn 0.3s ease forwards;
            z-index: 50;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        
        .modal-content input,
        .modal-content textarea {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            line-height: 1.5;
            min-height: 2.5rem;
            font-size: 0.875rem;
        }
        
        .modal-content textarea {
            min-height: 6rem;
            resize: vertical;
        }
        
        .modal-content input:focus,
        .modal-content textarea:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
            outline: none;
        }
        
        .modal-content input:invalid[required]:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }
        
        .modal-error {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.5rem;
            display: none;
        }
        
        .modal-button {
            transition: background-color 0.3s ease, transform 0.2s ease;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .modal-button:hover {
            transform: translateY(-2px);
        }
        
        .modal-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
        
        /* Animation utilities */
        .animate-fadeIn {
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 767px) {
            th, td {
                padding: 12px 8px;
                font-size: 13px;
            }
            .modal-content {
                padding: 1.5rem;
                max-width: 90%;
            }
            .modal-button {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }
            .modal-content input,
            .modal-content textarea {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="header-gradient sticky top-0 z-40 shadow-lg">
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
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center animate-fadeIn">Pengaturan Jam Masuk Kerja</h2>

        <!-- Form untuk mengatur jam masuk -->
        <div class="bg-white p-6 rounded-2xl shadow-card mb-8 max-w-lg mx-auto card animate-fadeIn">
            <?php if (isset($message)) { ?>
                <div class="mb-4 p-4 rounded-lg text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>
            <form method="POST" action="">
                <input type="hidden" name="add_work_hour" value="1">
                <div class="mb-6">
                    <label for="start_time" class="block text-sm font-semibold text-gray-700 mb-2">Jam Masuk</label>
                    <input type="time" name="start_time" id="start_time" required class="w-full px-4 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="mb-6">
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Keterangan (Opsional)</label>
                    <textarea name="description" id="description" class="w-full px-4 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500" rows="4" placeholder="Masukkan keterangan, misalnya: Jam masuk default untuk semua karyawan"></textarea>
                </div>
                <button type="submit" class="btn-submit w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            </form>
        </div>

        <!-- Tabel untuk menampilkan pengaturan -->
        <div class="table-container">
            <table class="w-full bg-white">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Jam Masuk</th>
                        <th class="text-center">Keterangan</th>
                        <th class="text-center">Tanggal Dibuat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php
                    if ($result->num_rows > 0) {
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            $start_time = date('H:i', strtotime($row['start_time']));
                            $created_at = date('d-m-Y H:i:s', strtotime($row['created_at']));
                            $description = isset($row['description']) && $row['description'] ? $row['description'] : '-';
                            $modal_description = isset($row['description']) ? $row['description'] : '';
                            ?>
                            <tr class='border-b border-gray-200 hover:bg-gray-50'>
                                <td class='py-3 px-6 text-center'><?php echo $no; ?></td>
                                <td class='py-3 px-6 text-center'><?php echo htmlspecialchars($start_time); ?></td>
                                <td class='py-3 px-6 text-center'><?php echo htmlspecialchars($description); ?></td>
                                <td class='py-3 px-6 text-center'><?php echo htmlspecialchars($created_at); ?></td>
                                <td class='py-3 px-6 text-center'>
                                    <button onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['start_time'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($modal_description, ENT_QUOTES); ?>')" class='text-primary-600 hover:underline mr-2'>Edit</button>
                                    <a href='?delete=<?php echo $row['id']; ?>' onclick='return confirm("Yakin ingin menghapus jam masuk ini?")' class='text-red-600 hover:underline'>Hapus</a>
                                </td>
                            </tr>
                            <?php
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='5' class='py-3 px-6 text-center'>Belum ada pengaturan jam masuk</td></tr>";
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Edit Jam Masuk -->
    <div id="editModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="modal-content">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">Ubah Jam Masuk Kerja</h3>
            <form id="editForm" method="POST" action="" onsubmit="handleSubmit(event)">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="edit_work_hour" value="1">
                <div class="mb-6">
                    <label for="edit_start_time" class="block text-sm font-semibold text-gray-700 mb-2">Jam Masuk</label>
                    <input type="time" name="start_time" id="edit_start_time" required class="w-full shadow-sm" placeholder="Pilih jam masuk">
                    <p class="modal-error" id="edit_start_time_error">Jam masuk wajib diisi.</p>
                </div>
                <div class="mb-6">
                    <label for="edit_description" class="block text-sm font-semibold text-gray-700 mb-2">Keterangan (Opsional)</label>
                    <textarea name="description" id="edit_description" class="w-full shadow-sm" rows="4" placeholder="Masukkan keterangan, misalnya: Jam masuk default untuk semua karyawan"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()" class="modal-button bg-gray-200 text-gray-800 flex items-center gap-2 hover:bg-gray-300">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" id="saveButton" class="modal-button bg-primary-600 text-white flex items-center gap-2 hover:bg-primary-700">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(id, start_time, description) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_start_time').value = start_time;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_start_time_error').style.display = 'none';
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editForm').reset();
        document.getElementById('edit_start_time_error').style.display = 'none';
    }

    function handleSubmit(event) {
        event.preventDefault();
        const form = document.getElementById('editForm');
        const startTime = document.getElementById('edit_start_time');
        const error = document.getElementById('edit_start_time_error');

        if (!startTime.value.trim()) {
            error.style.display = 'block';
            return;
        } else {
            error.style.display = 'none';
        }

        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        form.submit();
    }
    </script>
</body>
</html>
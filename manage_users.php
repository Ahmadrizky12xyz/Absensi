<?php
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi koneksi database
$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel pesan
$message = '';
$message_type = '';

// Proses tambah pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $nomor_id = trim($_POST['nomor_id']);
    $role = trim($_POST['role']);

    // Validasi input
    if (empty($nama) || empty($username) || empty($password) || empty($nomor_id) || empty($role)) {
        $message = "Semua kolom wajib diisi.";
        $message_type = "error";
    } else {
        // Cek apakah username atau nomor_id sudah ada
        $check_sql = "SELECT id FROM users WHERE username = ? OR nomor_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $nomor_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Username atau Nomor ID sudah digunakan.";
            $message_type = "error";
        } else {
            // Tambah pengguna
            $insert_sql = "INSERT INTO users (nama, username, password, nomor_id, role) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $nama, $username, $password, $nomor_id, $role);

            if ($insert_stmt->execute()) {
                $message = "Pengguna berhasil ditambahkan.";
                $message_type = "success";
            } else {
                $message = "Gagal menambahkan pengguna: " . $conn->error;
                $message_type = "error";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Proses edit pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = intval($_POST['id']);
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $nomor_id = trim($_POST['nomor_id']);
    $role = trim($_POST['role']);
    $password = !empty(trim($_POST['password'])) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : null;

    // Validasi input
    if (empty($nama) || empty($username) || empty($nomor_id) || empty($role)) {
        $message = "Semua kolom wajib diisi kecuali kata sandi.";
        $message_type = "error";
    } else {
        // Cek apakah username atau nomor_id sudah digunakan oleh pengguna lain
        $check_sql = "SELECT id FROM users WHERE (username = ? OR nomor_id = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $username, $nomor_id, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Username atau Nomor ID sudah digunakan oleh pengguna lain.";
            $message_type = "error";
        } else {
            // Update pengguna
            if ($password) {
                $update_sql = "UPDATE users SET nama = ?, username = ?, password = ?, nomor_id = ?, role = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssssi", $nama, $username, $password, $nomor_id, $role, $id);
            } else {
                $update_sql = "UPDATE users SET nama = ?, username = ?, nomor_id = ?, role = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $nama, $username, $nomor_id, $role, $id);
            }

            if ($update_stmt->execute()) {
                $message = "Pengguna berhasil diperbarui.";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui pengguna: " . $conn->error;
                $message_type = "error";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Proses hapus pengguna
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);

    if ($delete_stmt->execute()) {
        $message = "Pengguna berhasil dihapus.";
        $message_type = "success";
    } else {
        $message = "Gagal menghapus pengguna: " . $conn->error;
        $message_type = "error";
    }
    $delete_stmt->close();
}

// Ambil daftar pengguna
$users_sql = "SELECT id, nama, username, nomor_id, role FROM users ORDER BY nama";
$users_result = $conn->query($users_sql);
if (!$users_result) {
    die("Error dalam query pengguna: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - PT INDO TEKHNO PLUS</title>
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
        
        /* Modal Styles */
        .modal {
            animation: fadeIn 0.3s ease forwards;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            position: relative;
        }
        
        .modal-content input,
        .modal-content select {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .modal-content input:focus,
        .modal-content select:focus {
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
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .modal-button {
            transition: background-color 0.3s ease, transform 0.2s ease;
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

    <!-- Konten Utama -->
    <main class="flex-1 container mx-auto px-6 py-10">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 animate-fadeIn">Kelola Pengguna</h2>

        <!-- Pesan -->
        <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-lg animate-fadeIn <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Formulir Tambah Pengguna -->
        <div class="bg-white p-6 rounded-lg shadow-card mb-8 card animate-fadeIn">
            <h3 class="text-xl font-semibold mb-4">Tambah Pengguna Baru</h3>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="nama" id="nama" class="mt-1 p-2 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label for="nomor_id" class="block text-sm font-medium text-gray-700">Nomor ID</label>
                        <input type="text" name="nomor_id" id="nomor_id" class="mt-1 p-2 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required placeholder="Contoh: EMP001">
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" id="username" class="mt-1 p-2 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi</label>
                        <input type="password" name="password" id="password" class="mt-1 p-2 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Peran</label>
                        <select name="role" id="role" class="mt-1 p-2 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="karyawan">Karyawan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" name="add_user" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Tambah Pengguna
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Pengguna -->
        <div class="bg-white p-6 rounded-lg shadow-card card animate-fadeIn">
            <h3 class="text-xl font-semibold mb-4">Daftar Pengguna</h3>
            <div class="overflow-x-auto table-container">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th class="text-center">Nomor ID</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Username</th>
                            <th class="text-center">Peran</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php $no = 1; while ($user = $users_result->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="py-3 px-4 text-center"><?php echo $no++; ?></td>
                                <td class="py-3 px-4 text-center"><?php echo htmlspecialchars($user['nomor_id']); ?></td>
                                <td class="py-3 px-4 text-center"><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td class="py-3 px-4 text-center"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="py-3 px-4 text-center"><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="py-3 px-4 text-center">
                                    <button onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nama']); ?>', '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['nomor_id']); ?>', '<?php echo $user['role']; ?>')" class="text-primary-600 hover:underline">Edit</button>
                                    <a href="?delete=<?php echo $user['id']; ?>" onclick="return confirm('Yakin ingin menghapus pengguna ini?')" class="text-red-600 hover:underline ml-2">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-3 px-4 text-center">Tidak ada pengguna.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Edit Pengguna -->
    <div id="editModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="modal-content">
            <h3 class="text-xl font-semibold mb-6 text-gray-800">Edit Pengguna</h3>
            <form id="editForm" method="POST" action="" onsubmit="handleSubmit(event)">
                <input type="hidden" name="id" id="edit_id">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="edit_nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="nama" id="edit_nama" class="mt-1 p-3 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                        <p class="modal-error" id="edit_nama_error">Nama wajib diisi.</p>
                    </div>
                    <div>
                        <label for="edit_nomor_id" class="block text-sm font-medium text-gray-700">Nomor ID</label>
                        <input type="text" name="nomor_id" id="edit_nomor_id" class="mt-1 p-3 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required placeholder="Contoh: EMP001">
                        <p class="modal-error" id="edit_nomor_id_error">Nomor ID wajib diisi.</p>
                    </div>
                    <div>
                        <label for="edit_username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" id="edit_username" class="mt-1 p-3 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                        <p class="modal-error" id="edit_username_error">Username wajib diisi.</p>
                    </div>
                    <div>
                        <label for="edit_password" class="block text-sm font-medium text-gray-700">Kata Sandi Baru (kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" id="edit_password" class="mt-1 p-3 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="edit_role" class="block text-sm font-medium text-gray-700">Peran</label>
                        <select name="role" id="edit_role" class="mt-1 p-3 w-full border rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="karyawan">Karyawan</option>
                            <option value="admin">Admin</option>
                        </select>
                        <p class="modal-error" id="edit_role_error">Peran wajib dipilih.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="modal-button bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 flex items-center gap-2">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="edit_user" id="saveButton" class="modal-button bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(id, nama, username, nomor_id, role) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_nomor_id').value = nomor_id;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_password').value = '';
        document.querySelectorAll('.modal-error').forEach(el => el.style.display = 'none');
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editForm').reset();
        document.querySelectorAll('.modal-error').forEach(el => el.style.display = 'none');
    }

    function handleSubmit(event) {
        event.preventDefault();
        const form = document.getElementById('editForm');
        let isValid = true;

        // Validate required fields
        const requiredFields = ['edit_nama', 'edit_username', 'edit_nomor_id', 'edit_role'];
        requiredFields.forEach(fieldId => {
            const input = document.getElementById(fieldId);
            const error = document.getElementById(`${fieldId}_error`);
            if (!input.value.trim()) {
                error.style.display = 'block';
                isValid = false;
            } else {
                error.style.display = 'none';
            }
        });

        if (isValid) {
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            form.submit();
        }
    }
    </script>

    <?php $conn->close(); ?>
</body>
</html>
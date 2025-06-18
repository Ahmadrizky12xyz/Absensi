<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "absensi_ptindotekhnoplus";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role, nama FROM users WHERE username = ?");
    if ($stmt === false) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['nama'] = $row['nama']; // Store nama for dashboard
            $redirect = $row['role'] === 'admin' ? 'admin_dashboard.php' : 'scan.php';
            header("Location: $redirect");
            exit;
        } else {
            $message = "Password salah.";
            $message_type = "error";
        }
    } else {
        $message = "Username tidak ditemukan.";
        $message_type = "error";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT INDO TEKHNO PLUS</title>
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
            background: linear-gradient(135deg, #0ea5e9 0%, #bae6fd 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
        }
        
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease forwards;
        }
        
        .btn-submit {
            transition: transform 0.2s ease-in-out, background-color 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            background-color: #0284c7;
        }
        
        /* Text Animation Styles */
        .animated-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0369a1;
            text-align: center;
            margin: 1.5rem 0;
            letter-spacing: 1px;
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
        
        /* Form Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .animated-title {
                font-size: 1.5rem;
            }
            .form-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center py-8">
        <div class="max-w-md w-full">
            <!-- Animated Title -->
            <div class="animated-title">
                <?php
                $title = "PT. INDO TEKHNO PLUS";
                for ($i = 0; $i < strlen($title); $i++) {
                    $char = $title[$i];
                    echo "<span>" . htmlspecialchars($char) . "</span>";
                }
                ?>
            </div>

            <!-- Login Form -->
            <div class="form-container">
                <?php if (isset($message)) { ?>
                    <div class="mb-4 p-4 rounded-lg text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>
                <form method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                        <input type="text" name="username" id="username" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                        <input type="password" name="password" id="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <button type="submit" class="btn-submit w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
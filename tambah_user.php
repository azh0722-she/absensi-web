<?php
session_start();
include 'koneksi.php'; // koneksi database

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil data profil admin dari tabel admin
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Proses submit form
if(isset($_POST['submit'])){
    $usernameInput = mysqli_real_escape_string($conn, $_POST['username']);
    $emailInput = mysqli_real_escape_string($conn, $_POST['email']);
    $passwordInput = password_hash($_POST['password'], PASSWORD_DEFAULT); // hash password
    $roleInput = mysqli_real_escape_string($conn, $_POST['role']);

    $query = "INSERT INTO users (username, email, password, role) VALUES ('$usernameInput', '$emailInput', '$passwordInput', '$roleInput')";
    if(mysqli_query($conn, $query)){
        header('Location: user.php');
        exit;
    } else {
        $error = "Gagal menambahkan user: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah User - Sistem Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body { font-family:"Poppins",sans-serif; background:linear-gradient(135deg,#1e293b,#0f172a); color:#fff; min-height:100vh; overflow-x:hidden; }
.content { padding:30px; animation:fadeIn 0.8s ease; }
.card { background:rgba(255,255,255,0.1); border:none; color:#fff; backdrop-filter:blur(10px); transition:transform 0.2s ease; }
.card:hover { transform:translateY(-5px); }
.navbar { background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.1); }
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
input, select { width: 100%; padding: 10px 15px; border-radius: 8px; border: none; background: rgba(255,255,255,0.1); color: #6a6a6aff; margin-bottom: 15px; outline:none; }
input:focus, select:focus { background: rgba(255,255,255,0.15); box-shadow: 0 0 10px rgba(0,150,255,0.3); }
button { background: linear-gradient(135deg,#007bff,#00bfff); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold; transition:0.3s; }
button:hover { background: linear-gradient(135deg,#005ecb,#00a2e8); transform:scale(1.03); }
.navbar img { width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; }
</style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar-container" class="col-2"></div>

    <!-- Konten utama -->
    <div class="content col">
        <!-- Navbar bergaya dashboard -->
        <nav class="navbar navbar-expand-lg px-3 mb-4">
          <div class="container-fluid">
            <span class="navbar-brand text-light fw-semibold"><i class="bi bi-person-plus-fill"></i> Tambah User</span>
            <div class="d-flex align-items-center">
              <img src="<?= !empty($admin['foto']) ? $admin['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
              <span><?= htmlspecialchars($admin['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
            </div>
          </div>
        </nav>

        <!-- Form Tambah User -->
        <div class="card p-4" style="max-width:600px;">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                </select>
                <div class="d-flex justify-content-between">
                    <button type="submit" name="submit">💾 Tambah User</button>
                    <button type="button" onclick="window.location.href='user.php'" class="btn btn-danger">↩️ Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Load Sidebar -->
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html);
</script>
</body>
</html>

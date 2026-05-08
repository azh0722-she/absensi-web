<?php
session_start();
include 'koneksi.php'; // koneksi ke database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepared statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Gunakan password_verify untuk hash yang aman
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header('Location: dashboard.php');
                exit;
            } elseif ($user['role'] === 'guru') {
                header('Location: guru/dashboard.php');
                exit;
            } else {
                $error = "Role tidak dikenali!";
            }
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Akun tidak ditemukan!";
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sistem Absensi
  SMA N 1 KOTA AGUNG
</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
    font-family: "Poppins", sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 20% 20%, rgba(58, 123, 213, 0.35), transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(30, 42, 120, 0.35), transparent 40%),
                #0f172a;
    color: #fff;
    overflow: hidden;
}
.login-card {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 45px 40px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    animation: fadeIn 0.9s ease;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.login-icon { font-size: 3.5rem; color: #ffffffcc; }
.login-title { font-weight: 600; font-size: 1.7rem; margin-top: 10px; color: #fff; }
.login-subtitle { color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; margin-bottom: 25px; }
.form-label { font-weight: 500; color: rgba(255, 255, 255, 0.8); }
.form-control {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #fff;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.3s ease;
}
.form-control::placeholder { color: rgba(255, 255, 255, 0.6); }
.form-control:focus {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
}
.btn-login {
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #3a7bd5, #00d2ff);
    color: #fff;
    font-weight: 600;
    transition: all 0.3s ease;
}
.btn-login:hover {
    background: linear-gradient(135deg, #00d2ff, #3a7bd5);
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0, 210, 255, 0.3);
}
.forgot-link { text-decoration: none; color: #00d2ff; font-size: 0.9rem; }
.forgot-link:hover { text-decoration: underline; }
.glow { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.4; animation: float 8s infinite ease-in-out alternate; }
.glow.one { width: 300px; height: 300px; background: #3a7bd5; top: -100px; left: -80px; }
.glow.two { width: 250px; height: 250px; background: #00d2ff; bottom: -80px; right: -60px; }
@keyframes float { from { transform: translateY(0px); } to { transform: translateY(20px); } }
footer { position: absolute; bottom: 10px; text-align: center; width: 100%; color: rgba(255, 255, 255, 0.6); font-size: 0.85rem; }
.alert { background: rgba(255, 50, 50, 0.2); border: 1px solid rgba(255, 0, 0, 0.4); color: #ffbaba; border-radius: 10px; }
</style>
</head>

<body>
<div class="glow one"></div>
<div class="glow two"></div>

<div class="login-card text-center">
  <i class="bi bi-person-circle login-icon"></i>
  <h2 class="login-title">ABSENSI SMA N 1
    KOTA AGUNG
  </h2>
  <!-- <p class="login-subtitle">Masuk untuk melanjutkan</p> -->

  <?php if (isset($error)): ?>
    <div class="alert alert-danger text-center py-2"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3 text-start">
      <label for="username" class="form-label">Username atau Email</label>
      <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username atau email" required>
    </div>

    <div class="mb-3 text-start">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
    </div>

    <div class="d-grid mt-4">
      <button type="submit" class="btn btn-login">Masuk</button>
    </div>
  </form>

  <!-- <div class="text-center mt-3">
    <a href="#" class="forgot-link">Lupa Password?</a>
  </div> -->
</div>

<!-- <footer>© 2025 Sistem Absensi Siswa</footer> -->
</body>
</html>

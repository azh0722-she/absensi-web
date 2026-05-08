<?php
session_start();
include '../koneksi.php'; // pastikan path sesuai lokasi file

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil data profil guru + username dari tabel users
$stmt = mysqli_prepare($conn, "SELECT g.*, u.username FROM guru g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$guru = mysqli_fetch_assoc($result);

// Jika belum ada, buat entry default di tabel guru
if(!$guru){
    $queryInsert = "INSERT INTO guru (user_id, nama_lengkap) VALUES ($user_id, '')";
    mysqli_query($conn, $queryInsert);

    $stmt = mysqli_prepare($conn, "SELECT g.*, u.username FROM guru g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $guru = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Guru Futuristik</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family:"Poppins",sans-serif;
    background:linear-gradient(135deg,#0f172a,#1e293b);
    color:#fff;
    min-height:100vh;
}
.content { padding:40px; }

/* Profil Container Futuristik */
.profile-container {
    max-width:900px;
    margin:auto;
    background:rgba(255,255,255,0.05);
    border-radius:25px;
    padding:40px 30px;
    box-shadow:0 15px 40px rgba(0,0,0,0.5);
    text-align:center;
    position:relative;
    overflow:hidden;
    transition:0.4s;
}
.profile-container::before {
    content:'';
    position:absolute;
    top:-50%;
    left:-50%;
    width:200%;
    height:200%;
    background:radial-gradient(circle at center, rgba(0,123,255,0.15), transparent 70%);
    animation:rotateBg 20s linear infinite;
    z-index:0;
}
@keyframes rotateBg { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }

/* Foto Profil */
.profile-photo {
    width:160px;
    height:160px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid rgba(0,123,255,0.6);
    margin-bottom:20px;
    position:relative;
    z-index:1;
    transition: transform 0.3s, box-shadow 0.3s;
}
.profile-photo:hover { 
    transform:scale(1.05) rotate(5deg); 
    box-shadow:0 0 25px rgba(0,123,255,0.7);
}

/* Nama */
.profile-name { font-size:30px; font-weight:700; margin-bottom:25px; position:relative; z-index:1; }

/* Kartu Expandable */
.card-expand {
    background:rgba(255,255,255,0.05);
    border-radius:20px;
    margin-bottom:20px;
    overflow:hidden;
    transition:0.3s;
    box-shadow:0 0 20px rgba(0,123,255,0.2);
}
.card-header {
    padding:15px 20px;
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:bold;
    font-size:16px;
    color:#0ea5e9;
    border-bottom:1px solid rgba(255,255,255,0.1);
}
.card-header:hover { background:rgba(14,165,233,0.1); }

/* Info Field */
.card-header {
    position: relative;
    z-index: 2;
}
.card-body {
    position: relative;
    z-index: 1;
    display:none;
    animation:fadeInCard 0.5s ease forwards;
}
@keyframes fadeInCard { from {opacity:0; transform:translateY(-5px);} to {opacity:1; transform:translateY(0);} }

/* Tombol Edit */
button.edit-btn {
    margin-top:25px;
    background:linear-gradient(135deg,#0ea5e9,#3b82f6);
    color:white;
    border:none;
    padding:12px 30px;
    border-radius:15px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 5px 15px rgba(0,123,255,0.4);
    position:relative;
    z-index:10;
}
button.edit-btn:hover { 
    transform:scale(1.05);
    background:linear-gradient(135deg,#0284c7,#2563eb);
    box-shadow:0 0 25px rgba(0,123,255,0.7);
}

/* Navbar */
.navbar { background:rgba(255,255,255,0.05); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.1); }
.navbar img { width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; }
</style>
</head>
<body>
<div class="d-flex">
    <div id="sidebar-container" class="col-2"></div>
    <div class="content col">
        <nav class="navbar navbar-expand-lg px-3 mb-4">
          <div class="container-fluid">
            <span class="navbar-brand text-light fw-semibold"><i class="bi bi-speedometer2"></i> PROFIL GURU</span>
            <div class="d-flex align-items-center">
              <img src="<?= !empty($guru['foto']) ? $guru['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
              <span><?= htmlspecialchars($guru['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
            </div>
          </div>
        </nav>

        <div class="profile-container">
            <img class="profile-photo" src="<?= !empty($guru['foto']) ? $guru['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
            <div class="profile-name"><?= htmlspecialchars($guru['nama_lengkap']) ?></div>

            <!-- Expandable Cards -->
            <div class="card-expand">
                <div class="card-header"><i class="bi bi-person"></i> Data Pribadi <i class="bi bi-chevron-down"></i></div>
                <div class="card-body">
                    <p><i class="bi bi-person-circle"></i> Username: <?= htmlspecialchars($guru['username']) ?></p>
                    <p><i class="bi bi-calendar-event"></i> Tanggal Lahir: <?= !empty($guru['tanggal_lahir']) ? date('d-m-Y', strtotime($guru['tanggal_lahir'])) : '-' ?></p>
                    <p>Tempat Lahir: <?= htmlspecialchars($guru['tempat_lahir']) ?: '-' ?></p>
                    <p>Jenis Kelamin: <?= !empty($guru['jenis_kelamin']) ? htmlspecialchars($guru['jenis_kelamin']) : '-' ?></p>
                    <p>Mata Pelajaran: <?= !empty($guru['mata_pelajaran']) ? htmlspecialchars($guru['mata_pelajaran']) : '-' ?></p>
                    <p>NIP: <?= !empty($guru['nip']) ? htmlspecialchars($guru['nip']) : '-' ?></p>
                    <p>Status: <?= !empty($guru['status_guru']) ? htmlspecialchars($guru['status_guru']) : '-' ?></p>
                </div>
            </div>

            <div class="card-expand">
                <div class="card-header"><i class="bi bi-envelope"></i> Kontak & Role <i class="bi bi-chevron-down"></i></div>
                <div class="card-body">
                    <p>Email: <?= htmlspecialchars($guru['email']) ?: '-' ?></p>
                    <p>Alamat: <?= htmlspecialchars($guru['alamat']) ?: '-' ?></p>
                    <p>No. Telepon: <?= htmlspecialchars($guru['no_telp']) ?: '-' ?></p>
                    <p>Role: <?= htmlspecialchars($role) ?></p>
                </div>
            </div>

            <button class="edit-btn" onclick="window.location.href='pengaturan.php'">
                <i class="bi bi-pencil-square"></i> Edit Profil
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

  // Muat sidebar
  fetch("sidebar.php")
    .then(res => res.text())
    .then(html => {
      document.getElementById("sidebar-container").innerHTML = html;

      // Event logout
      const logoutBtn = document.getElementById('logoutBtn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
          e.preventDefault();
          Swal.fire({
            title: 'Yakin ingin logout?',
            text: "Anda akan keluar dari akun ini.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Logout!',
            cancelButtonText: 'Batal'
          }).then((result) => {
            if (result.isConfirmed) {
              document.body.style.opacity = 0;
              setTimeout(() => {
                window.location.href = '../logout.php';
              }, 500);
            }
          });
        });
      }
    });

  // Expand/Collapse card
  document.querySelectorAll('.card-expand').forEach(card => {
      const header = card.querySelector('.card-header');
      const body = card.querySelector('.card-body');
      const icon = header.querySelector('.bi-chevron-down');

      body.style.display = 'none';
      header.style.cursor = 'pointer';

      header.addEventListener('click', () => {
          const isOpen = body.style.display === 'block';
          document.querySelectorAll('.card-body').forEach(b => b.style.display = 'none');
          document.querySelectorAll('.bi-chevron-down').forEach(i => i.style.transform = 'rotate(0deg)');
          if (!isOpen) {
              body.style.display = 'block';
              icon.style.transform = 'rotate(180deg)';
          }
      });
  });
});
</script>
</body>
</html>

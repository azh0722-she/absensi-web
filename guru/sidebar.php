<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Jika user belum login, redirect ke login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Ambil username dan role
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar</title>
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .sidebar {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      min-height: 100vh;
      padding-top: 4rem;
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      width: 250px;
      flex-shrink: 0;
    }

    .sidebar a {
      color: #cbd5e1;
      text-decoration: none;
      display: block;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background-color: rgba(255, 255, 255, 0.2);
      color: #fff;
    }
  </style>
</head>

<!-- 🔥 Tambahkan ini supaya DOM benar-benar utuh -->
<body>

<div class="sidebar p-3">
  <h4 class="text-center mb-4"><i class="bi bi-people-fill"></i> ABSENSI SISWA</h4>
  <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
  <a href="absensi_siswa.php"><i class="bi bi-calendar-check"></i> Absensi Siswa</a>
  <a href="daftar_hadir.php"><i class="bi bi-calendar-check"></i> Daftar Kehadiran</a>
  <a href="data_kelas.php"><i class="bi bi-calendar-check"></i> Data Kelas</a>
  <a href="data_siswa.php"><i class="bi bi-person-lines-fill"></i> Data Siswa</a>
  <a href="laporan.php"><i class="bi bi-graph-up"></i> Laporan</a>
  <a href="profil_guru.php"><i class="bi bi-gear"></i> Profil</a>
  <hr style="border-color: rgba(255, 255, 255, 0.1)" />
  <a href="javascript:void(0)" class="text-danger" id="logoutBtn">
    <i class="bi bi-box-arrow-left"></i> Logout
  </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Highlight menu aktif
  const currentPath = window.location.pathname.split("/").pop();
  document.querySelectorAll(".sidebar a").forEach((link) => {
    if (link.getAttribute("href") === currentPath) link.classList.add("active");
  });

  // Logout dengan SweetAlert2
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
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
</script>

</body>
</html>

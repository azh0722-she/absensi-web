<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil data guru
$stmt = mysqli_prepare($conn, "SELECT * FROM guru WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$guru = mysqli_fetch_assoc($result);

// Filter jurusan, tingkat, dan tanggal opsional
$jurusan = $_GET['jurusan'] ?? '';
$tingkat = $_GET['tingkat'] ?? '';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Ambil list jurusan
$jurusan_list = [];
$res_jurusan = mysqli_query($conn, "SELECT DISTINCT jurusan FROM kelas ORDER BY jurusan ASC");
while ($row = mysqli_fetch_assoc($res_jurusan)) {
    $jurusan_list[] = $row['jurusan'];
}

// Ambil absensi sesuai filter
$query_absen = "
    SELECT a.siswa_id, a.status, a.keterangan, s.nama_lengkap, s.nis, k.jurusan, k.tingkat, a.tanggal
    FROM absensi a
    JOIN siswa s ON a.siswa_id = s.id
    JOIN siswa_kelas sk ON sk.siswa_id = s.id
    JOIN kelas k ON sk.kelas_id = k.id
    WHERE a.tanggal = ?
";
$params = [$tanggal];
$types = 's';

if ($jurusan) {
    $query_absen .= " AND k.jurusan = ?";
    $params[] = $jurusan;
    $types .= 's';
}
if ($tingkat) {
    $query_absen .= " AND k.tingkat = ?";
    $params[] = $tingkat;
    $types .= 's';
}

$query_absen .= " ORDER BY k.tingkat, k.jurusan, s.nama_lengkap ASC";

$stmt_absen = mysqli_prepare($conn, $query_absen);
mysqli_stmt_bind_param($stmt_absen, $types, ...$params);
mysqli_stmt_execute($stmt_absen);
$res_absen = mysqli_stmt_get_result($stmt_absen);

$absensi_list = [];
while ($row = mysqli_fetch_assoc($res_absen)) {
    $kelas_id = $row['tingkat'] . '_' . $row['jurusan'];
    $absensi_list[$kelas_id][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Hadir - Sistem Absensi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  color: #fff;
  min-height: 100vh;
  overflow-x: hidden;
}
.content {
  padding: 30px;
  animation: fadeIn 0.8s ease;
  flex-grow: 1;
}
.navbar {
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.navbar img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 8px;
}
.card {
  background: rgba(172,172,172,0.85);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 15px;
  box-shadow: 0 0 20px rgba(0,0,0,0.3);
  color: #f8fafc;
}
.table {
  --bs-table-bg: transparent !important;
  --bs-table-striped-bg: rgba(255,255,255,0.05);
  --bs-table-hover-bg: rgba(255,255,255,0.1);
  color: #e2e8f0 !important;
  border-color: rgba(255,255,255,0.1) !important;
}
.table thead {
  background-color: rgba(255,255,255,0.1) !important;
  color: #fff;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
</head>

<body>
<div class="d-flex">
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Konten Utama -->
  <div class="content">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold">
          <i class="bi bi-door-open"></i> DAFTAR HADIR
        </span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($guru['foto']) ? $guru['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
          <span><?= htmlspecialchars($guru['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
        </div>
      </div>
    </nav>

    <!-- Filter Jurusan, Tingkat & Tanggal -->
    <div class="mb-4">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label for="jurusan" class="form-label">Jurusan</label>
          <select name="jurusan" id="jurusan" class="form-select">
            <option value="" class="text-center">-- Pilih Jurusan --</option>
            <?php foreach ($jurusan_list as $j): ?>
              <option value="<?= htmlspecialchars($j) ?>" <?= ($jurusan == $j) ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="tingkat" class="form-label">Kelas</label>
          <select name="tingkat" id="tingkat" class="form-select">
            <option value="">-- Pilih Kelas --</option>
            <option value="10" <?= ($tingkat == '10') ? 'selected' : '' ?>>10</option>
            <option value="11" <?= ($tingkat == '11') ? 'selected' : '' ?>>11</option>
            <option value="12" <?= ($tingkat == '12') ? 'selected' : '' ?>>12</option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="tanggal" class="form-label">Tanggal</label>
          <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
        </div>
      </form>
    </div>

    <!-- Daftar Hadir -->
    <?php if (!empty($absensi_list)): ?>
      <?php foreach ($absensi_list as $kelas_id => $siswa_list): ?>
        <div class="card mb-4 p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-semibold mb-0">
              <i class="bi bi-buildings"></i> Kelas <?= htmlspecialchars($siswa_list[0]['tingkat'] . " " . $siswa_list[0]['jurusan']); ?>
            </h5>
            <a href="hapus_absen.php?jurusan=<?= $siswa_list[0]['jurusan'] ?>&tingkat=<?= $siswa_list[0]['tingkat'] ?>&tanggal=<?= $tanggal ?>" 
               class="btn btn-sm btn-danger"
               onclick="return confirm('Yakin ingin menghapus seluruh absensi kelas ini untuk tanggal ini?');">
               <i class="bi bi-trash"></i> Hapus Kelas
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Lengkap</th>
                  <th>NIS</th>
                  <th>Status</th>
                  <th>Keterangan</th>
                  <th>Tanggal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($siswa_list as $s): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($s['nis']) ?></td>
                    <td><?= htmlspecialchars($s['status']) ?></td>
                    <td><?= htmlspecialchars($s['keterangan']) ?></td>
                    <td><?= htmlspecialchars($s['tanggal']) ?></td>
                    <td>
                      <a href="edit_absen.php?siswa_id=<?= $s['siswa_id'] ?>&tanggal=<?= $s['tanggal'] ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-center"><em>Belum ada data absensi untuk tanggal ini.</em></p>
    <?php endif; ?>
  </div>
</div>

<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => {
    document.getElementById("sidebar-container").innerHTML = html;

    // aktifkan ulang event logout setelah sidebar dimuat
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
</script>

</body>
</html>

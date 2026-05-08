<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil data admin
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Ambil filter jurusan & tingkat
$jurusan = $_GET['jurusan'] ?? '';
$tingkat = $_GET['tingkat'] ?? '';

// Ambil list jurusan
$jurusan_list = [];
$res_jurusan = mysqli_query($conn, "SELECT DISTINCT jurusan FROM kelas ORDER BY jurusan ASC");
while($row = mysqli_fetch_assoc($res_jurusan)){
    $jurusan_list[] = $row['jurusan'];
}

// Jika filter dipilih, ambil kelas & siswa
$siswa_per_kelas = [];
$res_kelas = null;

if($jurusan || $tingkat) {
    // Ambil kelas sesuai filter
    $query_kelas = "SELECT k.id AS kelas_id, k.jurusan, k.tingkat, g.nama_lengkap AS wali_kelas
                    FROM kelas k
                    LEFT JOIN admin g ON k.wali_kelas_id = g.id";
    $conditions = [];
    $params = [];
    $types = '';

    if($jurusan){
        $conditions[] = "k.jurusan = ?";
        $params[] = $jurusan;
        $types .= 's';
    }
    if($tingkat){
        $conditions[] = "k.tingkat = ?";
        $params[] = $tingkat;
        $types .= 's';
    }
    if($conditions){
        $query_kelas .= " WHERE " . implode(" AND ", $conditions);
    }
    $query_kelas .= " ORDER BY k.tingkat, k.jurusan ASC";

    $stmt_kelas = mysqli_prepare($conn, $query_kelas);
    if($params){
        mysqli_stmt_bind_param($stmt_kelas, $types, ...$params);
    }
    mysqli_stmt_execute($stmt_kelas);
    $res_kelas = mysqli_stmt_get_result($stmt_kelas);

    // Ambil siswa per kelas beserta absensi berdasarkan tanggal terpilih (default hari ini)
    $tanggal_absen = $_POST['tanggal_absen'] ?? date('Y-m-d');
    $siswa_query = "
        SELECT sk.kelas_id, s.id AS siswa_id, s.nama_lengkap, s.nis,
               COALESCE(a.status,'Alpha') AS status, COALESCE(a.keterangan,'') AS keterangan
        FROM siswa_kelas sk
        JOIN siswa s ON sk.siswa_id = s.id
        LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
        ORDER BY s.nama_lengkap ASC
    ";
    $stmt_siswa = mysqli_prepare($conn, $siswa_query);
    mysqli_stmt_bind_param($stmt_siswa, "s", $tanggal_absen);
    mysqli_stmt_execute($stmt_siswa);
    $res_siswa = mysqli_stmt_get_result($stmt_siswa);

    while($row = mysqli_fetch_assoc($res_siswa)){
        $siswa_per_kelas[$row['kelas_id']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Absensi Kelas - Sistem Absensi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; min-height: 100vh; overflow-x: hidden; }
.content { padding: 30px; animation: fadeIn 0.8s ease; }
.navbar { background: rgba(255,255,255,0.1); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.1); }
.navbar img { width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; }
.card { background: rgba(172,172,172,0.85); border:1px solid rgba(255,255,255,0.05); border-radius:15px; box-shadow:0 0 20px rgba(0,0,0,0.3); color:#f8fafc; }
.table { --bs-table-bg: transparent !important; --bs-table-striped-bg: rgba(255,255,255,0.05); --bs-table-hover-bg: rgba(255,255,255,0.1); color:#e2e8f0 !important; border-color: rgba(255,255,255,0.1) !important; }
.table thead { background-color: rgba(255,255,255,0.1) !important; color:#fff; }
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>

<body>
<div class="d-flex">
  <div id="sidebar-container" class="col-2"></div>
  <div class="content flex-grow-1">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold"><i class="bi bi-door-open"></i> Absensi Kelas</span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($admin['foto']) ? $admin['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
          <span><?= htmlspecialchars($admin['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
        </div>
      </div>
    </nav>

    <!-- Filter Jurusan & Tingkat -->
    <div class="mb-4">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-6">
          <label for="jurusan" class="form-label">Jurusan</label>
          <select name="jurusan" id="jurusan" class="form-select">
            <option value="">Pilih Jurusan</option>
            <?php foreach($jurusan_list as $j): ?>
              <option value="<?= htmlspecialchars($j) ?>" <?= ($jurusan==$j)?'selected':'' ?>><?= htmlspecialchars($j) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="tingkat" class="form-label">Kelas</label>
          <select name="tingkat" id="tingkat" class="form-select">
            <option value="">Pilih Kelas</option>
            <option value="10" <?= ($tingkat=='10')?'selected':'' ?>>10</option>
            <option value="11" <?= ($tingkat=='11')?'selected':'' ?>>11</option>
            <option value="12" <?= ($tingkat=='12')?'selected':'' ?>>12</option>
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </div>
      </form>
    </div>

    <!-- Absensi Siswa per Kelas -->
    <?php if($res_kelas && mysqli_num_rows($res_kelas) > 0): ?>
      <form method="POST" action="proses_absen.php">
        <div class="mb-3">
          <label for="tanggal_absen" class="form-label">Tanggal Absensi</label>
          <input type="date" name="tanggal_absen" id="tanggal_absen" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <?php while($k = mysqli_fetch_assoc($res_kelas)): ?>
          <div class="card mb-4 p-3">
            <h5 class="fw-semibold mb-2"><i class="bi bi-buildings"></i> Kelas <?= htmlspecialchars($k['tingkat'] . " " . $k['jurusan']); ?></h5>
            <p><strong>Wali Kelas:</strong> <?= htmlspecialchars($k['wali_kelas'] ?? 'Belum ditentukan'); ?></p>
            
            <?php if(!empty($siswa_per_kelas[$k['kelas_id']])): ?>
              <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Nama Lengkap</th>
                      <th>NIS</th>
                      <th>Hadir</th>
                      <th>Izin</th>
                      <th>Alpha</th>
                      <th>Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no=1; foreach($siswa_per_kelas[$k['kelas_id']] as $s): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                      <td><?= htmlspecialchars($s['nis']) ?></td>
                      <td><input type="radio" name="status[<?= $s['siswa_id'] ?>]" value="Hadir" <?= $s['status']=='Hadir'?'checked':'' ?>></td>
                      <td><input type="radio" name="status[<?= $s['siswa_id'] ?>]" value="Izin" <?= $s['status']=='Izin'?'checked':'' ?>></td>
                      <td><input type="radio" name="status[<?= $s['siswa_id'] ?>]" value="Alpha" <?= $s['status']=='Alpha'?'checked':'' ?>></td>
                      <td><input type="text" name="keterangan[<?= $s['siswa_id'] ?>]" class="form-control" value="<?= htmlspecialchars($s['keterangan']) ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p><em>Belum ada siswa di kelas ini.</em></p>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
        <button type="submit" class="btn btn-success">Simpan Absensi</button>
      </form>
    <?php elseif($jurusan || $tingkat): ?>
      <p class="text-center"><em>Belum ada siswa di kelas ini.</em></p>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html);
</script>
</body>
</html>

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

// Hanya admin
if ($role !== 'admin') {
    echo "Anda tidak memiliki izin mengakses halaman ini.";
    exit;
}

// Ambil data profil admin
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Buat entri admin kosong jika belum ada
if (!$admin) {
    $queryInsert = "INSERT INTO admin 
        (user_id, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, no_telp, alamat, foto)
        VALUES ($user_id, '', '', NULL, '', '', '', '')";
    mysqli_query($conn, $queryInsert);
    $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);
}

// Ambil rekap absensi bulan ini
$bulan_ini = date('Y-m'); // format YYYY-MM
$query = "SELECT status, COUNT(*) as jumlah 
          FROM absensi 
          WHERE tanggal LIKE ? 
          GROUP BY status";
$stmt = mysqli_prepare($conn, $query);
$like_bulan = $bulan_ini . '%';
mysqli_stmt_bind_param($stmt, "s", $like_bulan);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$hadir = $izin = $alfa = 0;
while($row = mysqli_fetch_assoc($res)){
    $status = strtolower($row['status']);
    if($status == 'hadir') $hadir = $row['jumlah'];
    if($status == 'izin') $izin = $row['jumlah'];
    if($status == 'alpha' || $status == 'alfa') $alfa = $row['jumlah']; // menyesuaikan DB
}


// Ambil data rekap per tanggal untuk grafik
$query_grafik = "SELECT tanggal, 
                 SUM(status='Hadir') as hadir,
                 SUM(status='Izin') as izin,
                 SUM(status='Alpha') as alfa
                 FROM absensi
                 WHERE tanggal LIKE ?
                 GROUP BY tanggal
                 ORDER BY tanggal ASC";
$stmt = mysqli_prepare($conn, $query_grafik);
mysqli_stmt_bind_param($stmt, "s", $like_bulan);
mysqli_stmt_execute($stmt);
$res_grafik = mysqli_stmt_get_result($stmt);

$grafik_labels = [];
$grafik_hadir = [];
$grafik_izin = [];
$grafik_alfa = [];

while($row = mysqli_fetch_assoc($res_grafik)){
    $grafik_labels[] = $row['tanggal'];
    $grafik_hadir[] = (int)$row['hadir'];
    $grafik_izin[] = (int)$row['izin'];
    $grafik_alfa[] = (int)$row['alfa'];
}

// Data tambahan untuk card ringkasan
$total_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa"))['total'];
$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Sistem Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Body */
body {
    font-family: "Poppins", sans-serif;
    background: linear-gradient(135deg,#0f172a,#1e293b);
    color: #f1f5f9; /* teks lembut */
    min-height: 100vh;
    overflow-x: hidden;
    animation: fadeIn 1s ease forwards;
}
@keyframes fadeIn { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }

/* Navbar */
.navbar {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(15px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 0 20px rgba(14,165,233,0.3);
}
.navbar img {
    width:32px; height:32px;
    border-radius:50%;
    object-fit:cover;
    margin-right:8px;
    box-shadow:0 0 10px rgba(0,123,255,0.3);
}

/* Cards Futuristik */
.card {
    background: rgba(255,255,255,0.05);
    border-radius:20px;
    color:#f1f5f9; /* teks lembut */
    backdrop-filter:blur(12px);
    transition: transform 0.3s, box-shadow 0.3s;
    box-shadow:0 0 15px rgba(0,123,255,0.2);
}
.card:hover {
    transform:translateY(-5px) scale(1.03);
    box-shadow:0 0 25px rgba(0,123,255,0.3), 0 0 50px rgba(0,123,255,0.2) inset;
}

/* Text Header */
h5,h6 {
    color: #f1f5f9; /* putih lembut */
    text-shadow: 0 0 3px rgba(0,0,0,0.2);
}

/* Chart Futuristik */
canvas { 
    background: rgba(255,255,255,0.02); 
    border-radius:15px; 
    box-shadow:0 0 20px rgba(14,165,233,0.2); 
}

/* Buttons */
button {
    background: linear-gradient(135deg,#0ea5e9,#3b82f6);
    color:#f1f5f9;
    border:none;
    padding:12px 25px;
    border-radius:15px;
    cursor:pointer;
    font-size:16px;
    font-weight:bold;
    transition: all 0.3s ease;
    box-shadow:0 5px 15px rgba(0,123,255,0.3);
}
button:hover {
    transform:scale(1.05);
    background: linear-gradient(135deg,#0284c7,#2563eb);
    box-shadow:0 0 25px rgba(0,123,255,0.3),0 0 50px rgba(0,123,255,0.2);
}

/* Scrollbar */
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
::-webkit-scrollbar-thumb { background: rgba(14,165,233,0.3); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(14,165,233,0.6); }

</style>
</style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Konten utama -->
  <div class="content col">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold"><i class="bi bi-speedometer2"></i> DASHBOARD</span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($admin['foto']) ? $admin['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
          <span><?= htmlspecialchars($admin['nama_lengkap']) ?> ( <?= htmlspecialchars($role) ?> )</span>
        </div>
      </div>
    </nav>

    <!-- Card Ringkasan -->
    <div class="row g-4">
      <div class="col-md-3">
        <div class="card p-3 text-center">
          <i class="bi bi-people-fill fs-2 mb-2"></i>
          <h6>Total Siswa</h6>
          <h3><?= $total_siswa ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 text-center">
          <i class="bi bi-building fs-2 mb-2"></i>
          <h6>Total Kelas</h6>
          <h3><?= $total_kelas ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 text-center">
          <i class="bi bi-check-circle fs-2 mb-2 text-success"></i>
          <h6>Total Hadir Bulan Ini</h6>
          <h3><?= $hadir ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 text-center">
          <i class="bi bi-exclamation-circle fs-2 mb-2 text-warning"></i>
          <h6>Total Izin Bulan Ini</h6>
          <h3><?= $izin ?></h3>
        </div>
      </div>
    </div>
    <!-- Grafik Absensi -->
    <div class="row mt-4 g-4">
      <div class="col-md-12">
        <div class="card p-3">
          <h5 class="mb-3"><i class="bi bi-graph-up"></i> Rekap Absensi Bulanan</h5>
          <canvas id="chartAbsensi" height="120"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
          <h6 class="mb-3"><i class="bi bi-bar-chart"></i> Grafik Hadir vs Izin vs Alfa</h6>
          <canvas id="chartHIAVs"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
          <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Persentase Kehadiran</h6>
          <canvas id="chartPie"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html);

// Line Chart Bulanan
const ctxLine = document.getElementById('chartAbsensi').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?= json_encode($grafik_labels) ?>,
        datasets: [
            { label: 'Hadir', data: <?= json_encode($grafik_hadir) ?>, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.2)', tension:0.4, fill:true },
            { label: 'Izin', data: <?= json_encode($grafik_izin) ?>, borderColor: '#facc15', backgroundColor: 'rgba(250,204,21,0.2)', tension:0.4, fill:true },
            { label: 'Alfa', data: <?= json_encode($grafik_alfa) ?>, borderColor: '#f87171', backgroundColor: 'rgba(248,113,113,0.2)', tension:0.4, fill:true }
        ]
    },
    options: {
        responsive:true,
        plugins: { legend:{ labels:{ color:'#fff' } }, tooltip:{ mode:'index', intersect:false } },
        scales: { x:{ ticks:{ color:'#fff' }, grid:{ color:'rgba(255,255,255,0.1)' } }, y:{ ticks:{ color:'#fff' }, grid:{ color:'rgba(255,255,255,0.1)' }, beginAtZero:true } }
    }
});

// Bar Chart Hadir vs Izin vs Alfa
const ctxBar = document.getElementById('chartHIAVs').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: ['Hadir','Izin','Alfa'],
        datasets:[{
            label:'Jumlah Siswa',
            data:[<?= $hadir ?>, <?= $izin ?>, <?= $alfa ?>],
            backgroundColor:['#22c55e','#facc15','#f87171']
        }]
    },
    options:{
        responsive:true,
        plugins:{ legend:{ display:false }, tooltip:{ mode:'index', intersect:false } },
        scales:{ x:{ ticks:{ color:'#fff' }, grid:{ color:'rgba(255,255,255,0.1)' } }, y:{ ticks:{ color:'#fff' }, beginAtZero:true, grid:{ color:'rgba(255,255,255,0.1)' } } }
    }
});

// Pie Chart Persentase
const ctxPie = document.getElementById('chartPie').getContext('2d');
new Chart(ctxPie, {
    type:'pie',
    data:{
        labels:['Hadir','Izin','Alfa'],
        datasets:[{ data:[<?= $hadir ?>, <?= $izin ?>, <?= $alfa ?>], backgroundColor:['#22c55e','#facc15','#f87171'] }]
    },
    options:{
        responsive:true,
        plugins:{ legend:{ labels:{ color:'#fff' } } }
    }
});
</script>
</body>
</html>

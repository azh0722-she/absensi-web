<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Hanya guru
if($role !== 'guru'){
    echo "Anda tidak memiliki izin mengakses halaman ini.";
    exit;
}

// Ambil profil guru + username dari tabel users
$stmt = mysqli_prepare($conn, "SELECT g.*, u.username FROM guru g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$guru = mysqli_fetch_assoc($result);

// Jika belum ada profil guru, buat entry kosong
if(!$guru){
    $queryInsert = "INSERT INTO guru (user_id, nama_lengkap, email, alamat, no_telp, tempat_lahir, tanggal_lahir, jenis_kelamin, mata_pelajaran, status_guru) 
                    VALUES ($user_id, '', '', '', '', '', '', '', '', '')";
    mysqli_query($conn, $queryInsert);

    $stmt = mysqli_prepare($conn, "SELECT g.*, u.username FROM guru g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $guru = mysqli_fetch_assoc($result);
}

// Proses submit form
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
    $mata_pelajaran = mysqli_real_escape_string($conn, $_POST['mata_pelajaran']);
    $nip = mysqli_real_escape_string($conn, $_POST['nip']);

    $status_guru = mysqli_real_escape_string($conn, $_POST['status_guru']);
    $password = $_POST['password'];
    $username_new = mysqli_real_escape_string($conn, $_POST['username']);

    // Upload foto jika ada
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK){
        $fileTmp = $_FILES['foto']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['foto']['name']);
        $targetDir = 'uploads/guru/';
        if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . $fileName;

        if(move_uploaded_file($fileTmp, $targetFile)){
            $stmt3 = mysqli_prepare($conn, "UPDATE guru SET foto=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt3, "si", $targetFile, $user_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);
        }
    }

    // Update data guru
    $stmt = mysqli_prepare($conn, "UPDATE guru SET nama_lengkap=?, email=?, alamat=?, no_telp=?, tempat_lahir=?, tanggal_lahir=?, jenis_kelamin=?, mata_pelajaran=?, nip=?, status_guru=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "ssssssssssi", $nama, $email, $alamat, $no_telp, $tempat_lahir, $tanggal_lahir, $jenis_kelamin, $mata_pelajaran, $nip, $status_guru, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update password jika diisi
    if(!empty($password)){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "si", $hashedPassword, $user_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }

    // Update username jika diubah
    if(!empty($username_new) && $username_new !== $guru['username']){
        $stmt_username = mysqli_prepare($conn, "UPDATE users SET username=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_username, "si", $username_new, $user_id);
        mysqli_stmt_execute($stmt_username);
        mysqli_stmt_close($stmt_username);
    }

    header('Location: profil_guru.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan - Sistem Absensi Guru</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family:"Poppins",sans-serif; background:linear-gradient(135deg,#1e293b,#0f172a); color:#fff; min-height:100vh; overflow-x:hidden; }
.content { padding:30px; display:flex; justify-content:center; align-items:flex-start; animation:fadeIn 0.8s ease; }
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
.profile-card { background: rgba(255,255,255,0.1); border-radius:20px; padding:40px; max-width:600px; width:100%; text-align:center; backdrop-filter:blur(10px); box-shadow:0 4px 30px rgba(0,0,0,0.3); }
.profile-card img { width:120px; height:120px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.3); margin-bottom:20px; cursor:pointer; box-shadow:0 0 20px rgba(0,150,255,0.4); }
.form-group { margin-bottom:15px; text-align:left; }
label { display:block; margin-bottom:5px; font-weight:500; color:#ddd; }
input, select { width:100%; padding:10px 12px; border-radius:10px; border:none; background: rgba(255,255,255,0.1); color:#fff; outline:none; font-size:14px; transition:0.2s; }
input:focus, select:focus { background: rgba(255,255,255,0.15); box-shadow:0 0 10px rgba(0,150,255,0.3); }
button { background: linear-gradient(135deg,#007bff,#00bfff); color:white; border:none; padding:12px 25px; border-radius:12px; cursor:pointer; font-size:16px; font-weight:bold; margin-top:15px; box-shadow:0 0 20px rgba(0,150,255,0.4); transition:0.3s; }
button:hover { transform:scale(1.05); background: linear-gradient(135deg,#005ecb,#00a2e8); box-shadow:0 0 25px rgba(0,150,255,0.7); }
.btn-cancel { background: linear-gradient(135deg,#ff4141,#ff7575); box-shadow:0 0 15px rgba(255,65,65,0.4); }
.btn-cancel:hover { background: linear-gradient(135deg,#e60000,#ff5050); box-shadow:0 0 20px rgba(255,80,80,0.7); }
</style>
</head>
<body>
<div class="d-flex">
    <div id="sidebar-container" class="col-2"></div>
    <div class="content col">
        <div class="profile-card">
            <h2>⚙️ Pengaturan Akun Guru</h2>
            <form method="post" enctype="multipart/form-data">
                <img id="preview-foto" src="<?= !empty($guru['foto']) ? $guru['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" onclick="document.getElementById('foto').click();" alt="Foto Profil">
                <input type="file" id="foto" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(event)">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($guru['username']) ?>">
                </div>

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($guru['nama_lengkap']) ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($guru['email']) ?>">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat" value="<?= htmlspecialchars($guru['alamat']) ?>">
                </div>

                <div class="form-group">
                    <label for="no_telp">No. Telepon</label>
                    <input type="text" id="no_telp" name="no_telp" value="<?= htmlspecialchars($guru['no_telp']) ?>">
                </div>

                <div class="form-group">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input type="text" id="tempat_lahir" name="tempat_lahir" value="<?= htmlspecialchars($guru['tempat_lahir']) ?>">
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($guru['tanggal_lahir']) ?>">
                </div>

                <div class="form-group">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin">
                        <option value="" <?= $guru['jenis_kelamin']==''?'selected':'' ?>>-- Pilih --</option>
                        <option value="Laki-laki" <?= $guru['jenis_kelamin']=='Laki-laki'?'selected':'' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= $guru['jenis_kelamin']=='Perempuan'?'selected':'' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mata_pelajaran">Mata Pelajaran</label>
                    <input type="text" id="mata_pelajaran" name="mata_pelajaran" value="<?= htmlspecialchars($guru['mata_pelajaran']) ?>">
                </div>

                <div class="form-group">
                    <label for="nip">NIP</label>
                    <input type="number" id="nip" name="nip" value="<?= htmlspecialchars($guru['nip']) ?>">
                </div>

<div class="form-group">
    <label for="status_guru">Status Guru</label>
    <select id="status_guru" name="status_guru">
        <option value="" <?= $guru['status_guru']==''?'selected':'' ?>>-- Pilih --</option>
        <option value="Tetap" <?= $guru['status_guru']=='Tetap'?'selected':'' ?>>Tetap</option>
        <option value="Honorer" <?= $guru['status_guru']=='Honorer'?'selected':'' ?>>Honorer</option>
        <option value="Magang" <?= $guru['status_guru']=='Magang'?'selected':'' ?>>Magang</option>
    </select>
</div>


                <div class="form-group">
                    <label for="password">Kata Sandi Baru</label>
                    <input type="password" id="password" name="password" placeholder="Kosong = tidak diubah">
                </div>

                <button type="submit">💾 Simpan Perubahan</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='profil_guru.php'">↩️ Kembali ke Profil</button>
            </form>
        </div>
    </div>
</div>

<script>
function previewFoto(event){
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('preview-foto').src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}

fetch("sidebar.php")
    .then(res => res.text())
    .then(html => document.getElementById("sidebar-container").innerHTML = html);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

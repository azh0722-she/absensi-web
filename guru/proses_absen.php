<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ✅ Simpan ID guru yang sedang login

$jurusan = $_POST['jurusan'] ?? '';
$tingkat = $_POST['tingkat'] ?? '';
$status = $_POST['status'] ?? [];
$keterangan = $_POST['keterangan'] ?? [];
$tanggal = $_POST['tanggal_absen'] ?? date('Y-m-d');

// Simpan absensi
foreach ($keterangan as $siswa_id => $ket) {
    $absen = $status[$siswa_id] ?? 'Alpha';

    // ✅ Masukkan user_id ke dalam query
    $stmt = mysqli_prepare($conn, "
        INSERT INTO absensi (siswa_id, tanggal, status, keterangan, user_id) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            status = VALUES(status), 
            keterangan = VALUES(keterangan),
            user_id = VALUES(user_id)
    ");
    mysqli_stmt_bind_param($stmt, "isssi", $siswa_id, $tanggal, $absen, $ket, $user_id);
    mysqli_stmt_execute($stmt);
}

header("Location: absensi_siswa.php?jurusan=$jurusan&tingkat=$tingkat");
exit;
?>

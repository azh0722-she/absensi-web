<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Parameter bisa per siswa atau per kelas
$siswa_id = $_GET['siswa_id'] ?? '';
$jurusan = $_GET['jurusan'] ?? '';
$tingkat = $_GET['tingkat'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

// Hapus per siswa
if ($siswa_id && $tanggal) {
    $stmt = mysqli_prepare($conn, "DELETE FROM absensi WHERE siswa_id=? AND tanggal=?");
    mysqli_stmt_bind_param($stmt, "is", $siswa_id, $tanggal);
    mysqli_stmt_execute($stmt);
}

// Hapus seluruh kelas
if ($jurusan && $tingkat && $tanggal) {
    $query = "
        DELETE a FROM absensi a
        JOIN siswa_kelas sk ON a.siswa_id = sk.siswa_id
        JOIN kelas k ON sk.kelas_id = k.id
        WHERE k.jurusan=? AND k.tingkat=? AND a.tanggal=?
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $jurusan, $tingkat, $tanggal);
    mysqli_stmt_execute($stmt);
}

// Redirect kembali ke halaman daftar hadir dengan filter yang sama
header("Location: daftar_hadir.php?jurusan=$jurusan&tingkat=$tingkat&tanggal=$tanggal");
exit;
?>

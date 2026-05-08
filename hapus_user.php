<?php
session_start();
include 'koneksi.php'; // koneksi database

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Hanya admin yang boleh hapus
if ($_SESSION['role'] !== 'admin') {
    echo "Anda tidak memiliki izin untuk menghapus user.";
    exit;
}

// Ambil id user dari GET
$id = $_GET['id'] ?? null;

if (!$id) {
    echo "ID user tidak ditemukan.";
    exit;
}

// Jangan biarkan admin menghapus diri sendiri
if ($id == $_SESSION['user_id']) {
    echo "Anda tidak bisa menghapus akun sendiri.";
    exit;
}

// Hapus user dari database
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if(mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header('Location: user.php'); // kembali ke halaman data user
    exit;
} else {
    echo "Gagal menghapus user.";
}

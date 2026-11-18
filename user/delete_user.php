<?php
session_start();
require_once '../config.php';

$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['user_id']) || !in_array($jabatan_lower, ['admin', 'administrator'])) {
    header('Location: ../login.php');
    exit();
}

$id_user = $_GET['id'] ?? null;

if ($id_user) {
    // Proteksi: jangan izinkan menghapus diri sendiri yang sedang aktif
    if ($_SESSION['user_id'] == $id_user) {
        header("Location: user.php?status=error_self_delete");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->bind_param("s", $id_user);

    if ($stmt->execute()) {
        header("Location: user.php?status=success_delete");
    } else {
        header("Location: user.php?status=error_delete");
    }
    $stmt->close();
} else {
    header("Location: user.php");
}
$conn->close();
?>

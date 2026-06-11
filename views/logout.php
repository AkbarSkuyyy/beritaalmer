<?php
// Menghapus semua data sesi (Session)
session_unset();
session_destroy();

// Lemparkan kembali ke halaman login
header("Location: /login");
exit;
?>
<?php
session_start();

if (!isset($_SESSION['login'])) {
  header('Location: login.php');
  exit;
}

require 'functions.php';

// Jika tidak ada id di url
if (!isset($_GET['id'])) {
  header('location: index.php');
  exit;
}

// Mengambil id dari url
$id = $_GET['id'];

if (hapus($id) > 0) {
  echo "<script>
          alert('Data berhasil dihapus');
          document.location.href = 'index.php';
        </script>";
} else {
  echo "Data gagal dihapus";
}

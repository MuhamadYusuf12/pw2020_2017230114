<?php

function koneksi()
{
  return mysqli_connect('localhost', 'root', '', 'pw_2017230114');
}

function query($query)
{
  $conn = koneksi();
  $result = mysqli_query($conn, $query);

  // Jika hasilnya hanya 1 data
  if (mysqli_num_rows($result) == 1) {
    return mysqli_fetch_assoc($result);
  }

  $rows = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
  }

  return $rows;
}

function upload()
{
  $nama_file = $_FILES['gambar']['name'];
  $tipe_file = $_FILES['gambar']['type'];
  $ukuran_file = $_FILES['gambar']['size'];
  $error = $_FILES['gambar']['error'];
  $tmp_file = $_FILES['gambar']['tmp_name'];

  // Ketika tidak ada gambar yang dipilih
  if ($error == 4) {
    // echo "<script>
    //         alert('Pilih gambar terlebih dahulu');
    //       </script>";

    return 'nophoto.jpg';
  }

  // Cek ekstensi file
  $daftar_gambar = ['jpg', 'jpeg', 'png'];
  $ekstensi_file = explode('.', $nama_file);
  $ekstensi_file = strtolower(end($ekstensi_file));

  if (!in_array($ekstensi_file, $daftar_gambar)) {
    echo "<script>
            alert('Yang anda pilih bukan gambar');
          </script>";

    return false;
  }

  // Cek tipe file
  if ($tipe_file != 'image/jpeg' && $tipe_file != 'image/png') {
    echo "<script>
            alert('Yang anda pilih bukan gambar');
          </script>";

    return false;
  }

  // Cek ukuran file
  // Maksimal 5MB == 5000000
  if ($ukuran_file > 5000000) {
    echo "<script>
            alert('Ukuran gambar terlalu besar');
          </script>";

    return false;
  }

  // Lolos pengecekan
  // Siap upload file
  // Generate nama file baru
  $nama_file_baru = uniqid();
  $nama_file_baru .= '.';
  $nama_file_baru .= $ekstensi_file;

  move_uploaded_file($tmp_file, 'img/' . $nama_file_baru);

  return $nama_file_baru;
}

function tambah($data)
{
  $conn = koneksi();

  $nama = htmlspecialchars($data['nama']);
  $nim = htmlspecialchars($data['nim']);
  $email = htmlspecialchars($data['email']);
  $jurusan = htmlspecialchars($data['jurusan']);
  // $gambar = htmlspecialchars($data['gambar']);

  // Upload gambar
  $gambar = upload();

  if (!$gambar) {
    return false;
  }

  $query = "INSERT INTO
              mahasiswa
            VALUES
          (null, '$nama', '$nim', '$email', '$jurusan', '$gambar');
        ";
  mysqli_query($conn, $query) or die(mysqli_error($conn));

  return mysqli_affected_rows($conn);
}

function hapus($id)
{
  $conn = koneksi();

  // Menghapus gambar di folder img
  $mhs = query("SELECT * FROM mahasiswa WHERE id = $id");
  if ($mhs['gambar'] != 'nophoto.jpg') {
    unlink('img/' . $mhs['gambar']);
  }

  mysqli_query($conn, "DELETE FROM mahasiswa WHERE id = $id") or die(mysqli_error($conn));

  return mysqli_affected_rows($conn);
}

function ubah($data)
{
  $conn = koneksi();

  $id = $data['id'];
  $nama = htmlspecialchars($data['nama']);
  $nim = htmlspecialchars($data['nim']);
  $email = htmlspecialchars($data['email']);
  $jurusan = htmlspecialchars($data['jurusan']);
  $gambar_lama = htmlspecialchars($data['gambar_lama']);

  $gambar = upload();

  if (!$gambar) {
    return false;
  }

  if ($gambar == 'nophoto.jpg') {
    $gambar = $gambar_lama;
  }

  $query = "UPDATE mahasiswa SET
              nama = '$nama',
              nim = '$nim',
              email = '$email',
              jurusan = '$jurusan',
              gambar = '$gambar'
            WHERE id = $id";
  mysqli_query($conn, $query) or die(mysqli_error($conn));

  return mysqli_affected_rows($conn);
}

function cari($keyword)
{
  $conn = koneksi();
  $query = "SELECT * FROM mahasiswa
              WHERE 
            nama LIKE '%$keyword%' OR
            nim LIKE '%$keyword%'
          ";
  $result = mysqli_query($conn, $query);

  $rows = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
  }

  return $rows;
}

function login($data)
{
  $conn = koneksi();
  $username = htmlspecialchars($data['username']);
  $password = htmlspecialchars($data['password']);

  // Cek dulu usernamenya
  if ($user = query("SELECT * FROM user WHERE username = '$username'")) {
    // Cek password
    if (password_verify($password, $user['password'])) {
      // Set session
      $_SESSION['login'] = true;

      header('Location: index.php');
      exit;
    }
  }

  return [
    'error' => true,
    'pesan' => 'Username / Password salah'
  ];
}

function registrasi($data)
{
  $conn = koneksi();
  $username = htmlspecialchars(strtolower($data['username']));
  $password1 = mysqli_real_escape_string($conn, $data['password1']);
  $password2 = mysqli_real_escape_string($conn, $data['password2']);

  // Jika username atau password kosong
  if (empty($username) || empty($password1) || empty($password2)) {
    echo "<script>
            alert('Username / Password tidak boleh kosong');
            document.location.href = 'registrasi.php';
          </script>";

    return false;
  }

  // Jika username sudah ada
  if (query("SELECT * FROM user WHERE username = '$username'")) {
    echo "<script>
            alert('Username sudah ada');
            document.location.href = 'registrasi.php';
          </script>";

    return false;
  }

  // Jika konfirmasi password tidak sesuai
  if ($password1 !== $password2) {
    echo "<script>
            alert('Konfirmasi password salah');
            document.location.href = 'registrasi.php';
          </script>";

    return false;
  }

  // Jika password < 5 digit
  if (strlen($password1) < 5) {
    echo "<script>
            alert('Password terlalu pendek');
            document.location.href = 'registrasi.php';
          </script>";

    return false;
  }

  // Jika username dan password sudah sesuai
  // Enkripsi password
  $password_baru = password_hash($password1, PASSWORD_DEFAULT);

  // Insert ke tabel user
  $query = "INSERT INTO user
              VALUES
            (null, '$username', '$password_baru')
          ";

  mysqli_query($conn, $query) or die(mysqli_error($conn));

  return mysqli_affected_rows($conn);
}

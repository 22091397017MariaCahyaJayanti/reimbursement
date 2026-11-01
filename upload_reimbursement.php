<?php
// Proses upload file dan simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan file dan data form
    // Misalnya file disimpan di folder 'uploads/'

    $date = $_POST['date'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    // Simpan file jika ada
    if (isset($_FILES['img'])) {
        $file = $_FILES['img'];
        $filePath = 'uploads/' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $filePath);
    }

    // Kirim data dalam bentuk HTML untuk ditambahkan ke tabel
    echo "
    <tr>
      <td>1</td>
      <td>$date</td>
      <td>$name</td>
      <td>$description</td>
      <td>$amount</td>
      <td><a href='$filePath' target='_blank'>View File</a></td>
      <td><span class='badge bg-warning'>Pending</span></td>
    </tr>
    ";
}
?>

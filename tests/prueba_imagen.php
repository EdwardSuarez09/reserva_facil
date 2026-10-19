<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_FILES['imagen']);
    echo "</pre>";

    if ($_FILES['imagen']['error'] === 0) {
        $img = base64_encode(file_get_contents($_FILES['imagen']['tmp_name']));
        echo "<h3>Imagen recibida:</h3><img src='data:image/jpeg;base64,{$img}' style='max-width:200px'>";
    }

    exit;
}
?>

<form method="POST" enctype="multipart/form-data">
  <label>Sube una imagen:
    <input type="file" name="imagen" accept="image/*">
  </label>
  <button type="submit">Enviar</button>
</form>

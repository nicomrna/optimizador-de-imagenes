<?php
$uploadedFiles = [];
$zipFile = 'listas/compressed_images.zip';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_FILES['images']['name'][0])) {
    $files = $_FILES['images'];
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $outputDir = 'listas/';

    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    // Eliminar el ZIP anterior si existe
    if (file_exists($zipFile)) {
        unlink($zipFile);
    }

    foreach ($files['name'] as $key => $name) {
        $filename = pathinfo($name, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $fileTmpName = $files['tmp_name'][$key];

        if (!in_array($extension, $allowed)) {
            continue; // Salta archivos no permitidos
        }

        $info = getimagesize($fileTmpName);
        if (!$info) {
            continue; // Salta archivos inválidos
        }

        $originalSize = filesize($fileTmpName); // Tamaño antes de optimizar
        $outputFile = $outputDir . 'optimized_' . uniqid() . '.' . $extension;

        $image = null;
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($fileTmpName);
                imagejpeg($image, $outputFile, 75);
                break;
            case 'image/png':
                $image = imagecreatefrompng($fileTmpName);
                imagepng($image, $outputFile, 6);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($fileTmpName);
                imagewebp($image, $outputFile, 75);
                break;
        }

        if ($image) {
            imagedestroy($image);
            $optimizedSize = filesize($outputFile); // Tamaño después de optimizar
            $uploadedFiles[] = [
                'optimized' => $outputFile,
                'originalSize' => round($originalSize / 1024, 2) . ' KB',
                'optimizedSize' => round($optimizedSize / 1024, 2) . ' KB'
            ];
        }
    }

    // Crear ZIP si hay imágenes optimizadas
    if (!empty($uploadedFiles)) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            foreach ($uploadedFiles as $file) {
                $zip->addFile($file['optimized'], basename($file['optimized']));
            }
            $zip->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimizador de Imágenes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
        }
        .container {
            background: white;
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        input[type="file"] {
            margin-bottom: 10px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background: #218838;
        }
        .image-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 20px;
        }
        .image-card {
            background: white;
            padding: 10px;
            margin: 10px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        img {
            max-width: 250px;
            border-radius: 5px;
        }
        .size-info {
            font-size: 14px;
            margin: 5px 0;
        }
        a {
            display: inline-block;
            margin-top: 5px;
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .download-all {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
        }
        .download-all:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Optimizador de Imágenes</h2>
        <p>Subí imágenes para reducir su tamaño sin perder calidad.</p>
        <p>Se guardan en carpeta raíz -> listas</p>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="images[]" accept="image/jpeg, image/png, image/webp" multiple required>
            <br>
            <button type="submit">Optimizar</button>
        </form>

        <?php if (!empty($uploadedFiles)): ?>
            <h3>Imágenes optimizadas:</h3>
            <div class="image-container">
                <?php foreach ($uploadedFiles as $file): ?>
                    <div class="image-card">
                        <img src="<?= $file['optimized'] ?>" alt="Optimizada">
                        <p class="size-info">
                            Original: <?= $file['originalSize'] ?> → Optimizada: <?= $file['optimizedSize'] ?>
                        </p>
                        <a href="<?= $file['optimized'] ?>" download>Descargar</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <a class="download-all" href="<?= $zipFile ?>" download>📥 Descargar Todo</a>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

function processImage($sourcePath, $targetPath) {
    // Verificar si GD está disponible
    if (extension_loaded('gd')) {
        list($width, $height, $type) = getimagesize($sourcePath);
        $maxSize = 300;
        
        // Calcular nuevas dimensiones
        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = floor($height * ($maxSize / $width));
        } else {
            $newHeight = $maxSize;
            $newWidth = floor($width * ($maxSize / $height));
        }
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Crear imagen basada en el tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagejpeg($newImage, $targetPath, 90);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                // Mantener transparencia
                imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagepng($newImage, $targetPath, 9);
                break;
            default:
                throw new Exception('Formato de imagen no soportado.');
        }
        
        imagedestroy($newImage);
        imagedestroy($source);
    } else {
        // Si GD no está disponible, solo mover el archivo
        if (!copy($sourcePath, $targetPath)) {
            throw new Exception('Error al copiar el archivo.');
        }
    }
}

function resizeImage($sourcePath, $targetPath, $width = 300, $height = 300) {
    list($originalWidth, $originalHeight, $type) = getimagesize($sourcePath);
    
    $newImage = imagecreatetruecolor($width, $height);
    
    // Mantener transparencia para PNG
    if ($type == IMAGETYPE_PNG) {
        imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }
    
    // Crear imagen basada en el tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        default:
            throw new Exception('Formato de imagen no soportado.');
    }
    
    // Calcular dimensiones para crop cuadrado
    $sourceWidth = $originalWidth;
    $sourceHeight = $originalHeight;
    $sourceX = 0;
    $sourceY = 0;
    
    if ($originalWidth > $originalHeight) {
        $sourceWidth = $originalHeight;
        $sourceX = ($originalWidth - $originalHeight) / 2;
    } elseif ($originalHeight > $originalWidth) {
        $sourceHeight = $originalWidth;
        $sourceY = ($originalHeight - $originalWidth) / 2;
    }
    
    // Redimensionar y hacer crop
    imagecopyresampled(
        $newImage, $source,
        0, 0,
        $sourceX, $sourceY,
        $width, $height,
        $sourceWidth, $sourceHeight
    );
    
    // Guardar imagen
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $targetPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $targetPath, 9);
            break;
    }
    
    // Liberar memoria
    imagedestroy($newImage);
    imagedestroy($source);
}

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    // Verificar si se subió un archivo
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se ha seleccionado ningún archivo o hubo un error en la subida.');
    }

    $file = $_FILES['avatar'];
    $fileName = $file['name'];
    $fileType = $file['type'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    $fileSize = $file['size'];

    // Validar el tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Solo se permiten archivos JPG y PNG.');
    }

    // Validar el tamaño (2MB máximo)
    $maxSize = 2 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        throw new Exception('El archivo es demasiado grande. El tamaño máximo es 2MB.');
    }

    // Crear directorio si no existe
    $uploadDir = '../uploads/avatars/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generar nombre único para el archivo
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid('avatar_') . '.' . $extension;
    $targetPath = $uploadDir . $newFileName;

    // Crear directorio temporal si no existe
    $tempDir = '../uploads/temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // Mover archivo a temporal primero
    $tempPath = $tempDir . '/' . uniqid('temp_') . '.' . $extension;
    if (!move_uploaded_file($fileTmpName, $tempPath)) {
        throw new Exception('Error al subir el archivo.');
    }
    
    // Procesar imagen
    try {
        processImage($tempPath, $targetPath);
        unlink($tempPath); // Eliminar archivo temporal
    } catch (Exception $e) {
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        throw new Exception('Error al procesar la imagen: ' . $e->getMessage());
    }

    // Actualizar la base de datos
    $userId = $_SESSION['user_id'];
    
    // Obtener avatar anterior
    $oldAvatar = fetchOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
    
    // Actualizar nuevo avatar
    if (!execute("UPDATE users SET avatar = ? WHERE id = ?", [$newFileName, $userId])) {
        throw new Exception('Error al actualizar la base de datos.');
    }

    // Eliminar avatar anterior si existe y no es el default
    if ($oldAvatar && $oldAvatar['avatar'] !== 'default-avatar.png' && file_exists($uploadDir . $oldAvatar['avatar'])) {
        unlink($uploadDir . $oldAvatar['avatar']);
    }

    $_SESSION['success_message'] = 'Avatar actualizado correctamente.';
    header('Location: profile.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: profile.php');
    exit();
}

<?php
// admin/eliminar_pelicula.php
session_start();
require_once __DIR__ . '/../conexion.php';

// Verificamos si viene el ID por GET
if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$id = intval($_GET['id']);

// Buscar la película en la BD
$stmt = $conn->prepare("SELECT poster, video FROM peliculas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$peli = $result->fetch_assoc();
$stmt->close();

// Si existe, eliminamos archivos asociados
if ($peli) {
    if (!empty($peli['poster'])) {
        $posterPath = __DIR__ . '/uploads/posters/' . $peli['poster'];
        if (file_exists($posterPath)) unlink($posterPath);
    }

    if (!empty($peli['video'])) {
        $videoPath = __DIR__ . '/uploads/videos/' . $peli['video'];
        if (file_exists($videoPath)) unlink($videoPath);
    }

    // Luego eliminamos el registro
    $stmt = $conn->prepare("DELETE FROM peliculas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Cerramos conexión y volvemos al panel
$conn->close();
header('Location: admin.php?deleted=1');
exit;
?>

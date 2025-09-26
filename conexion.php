<?php
$host = "localhost";
$user = "root"; // tu usuario de phpMyAdmin
$pass = "";     // tu contraseña de phpMyAdmin (déjalo vacío si no tienes)
$db   = "netflix_clone";

// Crear conexión
$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
?>

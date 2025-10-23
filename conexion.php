<?php
$host = "localhost";
$user = "root"; // tu usuario de phpMyAdmin
$pass = "";     // tu contraseña de phpMyAdmin (déjalo vacío si no tienes)
$db   = "netflix_clone";
$port = 3306;   // Asegúrate de especificar el puerto 3307

// Crear conexión
$conn = new mysqli($host, $user, $pass, $db, $port);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
?>

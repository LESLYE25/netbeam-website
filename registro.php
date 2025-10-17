<?php
include("conexion.php");

// Procesar cuando envíen el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre']; // NUEVO
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Encriptar la contraseña
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Preparar SQL con nombre incluido
    $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $passwordHash);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Registro exitoso. Ya puedes iniciar sesión'); window.location='login.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrarse</title>
    <link rel="stylesheet" href="./css/cuenta.css" />
    <link rel="shortcut icon" type="image/jpg" href="img/image.png" />
</head>
<body>
<div class="contenido">
    <nav>
        <img class="logo" src="./img/image2.png" alt="netbeamLogo" />
    </nav>
    <div class="caja">
        <h2>Registrarse</h2>

        <!-- FORMULARIO -->
        <form action="registro.php" method="POST" class="form">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Crear</button>
        </form>

    </div>
</div>
</body>
</html>

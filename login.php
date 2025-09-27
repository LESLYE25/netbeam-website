<?php
session_start();
include("conexion.php");

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['correo'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        if (password_verify($password, $usuario['password'])) {
            // Guardamos el id del usuario en la sesión
            $_SESSION['usuario_id'] = $usuario['id']; // <- importante
            $_SESSION['usuario'] = $usuario['nombre'];
            header("Location: preferencias.php"); // o home.php si ya tiene preferencias
            exit();
        } else {
            $error = "❌ Contraseña incorrecta.";
        }
    } else {
        $error = "❌ Usuario no encontrado.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="./css/cuenta.css" />
    <link rel="shortcut icon" type="image/jpg" href="img/image2.png" />

  </head>
  <body>
    <div class="contenido">
      <nav>
        <img class="logo" src="./img/logo.png" alt="netbeamLogo" />
      </nav>
      <div class="caja">
        <h2>Iniciar sesión</h2>

        <!-- FORMULARIO -->
        <form action="login.php" method="POST" class="form">
          <input
            type="text"
            name="correo"
            placeholder="Correo electrónico o número de teléfono"
            required
          />
          <input 
            type="password" 
            name="password" 
            placeholder="Contraseña" 
            required 
          />
          <button type="submit">Iniciar sesión</button>
        </form>
        <!-- /FORMULARIO -->

        <div class="checkbox">
          <div class="recordar">
            <input type="checkbox" id="checkbox1"/>
            <label for="checkbox1">Recuérdame</label>
          </div>
        </div>
        <div class="subscripcion">
          <p>¿Todavía sin Netbeam? 
            <a href="registro.php"><span>Registrate ya.</span></a>
          </p>
        </div>
      </div>
    </div>
  </body>
  <footer>
    <p>¿Preguntas? Llama al 900-000-000</p>
    <div class="links">
      <ul>
        <li><a href="#">Preguntas frecuentes</a></li>
        <li><a href="#">Centro de ayuda</a></li>
        <li><a href="#">Términos de uso</a></li>
        <li><a href="#">Privacidad</a></li>
        <li><a href="#">Preferencias de cookies</a></li>
        <li><a href="#">Información corporativa</a></li>
      </ul>
    </div>
  </footer>
</html>


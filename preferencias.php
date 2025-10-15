<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario_id'])) { // asegúrate de guardar id en login.php
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Guardar preferencias en la BD
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $generos = $_POST['generos'] ?? [];

    // Primero borramos las anteriores para no duplicar
    $conn->query("DELETE FROM preferencias WHERE usuario_id = $usuario_id");

    $sql = "INSERT INTO preferencias (usuario_id, genero) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    foreach ($generos as $genero) {
        $stmt->bind_param("is", $usuario_id, $genero);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();

    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Elige tus preferencias 🎬</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link rel="shortcut icon" type="image/jpg" href="img/image.png" />
  <style>

    body {
      background: #141414;
      color: white;
      font-family: Arial, sans-serif;
      text-align: center;
      padding: 40px;
    }
    h2 {
      font-size: 28px;
      margin-bottom: 20px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: auto;
    }
    .card {
      position: relative;
      cursor: pointer;
      border-radius: 15px;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card img {
      width: 100%;
      height: 250px;
      object-fit: cover;
      filter: brightness(70%);
    }
    .card span {
      position: absolute;
      bottom: 15px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 18px;
      font-weight: bold;
    }
    .card:hover {
      transform: scale(1.05);
      box-shadow: 0 0 20px rgba(255,255,255,0.3);
    }
    input[type="checkbox"] {
      display: none;
    }
    input[type="checkbox"]:checked + label .card {
      border: 3px solid red;
      box-shadow: 0 0 25px red;
    }
    button {
      margin-top: 30px;
      padding: 12px 30px;
      background: red;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 18px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #b20710;
    }
  </style>
</head>
<body>
  <h2 class="animate__animated animate__fadeInDown">¿Qué géneros de películas quieres ver? 🍿</h2>
  <form method="POST" action="">
    <div class="grid animate__animated animate__fadeInUp">
      <input type="checkbox" id="accion" name="generos[]" value="Acción">
      <label for="accion">
        <div class="card">
          <img src="https://image.tmdb.org/t/p/w500/gh4cZbhZxyTbgxQPxD0dOudNPTn.jpg" alt="Acción">
          <span>Acción</span>
        </div>
      </label>

      <input type="checkbox" id="comedia" name="generos[]" value="Comedia">
      <label for="comedia">
        <div class="card">
          <img src="https://image.tmdb.org/t/p/w500/4V2nTPfeB59TcqJcUfQ9ziTi7VN.jpg" alt="Comedia">
          <span>Comedia</span>
        </div>
      </label>

      <input type="checkbox" id="terror" name="generos[]" value="Terror">
      <label for="terror">
        <div class="card">
          <img src="https://image.tmdb.org/t/p/w500/9Gtg2DzBhmYamXBS1hKAhiwbBKS.jpg" alt="Terror">
          <span>Terror</span>
        </div>
      </label>

      <input type="checkbox" id="romance" name="generos[]" value="Romance">
      <label for="romance">
        <div class="card">
          <img src="https://image.tmdb.org/t/p/w500/2CAL2433ZeIihfX1Hb2139CX0pW.jpg" alt="Romance">
          <span>Romance</span>
        </div>
      </label>

      <input type="checkbox" id="scifi" name="generos[]" value="Ciencia ficción">
      <label for="scifi">
        <div class="card">
          <img src="https://image.tmdb.org/t/p/w500/fuVuDYrs8sxvEolnYr0wCSvtyTi.jpg" alt="Sci-Fi">
          <span>Ciencia Ficción</span>
        </div>
      </label>

      <input type="checkbox" id="drama" name="generos[]" value="Drama">
      <label for="drama">
        <div class="card">
          <img src="https://image.tmdb.org/t/p/w500/q719jXXEzOoYaps6babgKnONONX.jpg" alt="Drama">
          <span>Drama</span>
        </div>
      </label>
    </div>
    <button type="submit">Continuar ➡</button>
  </form>
</body>
</html>

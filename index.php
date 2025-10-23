<?php
session_start();
include("conexion.php");

// Verificar si hay usuario en sesión
$usuario_autenticado = isset($_SESSION['usuario_id']);
$usuario_id = $usuario_autenticado ? $_SESSION['usuario_id'] : null;

// Obtener información del usuario actual si está autenticado
if ($usuario_autenticado) {
    $sql_usuario = "SELECT id, nombre, rol FROM usuarios WHERE id = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    $usuario = $result_usuario->fetch_assoc();
    $stmt_usuario->close();
    
    // Si el usuario está autenticado, redirigir según su rol
    if ($usuario['rol'] === 'admin') {
        header("Location: admin/admin.php");
    } else {
        header("Location: home.php");
    }
    exit();
} else {
    $usuario = ['nombre' => 'Invitado'];
}

// Obtener películas destacadas para el carrusel principal
$sql_carrusel = "SELECT * FROM peliculas ORDER BY RAND() LIMIT 8";
$result_carrusel = $conn->query($sql_carrusel);
$peliculas_carrusel = $result_carrusel->fetch_all(MYSQLI_ASSOC);

// Obtener películas para mostrar en el catálogo
$sql_peliculas = "SELECT * FROM peliculas ORDER BY RAND() LIMIT 50";
$result_peliculas = $conn->query($sql_peliculas);
$peliculas = $result_peliculas->fetch_all(MYSQLI_ASSOC);

// Películas por género (para las secciones)
$generos = ["Acción", "Comedia", "Terror", "Romance", "Ciencia ficción", "Drama"];
$peliculas_por_genero = [];
foreach($generos as $gen) {
    $stmt = $conn->prepare("SELECT * FROM peliculas WHERE genero = ? ORDER BY RAND() LIMIT 10");
    $stmt->bind_param("s", $gen);
    $stmt->execute();
    $result = $stmt->get_result();
    $peliculas_por_genero[$gen] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// IMÁGENES ESPECÍFICAS PARA EL BANNER DE BIENVENIDA
$banner_images = [
    [
        'url' => 'https://wallpapers.com/images/hd/1920x1080-hd-movie-1920-x-1080-4wl5v81m8azwaka6.jpg',
        'title' => 'Bienvenido a Flixter',
        'description' => 'Disfruta de las mejores películas y series. Inicia sesión para una experiencia personalizada.'
    ],
    [
        'url' => 'https://wallpapers.com/images/high/action-movie-4000-x-2000-wallpaper-6yb1bqeuq59u47bw.webp',
        'title' => 'Miles de títulos',
        'description' => 'Descubre contenido exclusivo disponible solo en Flixter.'
    ],
    [
        'url' => 'https://wallpapers.com/images/featured/pelicula-de-accion-pb93e7r343erqgtt.jpg',
        'title' => 'En cualquier dispositivo',
        'description' => 'Ve Flixter en tu TV, computadora, tablet o smartphone.'
    ],
    [
        'url' => 'https://wallpapers.com/images/hd/action-movie-2880-x-1800-wallpaper-hpocopuuycj62j7f.jpg',
        'title' => 'Sin compromisos',
        'description' => 'Cancela online cuando quieras sin cargos adicionales.'
    ],
    [
        'url' => 'https://p4.wallpaperbetter.com/wallpaper/673/107/786/up-movie-pixar-animation-studios-movies-sky-wallpaper-preview.jpg',
        'title' => 'Contenido original',
        'description' => 'Disfruta de producciones exclusivas de Flixter.'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Flixter 🎬</title>
<link rel="shortcut icon" type="image/jpg" href="img/image.png" />
<style>
:root {
    --Flixter-red: #e50914;
    --Flixter-dark: #141414;
    --Flixter-gray: #2f2f2f;
    --Flixter-light-gray: #b3b3b3;
    --Flixter-white: #ffffff;
}

* {
    box-sizing: border-box;
}

body {
    background: var(--Flixter-dark);
    color: var(--Flixter-white);
    font-family: 'Helvetica Neue', Arial, sans-serif;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* HEADER */
header {
    position: fixed;
    top: 0;
    width: 100%;
    height: 68px;
    background: linear-gradient(to bottom, rgba(0,0,0,0.7) 10%, transparent);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 4%;
    z-index: 1000;
    transition: background-color 0.4s;
}

header.scrolled {
    background-color: var(--Flixter-dark);
}

header img.logo {
    height: 60px;
}

/* Botones de autenticación en el header */
.auth-buttons {
    display: flex;
    gap: 8px;
    margin-left: 15px;
}

.nav-button {
    border: 0;
    outline: 0;
    background: rgb(219, 0, 1); 
    color: white; 
    font-size: 12px; 
    font-weight: bold;
    border-radius: 4px; 
    padding: 7px 20px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.nav-button:hover {
    background: rgb(193, 0, 1);
}

.nav-button.secondary {
    background: transparent;
    border: 1px solid var(--Flixter-light-gray);
}

.nav-button.secondary:hover {
    background: rgba(255,255,255,0.1);
}

/* CARRUSEL DE PELÍCULAS - TAMAÑOS AUMENTADOS */
.carrusel-section {
    margin-top: 60px;
    padding: 20px 4%;
    position: relative;
}

.carrusel-title {
    font-size: 1.8rem;
    margin-bottom: 20px;
    font-weight: 600;
    color: var(--Flixter-white);
}

.carrusel-container {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: 8px;
}

.carrusel {
    display: flex;
    transition: transform 0.5s ease-in-out;
    gap: 15px;
}

.carrusel-item {
    min-width: 350px;
    height: 300px; 
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.carrusel-item:hover {
    transform: scale(1.05);
}

.carrusel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.carrusel-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    padding: 20px;
    color: white;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.carrusel-item:hover .carrusel-overlay {
    transform: translateY(0);
}

.carrusel-item-title {
    font-size: 1.3rem;
    font-weight: bold;
    margin-bottom: 8px;
}

.carrusel-item-genre {
    font-size: 1rem;
    color: var(--Flixter-light-gray);
}

.carrusel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.7);
    border: none;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
    z-index: 10;
}

.carrusel-btn:hover {
    background: rgba(0,0,0,0.9);
}

.carrusel-prev {
    left: 10px;
}

.carrusel-next {
    right: 10px;
}

.carrusel-indicators {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.carrusel-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--Flixter-light-gray);
    cursor: pointer;
    transition: background 0.3s;
}

.carrusel-indicator.active {
    background: var(--Flixter-red);
}

/* BANNER CARRUSEL - Estilo Netflix */
.banner-carrusel {
    position: relative;
    height: 90vh;
    min-height: 500px;
    margin-top: 68px;
    overflow: hidden;
}

.banner-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.banner-slide.active {
    opacity: 1;
}

.banner-slide::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.2) 100%);
}

.banner-content {
    position: relative;
    z-index: 2;
    max-width: 600px;
    padding: 0 4%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.banner h1 {
    font-size: 3.5rem;
    margin: 0 0 20px 0;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.banner p {
    font-size: 1.2rem;
    margin-bottom: 20px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    max-width: 500px;
}

.banner-buttons {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    padding: 10px 25px;
    border-radius: 4px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    transition: all 0.3s;
}

.btn-primary {
    background-color: var(--Flixter-white);
    color: var(--Flixter-dark);
}

.btn-primary:hover {
    background-color: rgba(255,255,255,0.75);
}

.btn-secondary {
    background-color: rgba(109, 109, 110, 0.7);
    color: var(--Flixter-white);
}

.btn-secondary:hover {
    background-color: rgba(109, 109, 110, 0.4);
}

.banner-controls {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
}

.banner-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: all 0.3s;
}

.banner-indicator.active {
    background: var(--Flixter-red);
    transform: scale(1.2);
}

.banner-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    border: none;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
    z-index: 10;
}

.banner-nav:hover {
    background: rgba(0,0,0,0.8);
}

.banner-prev {
    left: 20px;
}

.banner-next {
    right: 20px;
}

/* SECCIONES */
.section {
    margin-top: 40px;
    padding: 0 4%;
    position: relative;
}

.section-title {
    font-size: 1.5rem;
    margin-bottom: 15px;
    font-weight: 600;
}

/* FILAS DE PELÍCULAS - TAMAÑOS AUMENTADOS */
.row {
    display: flex;
    overflow-x: auto;
    gap: 12px;
    padding: 10px 0;
    scrollbar-width: none;
}

.row::-webkit-scrollbar {
    display: none;
}

.card {
    min-width: 180px;
    width: 180px;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.4s ease, z-index 0.4s;
    position: relative;
    flex-shrink: 0;
}

.card img {
    width: 100%;
    height: 270px;
    object-fit: cover;
    display: block;
}

.card:hover {
    transform: scale(1.1);
    z-index: 10;
}

.card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 50%);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 12px;
}

.card:hover .card-overlay {
    opacity: 1;
}

.card-title {
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 15px;
}

.card-details {
    display: flex;
    gap: 10px;
    font-size: 13px;
    color: var(--Flixter-light-gray);
}

/* MODAL DE PELÍCULA */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    z-index: 2000;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.modal-content {
    background-color: var(--Flixter-dark);
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.modal-header {
    height: 400px;
    min-height: 400px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

.modal-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    background: linear-gradient(to top, var(--Flixter-dark), transparent);
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--Flixter-dark);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    font-size: 24px;
    border: none;
    color: white;
    transition: background 0.3s;
}

.modal-close:hover {
    background: #333;
}

.modal-body {
    padding: 30px;
    margin-top: -100px;
    position: relative;
    z-index: 5;
}

.modal-title {
    font-size: 2.2rem;
    margin-bottom: 15px;
    font-weight: bold;
}

.modal-details {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    color: var(--Flixter-light-gray);
    align-items: center;
    flex-wrap: wrap;
}

.rating-badge {
    background: var(--Flixter-red);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.modal-description {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
    color: #ccc;
}

.modal-buttons {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.btn-comenzar {
    background: rgb(219, 0, 1);
    color: white;
    padding: 12px 30px;
    border-radius: 4px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-comenzar:hover {
    background: rgb(193, 0, 1);
}

/* MODAL DE LOGIN REQUERIDO */
.login-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}

.login-modal-content {
    background-color: var(--Flixter-dark);
    border-radius: 10px;
    width: 90%;
    max-width: 450px;
    padding: 40px;
    text-align: center;
    position: relative;
}

.login-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: transparent;
    border: none;
    color: var(--Flixter-light-gray);
    font-size: 24px;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.3s;
}

.login-modal-close:hover {
    background: #333;
}

.login-modal-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: block;
}

.login-modal-title {
    font-size: 1.8rem;
    margin-bottom: 15px;
    color: var(--Flixter-white);
}

.login-modal-text {
    font-size: 1rem;
    margin-bottom: 30px;
    color: var(--Flixter-light-gray);
    line-height: 1.5;
}

.login-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.login-modal-btn {
    padding: 12px 25px;
    border-radius: 4px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
    min-width: 140px;
}

.login-modal-btn.primary {
    background: rgb(219, 0, 1);
    color: white;
}

.login-modal-btn.primary:hover {
    background: rgb(193, 0, 1);
}

.login-modal-btn.secondary {
    background: transparent;
    border: 2px solid var(--Flixter-light-gray);
    color: var(--Flixter-white);
}

.login-modal-btn.secondary:hover {
    background: rgba(255,255,255,0.1);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .banner h1 {
        font-size: 2.5rem;
    }
    
    .banner p {
        font-size: 1rem;
    }
    
    .nav-links {
        display: none;
    }
    
    .card {
        min-width: 140px;
        width: 140px;
    }
    
    .card img {
        height: 210px;
    }
    
    .modal-content {
        width: 95%;
        max-width: 500px;
    }
    
    .modal-header {
        height: 300px;
        min-height: 300px;
    }
    
    .modal-title {
        font-size: 1.8rem;
    }
    
    .auth-buttons {
        margin-left: 10px;
    }
    
    .nav-button {
        padding: 7px 15px;
        font-size: 11px;
    }
    
    .login-modal-content {
        padding: 30px 20px;
    }
    
    .login-modal-buttons {
        flex-direction: column;
    }
    
    .login-modal-btn {
        width: 100%;
    }
    
    .carrusel-item {
        min-width: 280px;
        height: 400px;
    }
    
    .carrusel-btn {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .banner-nav {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .banner-prev {
        left: 10px;
    }
    
    .banner-next {
        right: 10px;
    }
}

@media (max-width: 480px) {
    .carrusel-item {
        min-width: 220px;
        height: 315px;
    }
    
    .banner h1 {
        font-size: 2rem;
    }
    
    .banner-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .card {
        min-width: 120px;
        width: 120px;
    }
    
    .card img {
        height: 180px;
    }
}
</style>
</head>
<body>

<header>
    <img class="logo" src="img/image2.png" alt="Logo">
    <div class="usuario-menu">
        <div class="auth-buttons">
            <a href="login.php">
                <button class="nav-button secondary">Iniciar sesión</button>
            </a>
            <a href="registro.php">
                <button class="nav-button">Registrarse</button>
            </a>
        </div>
    </div>
</header>

<!-- BANNER CARRUSEL - Con imágenes específicas -->
<div class="banner-carrusel" id="bannerCarrusel">
    <?php foreach($banner_images as $index => $banner): ?>
    <div class="banner-slide <?= $index === 0 ? 'active' : '' ?>" 
         style="background-image: url('<?= $banner['url'] ?>')">
        <div class="banner-content">
            <h1><?= $banner['title'] ?></h1>
            <p><?= $banner['description'] ?></p>
            <div class="banner-buttons">
                <button class="btn btn-primary" onclick="openRegisterModal()">▶ Comenzar</button>
                <button class="btn btn-secondary" onclick="openInfoModal()">ⓘ Más información</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <button class="banner-nav banner-prev" onclick="moveBanner(-1)">❮</button>
    <button class="banner-nav banner-next" onclick="moveBanner(1)">❯</button>
    
    <div class="banner-controls" id="bannerIndicators">
        <?php foreach($banner_images as $index => $banner): ?>
            <div class="banner-indicator <?= $index === 0 ? 'active' : '' ?>" 
                 onclick="goToBannerSlide(<?= $index ?>)"></div>
        <?php endforeach; ?>
    </div>
</div>

<!-- CARRUSEL DE PELÍCULAS -->
<div class="carrusel-section">
    <h2 class="carrusel-title">Películas Destacadas</h2>
    <div class="carrusel-container">
        <button class="carrusel-btn carrusel-prev" onclick="moveCarrusel(-1)">❮</button>
        <div class="carrusel" id="carrusel">
            <?php foreach($peliculas_carrusel as $p): ?>
                <div class="carrusel-item" onclick="openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>, event)">
                    <?php 
                    // Manejar tanto URLs como archivos locales para el poster
                    $poster_src = $p['poster'];
                    if (!filter_var($p['poster'], FILTER_VALIDATE_URL)) {
                        $poster_src = "admin/uploads/posters/" . $p['poster'];
                    }
                    ?>
                    <img src="<?= $poster_src ?>" alt="<?= $p['titulo'] ?>" onerror="this.src='img/poster_placeholder.svg'">
                    <div class="carrusel-overlay">
                        <div class="carrusel-item-title"><?= $p['titulo'] ?></div>
                        <div class="carrusel-item-genre"><?= $p['genero'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carrusel-btn carrusel-next" onclick="moveCarrusel(1)">❯</button>
        
        <div class="carrusel-indicators" id="carruselIndicators">
            <!-- Los indicadores se generan dinámicamente con JavaScript -->
        </div>
    </div>
</div>

<!-- PELÍCULAS POR GÉNERO -->
<?php foreach($peliculas_por_genero as $gen => $peliculas): ?>
<?php if(count($peliculas) > 0): ?>
<div class="section">
    <h2 class="section-title"><?= $gen ?></h2>
    <div class="row">
        <?php foreach($peliculas as $p): ?>
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>, event)">
                <?php 
                // Manejar tanto URLs como archivos locales para el poster
                $poster_src = $p['poster'];
                if (!filter_var($p['poster'], FILTER_VALIDATE_URL)) {
                    $poster_src = "admin/uploads/posters/" . $p['poster'];
                }
                ?>
                <img src="<?= $poster_src ?>" alt="<?= $p['titulo'] ?>" onerror="this.src='img/poster_placeholder.svg'">
                <div class="card-overlay">
                    <div class="card-title"><?= $p['titulo'] ?></div>
                    <div class="card-details">
                        <span><?= $p['genero'] ?></span>
                        <span><?= $p['anio'] ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- MODAL DE PELÍCULA -->
<div id="movieModal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="modalHeader"></div>
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-body">
            <h2 class="modal-title" id="modalTitle"></h2>
            <div class="modal-details" id="modalDetails"></div>
            <p class="modal-description" id="modalDescription"></p>
            
            <div class="modal-buttons">
                <button class="btn-comenzar" onclick="openRegisterModal()">
                    ▶ Comenzar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE INFORMACIÓN GENERAL -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeInfoModal()">×</button>
        <div class="modal-body">
            <h2 class="modal-title">Sobre Flixter</h2>
            <div class="modal-details">
                <span class="rating-badge">Plataforma</span>
                <span>⭐ 4.8/5</span>
            </div>
            <p class="modal-description">
                Flixter es tu destino para disfrutar de las mejores películas y series. Con una interfaz intuitiva 
                y contenido actualizado regularmente, ofrecemos una experiencia de entretenimiento excepcional.
                <br><br>
                Características principales:
                <br>• Miles de títulos disponibles
                <br>• Contenido original exclusivo
                <br>• Compatible con todos tus dispositivos
                <br>• Sin compromisos de permanencia
                <br>• Precios competitivos
            </p>
            
            <div class="modal-buttons">
                <button class="btn-comenzar" onclick="openRegisterModal()">
                    ▶ Comenzar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE LOGIN REQUERIDO -->
<div id="loginModal" class="login-modal">
    <div class="login-modal-content">
        <button class="login-modal-close" onclick="closeLoginModal()">×</button>
        <div class="login-modal-icon">🔒</div>
        <h2 class="login-modal-title">Inicia sesión para continuar</h2>
        <p class="login-modal-text">Para acceder a esta función necesitas tener una cuenta en Flixter. Únete a nuestra comunidad para disfrutar de todas las características.</p>
        <div class="login-modal-buttons">
            <button class="login-modal-btn primary" onclick="redirectToLogin()">Iniciar Sesión</button>
            <button class="login-modal-btn secondary" onclick="redirectToRegister()">Crear Cuenta</button>
        </div>
    </div>
</div>

<script>
// Variables para el carrusel de películas
let currentCarruselIndex = 0;
let carruselItems = [];
const itemsPerView = 4;

// Variables para el banner carrusel
let currentBannerIndex = 0;
let bannerSlides = [];
let bannerInterval;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeCarrusel();
    initializeBanner();
});

// ========== BANNER CARRUSEL ==========
function initializeBanner() {
    bannerSlides = document.querySelectorAll('.banner-slide');
    startBannerAutoPlay();
}

function moveBanner(direction) {
    const totalSlides = bannerSlides.length;
    currentBannerIndex = (currentBannerIndex + direction + totalSlides) % totalSlides;
    updateBanner();
    resetBannerAutoPlay();
}

function goToBannerSlide(index) {
    currentBannerIndex = index;
    updateBanner();
    resetBannerAutoPlay();
}

function updateBanner() {
    bannerSlides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentBannerIndex);
    });
    
    // Actualizar indicadores
    const indicators = document.querySelectorAll('.banner-indicator');
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentBannerIndex);
    });
}

function startBannerAutoPlay() {
    bannerInterval = setInterval(() => {
        moveBanner(1);
    }, 6000); // Cambia cada 6 segundos
}

function resetBannerAutoPlay() {
    clearInterval(bannerInterval);
    startBannerAutoPlay();
}

// ========== CARRUSEL DE PELÍCULAS ==========
function initializeCarrusel() {
    const carrusel = document.getElementById('carrusel');
    carruselItems = Array.from(carrusel.children);
    
    // Crear indicadores
    const indicatorsContainer = document.getElementById('carruselIndicators');
    const totalIndicators = Math.ceil(carruselItems.length / itemsPerView);
    
    indicatorsContainer.innerHTML = '';
    for (let i = 0; i < totalIndicators; i++) {
        const indicator = document.createElement('div');
        indicator.className = 'carrusel-indicator' + (i === 0 ? ' active' : '');
        indicator.onclick = () => goToCarruselSlide(i);
        indicatorsContainer.appendChild(indicator);
    }
    
    updateCarrusel();
}

function moveCarrusel(direction) {
    const totalSlides = Math.ceil(carruselItems.length / itemsPerView);
    currentCarruselIndex = (currentCarruselIndex + direction + totalSlides) % totalSlides;
    updateCarrusel();
}

function goToCarruselSlide(index) {
    const totalSlides = Math.ceil(carruselItems.length / itemsPerView);
    currentCarruselIndex = index;
    updateCarrusel();
}

function updateCarrusel() {
    const carrusel = document.getElementById('carrusel');
    const itemWidth = carruselItems[0].offsetWidth + 15; // + gap
    const translateX = -currentCarruselIndex * itemWidth * itemsPerView;
    carrusel.style.transform = `translateX(${translateX}px)`;
    
    // Actualizar indicadores
    const indicators = document.querySelectorAll('.carrusel-indicator');
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentCarruselIndex);
    });
}

// ========== FUNCIONES GENERALES ==========
// Efecto de scroll en el header
window.addEventListener('scroll', function() {
    const header = document.querySelector('header');
    if (window.scrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Variable global para la película actual en el modal
let peliculaActual = null;

// Función para abrir modal de información general
function openInfoModal() {
    const modal = document.getElementById('infoModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Función para cerrar modal de información
function closeInfoModal() {
    const modal = document.getElementById('infoModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Función para abrir modal de registro (desde el botón Comenzar)
function openRegisterModal() {
    closeModal(); // Cierra el modal de película si está abierto
    closeInfoModal(); // Cierra el modal de información si está abierto
    const modal = document.getElementById('loginModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Función para cerrar modal de login
function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Función para redirigir al login
function redirectToLogin() {
    window.location.href = 'login.php';
}

// Función para redirigir al registro
function redirectToRegister() {
    window.location.href = 'registro.php';
}

// Función para abrir el modal de película
function openModal(movie, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    peliculaActual = movie;
    
    const modal = document.getElementById('movieModal');
    const modalHeader = document.getElementById('modalHeader');
    const modalTitle = document.getElementById('modalTitle');
    const modalDetails = document.getElementById('modalDetails');
    const modalDescription = document.getElementById('modalDescription');
    
    // Establecer el fondo del header del modal
    let posterUrl = movie.poster;
    if (!movie.poster.startsWith('http')) {
        posterUrl = 'admin/uploads/posters/' + movie.poster;
    }
    modalHeader.style.backgroundImage = `url('${posterUrl}')`;
    
    // Establecer el título
    modalTitle.textContent = movie.titulo;
    
    // Establecer detalles
    modalDetails.innerHTML = `
        <span class="rating-badge">${movie.genero}</span>
        <span>${movie.anio}</span>
        <span>${movie.duracion || ''}</span>
        <span>⭐ ${(Math.random() * 2 + 3).toFixed(1)}/5</span>
    `;
    
    // Establecer descripción
    modalDescription.textContent = movie.descripcion || 
        `"${movie.titulo}" es una emocionante película del género ${movie.genero} estrenada en ${movie.anio}. Con una trama envolvente y actuaciones memorables, esta producción promete horas de entretenimiento.`;
    
    // Mostrar el modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Función para cerrar el modal de película
function closeModal() {
    const modal = document.getElementById('movieModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    peliculaActual = null;
}

// Configurar el botón de cerrar modal
document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (this.closest('#movieModal')) {
            closeModal();
        } else if (this.closest('#infoModal')) {
            closeInfoModal();
        }
    });
});

// Cerrar modales al hacer clic fuera del contenido
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(event) {
        if (event.target === this) {
            if (this.id === 'movieModal') {
                closeModal();
            } else if (this.id === 'infoModal') {
                closeInfoModal();
            } else if (this.id === 'loginModal') {
                closeLoginModal();
            }
        }
    });
});

// Prevenir que el clic dentro del modal lo cierre
document.querySelectorAll('.modal-content').forEach(content => {
    content.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});

// Cerrar modal de login al hacer clic fuera
document.getElementById('loginModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeLoginModal();
    }
});

// Prevenir que el clic dentro del modal de login lo cierre
document.querySelector('.login-modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Agregar event listener para la tecla Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
            closeInfoModal();
            closeLoginModal();
        }
    });
});

// Auto-avance del carrusel de películas
setInterval(() => {
    moveCarrusel(1);
}, 5000);
</script>

</body>
</html>
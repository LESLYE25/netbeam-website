<?php
session_start();
include("conexion.php");

// Si no hay usuario en sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Si se solicita cambiar de usuario
if (isset($_POST['cambiar_usuario'])) {
    $nuevo_usuario_id = $_POST['usuario_id'];
    
    // Verificar que el usuario existe
    $sql_verificar = "SELECT id FROM usuarios WHERE id = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("i", $nuevo_usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows > 0) {
        $_SESSION['usuario_id'] = $nuevo_usuario_id;
        // Recargar la página para mostrar las nuevas preferencias
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    $stmt_verificar->close();
}

// Agregar/eliminar de Mi Lista
if (isset($_POST['accion_lista'])) {
    $pelicula_id = $_POST['pelicula_id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    if ($_POST['accion_lista'] === 'agregar') {
        // Verificar si ya está en la lista
        $sql_verificar = "SELECT id FROM mi_lista WHERE usuario_id = ? AND pelicula_id = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("ii", $usuario_id, $pelicula_id);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows === 0) {
            $sql_agregar = "INSERT INTO mi_lista (usuario_id, pelicula_id) VALUES (?, ?)";
            $stmt_agregar = $conn->prepare($sql_agregar);
            $stmt_agregar->bind_param("ii", $usuario_id, $pelicula_id);
            $stmt_agregar->execute();
            $stmt_agregar->close();
        }
        $stmt_verificar->close();
    } else {
        $sql_eliminar = "DELETE FROM mi_lista WHERE usuario_id = ? AND pelicula_id = ?";
        $stmt_eliminar = $conn->prepare($sql_eliminar);
        $stmt_eliminar->bind_param("ii", $usuario_id, $pelicula_id);
        $stmt_eliminar->execute();
        $stmt_eliminar->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener información del usuario actual
$sql_usuario = "SELECT id, nombre FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();
$stmt_usuario->close();

// Obtener preferencias del usuario actual
$sql_preferencias = "SELECT genero FROM preferencias WHERE usuario_id = ?";
$stmt_preferencias = $conn->prepare($sql_preferencias);
$stmt_preferencias->bind_param("i", $usuario_id);
$stmt_preferencias->execute();
$result_preferencias = $stmt_preferencias->get_result();
$preferencias = [];
while ($row = $result_preferencias->fetch_assoc()) {
    $preferencias[] = $row['genero'];
}
$stmt_preferencias->close();

// Obtener Mi Lista del usuario actual
$sql_mi_lista = "SELECT p.* FROM peliculas p 
                 JOIN mi_lista ml ON p.id = ml.pelicula_id 
                 WHERE ml.usuario_id = ?";
$stmt_mi_lista = $conn->prepare($sql_mi_lista);
$stmt_mi_lista->bind_param("i", $usuario_id);
$stmt_mi_lista->execute();
$result_mi_lista = $stmt_mi_lista->get_result();
$mi_lista = $result_mi_lista->fetch_all(MYSQLI_ASSOC);
$stmt_mi_lista->close();

// Verificar qué películas están en Mi Lista
$peliculas_en_lista = [];
foreach($mi_lista as $pelicula) {
    $peliculas_en_lista[$pelicula['id']] = true;
}

// Recomendaciones basadas en las preferencias
$peliculas_filtradas = [];
if (count($preferencias) > 0) {
    $placeholders = str_repeat('?,', count($preferencias) - 1) . '?';
    $sql = "SELECT * FROM peliculas WHERE genero IN ($placeholders) ORDER BY RAND() LIMIT 10";
    $stmt = $conn->prepare($sql);
    
    // Vincular parámetros dinámicamente
    $types = str_repeat('s', count($preferencias));
    $stmt->bind_param($types, ...$preferencias);
    $stmt->execute();
    $result = $stmt->get_result();
    $peliculas_filtradas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Películas por género (para todas las secciones)
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

// Obtener lista de usuarios para cambiar
$sql_usuarios = "SELECT id, nombre FROM usuarios WHERE id != ?";
$stmt_usuarios = $conn->prepare($sql_usuarios);
$stmt_usuarios->bind_param("i", $usuario_id);
$stmt_usuarios->execute();
$result_usuarios = $stmt_usuarios->get_result();
$otros_usuarios = $result_usuarios->fetch_all(MYSQLI_ASSOC);
$stmt_usuarios->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tu Netbeam 🎬</title>
<style>
:root {
    --netflix-red: #e50914;
    --netflix-dark: #141414;
    --netflix-gray: #2f2f2f;
    --netflix-light-gray: #b3b3b3;
    --netflix-white: #ffffff;
}

* {
    box-sizing: border-box;
}

body {
    background: var(--netflix-dark);
    color: var(--netflix-white);
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
    background-color: var(--netflix-dark);
}

header img.logo {
    height: 30px;
}

.nav-links {
    display: flex;
    gap: 20px;
}

.nav-links a {
    color: var(--netflix-white);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: var(--netflix-light-gray);
}

.nav-links a.active {
    color: var(--netflix-white);
    font-weight: bold;
}

.usuario-menu {
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.usuario {
    color: var(--netflix-white);
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.usuario-avatar {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    background-color: var(--netflix-red);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Menú desplegable del usuario */
.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: rgba(0, 0, 0, 0.9);
    border: 1px solid #333;
    border-radius: 4px;
    width: 200px;
    padding: 10px 0;
    margin-top: 10px;
    display: none;
    z-index: 1001;
}

.user-dropdown.active {
    display: block;
}

.user-dropdown::before {
    content: '';
    position: absolute;
    top: -10px;
    right: 15px;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid rgba(0, 0, 0, 0.9);
}

.user-dropdown-item {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: background-color 0.2s;
}

.user-dropdown-item:hover {
    background-color: #333;
}

.user-dropdown-item i {
    width: 20px;
    text-align: center;
}

.user-dropdown-divider {
    height: 1px;
    background-color: #333;
    margin: 5px 0;
}

/* BANNER DESTACADO */
.banner {
    height: 70vh;
    min-height: 500px;
    background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                url('https://image.tmdb.org/t/p/w1280/gh4cZbhZxyTbgxQPxD0dOudNPTn.jpg') center/cover no-repeat;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 4%;
    position: relative;
    margin-top: 68px;
}

.banner-content {
    max-width: 600px;
    z-index: 2;
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
}

.banner-buttons {
    display: flex;
    gap: 15px;
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
    background-color: var(--netflix-white);
    color: var(--netflix-dark);
}

.btn-primary:hover {
    background-color: rgba(255,255,255,0.75);
}

.btn-secondary {
    background-color: rgba(109, 109, 110, 0.7);
    color: var(--netflix-white);
}

.btn-secondary:hover {
    background-color: rgba(109, 109, 110, 0.4);
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

/* FILAS DE PELÍCULAS - IMÁGENES ALTAS */
.row {
    display: flex;
    overflow-x: auto;
    gap: 8px;
    padding: 10px 0;
    scrollbar-width: none; /* Firefox */
}

.row::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Edge */
}

.card {
    min-width: 150px; /* Más angosto para imágenes altas */
    width: 150px;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.4s ease, z-index 0.4s;
    position: relative;
    flex-shrink: 0;
}

.card img {
    width: 100%;
    height: 225px; /* Imágenes más altas */
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
    padding: 10px;
}

.card:hover .card-overlay {
    opacity: 1;
}

.card-title {
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 14px;
}

.card-details {
    display: flex;
    gap: 10px;
    font-size: 12px;
    color: var(--netflix-light-gray);
}

.lista-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s, background 0.3s;
    z-index: 2;
}

.card:hover .lista-btn {
    opacity: 1;
}

.lista-btn.en-lista {
    background: var(--netflix-red);
    opacity: 1;
}

.lista-btn:hover {
    background: var(--netflix-red);
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
}

.modal-content {
    background-color: var(--netflix-dark);
    border-radius: 6px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
}

.modal-header {
    height: 40vh;
    min-height: 300px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.modal-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 100px;
    background: linear-gradient(to top, var(--netflix-dark), transparent);
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--netflix-dark);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    font-size: 20px;
    border: none;
    color: white;
}

.modal-body {
    padding: 30px;
    margin-top: -100px;
    position: relative;
    z-index: 5;
}

.modal-title {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.modal-details {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    color: var(--netflix-light-gray);
}

.modal-description {
    font-size: 1.1rem;
    line-height: 1.5;
    margin-bottom: 20px;
}

.modal-buttons {
    display: flex;
    gap: 15px;
}

.modal-lista-btn {
    background: transparent;
    border: 2px solid var(--netflix-light-gray);
    color: var(--netflix-white);
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 16px;
}

.modal-lista-btn.en-lista {
    background: var(--netflix-red);
    border-color: var(--netflix-red);
}

.modal-lista-btn:hover {
    background: var(--netflix-red);
    border-color: var(--netflix-red);
}

/* Modal de cambio de usuario */
.user-modal {
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

.user-modal-content {
    background-color: var(--netflix-dark);
    border-radius: 6px;
    width: 90%;
    max-width: 500px;
    padding: 30px;
    text-align: center;
}

.user-modal-title {
    font-size: 2rem;
    margin-bottom: 30px;
}

.user-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 30px;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    border: none;
    background: transparent;
    color: white;
    width: 100%;
    text-align: left;
}

.user-item:hover {
    background-color: #333;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    background-color: var(--netflix-red);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.5rem;
}

.user-name {
    font-size: 1.2rem;
    flex-grow: 1;
    text-align: left;
}

.user-preferences {
    font-size: 0.9rem;
    color: var(--netflix-light-gray);
    text-align: right;
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
        min-width: 120px;
        width: 120px;
    }
    
    .card img {
        height: 180px;
    }
    
    .modal-content {
        width: 95%;
    }
    
    .modal-title {
        font-size: 1.8rem;
    }
    
    .user-dropdown {
        width: 180px;
        right: -10px;
    }
    
    .user-item {
        flex-direction: column;
        text-align: center;
    }
    
    .user-preferences {
        text-align: center;
    }
}

/* Indicador de preferencias del usuario actual */
.preferences-indicator {
    background-color: var(--netflix-red);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    margin-left: 10px;
}

/* Sección Mi Lista */
.mi-lista-section {
    margin-top: 40px;
    padding: 0 4%;
}

.empty-list {
    text-align: center;
    padding: 40px;
    color: var(--netflix-light-gray);
}

.empty-list i {
    font-size: 3rem;
    margin-bottom: 20px;
    display: block;
}
</style>
</head>
<body>

<header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <div class="nav-links">
        <a href="#" class="active">Inicio</a>
        <a href="#">Novedades</a>
        <a href="#" id="miListaLink">Mi lista</a>
    </div>
    <div class="usuario-menu">
        <div class="usuario" onclick="toggleUserMenu()">
            <div class="usuario-avatar"><?= substr($usuario['nombre'], 0, 1) ?></div>
            <?= $usuario['nombre'] ?>
            <?php if(count($preferencias) > 0): ?>
                <span class="preferences-indicator"><?= count($preferencias) ?> pref.</span>
            <?php endif; ?>
            <span>▼</span>
        </div>
        <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-item" onclick="openUserModal()">
                <i>👤</i> Cambiar de usuario
            </div>
            <div class="user-dropdown-item" onclick="manageProfiles()">
                <i>⚙️</i> Gestionar perfiles
            </div>
            <div class="user-dropdown-divider"></div>
            <div class="user-dropdown-item" onclick="logout()">
                <i>🚪</i> Cerrar sesión
            </div>
        </div>
    </div>
</header>

<div class="banner">
    <div class="banner-content">
        <h1>Bienvenido, <?= $usuario['nombre'] ?></h1>
        <p>
            <?php if(count($preferencias) > 0): ?>
                Te recomendamos películas de: <?= implode(', ', $preferencias) ?>
            <?php else: ?>
                Configura tus preferencias para obtener recomendaciones personalizadas
            <?php endif; ?>
        </p>
        <div class="banner-buttons">
            <button class="btn btn-primary">▶ Reproducir</button>
            <button class="btn btn-secondary">ⓘ Más información</button>
        </div>
    </div>
</div>

<!-- SECCIÓN MI LISTA -->
<div class="mi-lista-section" id="miListaSection" style="<?= count($mi_lista) == 0 ? 'display:none;' : '' ?>">
    <h2 class="section-title">Mi Lista 💖</h2>
    <div class="row" id="miListaRow">
        <?php foreach($mi_lista as $p): ?>
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                <img src="<?= $p['imagen'] ?>" alt="<?= $p['titulo'] ?>">
                <button class="lista-btn en-lista" onclick="event.stopPropagation(); toggleLista(<?= $p['id'] ?>, this)" title="Quitar de Mi Lista">✓</button>
                <div class="card-overlay">
                    <div class="card-title"><?= $p['titulo'] ?></div>
                    <div class="card-details">
                        <span><?= $p['genero'] ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if(count($mi_lista) == 0): ?>
<div class="mi-lista-section">
    <div class="empty-list">
        <i>📝</i>
        <h3>Tu lista está vacía</h3>
        <p>Agrega películas a tu lista para verlas más tarde</p>
    </div>
</div>
<?php endif; ?>

<?php if(count($peliculas_filtradas) > 0): ?>
<div class="section">
    <h2 class="section-title">Recomendaciones para ti 🍿</h2>
    <div class="row">
        <?php foreach($peliculas_filtradas as $p): ?>
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode(array_merge($p, ['enLista' => isset($peliculas_en_lista[$p['id']])])), ENT_QUOTES, 'UTF-8') ?>)">
                <img src="<?= $p['imagen'] ?>" alt="<?= $p['titulo'] ?>">
                <button class="lista-btn <?= isset($peliculas_en_lista[$p['id']]) ? 'en-lista' : '' ?>" 
                        onclick="event.stopPropagation(); toggleLista(<?= $p['id'] ?>, this)" 
                        title="<?= isset($peliculas_en_lista[$p['id']]) ? 'Quitar de Mi Lista' : 'Agregar a Mi Lista' ?>">
                    <?= isset($peliculas_en_lista[$p['id']]) ? '✓' : '+' ?>
                </button>
                <div class="card-overlay">
                    <div class="card-title"><?= $p['titulo'] ?></div>
                    <div class="card-details">
                        <span><?= $p['genero'] ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="section">
    <h2 class="section-title">Configura tus preferencias</h2>
    <p>Para obtener recomendaciones personalizadas, selecciona tus géneros favoritos en la configuración de tu perfil.</p>
</div>
<?php endif; ?>

<?php foreach($peliculas_por_genero as $gen => $peliculas): ?>
<div class="section">
    <h2 class="section-title"><?= $gen ?></h2>
    <div class="row">
        <?php foreach($peliculas as $p): ?>
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode(array_merge($p, ['enLista' => isset($peliculas_en_lista[$p['id']])])), ENT_QUOTES, 'UTF-8') ?>)">
                <img src="<?= $p['imagen'] ?>" alt="<?= $p['titulo'] ?>">
                <button class="lista-btn <?= isset($peliculas_en_lista[$p['id']]) ? 'en-lista' : '' ?>" 
                        onclick="event.stopPropagation(); toggleLista(<?= $p['id'] ?>, this)" 
                        title="<?= isset($peliculas_en_lista[$p['id']]) ? 'Quitar de Mi Lista' : 'Agregar a Mi Lista' ?>">
                    <?= isset($peliculas_en_lista[$p['id']]) ? '✓' : '+' ?>
                </button>
                <div class="card-overlay">
                    <div class="card-title"><?= $p['titulo'] ?></div>
                    <div class="card-details">
                        <span><?= $p['genero'] ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
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
                <button class="btn btn-primary">▶ Reproducir</button>
                <button class="modal-lista-btn" id="modalListaBtn">+ Mi lista</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CAMBIO DE USUARIO -->
<div id="userModal" class="user-modal">
    <div class="user-modal-content">
        <h2 class="user-modal-title">¿Quién está viendo?</h2>
        <div class="user-list">
            <?php 
            foreach($otros_usuarios as $otro_usuario): 
                // Obtener preferencias de cada usuario
                $sql_user_prefs = "SELECT genero FROM preferencias WHERE usuario_id = ?";
                $stmt_user_prefs = $conn->prepare($sql_user_prefs);
                $stmt_user_prefs->bind_param("i", $otro_usuario['id']);
                $stmt_user_prefs->execute();
                $result_user_prefs = $stmt_user_prefs->get_result();
                $user_prefs = [];
                while ($row = $result_user_prefs->fetch_assoc()) {
                    $user_prefs[] = $row['genero'];
                }
                $stmt_user_prefs->close();
            ?>
            <form method="POST" class="user-item-form">
                <input type="hidden" name="usuario_id" value="<?= $otro_usuario['id'] ?>">
                <button type="submit" name="cambiar_usuario" class="user-item">
                    <div class="user-avatar"><?= substr($otro_usuario['nombre'], 0, 1) ?></div>
                    <div class="user-name"><?= $otro_usuario['nombre'] ?></div>
                    <div class="user-preferences">
                        <?= count($user_prefs) > 0 ? implode(', ', array_slice($user_prefs, 0, 2)) . (count($user_prefs) > 2 ? '...' : '') : 'Sin preferencias' ?>
                    </div>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
    </div>
</div>

<script>
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

// Función para agregar/eliminar de Mi Lista
function toggleLista(peliculaId, boton) {
    const formData = new FormData();
    formData.append('pelicula_id', peliculaId);
    
    if (boton.classList.contains('en-lista')) {
        formData.append('accion_lista', 'eliminar');
    } else {
        formData.append('accion_lista', 'agregar');
    }
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.ok) {
            location.reload();
        }
    });
}

// Navegación a Mi Lista
document.getElementById('miListaLink').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('miListaSection').scrollIntoView({ behavior: 'smooth' });
});

// Función para abrir el modal de película
function openModal(movie) {
    peliculaActual = movie;
    
    const modal = document.getElementById('movieModal');
    const modalHeader = document.getElementById('modalHeader');
    const modalTitle = document.getElementById('modalTitle');
    const modalDetails = document.getElementById('modalDetails');
    const modalDescription = document.getElementById('modalDescription');
    const modalListaBtn = document.getElementById('modalListaBtn');
    
    // Establecer el fondo del header del modal
    modalHeader.style.backgroundImage = `url('${movie.imagen}')`;
    
    // Establecer el título
    modalTitle.textContent = movie.titulo;
    
    // Establecer detalles
    modalDetails.innerHTML = `
        <span>${movie.genero}</span>
        <span>${movie.anio || ''}</span>
        <span>${movie.duracion || ''}</span>
    `;
    
    // Establecer descripción
    modalDescription.textContent = movie.descripcion || 
        `Disfruta de "${movie.titulo}", una película del género ${movie.genero} que te mantendrá en el borde de tu asiento.`;
    
    // Configurar botón de Mi Lista en el modal
    if (movie.enLista) {
        modalListaBtn.textContent = '✓ En Mi Lista';
        modalListaBtn.classList.add('en-lista');
    } else {
        modalListaBtn.textContent = '+ Mi lista';
        modalListaBtn.classList.remove('en-lista');
    }
    
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

// Cerrar modal al hacer clic fuera del contenido
window.addEventListener('click', function(event) {
    const modal = document.getElementById('movieModal');
    if (event.target === modal) {
        closeModal();
    }
});

// Configurar botón de lista en el modal
document.getElementById('modalListaBtn').addEventListener('click', function() {
    if (peliculaActual) {
        // Simular click en el botón de lista
        const formData = new FormData();
        formData.append('pelicula_id', peliculaActual.id);
        
        if (peliculaActual.enLista) {
            formData.append('accion_lista', 'eliminar');
        } else {
            formData.append('accion_lista', 'agregar');
        }
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                location.reload();
            }
        });
    }
});

// Menú desplegable del usuario
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('active');
}

// Cerrar menú desplegable al hacer clic fuera
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const userMenu = document.querySelector('.usuario');
    
    if (!userMenu.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Abrir modal de cambio de usuario
function openUserModal() {
    const modal = document.getElementById('userModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    // Cerrar el menú desplegable
    document.getElementById('userDropdown').classList.remove('active');
}

// Cerrar modal de cambio de usuario
function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modal de usuario al hacer clic fuera
window.addEventListener('click', function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
});

// Función para gestionar perfiles
function manageProfiles() {
    alert("Funcionalidad de gestión de perfiles - En desarrollo");
    document.getElementById('userDropdown').classList.remove('active');
}

// Función para cerrar sesión
function logout() {
    if (confirm("¿Estás seguro de que quieres cerrar sesión?")) {
        // Redirigir al logout
        window.location.href = "logout.php";
    }
    document.getElementById('userDropdown').classList.remove('active');
}
</script>

</body>
</html>
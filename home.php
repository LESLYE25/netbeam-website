<?php
session_start();
include("conexion.php");

// Si no hay usuario en sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// VERIFICAR SI EL USUARIO TIENE PREFERENCIAS CONFIGURADAS
$usuario_id = $_SESSION['usuario_id'];
$sql_verificar_preferencias = "SELECT COUNT(*) as total FROM preferencias WHERE usuario_id = ?";
$stmt_verificar = $conn->prepare($sql_verificar_preferencias);
$stmt_verificar->bind_param("i", $usuario_id);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();
$preferencias_count = $result_verificar->fetch_assoc()['total'];
$stmt_verificar->close();

// Si el usuario no tiene preferencias, redirigir a la página de configuración
if ($preferencias_count == 0) {
    header("Location: preferencias.php");
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

// Sistema de valoraciones (Me gusta/No me gusta)
if (isset($_POST['valorar_pelicula'])) {
    $pelicula_id = $_POST['pelicula_id'];
    $valoracion = $_POST['valoracion']; // 'like' o 'dislike'
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar si ya existe una valoración
    $sql_verificar = "SELECT id FROM valoraciones WHERE usuario_id = ? AND pelicula_id = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("ii", $usuario_id, $pelicula_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows > 0) {
        // Actualizar valoración existente
        $sql_actualizar = "UPDATE valoraciones SET valoracion = ? WHERE usuario_id = ? AND pelicula_id = ?";
        $stmt_actualizar = $conn->prepare($sql_actualizar);
        $stmt_actualizar->bind_param("sii", $valoracion, $usuario_id, $pelicula_id);
        $stmt_actualizar->execute();
        $stmt_actualizar->close();
    } else {
        // Insertar nueva valoración
        $sql_insertar = "INSERT INTO valoraciones (usuario_id, pelicula_id, valoracion) VALUES (?, ?, ?)";
        $stmt_insertar = $conn->prepare($sql_insertar);
        $stmt_insertar->bind_param("iis", $usuario_id, $pelicula_id, $valoracion);
        $stmt_insertar->execute();
        $stmt_insertar->close();
    }
    $stmt_verificar->close();
    
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

// Obtener valoraciones del usuario actual
$sql_valoraciones = "SELECT pelicula_id, valoracion FROM valoraciones WHERE usuario_id = ?";
$stmt_valoraciones = $conn->prepare($sql_valoraciones);
$stmt_valoraciones->bind_param("i", $usuario_id);
$stmt_valoraciones->execute();
$result_valoraciones = $stmt_valoraciones->get_result();
$valoraciones_usuario = [];
while ($row = $result_valoraciones->fetch_assoc()) {
    $valoraciones_usuario[$row['pelicula_id']] = $row['valoracion'];
}
$stmt_valoraciones->close();

// Obtener películas más populares (con más "me gusta")
$sql_populares = "SELECT p.*, 
                 COUNT(CASE WHEN v.valoracion = 'like' THEN 1 END) as likes,
                 COUNT(CASE WHEN v.valoracion = 'dislike' THEN 1 END) as dislikes
                 FROM peliculas p 
                 LEFT JOIN valoraciones v ON p.id = v.pelicula_id 
                 GROUP BY p.id 
                 ORDER BY likes DESC 
                 LIMIT 10";
$result_populares = $conn->query($sql_populares);
$peliculas_populares = $result_populares->fetch_all(MYSQLI_ASSOC);

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
    --netbeam-red: #e50914;
    --netbeam-dark: #141414;
    --netbeam-gray: #2f2f2f;
    --netbeam-light-gray: #b3b3b3;
    --netbeam-white: #ffffff;
}

* {
    box-sizing: border-box;
}

body {
    background: var(--netbeam-dark);
    color: var(--netbeam-white);
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
    background-color: var(--netbeam-dark);
}

header img.logo {
    height: 30px;
}

.nav-links {
    display: flex;
    gap: 20px;
}

.nav-links a {
    color: var(--netbeam-white);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: var(--netbeam-light-gray);
}

.nav-links a.active {
    color: var(--netbeam-white);
    font-weight: bold;
}

.usuario-menu {
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.usuario {
    color: var(--netbeam-white);
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
    background-color: var(--netbeam-red);
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
                url('https://es.wp-s.ru/wallpapers/16/17/464455474531767/portada-de-la-pel-cula-piratas-del-caribe-con-jack-sparrow.jpg') center/cover no-repeat;
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
    background-color: var(--netbeam-white);
    color: var(--netbeam-dark);
}

.btn-primary:hover {
    background-color: rgba(255,255,255,0.75);
}

.btn-secondary {
    background-color: rgba(109, 109, 110, 0.7);
    color: var(--netbeam-white);
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
    color: var(--netbeam-light-gray);
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
    background: var(--netbeam-red);
    opacity: 1;
}

.lista-btn:hover {
    background: var(--netbeam-red);
}

/* MODAL DE PELÍCULA - MÁS ALTO Y MENOS ANCHO */
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
    background-color: var(--netbeam-dark);
    border-radius: 10px;
    width: 90%;
    max-width: 600px; /* Menos ancho */
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.modal-header {
    height: 400px; /* Más alto */
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
    background: linear-gradient(to top, var(--netbeam-dark), transparent);
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--netbeam-dark);
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
    color: var(--netbeam-light-gray);
    align-items: center;
    flex-wrap: wrap;
}

.rating-badge {
    background: var(--netbeam-red);
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

.modal-lista-btn {
    background: transparent;
    border: 2px solid var(--netbeam-light-gray);
    color: var(--netbeam-white);
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-lista-btn.en-lista {
    background: var(--netbeam-red);
    border-color: var(--netbeam-red);
}

.modal-lista-btn:hover {
    background: var(--netbeam-red);
    border-color: var(--netbeam-red);
}

.valoracion-buttons {
    display: flex;
    gap: 10px;
}

.btn-like, .btn-dislike {
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-like.active {
    background: #4CAF50;
    border-color: #4CAF50;
}

.btn-dislike.active {
    background: #f44336;
    border-color: #f44336;
}

.btn-like:hover {
    background: #4CAF50;
    border-color: #4CAF50;
}

.btn-dislike:hover {
    background: #f44336;
    border-color: #f44336;
}

/* Sección de recomendaciones en el modal */
.modal-recommendations {
    margin-top: 20px;
    border-top: 1px solid #333;
    padding-top: 20px;
}

.modal-recommendations h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: var(--netbeam-white);
}

.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}

.recommendation-card {
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s;
}

.recommendation-card:hover {
    transform: scale(1.05);
}

.recommendation-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

/* Estrellas de valoración */
.star-rating {
    display: flex;
    gap: 2px;
    margin: 10px 0;
}

.star {
    color: #666;
    font-size: 18px;
    cursor: pointer;
    transition: color 0.2s;
}

.star.active {
    color: #ffd700;
}

.star:hover {
    color: #ffd700;
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
    background-color: var(--netbeam-dark);
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
    background-color: var(--netbeam-red);
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
    color: var(--netbeam-light-gray);
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
        max-width: 500px;
    }
    
    .modal-header {
        height: 300px;
        min-height: 300px;
    }
    
    .modal-title {
        font-size: 1.8rem;
    }
    
    .modal-buttons {
        flex-direction: column;
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
    background-color: var(--netbeam-red);
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
    color: var(--netbeam-light-gray);
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
    <img class="logo" src="img/image2.png" alt="Logo">
    <div class="nav-links">
        <a href="#" class="active">Inicio</a>
        <a href="#" id="miListaLink">Mi lista</a>
    </div>
    <div class="usuario-menu">
        <div class="usuario" onclick="toggleUserMenu(event)">
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
                <i>⚙️</i> Gestionar preferencias
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
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>, event)">
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

<!-- PELÍCULAS POPULARES -->
<?php if(count($peliculas_populares) > 0): ?>
<div class="section">
    <h2 class="section-title">🔥 Más populares</h2>
    <div class="row">
        <?php foreach($peliculas_populares as $p): ?>
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode(array_merge($p, ['enLista' => isset($peliculas_en_lista[$p['id']])])), ENT_QUOTES, 'UTF-8') ?>, event)">
                <img src="<?= $p['imagen'] ?>" alt="<?= $p['titulo'] ?>">
                <button class="lista-btn <?= isset($peliculas_en_lista[$p['id']]) ? 'en-lista' : '' ?>" 
                        onclick="event.stopPropagation(); toggleLista(<?= $p['id'] ?>, this)" 
                        title="<?= isset($peliculas_en_lista[$p['id']]) ? 'Quitar de Mi Lista' : 'Agregar a Mi Lista' ?>">
                    <?= isset($peliculas_en_lista[$p['id']]) ? '✓' : '+' ?>
                </button>
                <div class="card-overlay">
                    <div class="card-title"><?= $p['titulo'] ?></div>
                    <div class="card-details">
                        <span>👍 <?= $p['likes'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if(count($peliculas_filtradas) > 0): ?>
<div class="section">
    <h2 class="section-title">Para ti 🍿 (Basado en tus preferencias)</h2>
    <div class="row">
        <?php foreach($peliculas_filtradas as $p): ?>
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode(array_merge($p, ['enLista' => isset($peliculas_en_lista[$p['id']])])), ENT_QUOTES, 'UTF-8') ?>, event)">
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
            <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode(array_merge($p, ['enLista' => isset($peliculas_en_lista[$p['id']])])), ENT_QUOTES, 'UTF-8') ?>, event)">
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
                <button class="modal-lista-btn" id="modalListaBtn">
                    <span id="listaIcon">+</span> Mi lista
                </button>
                <div class="valoracion-buttons">
                    <button class="btn-like" id="btnLike">
                        <span>👍</span> Me gusta
                    </button>
                    <button class="btn-dislike" id="btnDislike">
                        <span>👎</span> No me gusta
                    </button>
                </div>
            </div>

            <!-- Valoración con estrellas -->
            <div class="star-rating" id="starRating">
                <span class="star" data-rating="1">★</span>
                <span class="star" data-rating="2">★</span>
                <span class="star" data-rating="3">★</span>
                <span class="star" data-rating="4">★</span>
                <span class="star" data-rating="5">★</span>
            </div>

            <!-- Recomendaciones similares -->
            <div class="modal-recommendations">
                <h3>🎬 También te puede gustar</h3>
                <div class="recommendations-grid" id="recommendationsGrid">
                    <!-- Las recomendaciones se cargan dinámicamente -->
                </div>
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
let modalAbierto = false;

// Función para agregar/eliminar de Mi Lista
function toggleLista(peliculaId, boton, event) {
    if (event) event.stopPropagation();
    
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

// Función para valorar película
function valorarPelicula(peliculaId, valoracion) {
    const formData = new FormData();
    formData.append('pelicula_id', peliculaId);
    formData.append('valoracion', valoracion);
    formData.append('valorar_pelicula', 'true');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.ok) {
            // Actualizar la interfaz sin recargar
            const btnLike = document.getElementById('btnLike');
            const btnDislike = document.getElementById('btnDislike');
            
            if (valoracion === 'like') {
                btnLike.classList.add('active');
                btnDislike.classList.remove('active');
            } else {
                btnDislike.classList.add('active');
                btnLike.classList.remove('active');
            }
        }
    });
}

// Navegación a Mi Lista
document.getElementById('miListaLink').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('miListaSection').scrollIntoView({ behavior: 'smooth' });
});

// Función para abrir el modal de película
function openModal(movie, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('Abriendo modal para:', movie.titulo);
    
    peliculaActual = movie;
    modalAbierto = true;
    
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
        <span class="rating-badge">${movie.genero}</span>
        <span>${movie.likes ? `👍 ${movie.likes}` : ''}</span>
        <span>⭐ ${(Math.random() * 2 + 3).toFixed(1)}/5</span>
    `;
    
    // Establecer descripción
    modalDescription.textContent = movie.descripcion || 
        `"${movie.titulo}" es una emocionante película del género ${movie.genero} que te mantendrá al borde de tu asiento. Con una trama envolvente y actuaciones memorables, esta producción promete horas de entretenimiento.`;
    
    // Configurar botón de Mi Lista en el modal
    if (movie.enLista) {
        modalListaBtn.innerHTML = '<span>✓</span> En Mi Lista';
        modalListaBtn.classList.add('en-lista');
    } else {
        modalListaBtn.innerHTML = '<span>+</span> Mi lista';
        modalListaBtn.classList.remove('en-lista');
    }
    
    // Configurar botones de valoración
    const btnLike = document.getElementById('btnLike');
    const btnDislike = document.getElementById('btnDislike');
    btnLike.classList.remove('active');
    btnDislike.classList.remove('active');
    
    // Cargar recomendaciones similares
    cargarRecomendaciones(movie.genero, movie.id);
    
    // Mostrar el modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Agregar clase para animación de entrada
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
}

// Función para cargar recomendaciones similares
function cargarRecomendaciones(genero, peliculaIdActual) {
    const recommendationsGrid = document.getElementById('recommendationsGrid');
    recommendationsGrid.innerHTML = '<p>Cargando recomendaciones...</p>';
    
    // Simular carga de recomendaciones del mismo género
    setTimeout(() => {
        // Filtrar películas del mismo género (excluyendo la actual)
        const peliculasMismoGenero = <?= json_encode($peliculas_por_genero) ?>[genero] || [];
        const recomendaciones = peliculasMismoGenero
            .filter(p => p.id != peliculaIdActual)
            .slice(0, 6);
        
        if (recomendaciones.length > 0) {
            recommendationsGrid.innerHTML = recomendaciones.map(pelicula => `
                <div class="recommendation-card" onclick="openModal(${JSON.stringify(pelicula).replace(/"/g, '&quot;')}, event)">
                    <img src="${pelicula.imagen}" alt="${pelicula.titulo}">
                </div>
            `).join('');
        } else {
            recommendationsGrid.innerHTML = '<p>No hay recomendaciones disponibles</p>';
        }
    }, 500);
}

// Función para cerrar el modal de película
function closeModal() {
    console.log('Cerrando modal');
    
    const modal = document.getElementById('movieModal');
    modal.classList.remove('active');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        peliculaActual = null;
        modalAbierto = false;
        
        // Limpiar las estrellas
        document.querySelectorAll('.star').forEach(star => {
            star.classList.remove('active');
        });
    }, 300);
}

// Configurar el botón de cerrar modal
document.querySelector('.modal-close').addEventListener('click', function(e) {
    e.stopPropagation();
    closeModal();
});

// Cerrar modal al hacer clic fuera del contenido
document.getElementById('movieModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeModal();
    }
});

// Prevenir que el clic dentro del modal lo cierre
document.querySelector('.modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Configurar botón de lista en el modal
document.getElementById('modalListaBtn').addEventListener('click', function(event) {
    event.stopPropagation();
    
    if (peliculaActual) {
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

// Configurar botones de valoración
document.getElementById('btnLike').addEventListener('click', function(event) {
    event.stopPropagation();
    
    if (peliculaActual) {
        valorarPelicula(peliculaActual.id, 'like');
    }
});

document.getElementById('btnDislike').addEventListener('click', function(event) {
    event.stopPropagation();
    
    if (peliculaActual) {
        valorarPelicula(peliculaActual.id, 'dislike');
    }
});

// Sistema de estrellas
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function(event) {
        event.stopPropagation();
        
        const rating = this.getAttribute('data-rating');
        const stars = document.querySelectorAll('.star');
        
        stars.forEach((s, index) => {
            if (index < rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
        
        // Aquí podrías guardar la valoración en la base de datos
        console.log(`Valoración: ${rating} estrellas para ${peliculaActual.titulo}`);
    });
});

// Menú desplegable del usuario
function toggleUserMenu(event) {
    if (event) event.stopPropagation();
    
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('active');
}

// Cerrar menú desplegable al hacer clic fuera
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    if (!event.target.closest('.usuario-menu')) {
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
document.getElementById('userModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeUserModal();
    }
});

// Prevenir que el clic dentro del modal de usuario lo cierre
document.querySelector('.user-modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Configurar botón de cancelar en modal de usuario
document.querySelector('.user-modal-content .btn-secondary').addEventListener('click', function(event) {
    event.stopPropagation();
    closeUserModal();
});

// Función para gestionar perfiles
function manageProfiles() {
    window.location.href = "preferencias.php";
}

// Función para cerrar sesión
function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        window.location.href = "logout.php";
    }
    document.getElementById('userDropdown').classList.remove('active');
}

// Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Agregar event listener para la tecla Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modalAbierto) {
            closeModal();
        }
        if (event.key === 'Escape' && document.getElementById('userModal').style.display === 'flex') {
            closeUserModal();
        }
    });
    
    // Agregar event listeners a todas las cards de películas (como respaldo)
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', function(event) {
            // Solo actuar si el clic no fue en el botón de lista
            if (!event.target.closest('.lista-btn')) {
                const movieData = this.getAttribute('data-movie');
                if (movieData) {
                    openModal(JSON.parse(movieData), event);
                }
            }
        });
    });
    
    // Agregar data-movie attribute a todas las cards como respaldo
    document.querySelectorAll('.card').forEach(card => {
        const onclickAttr = card.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes('openModal')) {
            // Extraer el JSON del onclick
            const match = onclickAttr.match(/openModal\(({[^}]+})/);
            if (match) {
                card.setAttribute('data-movie', match[1]);
            }
        }
    });
});

// Función auxiliar para prevenir clics en botones de lista
document.addEventListener('click', function(event) {
    if (event.target.closest('.lista-btn')) {
        event.stopPropagation();
        event.preventDefault();
    }
});
</script>

</body>
</html>
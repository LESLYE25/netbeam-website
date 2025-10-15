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

// Obtener películas destacadas para el carrusel principal
$sql_carrusel = "SELECT * FROM peliculas ORDER BY RAND() LIMIT 8";
$result_carrusel = $conn->query($sql_carrusel);
$peliculas_carrusel = $result_carrusel->fetch_all(MYSQLI_ASSOC);

// IMÁGENES ESPECÍFICAS PARA EL BANNER DE BIENVENIDA
$banner_images = [
    [
        'url' => 'https://wallpapers.com/images/hd/1920x1080-hd-movie-1920-x-1080-4wl5v81m8azwaka6.jpg',
        'title' => 'Bienvenido de vuelta, ' . $usuario['nombre'],
        'description' => 'Continúa disfrutando de Netbeam con recomendaciones personalizadas.'
    ],
    [
        'url' => 'https://wallpapers.com/images/high/action-movie-4000-x-2000-wallpaper-6yb1bqeuq59u47bw.webp',
        'title' => 'Basado en tus preferencias',
        'description' => 'Te recomendamos: ' . (count($preferencias) > 0 ? implode(', ', $preferencias) : 'Configura tus preferencias')
    ],
    [
        'url' => 'https://wallpapers.com/images/featured/pelicula-de-accion-pb93e7r343erqgtt.jpg',
        'title' => 'Mi Lista personalizada',
        'description' => count($mi_lista) . ' películas guardadas en tu lista'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Netbeam 🎬 - <?= $usuario['nombre'] ?></title>
<link rel="shortcut icon" type="image/jpg" href="img/image.png" />
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
    background: var(--netbeam-red);
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

/* CARRUSEL DE PELÍCULAS - TAMAÑOS AUMENTADOS */
.carrusel-section {
    margin-top: 60px;
    padding: 0 4%;
    position: relative;
}

.carrusel-title {
    font-size: 1.8rem;
    margin-bottom: 20px;
    font-weight: 600;
    color: var(--netbeam-white);
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
    color: var(--netbeam-light-gray);
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
    background: var(--netbeam-light-gray);
    cursor: pointer;
    transition: background 0.3s;
}

.carrusel-indicator.active {
    background: var(--netbeam-red);
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
    scroll-behavior: smooth;
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

/* Botones de navegación para secciones por género */
.section-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.7);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
    z-index: 10;
    opacity: 0;
    transition: opacity 0.3s;
}

.section:hover .section-nav {
    opacity: 1;
}

.section-nav:hover {
    background: rgba(0,0,0,0.9);
}

.section-prev {
    left: 10px;
}

.section-next {
    right: 10px;
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
    background-color: var(--netbeam-dark);
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

/* Modal de cierre de sesión */
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
    
    .section-nav {
        display: none;
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

<!-- MODAL DE CONFIRMACIÓN DE CIERRE DE SESIÓN -->
<div id="logoutModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <button class="modal-close" onclick="closeLogoutModal()">×</button>
        <div class="modal-body" style="text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🚪</div>
            <h2 class="modal-title" style="font-size: 1.8rem;">Cerrar Sesión</h2>
            <p class="modal-description" style="margin-bottom: 30px;">
                ¿Estás seguro de que quieres cerrar sesión?<br>
                Podrás volver a iniciar sesión en cualquier momento.
            </p>
            
            <div class="modal-buttons" style="justify-content: center; gap: 15px;">
                <button class="btn-secondary" onclick="closeLogoutModal()" style="padding: 12px 25px;">
                    Cancelar
                </button>
                <button class="btn-comenzar" onclick="confirmLogout()" style="padding: 12px 25px;">
                    Sí, Cerrar Sesión
                </button>
            </div>
        </div>
    </div>
</div>

<!-- BANNER CARRUSEL - Con imágenes específicas -->
<div class="banner-carrusel" id="bannerCarrusel">
    <?php foreach($banner_images as $index => $banner): ?>
    <div class="banner-slide <?= $index === 0 ? 'active' : '' ?>" 
         style="background-image: url('<?= $banner['url'] ?>')">
        <div class="banner-content">
            <h1><?= $banner['title'] ?></h1>
            <p><?= $banner['description'] ?></p>
            <div class="banner-buttons">
                <button class="btn btn-primary">▶ Reproducir</button>
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

<!-- MODAL DE INFORMACIÓN GENERAL -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeInfoModal()">×</button>
        <div class="modal-body">
            <h2 class="modal-title">Sobre Netbeam</h2>
            <div class="modal-details">
                <span class="rating-badge">Plataforma Premium</span>
                <span>⭐ 4.8/5</span>
            </div>
            <p class="modal-description">
                Netbeam es tu destino premium para disfrutar de las mejores películas y series. Con una interfaz intuitiva 
                y contenido actualizado regularmente, ofrecemos una experiencia de entretenimiento excepcional.
                <br><br>
                <strong>Características exclusivas para usuarios registrados:</strong>
                <br>• Recomendaciones personalizadas basadas en tus preferencias
                <br>• Mi Lista para guardar tus películas favoritas
                <br>• Sistema de valoraciones para mejorar las recomendaciones
                <br>• Múltiples perfiles de usuario
                <br>• Contenido exclusivo y original
                <br><br>
                <strong>Géneros disponibles:</strong>
                <br>• Acción • Comedia • Terror • Romance • Ciencia ficción • Drama
            </p>
            
            <div class="modal-buttons">
                <button class="btn-comenzar" onclick="closeInfoModal()">
                    ¡Entendido!
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CARRUSEL DE PELÍCULAS DESTACADAS -->
<div class="carrusel-section">
    <h2 class="carrusel-title">Películas Destacadas</h2>
    <div class="carrusel-container">
        <button class="carrusel-btn carrusel-prev" onclick="moveCarrusel('destacadas', -1)">❮</button>
        <div class="carrusel" id="carruselDestacadas">
            <?php foreach($peliculas_carrusel as $p): ?>
                <div class="carrusel-item" onclick="openModal(<?= htmlspecialchars(json_encode(array_merge($p, ['enLista' => isset($peliculas_en_lista[$p['id']])])), ENT_QUOTES, 'UTF-8') ?>, event)">
                    <img src="<?= $p['imagen'] ?>" alt="<?= $p['titulo'] ?>">
                    <button class="lista-btn <?= isset($peliculas_en_lista[$p['id']]) ? 'en-lista' : '' ?>" 
                            onclick="event.stopPropagation(); toggleLista(<?= $p['id'] ?>, this)" 
                            title="<?= isset($peliculas_en_lista[$p['id']]) ? 'Quitar de Mi Lista' : 'Agregar a Mi Lista' ?>">
                        <?= isset($peliculas_en_lista[$p['id']]) ? '✓' : '+' ?>
                    </button>
                    <div class="carrusel-overlay">
                        <div class="carrusel-item-title"><?= $p['titulo'] ?></div>
                        <div class="carrusel-item-genre"><?= $p['genero'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carrusel-btn carrusel-next" onclick="moveCarrusel('destacadas', 1)">❯</button>
        
        <div class="carrusel-indicators" id="carruselIndicatorsDestacadas">
            <!-- Los indicadores se generan dinámicamente con JavaScript -->
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
    <button class="section-nav section-prev" onclick="scrollSection('populares', -1)">❮</button>
    <div class="row" id="row-populares">
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
    <button class="section-nav section-next" onclick="scrollSection('populares', 1)">❯</button>
</div>
<?php endif; ?>

<?php if(count($peliculas_filtradas) > 0): ?>
<div class="section">
    <h2 class="section-title">Para ti 🍿 (Basado en tus preferencias)</h2>
    <button class="section-nav section-prev" onclick="scrollSection('recomendaciones', -1)">❮</button>
    <div class="row" id="row-recomendaciones">
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
    <button class="section-nav section-next" onclick="scrollSection('recomendaciones', 1)">❯</button>
</div>
<?php else: ?>
<div class="section">
    <h2 class="section-title">Configura tus preferencias</h2>
    <p>Para obtener recomendaciones personalizadas, selecciona tus géneros favoritos en la configuración de tu perfil.</p>
</div>
<?php endif; ?>

<?php foreach($peliculas_por_genero as $gen => $peliculas): ?>
<div class="section" id="section-<?= strtolower(str_replace(' ', '-', $gen)) ?>">
    <h2 class="section-title"><?= $gen ?></h2>
    <button class="section-nav section-prev" onclick="scrollSection('<?= strtolower(str_replace(' ', '-', $gen)) ?>', -1)">❮</button>
    <div class="row" id="row-<?= strtolower(str_replace(' ', '-', $gen)) ?>">
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
    <button class="section-nav section-next" onclick="scrollSection('<?= strtolower(str_replace(' ', '-', $gen)) ?>', 1)">❯</button>
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

<script>
// Variables para los carruseles
const carruseles = {
    destacadas: {
        currentIndex: 0,
        items: [],
        indicators: []
    }
};

const itemsPerView = 4;

// Variables para el banner carrusel
let currentBannerIndex = 0;
let bannerSlides = [];
let bannerInterval;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeCarruseles();
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

// ========== CARRUSEL DE PELÍCULAS DESTACADAS ==========
function initializeCarruseles() {
    // Inicializar carrusel de destacadas
    const carrusel = document.getElementById('carruselDestacadas');
    if (carrusel) {
        carruseles.destacadas.items = Array.from(carrusel.children);
        
        // Crear indicadores
        const indicatorsContainer = document.getElementById('carruselIndicatorsDestacadas');
        if (indicatorsContainer) {
            const totalIndicators = Math.ceil(carruseles.destacadas.items.length / itemsPerView);
            
            indicatorsContainer.innerHTML = '';
            for (let i = 0; i < totalIndicators; i++) {
                const indicator = document.createElement('div');
                indicator.className = 'carrusel-indicator' + (i === 0 ? ' active' : '');
                indicator.onclick = () => goToCarruselSlide('destacadas', i);
                indicatorsContainer.appendChild(indicator);
            }
            
            carruseles.destacadas.indicators = Array.from(indicatorsContainer.children);
            updateCarrusel('destacadas');
            
            // Auto-avance para el carrusel
            setInterval(() => {
                moveCarrusel('destacadas', 1);
            }, 5000);
        }
    }
}

function moveCarrusel(carruselKey, direction) {
    const carrusel = carruseles[carruselKey];
    if (carrusel && carrusel.items.length > 0) {
        const totalSlides = Math.ceil(carrusel.items.length / itemsPerView);
        carrusel.currentIndex = (carrusel.currentIndex + direction + totalSlides) % totalSlides;
        updateCarrusel(carruselKey);
    }
}

function goToCarruselSlide(carruselKey, index) {
    const carrusel = carruseles[carruselKey];
    if (carrusel) {
        const totalSlides = Math.ceil(carrusel.items.length / itemsPerView);
        carrusel.currentIndex = index;
        updateCarrusel(carruselKey);
    }
}

function updateCarrusel(carruselKey) {
    const carrusel = carruseles[carruselKey];
    if (carrusel && carrusel.items.length > 0) {
        const carruselElement = document.getElementById(`carrusel${carruselKey.charAt(0).toUpperCase() + carruselKey.slice(1)}`);
        if (carruselElement) {
            const itemWidth = carrusel.items[0].offsetWidth + 15;
            const translateX = -carrusel.currentIndex * itemWidth * itemsPerView;
            carruselElement.style.transform = `translateX(${translateX}px)`;
            
            // Actualizar indicadores
            if (carrusel.indicators) {
                carrusel.indicators.forEach((indicator, index) => {
                    indicator.classList.toggle('active', index === carrusel.currentIndex);
                });
            }
        }
    }
}

// ========== NAVEGACIÓN PARA SECCIONES POR GÉNERO ==========
function scrollSection(sectionId, direction) {
    const row = document.getElementById(`row-${sectionId}`);
    if (row) {
        const scrollAmount = 300;
        const newScrollPosition = row.scrollLeft + (scrollAmount * direction);
        
        row.scrollTo({
            left: newScrollPosition,
            behavior: 'smooth'
        });
    }
}

// ========== FUNCIONES GENERALES ==========
// Efecto de scroll en el header
window.addEventListener('scroll', function() {
    const header = document.querySelector('header');
    if (header) {
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
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

// Función para gestionar perfiles
function manageProfiles() {
    window.location.href = "preferencias.php";
}

// Función para abrir modal de cierre de sesión
function openLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // Cerrar el menú desplegable
        document.getElementById('userDropdown').classList.remove('active');
    }
}

// Función para cerrar modal de cierre de sesión
function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Función para confirmar cierre de sesión
function confirmLogout() {
    window.location.href = "logout.php";
}

// Función para cerrar sesión
function logout() {
    openLogoutModal();
}

// Configurar el cierre del modal de logout
document.querySelector('#logoutModal .modal-close').addEventListener('click', function(e) {
    e.stopPropagation();
    closeLogoutModal();
});

// Cerrar modal de logout al hacer clic fuera
document.getElementById('logoutModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeLogoutModal();
    }
});

// Prevenir que el clic dentro del modal de logout lo cierre
document.querySelector('#logoutModal .modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Función para abrir modal de información general
function openInfoModal() {
    const modal = document.getElementById('infoModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// Función para cerrar modal de información
function closeInfoModal() {
    const modal = document.getElementById('infoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Configurar el cierre del modal de información
document.querySelector('#infoModal .modal-close').addEventListener('click', function(e) {
    e.stopPropagation();
    closeInfoModal();
});

// Cerrar modal de información al hacer clic fuera
document.getElementById('infoModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeInfoModal();
    }
});

// Prevenir que el clic dentro del modal de información lo cierre
document.querySelector('#infoModal .modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Agregar event listener para la tecla Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modalAbierto) {
            closeModal();
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
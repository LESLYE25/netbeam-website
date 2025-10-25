<?php
session_start();
require_once __DIR__ . '/conexion.php';

// --- Obtener filtros ---
$where = [];
$buscar = $anio = $genero = '';
if (!empty($_GET['buscar'])) {
  $buscar = $conn->real_escape_string($_GET['buscar']);
  $where[] = "titulo LIKE '%$buscar%'";
}
if (!empty($_GET['anio'])) {
  $anio = intval($_GET['anio']);
  $where[] = "anio = $anio";
}
if (!empty($_GET['genero'])) {
  $genero = $conn->real_escape_string($_GET['genero']);
  $where[] = "genero = '$genero'";
}

$condicion = count($where) ? "WHERE " . implode(' AND ', $where) : "";

// --- Paginación ---
$por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// --- Obtener películas ---
$total = $conn->query("SELECT COUNT(*) as total FROM peliculas $condicion")->fetch_assoc()['total'];
$total_paginas = ceil($total / $por_pagina);
$sql = "SELECT * FROM peliculas $condicion ORDER BY id DESC LIMIT $por_pagina OFFSET $offset";
$peliculas = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// --- Obtener géneros para filtro ---
$generos = $conn->query("SELECT DISTINCT genero FROM peliculas ORDER BY genero ASC")->fetch_all(MYSQLI_ASSOC);

// Agregar esta variable para las recomendaciones
$peliculas_por_genero = [];
$generos_disponibles = $conn->query("SELECT DISTINCT genero FROM peliculas")->fetch_all(MYSQLI_ASSOC);
foreach($generos_disponibles as $gen) {
    $genero_nombre = $gen['genero'];
    $stmt = $conn->prepare("SELECT * FROM peliculas WHERE genero = ? ORDER BY RAND() LIMIT 10");
    $stmt->bind_param("s", $genero_nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $peliculas_por_genero[$genero_nombre] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Verificar qué películas están en Mi Lista (si el usuario está logueado)
$peliculas_en_lista = [];
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $sql_mi_lista = "SELECT pelicula_id FROM mi_lista WHERE usuario_id = ?";
    $stmt_mi_lista = $conn->prepare($sql_mi_lista);
    $stmt_mi_lista->bind_param("i", $usuario_id);
    $stmt_mi_lista->execute();
    $result_mi_lista = $stmt_mi_lista->get_result();
    while ($row = $result_mi_lista->fetch_assoc()) {
        $peliculas_en_lista[$row['pelicula_id']] = true;
    }
    $stmt_mi_lista->close();
}

// Obtener usuario para header
$usuario = null;
if (isset($_SESSION['usuario_id'])) {
    $q = $conn->prepare('SELECT id, nombre FROM usuarios WHERE id = ?');
    $q->bind_param('i', $_SESSION['usuario_id']);
    $q->execute();
    $res = $q->get_result();
    $usuario = $res->fetch_assoc();
    $q->close();
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
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
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
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resultados de búsqueda - Flixter</title>
<link rel="shortcut icon" type="image/jpg" href="img/image.png" />
<style>
/* Reutilizar variables y estilos base (simplificado) */
:root{--Flixter-dark:#141414;--Flixter-white:#fff;--Flixter-light-gray:#b3b3b3;--Flixter-red:#e50914}
body{background:var(--Flixter-dark);color:var(--Flixter-white);font-family:Arial,Helvetica,sans-serif;margin:0;padding:0}
.header{height:68px;display:flex;align-items:center;padding:0 4%;gap:18px;border-bottom:1px solid rgba(255,255,255,0.03)}
.header img.logo{height:60px}
.header .header-search{display:flex;align-items:center;gap:6px}
.header .header-search input{padding:8px;border-radius:4px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.06);color:var(--Flixter-white)}
.header .header-search select{padding:8px;border-radius:4px;border:1px solid rgba(255,255,255,0.08);background:#000;color:#fff;cursor:pointer}
.header .header-search button{background:#000;color:#fff;border:1px solid rgba(255,255,255,0.08);padding:8px 12px;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.header .header-search button svg{width:20px;height:20px;display:block}
.header .header-search button:hover{background:#111}
.header .header-search select:hover{background:#111}
.container{padding:24px 4%}
.results-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}

/* Estilos para las tarjetas corregidas */
.card {
    background:#222;
    border-radius:6px;
    overflow:hidden;
    cursor: pointer;
    transition: transform 0.3s ease;
    position: relative;
}
.card:hover {
    transform: scale(1.05);
}
.card img{width:100%;height:250px;object-fit:cover}
.card .card-body{padding:10px}
.card .card-body h3{margin:0 0 6px 0;font-size:16px}
.card .card-body p{margin:0;color:var(--Flixter-light-gray);font-size:13px}

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
    background: var(--Flixter-red);
    opacity: 1;
}
.lista-btn:hover {
    background: var(--Flixter-red);
}

.filter-bar{display:flex;gap:8px;align-items:center;margin-bottom:18px}
.filter-bar select,.filter-bar input{padding:8px;border-radius:4px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.03);color:var(--Flixter-white)}
.pager{margin-top:18px;display:flex;gap:8px;align-items:center}
.pager a{color:var(--Flixter-white);text-decoration:none;padding:6px 10px;background:#222;border-radius:4px}

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

.modal-lista-btn {
    background: transparent;
    border: 2px solid var(--Flixter-light-gray);
    color: var(--Flixter-white);
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--Flixter-white);
    color: var(--Flixter-dark);
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

.btn-primary:hover {
    background: rgba(255,255,255,0.75);
}

.modal-lista-btn.en-lista {
    background: var(--Flixter-red);
    border-color: var(--Flixter-red);
}

.modal-lista-btn:hover {
    background: var(--Flixter-red);
    border-color: var(--Flixter-red);
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
    color: var(--Flixter-white);
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

/* MODAL REPRODUCTOR DE VIDEO */
#playerModal .modal-content {
    max-width: 900px;
    width: 90%;
    max-height: 90vh;
    padding:0;
    background:transparent;
    box-shadow:none;
}

#playerModal .modal-content > div {
    position:relative;
    width:100%;
}

#playerCloseBtn {
    position:absolute;
    top:10px;
    right:10px;
    z-index:3000;
}

#playerVideo {
    width:100%;
    height:480px;
    max-height:70vh;
    background:#000;
    display:block;
    border-radius: 8px;
}
</style>
</head>
<body>

<header class="header">
    <a href="home.php"><img class="logo" src="img/image2.png" alt="Flixter"></a>
    <form method="GET" action="busqueda.php" class="header-search">
        <input type="text" name="buscar" placeholder="Buscar título..." value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
        <select name="anio">
            <option value="">Año</option>
            <?php for ($y = date('Y'); $y >= 1900; $y--): ?>
                <option value="<?= $y ?>" <?= (isset($_GET['anio']) && intval($_GET['anio']) === $y) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="genero">
            <option value="">Género</option>
            <?php foreach($generos as $g): ?>
                <option value="<?= htmlspecialchars($g['genero']) ?>" <?= (isset($_GET['genero']) && $_GET['genero'] === $g['genero']) ? 'selected' : '' ?>><?= htmlspecialchars($g['genero']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" aria-label="Buscar">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="11" cy="11" r="6" fill="none" stroke="#ffffff" stroke-width="1.8" />
                <line x1="15.5" y1="15.5" x2="21" y2="21" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </button>
    </form>

    <div style="margin-left:auto; display:flex; align-items:center; gap:12px; color:var(--Flixter-white)">
        <?php if($usuario): ?>
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:30px;height:30px;border-radius:4px;background:var(--Flixter-red);display:flex;align-items:center;justify-content:center;font-weight:bold"><?= htmlspecialchars(substr($usuario['nombre'],0,1)) ?></div>
                <div><?= htmlspecialchars($usuario['nombre']) ?></div>
            </div>
        <?php else: ?>
            <a href="login.php" style="color:var(--Flixter-white);text-decoration:none">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <h2>Resultados de búsqueda</h2>
    <div class="filter-bar">
        <div><?= $total ?> resultados</div>
    </div>

    <?php if(empty($peliculas)): ?>
        <p>No se encontraron películas con esos criterios.</p>
    <?php else: ?>
        <div class="results-grid">
            <?php foreach($peliculas as $p): ?>
                <?php
                $poster = $p['poster'];
                if (!filter_var($poster, FILTER_VALIDATE_URL)) $poster = 'admin/uploads/posters/' . $p['poster'];
                
                // Preparar datos para el modal
                $movie_data = array_merge($p, [
                    'enLista' => isset($peliculas_en_lista[$p['id']])
                ]);
                ?>
                <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode($movie_data), ENT_QUOTES, 'UTF-8') ?>, event)">
                    <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <button class="lista-btn <?= isset($peliculas_en_lista[$p['id']]) ? 'en-lista' : '' ?>" 
                            onclick="event.stopPropagation(); toggleLista(<?= $p['id'] ?>, this)" 
                            title="<?= isset($peliculas_en_lista[$p['id']]) ? 'Quitar de Mi Lista' : 'Agregar a Mi Lista' ?>">
                        <?= isset($peliculas_en_lista[$p['id']]) ? '✓' : '+' ?>
                    </button>
                    <?php endif; ?>
                    <div class="card-overlay">
                        <div class="card-title"><?= htmlspecialchars($p['titulo']) ?></div>
                        <div class="card-details">
                            <span><?= htmlspecialchars($p['genero']) ?></span>
                            <span><?= $p['anio'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_paginas > 1): ?>
            <div class="pager">
                <?php if($pagina_actual > 1): $params = $_GET; $params['pagina'] = $pagina_actual - 1; ?>
                    <a href="busqueda.php?<?= http_build_query($params) ?>">« Anterior</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_paginas; $i++): $params = $_GET; $params['pagina'] = $i; ?>
                    <a href="busqueda.php?<?= http_build_query($params) ?>" style="<?= $i == $pagina_actual ? 'font-weight:bold' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if($pagina_actual < $total_paginas): $params = $_GET; $params['pagina'] = $pagina_actual + 1; ?>
                    <a href="busqueda.php?<?= http_build_query($params) ?>">Siguiente »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

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
                    <button class="btn-primary" id="modalPlayBtn">▶ Reproducir</button>
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

    <!-- MODAL REPRODUCTOR DE VIDEO -->
    <div id="playerModal" class="modal" aria-hidden="true">
        <div class="modal-content">
            <div style="position:relative; width:100%;">
                <button class="modal-close" id="playerCloseBtn" style="position:absolute; top:10px; right:10px; z-index:3000;">×</button>
                <div style="background: #000; border-radius:8px; overflow:hidden;">
                    <video id="playerVideo" controls style="width:100%; height:480px; max-height:70vh; background:#000; display:block;" playsinline webkit-playsinline></video>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
// ========== FUNCIONES GENERALES ==========
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
        <span>${movie.likes ? `👍 ${movie.likes}` : ''}</span>
        <span>⭐ ${(Math.random() * 2 + 3).toFixed(1)}/5</span>
    `;
    
    // Establecer descripción
    modalDescription.textContent = movie.descripcion || 
        `"${movie.titulo}" es una emocionante película del género ${movie.genero} estrenada en ${movie.anio}. Con una trama envolvente y actuaciones memorables, esta producción promete horas de entretenimiento.`;
    
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

    // Configurar disponibilidad del botón Reproducir según existencia del video
    const playBtn = document.getElementById('modalPlayBtn');
    if (playBtn) {
        // Algunas filas pasan el campo 'video' como null o empty
        if (movie.video && String(movie.video).trim() !== '') {
            playBtn.disabled = false;
            playBtn.classList.remove('disabled');
        } else {
            playBtn.disabled = true;
            playBtn.classList.add('disabled');
        }
    }
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
                    <img src="${pelicula.poster.startsWith('http') ? pelicula.poster : 'admin/uploads/posters/' + pelicula.poster}" alt="${pelicula.titulo}">
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

// PLAYER MODAL - abrir desde el movieModal y reproducir el archivo asociado
const playerModal = document.getElementById('playerModal');
const playerVideo = document.getElementById('playerVideo');
const playerCloseBtn = document.getElementById('playerCloseBtn');

function openPlayerModal() {
    if (!peliculaActual) return;

    // Determinar la ruta del video
    let videoSrc = peliculaActual.video || peliculaActual.videofile || peliculaActual.ruta_video || '';
    if (!videoSrc) {
        // Si no hay video asociado, mostrar alerta y no abrir
        alert('No se encontró el archivo de video para esta película.');
        return;
    }

    // Si el valor no es una URL completa, asumimos que está en admin/uploads/videos/
    if (!videoSrc.startsWith('http') && !videoSrc.startsWith('//') && !videoSrc.startsWith('/')) {
        videoSrc = 'admin/uploads/videos/' + videoSrc;
    }

    // Establecer la fuente y reproducir automáticamente cuando esté lista
    playerVideo.src = videoSrc;
    playerVideo.load();

    // Mostrar el modal
    playerModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Intentar reproducir cuando los metadatos estén cargados
    playerVideo.addEventListener('canplay', tryAutoPlay);
    playerVideo.addEventListener('error', onPlayerError);
}

function tryAutoPlay() {
    // Quitar el listener para no repetir
    playerVideo.removeEventListener('canplay', tryAutoPlay);
    // Intentar reproducir (puede ser bloqueado por el navegador si no hay interacción)
    const playPromise = playerVideo.play();
    if (playPromise !== undefined) {
        playPromise.catch(err => {
            // Si está bloqueado, dejar controles visibles para que el usuario inicie
            console.warn('Auto-play bloqueado por el navegador:', err);
        });
    }
}

function onPlayerError() {
    alert('Error al cargar el video. Comprueba que el archivo exista en admin/uploads/videos/ y que el formato sea compatible (mp4/webm/ogg).');
}

function closePlayerModal() {
    // Pausar y limpiar
    try { playerVideo.pause(); } catch(e){}
    playerVideo.removeAttribute('src');
    playerVideo.load();

    playerModal.classList.remove('active');
    setTimeout(() => {
        playerModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }, 150);
}

// Asociar eventos
document.addEventListener('DOMContentLoaded', function() {
    const playBtn = document.getElementById('modalPlayBtn');
    if (playBtn) {
        playBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('modalPlayBtn clicked, peliculaActual:', peliculaActual);
            openPlayerModal();
        });
    } else {
        console.warn('modalPlayBtn no se encontró en el DOM');
    }
});

playerCloseBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    closePlayerModal();
});

// Cerrar al hacer click fuera
playerModal.addEventListener('click', function(event) {
    if (event.target === this) closePlayerModal();
});

// Evitar que click en el contenido cierre
document.querySelector('#playerModal .modal-content').addEventListener('click', function(event) {
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
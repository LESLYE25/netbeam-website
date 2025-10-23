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

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resultados de búsqueda - Netbeam</title>
<link rel="shortcut icon" type="image/jpg" href="img/image.png" />
<style>
/* Reutilizar variables y estilos base (simplificado) */
:root{--netbeam-dark:#141414;--netbeam-white:#fff;--netbeam-light-gray:#b3b3b3;--netbeam-red:#e50914}
body{background:var(--netbeam-dark);color:var(--netbeam-white);font-family:Arial,Helvetica,sans-serif;margin:0;padding:0}
.header{height:68px;display:flex;align-items:center;padding:0 4%;gap:18px;border-bottom:1px solid rgba(255,255,255,0.03)}
.header img.logo{height:30px}
.header .header-search{display:flex;align-items:center;gap:6px}
.header .header-search input{padding:8px;border-radius:4px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.06);color:var(--netbeam-white)}
.header .header-search select{padding:8px;border-radius:4px;border:1px solid rgba(255,255,255,0.08);background:#000;color:#fff;cursor:pointer}
.header .header-search button{background:#000;color:#fff;border:1px solid rgba(255,255,255,0.08);padding:8px 12px;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.header .header-search button svg{width:20px;height:20px;display:block}
.header .header-search button:hover{background:#111}
.header .header-search select:hover{background:#111}
.container{padding:24px 4%}
.results-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}
.card{background:#222;border-radius:6px;overflow:hidden}
.card img{width:100%;height:250px;object-fit:cover}
.card .card-body{padding:10px}
.card .card-body h3{margin:0 0 6px 0;font-size:16px}
.card .card-body p{margin:0;color:var(--netbeam-light-gray);font-size:13px}
.filter-bar{display:flex;gap:8px;align-items:center;margin-bottom:18px}
.filter-bar select,.filter-bar input{padding:8px;border-radius:4px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.03);color:var(--netbeam-white)}
.pager{margin-top:18px;display:flex;gap:8px;align-items:center}
.pager a{color:var(--netbeam-white);text-decoration:none;padding:6px 10px;background:#222;border-radius:4px}
</style>
</head>
<body>

<header class="header">
    <a href="home.php"><img class="logo" src="img/image2.png" alt="Netbeam"></a>
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

    <div style="margin-left:auto; display:flex; align-items:center; gap:12px; color:var(--netbeam-white)">
        <?php if($usuario): ?>
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:30px;height:30px;border-radius:4px;background:var(--netbeam-red);display:flex;align-items:center;justify-content:center;font-weight:bold"><?= htmlspecialchars(substr($usuario['nombre'],0,1)) ?></div>
                <div><?= htmlspecialchars($usuario['nombre']) ?></div>
            </div>
        <?php else: ?>
            <a href="login.php" style="color:var(--netbeam-white);text-decoration:none">Iniciar sesión</a>
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
                ?>
                <div class="card">
                <a href="home.php?pelicula_id=<?= $p['id'] ?>" style="color:inherit;text-decoration:none">
                    <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>">
                </a>
                <div class="card-body">
                    <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                    <p><?= htmlspecialchars($p['genero']) ?> • <?= $p['anio'] ?></p>
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

</main>

</body>
</html>

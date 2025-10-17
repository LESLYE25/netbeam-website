<?php
session_start();
require_once __DIR__ . '/../conexion.php';

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
$por_pagina = 6;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// --- Obtener películas ---
$total = $conn->query("SELECT COUNT(*) as total FROM peliculas $condicion")->fetch_assoc()['total'];
$total_paginas = ceil($total / $por_pagina);
$sql = "SELECT * FROM peliculas $condicion ORDER BY id DESC LIMIT $por_pagina OFFSET $offset";
$peliculas = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// --- Obtener géneros para filtro ---
$generos = $conn->query("SELECT DISTINCT genero FROM peliculas ORDER BY genero ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de administración - Netbeam</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="shortcut icon" type="image/jpg" href="../img/image.png" />
</head>

<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="admin-logo"><img src="../img/image2.png" alt="logo"></div>
      <nav>
        <ul>
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="admin.php" class="active">Lista de películas</a></li>
          <li><a href="subir_pelicula.php">Añadir película</a></li>
          <li><a href="../logout.php">Cerrar sesión</a></li>
        </ul>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-header">
        <h1>Panel de administración</h1>
        <p>Gestiona tu catálogo de películas: sube videos, posters y edita metadatos.</p>
      </header>

      <!-- SECCIÓN PELÍCULAS -->
      <section id="peliculas" class="panel-section">
        <div class="panel-title">
          <h2>Películas registradas</h2>
          <a href="subir_pelicula.php" class="btn-primary">Añadir nueva película</a>
        </div>

        <!-- FILTROS -->
        <form method="GET" class="filters">
          <input type="text" name="buscar" placeholder="Buscar título..." value="<?= htmlspecialchars($buscar) ?>">
          <select name="anio">
            <option value="">Año</option>
            <?php for ($y = date('Y'); $y >= 1900; $y--): ?>
              <option value="<?= $y ?>" <?= $anio == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          <select name="genero">
            <option value="">Género</option>
            <?php foreach ($generos as $g): ?>
              <option value="<?= htmlspecialchars($g['genero']) ?>" <?= $genero == $g['genero'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['genero']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-filter">Filtrar</button>
          <button type="button" onclick="window.location.href='admin.php'" class="btn-clear">Limpiar filtros</button>
        </form>

        <!-- TARJETAS -->
        <div class="cards-container">
          <?php if(empty($peliculas)): ?>
            <p class="no-data">No hay películas registradas.</p>
          <?php else: foreach($peliculas as $p): ?>
            <div class="movie-card">
              <div class="poster">
                <?php if($p['poster']): ?>
                  <!-- Verificar si es URL o archivo local -->
                  <?php if (filter_var($p['poster'], FILTER_VALIDATE_URL)): ?>
                    <img src="<?= htmlspecialchars($p['poster']) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>">
                  <?php else: ?>
                    <img src="uploads/posters/<?= htmlspecialchars($p['poster']) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>">
                  <?php endif; ?>
                <?php else: ?>
                  <div class="poster-placeholder">No Poster</div>
                <?php endif; ?>
              </div>
              <div class="movie-info">
                <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                <p><strong>Género:</strong> <?= htmlspecialchars($p['genero'] ?? '—') ?></p>
                <p><strong>Año:</strong> <?= $p['anio'] ?></p>
                <p><strong>Duración:</strong> <?= $p['duracion'] ? htmlspecialchars($p['duracion']) : '—' ?></p>
                <?php if($p['descripcion']): ?>
                  <p class="descripcion"><?= htmlspecialchars(substr($p['descripcion'], 0, 100)) . (strlen($p['descripcion']) > 100 ? '...' : '') ?></p>
                <?php endif; ?>
              </div>
              <div class="movie-actions">
                <?php if($p['video']): ?>
                  <a href="uploads/videos/<?= htmlspecialchars($p['video']) ?>" target="_blank" class="btn-small btn-view">Ver video</a>
                <?php else: ?>
                  <span class="btn-small btn-disabled">Sin video</span>
                <?php endif; ?>
                <a href="editar_pelicula.php?id=<?= $p['id'] ?>" class="btn-small btn-edit">Editar</a>
                <button class="btn-small btn-delete" data-id="<?= $p['id'] ?>" data-title="<?= htmlspecialchars($p['titulo']) ?>">Eliminar</button>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if($total_paginas > 1): ?>
          <div class="pagination">
            <?php if($pagina_actual > 1): ?>
              <?php $params = $_GET; $params['pagina'] = $pagina_actual - 1; ?>
              <a href="admin.php?<?= http_build_query($params) ?>" class="page-link">« Anterior</a>
            <?php endif; ?>

            <?php 
            $inicio = max(1, $pagina_actual - 2);
            $fin = min($total_paginas, $pagina_actual + 2);
            for($i = $inicio; $i <= $fin; $i++):
              $params = $_GET; $params['pagina'] = $i; 
            ?>
              <a href="admin.php?<?= http_build_query($params) ?>" class="page-link <?= $i == $pagina_actual ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if($pagina_actual < $total_paginas): ?>
              <?php $params = $_GET; $params['pagina'] = $pagina_actual + 1; ?>
              <a href="admin.php?<?= http_build_query($params) ?>" class="page-link">Siguiente »</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </section>

      <footer class="admin-footer">
        <p>Netbeam — panel de administración</p>
      </footer>
    </main>
  </div>

  <!-- MODAL DE CONFIRMACIÓN -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
      <h3>¿Eliminar película?</h3>
      <p id="modal-message">¿Estás seguro de que deseas eliminar esta película?</p>
      <div class="modal-buttons">
        <button class="modal-btn cancel">Cancelar</button>
        <button class="modal-btn delete">Eliminar</button>
      </div>
    </div>
  </div>

  <!-- TOASTS + ELIMINACIÓN -->
  <script>
    const params = new URLSearchParams(window.location.search);
    let msg = "", type = "";
    
    if(params.get('success') === '1') { 
      msg = "✅ Película subida con éxito"; 
      type = "success"; 
    }
    if(params.get('updated') === '1') { 
      msg = "✅ Película actualizada con éxito"; 
      type = "success"; 
    }
    if(params.get('deleted') === '1') { 
      msg = "✅ Película eliminada correctamente"; 
      type = "delete"; 
    }
    if(params.get('error')) { 
      msg = "❌ " + params.get('error'); 
      type = "error"; 
    }

    // Mostrar toast
    if(msg) {
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.textContent = msg;
      document.body.appendChild(toast);
      setTimeout(() => toast.classList.add('visible'), 50);
      setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 500);
      }, 3000);
    }

    // Manejo del modal de eliminación
    const modal = document.getElementById('confirmModal');
    const modalMessage = document.getElementById('modal-message');
    const cancelBtn = modal.querySelector('.cancel');
    const deleteBtn = modal.querySelector('.delete');
    let idToDelete = null;

    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => { 
        idToDelete = btn.dataset.id;
        const title = btn.dataset.title;
        modalMessage.textContent = `¿Estás seguro de que deseas eliminar "${title}"? Esta acción no se puede deshacer.`;
        modal.classList.add('active'); 
      });
    });

    cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
    
    modal.addEventListener('click', e => {
      if(e.target === modal) modal.classList.remove('active');
    });

    deleteBtn.addEventListener('click', () => {
      if(idToDelete) {
        window.location.href = `eliminar_pelicula.php?id=${idToDelete}`;
      }
    });

    // Limpiar parámetros de la URL después de mostrar el mensaje
    if(window.location.search.includes('success') || window.location.search.includes('deleted') || window.location.search.includes('error')) {
      setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        url.searchParams.delete('deleted');
        url.searchParams.delete('updated');
        url.searchParams.delete('error');
        window.history.replaceState({}, '', url);
      }, 3500);
    }
  </script>
</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../conexion.php';

// --- Obtener datos para el dashboard ---

// Total de películas
$totalPeliculas = $conn->query("SELECT COUNT(*) as total FROM peliculas")->fetch_assoc()['total'];

// Películas por género
$generosData = $conn->query("SELECT genero, COUNT(*) as total FROM peliculas GROUP BY genero ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);

// Películas por año (últimos 10 años)
$yearsData = $conn->query("SELECT anio, COUNT(*) as total FROM peliculas WHERE anio >= YEAR(CURDATE())-10 GROUP BY anio ORDER BY anio DESC")->fetch_all(MYSQLI_ASSOC);

// Duración promedio
$promedioDuracion = $conn->query("SELECT AVG(duracion) as promedio FROM peliculas")->fetch_assoc()['promedio'];
$maxDuracion = 300; // Para calcular ancho relativo de barra
$widthDuracion = $promedioDuracion ? ($promedioDuracion / $maxDuracion) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard - Netbeam</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="admin.css">
<link rel="shortcut icon" type="image/jpg" href="../img/image.png" />
<style>
.dashboard-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-top: 20px;
}
.dashboard-card {
  flex: 1 1 250px;
  background: var(--card);
  padding: 20px;
  border-radius: 8px;
  color: #fff;
}
.dashboard-card h3 { margin-bottom: 12px; font-size: 18px; }
.counter { font-size: 32px; font-weight: bold; color: var(--accent); }

.bar-wrapper { margin-bottom: 12px; position: relative; }
.bar-wrapper span.label { display: block; margin-bottom: 4px; font-size: 13px; color: var(--muted); }
.bar-wrapper span.percentage {
  position: absolute;
  right: 6px;
  top: 0;
  font-size: 12px;
  color: #fff;
}

/* Barra */
.bar-bg {
  background: #222;
  height: 18px;
  border-radius: 9px;
  overflow: hidden;
  position: relative;
}
.bar-fill {
  height: 18px;
  border-radius: 9px 0 0 9px;
  width: 0%;
  transition: width 1.2s ease-out;
  position: relative;
}

/* Colores de barras */
.bar-genero { background: var(--accent); }
.bar-year { background: #b0060e; }
.bar-duracion { background: #f6121d; }

.bar-fill.animate {
  width: var(--width);
}

</style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="admin-logo"><img src="../img/image2.png" alt="logo"></div>
      <nav>
        <ul>
          <li><a href="dashboard.php">Dashboard</a></li> <!-- ENLACE AGREGADO -->
          <li><a href="admin.php">Lista de peliculas</a></li> <!-- ENLACE AGREGADO -->
          <li><a href="subir_pelicula.php">Añadir película</a></li> <!-- ENLACE AGREGADO -->
          <li><a href="../logout.php">Cerrar sesión</a></li>
        </ul>
      </nav>
    </aside>
  <main class="admin-main">
    <header class="admin-header">
      <h1>Dashboard</h1>
      <p>Resumen de tu catálogo de películas</p>
    </header>

    <div class="dashboard-grid">
      <!-- Total películas -->
      <div class="dashboard-card">
        <h3>Total de películas</h3>
        <div class="counter"><?= $totalPeliculas ?></div>
      </div>

      <!-- Duración promedio -->
      <div class="dashboard-card">
        <h3>Duración promedio (min)</h3>
        <div class="counter"><?= round($promedioDuracion) ?></div>
        <div class="bar-wrapper">
          <div class="bar-bg">
            <div class="bar-fill bar-duracion" style="--width: <?= $widthDuracion ?>%;"></div>
          </div>
          <span class="percentage">0%</span>
        </div>
      </div>

      <!-- Películas por género -->
      <div class="dashboard-card" style="flex-basis: 100%;">
        <h3>Películas por género</h3>
        <?php foreach($generosData as $g): 
            $width = $totalPeliculas ? ($g['total'] / $totalPeliculas) * 100 : 0;
        ?>
          <div class="bar-wrapper">
            <span class="label"><?= htmlspecialchars($g['genero']) ?> (<?= $g['total'] ?>)</span>
            <div class="bar-bg">
              <div class="bar-fill bar-genero" style="--width: <?= $width ?>%;"></div>
            </div>
            <span class="percentage">0%</span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Películas últimos 10 años -->
      <div class="dashboard-card" style="flex-basis: 100%;">
        <h3>Películas últimos 10 años</h3>
        <?php 
        $maxYearCount = max(array_column($yearsData,'total') ?: [1]);
        foreach($yearsData as $y): 
            $width = $maxYearCount ? ($y['total'] / $maxYearCount) * 100 : 0;
        ?>
          <div class="bar-wrapper">
            <span class="label"><?= $y['anio'] ?> (<?= $y['total'] ?>)</span>
            <div class="bar-bg">
              <div class="bar-fill bar-year" style="--width: <?= $width ?>%;"></div>
            </div>
            <span class="percentage">0%</span>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <footer class="admin-footer" style="margin-top:30px;">
      <p>Netbeam — panel de administración</p>
    </footer>
  </main>
</div>

<script>
  document.querySelectorAll('.bar-wrapper').forEach(wrapper => {
    const bar = wrapper.querySelector('.bar-fill');
    const percentSpan = wrapper.querySelector('.percentage');
    const targetWidth = parseFloat(getComputedStyle(bar).getPropertyValue('--width'));
    bar.classList.add('animate');

    let progress = 0;
    const interval = setInterval(() => {
      if(progress >= targetWidth){
        percentSpan.textContent = Math.round(targetWidth) + '%';
        clearInterval(interval);
      } else {
        percentSpan.textContent = Math.round(progress) + '%';
        progress += 1;
      }
    }, 10);
  });
</script>

</body>
</html>

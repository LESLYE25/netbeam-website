<?php
session_start();
require_once __DIR__ . '/../conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: admin.php');
    exit;
}

// Obtener datos actuales
$stmt = $conn->prepare("SELECT * FROM peliculas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$peli = $result->fetch_assoc();
$stmt->close();

if (!$peli) {
    die("Película no encontrada.");
}

// ---- Procesar envío POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $genero = trim($_POST['genero']);
    $descripcion = trim($_POST['descripcion']);
    $anio = intval($_POST['anio']);
    $duracion = trim($_POST['duracion']); // Ahora es string para formato HH:MM
    
    // Validar formato de duración (HH:MM)
    if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $duracion)) {
        $error = "Formato de duración inválido. Use HH:MM (ej: 01:44)";
        header('Location: editar_pelicula.php?id=' . $id . '&error=' . urlencode($error));
        exit;
    }

    // --- POSTER: URL o archivo ---
    $posterNuevo = $peli['poster'];
    
    // Si se proporciona URL de poster
    if (!empty($_POST['poster_url'])) {
        $poster_url = filter_var(trim($_POST['poster_url']), FILTER_SANITIZE_URL);
        if (filter_var($poster_url, FILTER_VALIDATE_URL)) {
            $posterNuevo = $poster_url;
            // Eliminar archivo anterior si existía
            if ($peli['poster'] && !filter_var($peli['poster'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . "/uploads/posters/{$peli['poster']}")) {
                unlink(__DIR__ . "/uploads/posters/{$peli['poster']}");
            }
        }
    }
    // Si se sube archivo de poster
    elseif (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION));
        $allowedImg = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowedImg)) {
            $posterNuevo = time() . '_' . preg_replace('/[^a-z0-9_\-\.]/i', '', $_FILES['poster_file']['name']);
            move_uploaded_file($_FILES['poster_file']['tmp_name'], __DIR__ . "/uploads/posters/$posterNuevo");
            // Eliminar archivo anterior si existía
            if ($peli['poster'] && !filter_var($peli['poster'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . "/uploads/posters/{$peli['poster']}")) {
                unlink(__DIR__ . "/uploads/posters/{$peli['poster']}");
            }
        }
    }

    // --- VIDEO ---
    $videoNuevo = $peli['video'];
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $allowedVid = ['mp4','webm','ogg'];
        if (in_array($ext, $allowedVid)) {
            $videoNuevo = time() . '_' . preg_replace('/[^a-z0-9_\-\.]/i', '', $_FILES['video']['name']);
            move_uploaded_file($_FILES['video']['tmp_name'], __DIR__ . "/uploads/videos/$videoNuevo");
            if ($peli['video'] && file_exists(__DIR__ . "/uploads/videos/{$peli['video']}")) {
                unlink(__DIR__ . "/uploads/videos/{$peli['video']}");
            }
        }
    }

    // --- Actualizar en DB ---
    $stmt = $conn->prepare("UPDATE peliculas SET titulo=?, genero=?, descripcion=?, anio=?, duracion=?, poster=?, video=? WHERE id=?");
    $stmt->bind_param("sssisssi", $titulo, $genero, $descripcion, $anio, $duracion, $posterNuevo, $videoNuevo, $id);
    
    if ($stmt->execute()) {
        header('Location: admin.php?updated=1');
    } else {
        header('Location: editar_pelicula.php?id=' . $id . '&error=' . urlencode($stmt->error));
    }
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar película - Netbeam</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="admin.css">
  <style>
    .poster-options { display: flex; gap: 20px; flex-wrap: wrap; }
    .poster-option { flex: 1; min-width: 250px; }
    .mini-poster { max-width: 200px; max-height: 300px; margin: 10px 0; }
    .current-poster { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    .preview-video { width: 100%; max-width: 300px; margin-top: 8px; }
    #durationInfo { margin-top: 5px; font-size: 14px; color: #666; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="admin-logo"><img src="../img/image2.png" alt="logo" /></div>
      <nav>
        <ul>
          <li><a href="admin.php">← Volver al panel</a></li>
        </ul>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-header">
        <h1>Editar película</h1>
        <p>Actualiza la información o reemplaza archivos existentes.</p>
      </header>

      <section class="panel-section">
        <?php if (isset($_GET['error'])): ?>
          <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <form action="editar_pelicula.php?id=<?=htmlspecialchars($id)?>" method="POST" enctype="multipart/form-data" class="form-admin" id="editForm">
          
          <div class="form-row">
            <label>Título</label>
            <input type="text" name="titulo" required value="<?=htmlspecialchars($peli['titulo'])?>">
          </div>

          <div class="form-row">
            <label>Género</label>
            <input type="text" name="genero" required placeholder="Ej: Acción, Drama, Comedia" value="<?=htmlspecialchars($peli['genero'])?>">
          </div>

          <div class="form-row">
            <label>Descripción</label>
            <textarea name="descripcion" rows="4"><?=htmlspecialchars($peli['descripcion'])?></textarea>
          </div>

          <div class="form-row">
            <label>Año</label>
            <input type="number" name="anio" min="1900" max="2100" required value="<?=htmlspecialchars($peli['anio'])?>">
          </div>

          <div class="form-row">
            <label>Duración (HH:MM)</label>
            <input type="text" name="duracion" id="duracionInput" required placeholder="Ej: 01:44, 02:30" 
                   pattern="([0-1]?[0-9]|2[0-3]):[0-5][0-9]" 
                   title="Formato HH:MM (ej: 01:44, 02:30)" 
                   value="<?=htmlspecialchars($peli['duracion'])?>">
            <small>Formato: HH:MM (ej: 01:44 para 1 hora 44 minutos)</small>
          </div>

          <!-- Poster actual -->
          <div class="form-row current-poster">
            <label>Poster actual</label><br>
            <?php if ($peli['poster']): ?>
              <?php if (filter_var($peli['poster'], FILTER_VALIDATE_URL)): ?>
                <img src="<?=htmlspecialchars($peli['poster'])?>" class="mini-poster" id="posterPreview">
                <p><small>URL: <?= htmlspecialchars($peli['poster']) ?></small></p>
              <?php else: ?>
                <img src="uploads/posters/<?=htmlspecialchars($peli['poster'])?>" class="mini-poster" id="posterPreview">
              <?php endif; ?>
            <?php else: ?>
              <span class="no-poster">Sin poster</span>
            <?php endif; ?>
          </div>

          <!-- Opciones para nuevo poster -->
          <div class="form-row">
            <label>Actualizar poster</label>
            <div class="poster-options">
              <div class="poster-option">
                <label>URL del poster</label>
                <input type="url" name="poster_url" placeholder="https://ejemplo.com/poster.jpg" 
                       onchange="document.getElementById('posterFile').value = ''">
              </div>
              <div class="poster-option">
                <label>Subir archivo</label>
                <input type="file" name="poster_file" id="posterFile" accept="image/*" 
                       onchange="previewPoster(event); document.querySelector('[name=poster_url]').value = ''">
              </div>
            </div>
          </div>

          <!-- Video actual -->
          <div class="form-row">
            <label>Video actual</label><br>
            <?php if ($peli['video']): ?>
              <video src="uploads/videos/<?=htmlspecialchars($peli['video'])?>" width="300" controls class="preview-video"></video>
            <?php else: ?>
              <span>Sin video</span>
            <?php endif; ?>
          </div>

          <!-- Nuevo video -->
          <div class="form-row">
            <label>Nuevo video (opcional)</label>
            <input type="file" name="video" id="videoFile" accept="video/*" onchange="previewVideoAndGetDuration(event)">
            <video id="videoPreview" class="preview-video" controls style="display:none;"></video>
            <div id="durationInfo"></div>
          </div>

          <div class="form-row actions">
            <button class="btn-primary" type="submit">Guardar cambios</button>
            <a href="admin.php" class="btn-secondary">Cancelar</a>
          </div>
        </form>
      </section>
    </main>
  </div>

<script>
function previewPoster(e) {
  const [file] = e.target.files;
  if (!file) return;
  const preview = document.getElementById('posterPreview');
  if (preview) {
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
  }
}

function previewVideoAndGetDuration(e) {
  const file = e.target.files[0];
  if (!file) return;
  
  const videoPreview = document.getElementById('videoPreview');
  const durationInfo = document.getElementById('durationInfo');
  const duracionInput = document.getElementById('duracionInput');
  
  // Mostrar preview del video
  videoPreview.src = URL.createObjectURL(file);
  videoPreview.style.display = 'block';
  durationInfo.textContent = 'Calculando duración...';
  durationInfo.className = 'duration-info';
  
  // Cuando los metadatos del video estén cargados, obtener la duración
  videoPreview.onloadedmetadata = function() {
    const duration = videoPreview.duration; // Duración en segundos
    if (duration && !isNaN(duration)) {
      // Convertir segundos a formato HH:MM o MM:SS según la duración
      let formattedDuration = '';
      let displayText = '';
      
      if (duration >= 3600) {
        // Más de 1 hora: formato HH:MM
        const hours = Math.floor(duration / 3600);
        const minutes = Math.floor((duration % 3600) / 60);
        formattedDuration = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        displayText = `Duración detectada: ${formattedDuration} (${hours}h ${minutes}m)`;
      } else if (duration >= 60) {
        // Menos de 1 hora pero más de 1 minuto: formato MM:SS
        const minutes = Math.floor(duration / 60);
        const seconds = Math.floor(duration % 60);
        formattedDuration = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        displayText = `Duración detectada: ${formattedDuration} (${minutes}m ${seconds}s)`;
      } else {
        // Menos de 1 minuto: formato 00:SS
        const seconds = Math.floor(duration);
        formattedDuration = `00:${seconds.toString().padStart(2, '0')}`;
        displayText = `Duración detectada: ${formattedDuration} (${seconds} segundos)`;
      }
      
      // Actualizar el campo de duración
      duracionInput.value = formattedDuration;
      
      // Mostrar información adicional
      durationInfo.textContent = displayText;
      durationInfo.className = 'duration-success';
      
      console.log(`Duración del video: ${duration} segundos -> ${formattedDuration}`);
    } else {
      durationInfo.textContent = 'No se pudo detectar la duración del video';
      durationInfo.className = 'duration-error';
    }
  };
  
  videoPreview.onerror = function() {
    durationInfo.textContent = 'Error al cargar el video. Asegúrate de que sea un formato válido.';
    durationInfo.className = 'duration-error';
  };
}

// Validación del formato de duración mejorada
document.getElementById('editForm').addEventListener('submit', function(e) {
  const duracionInput = document.getElementById('duracionInput');
  const duracion = duracionInput.value.trim();
  
  // Permitir tanto HH:MM como MM:SS
  if (!/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(duracion) && 
      !/^[0-5]?[0-9]:[0-5][0-9]$/.test(duracion)) {
    e.preventDefault();
    alert('Por favor ingresa la duración en formato HH:MM o MM:SS (ej: 01:44 para 1h44m o 10:30 para 10m30s)');
    duracionInput.focus();
    return false;
  }
  
  return true;
});

// También actualizamos el pattern del input para aceptar ambos formatos
document.addEventListener('DOMContentLoaded', function() {
  const duracionInput = document.getElementById('duracionInput');
  if (duracionInput) {
    duracionInput.pattern = '([0-1]?[0-9]|2[0-3]):[0-5][0-9]|[0-5]?[0-9]:[0-5][0-9]';
    duracionInput.title = 'Formato HH:MM o MM:SS (ej: 01:44 para 1h44m o 10:30 para 10m30s)';
  }
});
</script>
</body>
</html>
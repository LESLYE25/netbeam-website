<?php
session_start();
require_once __DIR__ . '/../conexion.php';

// Crear directorios si no existen
$upload_dirs = ['uploads/posters', 'uploads/videos'];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $titulo = trim($_POST['titulo']);
    $genero = trim($_POST['genero']);
    $anio = intval($_POST['anio']);
    $duracion = trim($_POST['duracion']);
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones básicas
    if (empty($titulo) || empty($genero) || empty($duracion)) {
        $error = "Todos los campos obligatorios deben ser completados";
    }
    // Validar formato de duración (ahora acepta HH:MM y MM:SS)
    elseif (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $duracion) && 
            !preg_match('/^[0-5]?[0-9]:[0-5][0-9]$/', $duracion)) {
        $error = "Formato de duración inválido. Use HH:MM o MM:SS (ej: 01:44 o 10:30)";
    }
    // Validar año
    elseif ($anio < 1900 || $anio > date('Y') + 5) {
        $error = "Año inválido";
    }

    if (empty($error)) {
        // --- Manejo de poster: URL o archivo ---
        $poster_value = '';
        
        // Si se proporciona URL
        if (!empty($_POST['poster_url'])) {
            $poster_url = filter_var(trim($_POST['poster_url']), FILTER_SANITIZE_URL);
            if (filter_var($poster_url, FILTER_VALIDATE_URL)) {
                $poster_value = $poster_url;
            } else {
                $error = "URL del poster no válida";
            }
        }
        // Si se sube archivo
        elseif (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $file_type = $_FILES['poster_file']['type'];
            $file_size = $_FILES['poster_file']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) { // 5MB max
                $ext = pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION);
                $poster_name = time() . '_' . uniqid() . '.' . $ext;
                $poster_path = "uploads/posters/" . $poster_name;
                
                if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $poster_path)) {
                    $poster_value = $poster_name;
                } else {
                    $error = "Error al subir el archivo de poster";
                }
            } else {
                $error = "Tipo de archivo no permitido o tamaño muy grande (máx 5MB)";
            }
        } else {
            $error = "Debes proporcionar un poster (URL o archivo)";
        }

        // --- Manejo de video ---
        $video_name = '';
        if (empty($error) && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
            $video_type = $_FILES['video']['type'];
            $video_size = $_FILES['video']['size'];
            
            if (in_array($video_type, $allowed_video_types) && $video_size <= 100 * 1024 * 1024) { // 100MB max
                $ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $video_name = time() . '_' . uniqid() . '.' . $ext;
                $video_path = "uploads/videos/" . $video_name;
                
                if (!move_uploaded_file($_FILES['video']['tmp_name'], $video_path)) {
                    $error = "Error al subir el archivo de video";
                }
            } else {
                $error = "Tipo de video no permitido o tamaño muy grande (máx 100MB)";
            }
        } else {
            $error = "Debes subir un archivo de video";
        }

        // --- Insertar en DB si no hay errores ---
        if (empty($error)) {
            // Escapar datos para SQL
            $titulo = $conn->real_escape_string($titulo);
            $genero = $conn->real_escape_string($genero);
            $descripcion = $conn->real_escape_string($descripcion);
            
            $sql = "INSERT INTO peliculas (titulo, genero, anio, duracion, descripcion, poster, video) 
                    VALUES ('$titulo', '$genero', $anio, '$duracion', '$descripcion', '$poster_value', '$video_name')";
            
            if ($conn->query($sql)) {
                $_SESSION['success_message'] = "Película agregada correctamente";
                header("Location: admin.php?success=1");
                exit;
            } else {
                $error = "Error en la base de datos: " . $conn->error;
                
                // Limpiar archivos subidos si hay error en DB
                if ($poster_value && !filter_var($poster_value, FILTER_VALIDATE_URL) && file_exists("uploads/posters/$poster_value")) {
                    unlink("uploads/posters/$poster_value");
                }
                if ($video_name && file_exists("uploads/videos/$video_name")) {
                    unlink("uploads/videos/$video_name");
                }
            }
        }
    }
}

// Mostrar mensaje de éxito de sesión si existe
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Subir Película - Netbeam</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="admin.css">
<link rel="shortcut icon" type="image/jpg" href="../img/image.png" />

<style>
.row-flex { 
    display: flex; 
    gap: 20px; 
    flex-wrap: wrap;
}
.row-flex .form-row { 
    flex: 1; 
    display: flex; 
    flex-direction: column; 
}
.preview-img, .preview-video { 
    width: 100%; 
    max-height: 250px; 
    margin-top: 8px; 
    object-fit: contain; 
    border: 1px solid #444; 
    border-radius: 5px; 
}
.poster-options { display: flex; gap: 20px; flex-wrap: wrap; }
.poster-option { flex: 1; min-width: 250px; }
.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}
.success-message {
    background: #d1edff;
    color: #155724;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}
.required::after {
    content: " *";
    color: #e50914;
}
#durationInfo { 
    margin-top: 5px; 
    font-size: 14px; 
}
.duration-success { color: #28a745; }
.duration-error { color: #dc3545; }
.duration-info { color: #666; }
@media (max-width: 768px) {
    .row-flex, .poster-options {
        flex-direction: column;
    }
}
</style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-logo"><img src="../img/image2.png" alt="logo"></div>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="admin.php">Lista de películas</a></li>
                <li><a href="subir_pelicula.php" class="active">Añadir película</a></li>
                <li><a href="../logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </aside>
    
    <main class="admin-main">
        <header class="admin-header">
            <h1>Subir nueva película</h1>
            <p>Completa los datos para agregar una película al catálogo.</p>
        </header>

        <section class="panel-section">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="subir_pelicula.php" method="POST" enctype="multipart/form-data" class="form-admin" id="uploadForm">
                <div class="form-row">
                    <label class="required">Título</label>
                    <input required name="titulo" type="text" value="<?= isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : '' ?>">
                </div>
                
                <div class="form-row">
                    <label class="required">Género</label>
                    <input required name="genero" type="text" placeholder="Ej: Acción, Drama, Comedia" 
                           value="<?= isset($_POST['genero']) ? htmlspecialchars($_POST['genero']) : '' ?>">
                </div>
                
                <div class="row-flex">
                    <div class="form-row">
                        <label class="required">Año</label>
                        <input required name="anio" type="number" min="1900" max="<?= date('Y') + 5 ?>" 
                               value="<?= isset($_POST['anio']) ? htmlspecialchars($_POST['anio']) : date('Y') ?>">
                    </div>
                    
                    <div class="form-row">
                        <label class="required">Duración (HH:MM o MM:SS)</label>
                        <input required name="duracion" id="duracionInput" type="text" placeholder="Ej: 01:44, 02:30, 10:30" 
                               pattern="([0-1]?[0-9]|2[0-3]):[0-5][0-9]|[0-5]?[0-9]:[0-5][0-9]" 
                               title="Formato HH:MM o MM:SS (ej: 01:44 para 1h44m o 10:30 para 10m30s)"
                               value="<?= isset($_POST['duracion']) ? htmlspecialchars($_POST['duracion']) : '' ?>">
                        <small>Formato: HH:MM o MM:SS (ej: 01:44 para 1h44m o 10:30 para 10m30s)</small>
                    </div>
                </div>

                <div class="form-row">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
                </div>

                <!-- Poster: URL o archivo -->
                <div class="form-row">
                    <label class="required">Poster</label>
                    <div class="poster-options">
                        <div class="poster-option">
                            <label>URL del poster</label>
                            <input type="url" name="poster_url" placeholder="https://ejemplo.com/poster.jpg"
                                   value="<?= isset($_POST['poster_url']) ? htmlspecialchars($_POST['poster_url']) : '' ?>"
                                   onchange="document.getElementById('posterFile').value = ''; document.getElementById('posterPreview').style.display = 'none';">
                            <small>Usa una URL de imagen</small>
                        </div>
                        <div class="poster-option">
                            <label>Subir archivo</label>
                            <input type="file" name="poster_file" id="posterFile" accept="image/*" 
                                   onchange="previewPoster(event); document.querySelector('[name=poster_url]').value = '';">
                            <small>Formatos: JPG, PNG, WebP (máx 5MB)</small>
                        </div>
                    </div>
                    <img id="posterPreview" class="preview-img" src="#" alt="Vista previa poster" style="display:none;">
                </div>

                <!-- Video -->
                <div class="form-row">
                    <label class="required">Video</label>
                    <input required type="file" name="video" id="videoFile" accept="video/*" onchange="previewVideoAndGetDuration(event)">
                    <small>Formatos: MP4, WebM, OGG (máx 100MB)</small>
                    <video id="videoPreview" class="preview-video" controls style="display:none;"></video>
                    <div id="durationInfo"></div>
                </div>

                <div class="form-row actions">
                    <button class="btn-primary" type="submit">Subir película</button>
                    <a href="admin.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
function previewPoster(event) {
    const output = document.getElementById('posterPreview');
    if (event.target.files && event.target.files[0]) {
        output.src = URL.createObjectURL(event.target.files[0]);
        output.style.display = 'block';
    }
}

function previewVideoAndGetDuration(event) {
    const file = event.target.files[0];
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
document.getElementById('uploadForm').addEventListener('submit', function(e) {
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
    
    // Validar que se haya proporcionado poster (URL o archivo)
    const posterUrl = document.querySelector('[name="poster_url"]').value;
    const posterFile = document.getElementById('posterFile').files[0];
    
    if (!posterUrl && !posterFile) {
        e.preventDefault();
        alert('Debes proporcionar un poster (URL o archivo)');
        return false;
    }
    
    // Validar que se haya subido video
    const videoFile = document.getElementById('videoFile').files[0];
    if (!videoFile) {
        e.preventDefault();
        alert('Debes subir un archivo de video');
        return false;
    }
    
    return true;
});
</script>
</body>
</html>
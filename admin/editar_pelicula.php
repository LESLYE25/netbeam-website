<?php
session_start();
require_once __DIR__ . '/../conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: admin.php');
    exit;
}

// Obtener datos actuales de la película
$stmt = $conn->prepare("SELECT * FROM peliculas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$peli = $result->fetch_assoc();
$stmt->close();

if (!$peli) {
    die("Película no encontrada.");
}

// Obtener lista de géneros (necesario para el dropdown)
$generos_result = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre ASC");

// Los géneros actuales de la película están almacenados como una cadena separada por comas
$generos_actuales_array = explode(', ', $peli['genero'] ?? '');

// ---- Procesar envío POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $titulo = trim($_POST['titulo']);
    $generos_seleccionados = isset($_POST['generos']) ? $_POST['generos'] : [];
    // Guarda hasta 3 géneros como una cadena separada por comas
    $genero_cadena = implode(', ', array_slice($generos_seleccionados, 0, 3)); 
    
    $descripcion = trim($_POST['descripcion'] ?? '');
    $anio = intval($_POST['anio']);
    $duracion = trim($_POST['duracion']);

    // Validar formato de duración
    if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $duracion) && 
        !preg_match('/^[0-5]?[0-9]:[0-5][0-9]$/', $duracion)) {
        $error = "Formato de duración inválido. Use HH:MM o MM:SS (ej: 01:44 o 10:30)";
        header('Location: editar_pelicula.php?id=' . $id . '&error=' . urlencode($error));
        exit;
    }

    // --- Poster ---
    $posterNuevo = $peli['poster'];
    $error = '';

    if (!empty($_POST['poster_url'])) {
        // Lógica para URL del poster
        $poster_url = filter_var(trim($_POST['poster_url']), FILTER_SANITIZE_URL);
        if (filter_var($poster_url, FILTER_VALIDATE_URL)) {
            $posterNuevo = $poster_url;
            // Eliminar poster anterior si era un archivo local
            if ($peli['poster'] && !filter_var($peli['poster'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . "/uploads/posters/{$peli['poster']}")) {
                unlink(__DIR__ . "/uploads/posters/{$peli['poster']}");
            }
        } else {
            $error = "URL del poster no válida";
        }
    } elseif (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        // Lógica para subir nuevo archivo de poster
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type = $_FILES['poster_file']['type'];
        $file_size = $_FILES['poster_file']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
            $ext = pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION);
            $poster_name = time() . '_' . uniqid() . '.' . $ext;
            $poster_path = __DIR__ . "/uploads/posters/" . $poster_name;

            if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $poster_path)) {
                $posterNuevo = $poster_name;
                // Eliminar poster anterior si era un archivo local
                if ($peli['poster'] && !filter_var($peli['poster'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . "/uploads/posters/{$peli['poster']}")) {
                    unlink(__DIR__ . "/uploads/posters/{$peli['poster']}");
                }
            } else {
                $error = "Error al subir el archivo de poster";
            }
        } else {
            $error = "Tipo de archivo de poster no permitido o tamaño muy grande (máx 5MB)";
        }
    }
    // Si no se proporcionó ni URL ni archivo, se mantiene el poster original.
    
    if (!empty($error)) {
        header('Location: editar_pelicula.php?id=' . $id . '&error=' . urlencode($error));
        exit;
    }

    // --- Video ---
    $videoNuevo = $peli['video'];
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        // Lógica para subir nuevo video
        $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
        $video_type = $_FILES['video']['type'];
        $video_size = $_FILES['video']['size'];
            
        if (in_array($video_type, $allowed_video_types) && $video_size <= 100 * 1024 * 1024) {
            $ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
            $video_name = time() . '_' . uniqid() . '.' . $ext;
            $video_path = __DIR__ . "/uploads/videos/" . $video_name;
            
            if (move_uploaded_file($_FILES['video']['tmp_name'], $video_path)) {
                $videoNuevo = $video_name;
                // Eliminar video anterior
                if ($peli['video'] && file_exists(__DIR__ . "/uploads/videos/{$peli['video']}")) {
                    unlink(__DIR__ . "/uploads/videos/{$peli['video']}");
                }
            } else {
                $error = "Error al subir el archivo de video";
            }
        } else {
            $error = "Tipo de video no permitido o tamaño muy grande (máx 100MB)";
        }
    }

    if (!empty($error)) {
        header('Location: editar_pelicula.php?id=' . $id . '&error=' . urlencode($error));
        exit;
    }

    // --- Actualizar en DB ---
    // NOTA: se usa $genero_cadena en lugar de $genero
    $stmt = $conn->prepare("UPDATE peliculas SET titulo=?, genero=?, descripcion=?, anio=?, duracion=?, poster=?, video=? WHERE id=?");
    $stmt->bind_param("sssisssi", $titulo, $genero_cadena, $descripcion, $anio, $duracion, $posterNuevo, $videoNuevo, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Película actualizada correctamente.";
        header('Location: admin.php?updated=1');
    } else {
        header('Location: editar_pelicula.php?id=' . $id . '&error=' . urlencode("Error en DB: " . $stmt->error));
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
        /* Agregamos el estilo de la vista previa de video */
        .preview-video { width: 100%; max-width: 300px; max-height: 250px; margin-top: 8px; object-fit: contain; border: 1px solid #444; border-radius: 5px; }
        .poster-options { display: flex; gap: 20px; flex-wrap: wrap; }
        .poster-option { flex: 1; min-width: 250px; }
        .mini-poster { max-width: 200px; max-height: 300px; margin: 10px 0; object-fit: cover; }
        .current-poster { background: #1a1a1a; padding: 15px; border-radius: 5px; }
        .error-message { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        
        /* Estilos del Dropdown Personalizado (Copiar de subir_pelicula.php si no están en admin.css) */
        /* Si los estilos ya están en admin.css no es necesario, pero los dejo aquí por si acaso */
        .custom-dropdown { position: relative; width: 100%; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; color: #fff; cursor: pointer; user-select: none; transition: border 0.3s; }
        .custom-dropdown:hover { border-color: #e50914; }
        .dropdown-selected { padding: 10px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
        .dropdown-selected::after { content: "▼"; font-size: 12px; color: #aaa; margin-left: 10px; transition: transform 0.3s; }
        .custom-dropdown.active .dropdown-selected::after { transform: rotate(-180deg); color: #e50914; }
        .dropdown-options { display: none; position: absolute; top: 100%; left: 0; width: 100%; background: #222; border: 1px solid #333; border-radius: 8px; margin-top: 4px; z-index: 10; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.4); }
        .custom-dropdown.active .dropdown-options { display: block; }
        .dropdown-option { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #333; transition: background 0.2s; }
        .dropdown-option:last-child { border-bottom: none; }
        .dropdown-option:hover { background: #333; }
        .dropdown-option input[type="checkbox"] { display: none; }
        .dropdown-option span { flex: 1; }
        .dropdown-option input:checked + span { color: #e50914; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo"><img src="../img/image2.png" alt="logo" /></div>
            <nav>
                <ul><li><a href="admin.php">← Volver al panel</a></li></ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Editar película: <?=htmlspecialchars($peli['titulo'])?></h1>
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
                        <label>Géneros (Máx 3)</label>
                        <div class="custom-dropdown" id="dropdownGeneros">
                            <div class="dropdown-selected" onclick="toggleGenerosDropdown()">
                                <?= empty($peli['genero']) ? 'Selecciona hasta 3 géneros' : htmlspecialchars($peli['genero']) ?>
                            </div>
                            <div class="dropdown-options" id="dropdownOptions">
                                <?php if ($generos_result && $generos_result->num_rows > 0): ?>
                                    <?php while($g = $generos_result->fetch_assoc()): ?>
                                        <label class="dropdown-option">
                                            <input type="checkbox" name="generos[]" value="<?= htmlspecialchars($g['nombre']) ?>"
                                                <?= in_array($g['nombre'], $generos_actuales_array) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($g['nombre']) ?></span>
                                        </label>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p>No hay géneros disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
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
                        <label>Duración (HH:MM o MM:SS)</label>
                        <input type="text" name="duracion" id="duracionInput" required placeholder="Ej: 01:44 o 10:30" 
                                pattern="([0-1]?[0-9]|2[0-3]):[0-5][0-9]|[0-5]?[0-9]:[0-5][0-9]" 
                                title="Formato HH:MM o MM:SS (ej: 01:44 o 10:30)"
                                value="<?=htmlspecialchars($peli['duracion'])?>">
                        <small id="durationInfo"></small>
                    </div>

                    <div class="form-row current-poster">
                        <label>Poster actual</label><br>
                        <?php if ($peli['poster']): 
                            $poster_src = filter_var($peli['poster'], FILTER_VALIDATE_URL) ? htmlspecialchars($peli['poster']) : 'uploads/posters/' . htmlspecialchars($peli['poster']); ?>
                            <img src="<?= $poster_src ?>" class="mini-poster" id="currentPosterPreview">
                        <?php else: ?>
                            <span id="currentPosterPreview">Sin poster</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <label>Actualizar poster (URL)</label>
                        <input type="url" name="poster_url" placeholder="URL del poster" onchange="document.querySelector('[name=poster_file]').value = ''">
                    </div>
                    <div class="form-row">
                        <label>Actualizar poster (Archivo)</label>
                        <input type="file" name="poster_file" accept="image/*" onchange="document.querySelector('[name=poster_url]').value = ''">
                    </div>

                    <div class="form-row">
                        <label>Video actual</label><br>
                        <?php if ($peli['video']): ?>
                            <video src="uploads/videos/<?=htmlspecialchars($peli['video'])?>" class="preview-video" controls id="currentVideoPreview"></video>
                        <?php else: ?>
                            <span>Sin video</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <label>Nuevo video (opcional)</label>
                        <input type="file" name="video" id="videoFile" accept="video/*" onchange="previewVideoAndGetDuration(event)">
                        <video id="videoPreview" class="preview-video" controls style="display:none;"></video>
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
        // Función para el dropdown de géneros
        function toggleGenerosDropdown() {
            document.getElementById("dropdownGeneros").classList.toggle("active");
        }

        // Cierra el menú si haces clic fuera
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("dropdownGeneros");
            if (dropdown && !dropdown.contains(e.target)) dropdown.classList.remove("active");
        });

        // Actualiza el texto del campo y limita la selección a 3 géneros
        document.addEventListener("change", function(e) {
            if (e.target.name === "generos[]") {
                const checked = document.querySelectorAll('input[name="generos[]"]:checked');
                if (checked.length > 3) {
                    e.target.checked = false;
                    alert("Solo puedes seleccionar hasta 3 géneros.");
                    return;
                }
                const selectedNames = Array.from(checked).map(cb => cb.value);
                document.querySelector(".dropdown-selected").textContent =
                    selectedNames.length > 0 ? selectedNames.join(", ") : "Selecciona hasta 3 géneros";
            }
        });

        // Función para la vista previa del video y obtener duración (copiada de subir_pelicula.php)
        function previewVideoAndGetDuration(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const videoPreview = document.getElementById('videoPreview');
            const durationInfo = document.getElementById('durationInfo');
            const duracionInput = document.getElementById('duracionInput');
            
            // Ocultar el video actual al subir uno nuevo
            document.getElementById('currentVideoPreview').style.display = 'none';

            videoPreview.src = URL.createObjectURL(file);
            videoPreview.style.display = 'block';
            durationInfo.textContent = 'Calculando duración...';
            durationInfo.className = 'duration-info';
            
            videoPreview.onloadedmetadata = function() {
                const duration = videoPreview.duration;
                if (duration && !isNaN(duration)) {
                    let formattedDuration = '';
                    
                    if (duration >= 3600) {
                        const hours = Math.floor(duration / 3600);
                        const minutes = Math.floor((duration % 3600) / 60);
                        formattedDuration = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
                    } else if (duration >= 60) {
                        const minutes = Math.floor(duration / 60);
                        const seconds = Math.floor(duration % 60);
                        formattedDuration = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    } else {
                        const seconds = Math.floor(duration);
                        formattedDuration = `00:${seconds.toString().padStart(2, '0')}`;
                    }
                    
                    duracionInput.value = formattedDuration;
                    durationInfo.textContent = `Duración detectada: ${formattedDuration}. Campo actualizado.`;
                    durationInfo.className = 'duration-success';
                } else {
                    durationInfo.textContent = 'No se pudo detectar la duración del video.';
                    durationInfo.className = 'duration-error';
                }
            };
        }

        // Inicializar el texto del dropdown con los géneros seleccionados al cargar
        document.addEventListener('DOMContentLoaded', () => {
            const checked = document.querySelectorAll('input[name="generos[]"]:checked');
            const selectedNames = Array.from(checked).map(cb => cb.value);
            const dropdownSelected = document.querySelector(".dropdown-selected");
            if (dropdownSelected) {
                dropdownSelected.textContent = selectedNames.length > 0 ? selectedNames.join(", ") : "Selecciona hasta 3 géneros";
            }
        });
    </script>
</body>
</html>
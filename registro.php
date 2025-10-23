<?php
include("conexion.php");

// Procesar cuando envíen el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre']; // NUEVO
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Encriptar la contraseña
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Preparar SQL con nombre incluido y rol por defecto 'usuario'
    $rolPorDefecto = 'usuario';
    $sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $passwordHash, $rolPorDefecto);

    if ($stmt->execute()) {
        // Registro exitoso -> mostrar animación y redirigir a login.php
        $stmt->close();
        $conn->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Registro exitoso</title>
            <style>
                /* Fondo totalmente negro, bordes blancos y tonos rojos */
                :root{--nb-red:#e50914;--nb-dark:#000}
                body{margin:0;background:var(--nb-dark);color:#fff;font-family:Arial,Helvetica,sans-serif}
                .overlay{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:20px}
                .card{background:transparent;padding:30px;border-radius:12px;border:2px solid rgba(255,255,255,0.08);text-align:center;width:520px}
                .check{width:160px;height:160px;margin:0 auto;display:block}
                .message{font-size:22px;margin-top:18px;font-weight:700}
                .sub{color:rgba(255,255,255,0.75);margin-top:8px}
                /* Toast */
                .toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:var(--nb-red);padding:12px 20px;border-radius:8px;color:#fff;opacity:0;transition:opacity .4s,transform .4s}
                .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
                /* small accent line */
                .accent{height:6px;background:linear-gradient(90deg,var(--nb-red),#ff6b6b);border-radius:4px;margin-top:18px}
            </style>
        </head>
        <body>
            <div class="overlay">
                <div class="card">
                    <svg class="check" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="g" x1="0" x2="1">
                                <stop offset="0" stop-color="var(--nb-red)" />
                                <stop offset="1" stop-color="#ff6b6b" />
                            </linearGradient>
                        </defs>
                        <!-- outer white stroke circle subtle -->
                        <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="4" />
                        <!-- animated red ring -->
                        <circle cx="60" cy="60" r="48" fill="none" stroke="url(#g)" stroke-width="6" stroke-dasharray="302" stroke-dashoffset="302" id="ring" stroke-linecap="round" />
                        <!-- white tick -->
                        <path d="M36 62 L52 78 L86 44" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" id="tick" stroke-dasharray="100" stroke-dashoffset="100" />
                    </svg>
                    <div class="message">Registro exitoso ✅</div>
                    <div class="sub">Nos vemos pronto — te llevamos al inicio de sesión...</div>
                    <div class="accent"></div>
                </div>
            </div>
            <div class="toast" id="toast">Cuenta creada correctamente</div>
            <script>
                // Animaciones SVG con acento rojo
                const ring = document.getElementById('ring');
                const tick = document.getElementById('tick');
                setTimeout(()=>{
                    ring.style.transition = 'stroke-dashoffset 0.9s cubic-bezier(.2,.8,.2,1)';
                    ring.style.strokeDashoffset = '0';
                },150);
                setTimeout(()=>{
                    tick.style.transition = 'stroke-dashoffset 0.6s cubic-bezier(.2,.8,.2,1)';
                    tick.style.strokeDashoffset = '0';
                },950);

                // Mostrar toast
                const toast = document.getElementById('toast');
                setTimeout(()=>{ toast.classList.add('show'); }, 1000);

                // Redirigir después de 3s
                setTimeout(()=>{ window.location.href = 'login.php'; }, 3000);
            </script>
        </body>
        </html>
        <?php
        exit;
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        // Mostrar error inline
        echo "<script>window.onload=function(){alert('❌ Error al crear cuenta: " . addslashes($errorMsg) . "'); window.history.back();}</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrarse</title>
    <link rel="stylesheet" href="./css/cuenta.css" />
    <link rel="shortcut icon" type="image/jpg" href="img/image.png" />
</head>
<body>
<div class="contenido">
    <nav>
        <img class="logo" src="./img/image2.png" alt="netbeamLogo" />
    </nav>
    <div class="caja">
        <h2>Registrarse</h2>

        <!-- FORMULARIO -->
        <form action="registro.php" method="POST" class="form">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Crear</button>
        </form>

    </div>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/auth.php';

$publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($correo) && !empty($password)) {
        // Buscamos al usuario por su correo en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo AND estado = TRUE");
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();

        // Verificamos si el usuario existe y si la contraseña coincide con el hash seguro
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Guardamos los datos clave en la sesión del navegador
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['nombres'] = $usuario['nombres'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['p_matricular'] = !empty($usuario['permiso_matricular'] ?? false);
            $_SESSION['p_cobrar'] = !empty($usuario['permiso_cobrar'] ?? false);
            $_SESSION['p_editar'] = !empty($usuario['permiso_editar'] ?? false);
            registrar_auditoria('inicio_sesion', 'Inicio de sesión correcto.');

            // Redirigimos según el rol (Director o Asistente)
            if ($usuario['rol'] === 'director') {
                $publicPath = defined('APP_PUBLIC_PATH') ? APP_PUBLIC_PATH : '';
                header("Location: {$publicPath}director/panel.php");
            } else {
                $publicPath = defined('APP_PUBLIC_PATH') ? APP_PUBLIC_PATH : '';
                header("Location: {$publicPath}asistente/panel.php");
            }
            exit;
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
    } else {
        $error = "Por favor completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #172b4d;
            --muted: #6b7a90;
            --blue: #1261a0;
            --aqua: #2bb3a3;
            --mist: #edf5f7;
        }

        body.login-page {
            min-height: 100vh;
            color: var(--ink);
            background: linear-gradient(rgba(255, 255, 255, .48), rgba(255, 255, 255, .48)), url("<?= htmlspecialchars($publicPath) ?>assets/images/institucion.jpg.jfif") center / cover fixed;
            overflow: auto;
        }

        .login-page::before,
        .login-page::after {
            position: fixed;
            content: "";
            width: 100%;
            height: 100%;
            background: transparent;
            pointer-events: none;
        }

        .login-page::before {
            inset: 0;
        }

        .login-page::after {
            inset: 0;
        }

        .login-shell {
            position: relative;
            z-index: 1;
            width: min(100% - 2rem, 490px);
            animation: arrive .65s ease-out both;
        }

        .login-card {
            position: relative;
            border: 1px solid rgba(255, 255, 255, .9);
            border-radius: 3px;
            background: rgba(247, 247, 247, .92);
            box-shadow: 0 14px 30px rgba(23, 43, 77, .15);
            overflow: hidden;
            margin-top: 1.35rem;
        }

        .login-card::before {
            display: block;
            height: 4px;
            content: "";
            background: linear-gradient(90deg, #1264f5, #2bb3a3, #f4be8d);
        }

        .brand-mark {
            display: none;
        }

        .login-card .text-center {
            display: none;
        }

        .institution-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: .9rem;
            margin-bottom: 1rem;
            text-align: left;
        }

        .institution-logo {
            flex: 0 0 112px;
            width: 112px;
            height: 112px;
            margin: 0;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 6px 14px rgba(23, 43, 77, .18);
            animation: logoFloat 3.5s ease-in-out infinite;
        }

        .institution-name {
            flex: 1;
            margin: 0;
            color: var(--ink);
            font-size: 1.28rem;
            font-weight: 700;
            line-height: 1.3;
            animation: fadeUp .7s .15s ease-out both;
        }

        @media (max-width: 420px) {
            .institution-header {
                gap: .6rem;
            }

            .institution-logo {
                flex-basis: 86px;
                width: 86px;
                height: 86px;
            }

            .institution-name {
                font-size: 1rem;
            }
        }

        .login-subtitle,
        .form-label {
            color: var(--muted);
        }

        .form-control {
            min-height: 52px;
            padding: .7rem .9rem;
            border-color: #b9b9b9;
            border-radius: 2px;
            font-size: 1rem;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .form-control:focus {
            border-color: var(--aqua);
            box-shadow: 0 0 0 .25rem rgba(43, 179, 163, .16);
            transform: translateY(-1px);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .form-control {
            padding-right: 78px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            color: #6b7a90;
            background: transparent;
            font-size: .75rem;
            font-weight: 700;
        }

        .login-button {
            min-height: 52px;
            border: 0;
            border-radius: 2px;
            background: #1264f5;
            box-shadow: 0 2px 4px rgba(18, 97, 160, .2);
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
            font-size: 1rem;
        }

        .login-button:hover {
            background: #0c53d6;
            box-shadow: 0 3px 7px rgba(18, 97, 160, .28);
            transform: translateY(-2px);
        }

        .forgot-link {
            display: block;
            margin-top: .55rem;
            color: #1264c7;
            font-size: .9rem;
            text-align: center;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        @keyframes arrive {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
    </style>
</head>
<body class="login-page d-flex align-items-center justify-content-center py-4">
    <main class="login-shell">
        <header class="institution-header">
            <img class="institution-logo" src="<?= htmlspecialchars($publicPath) ?>assets/images/logo-institucion.jpg.jpg" alt="Logo de la Institución Educativa Divino Maestro">
            <h1 class="institution-name">Institución Educativa &quot;Divino Maestro&quot; de Mollepampa-Cajamarca</h1>
        </header>
        <div class="login-card p-3 p-sm-4">
            <div class="text-center mb-4">
                <span class="brand-mark mb-3">DM</span>
                <h1 class="login-title h3 fw-bold mb-2">Divino Maestro</h1>
                <p class="login-subtitle mb-0">Ingresa para continuar con tu jornada</p>
            </div>

            <?php if (!empty($error)): ?>
                <p class="text-danger text-center small mb-3" role="alert"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        
            <form method="POST">
                <div class="mb-1">
                    <label for="correo" class="visually-hidden">Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="Correo electrónico" autocomplete="email" required>
                </div>
                <div class="mb-2">
                    <label for="password" class="visually-hidden">Contraseña</label>
                    <div class="password-wrap">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="togglePassword">Mostrar</button>
                    </div>
                </div>
                <button type="submit" class="login-button btn btn-primary w-100 fw-semibold">Ingresar</button>
                <a class="forgot-link" href="mailto:administracion@colegio.com?subject=Recuperar%20contraseña">¿Olvidaste tu contraseña?</a>
            </form>
        </div>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassword.textContent = isPassword ? 'Ocultar' : 'Mostrar';
        });
    </script>
</body>
</html>
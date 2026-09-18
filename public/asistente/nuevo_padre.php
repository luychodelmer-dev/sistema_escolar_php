<?php
session_start();

// 1. Requerimos la configuración de la base de datos
require_once '../../config/database.php';
require_once '../../app/auth.php';

// Validar seguridad básica
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require_permission('p_editar');

$mensaje_exito = '';
$mensaje_error = '';

// 2. Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = trim($_POST['dni']);
    $nombres = trim($_POST['nombres']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);
    $nombre_padre_adicional = trim($_POST['nombre_padre_adicional']);
    $direccion = trim($_POST['direccion']);
    $celular_actual = trim($_POST['celular_actual']);
    $celular_padre = trim($_POST['celular_padre']);
    $celular_madre = trim($_POST['celular_madre']);
    $nro_tarjeta_apafa = trim($_POST['nro_tarjeta_apafa']) ?: null; // Si está vacío, se guarda como NULL

    if (!empty($dni) && !empty($nombres) && !empty($apellido_paterno)) {
        try {
            $sql = "INSERT INTO apoderados (
                        dni, nombres, apellido_paterno, apellido_materno, 
                        nombre_padre_adicional, direccion, celular_actual, 
                        celular_padre, celular_madre, nro_tarjeta_apafa
                    ) VALUES (
                        :dni, :nombres, :ap, :am, :padre_adic, :dir, :cel_act, :cel_p, :cel_m, :tarjeta
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'dni' => $dni,
                'nombres' => $nombres,
                'ap' => $apellido_paterno,
                'am' => $apellido_materno,
                'padre_adic' => $nombre_padre_adicional,
                'dir' => $direccion,
                'cel_act' => $celular_actual,
                'cel_p' => $celular_padre,
                'cel_m' => $celular_madre,
                'tarjeta' => $nro_tarjeta_apafa
            ]);

            registrar_auditoria('crear_apoderado', 'Registró el apoderado con DNI ' . $dni . '.');

            $mensaje_exito = "¡Apoderado registrado correctamente!";
        } catch (PDOException $e) {
            // Manejo de errores (ej. si el DNI o Tarjeta APAFA ya existe)
            if ($e->getCode() == 23505) { // Código de error de violación de unicidad en PostgreSQL
                $mensaje_error = "Error: El DNI o el Número de Tarjeta APAFA ya están registrados en el sistema.";
            } else {
                $mensaje_error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } else {
        $mensaje_error = "Por favor, completa los campos obligatorios (DNI, Nombres y Apellido Paterno).";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Apoderado - Sistema APAFA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="padres.php">⬅ Volver al Directorio</a>
            <span class="text-white">Módulo de Familias y Apoderados</span>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="card shadow-sm border-0 mx-auto" style="max-width: 900px;">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Registrar Nuevo Apoderado</h4>
            </div>
            <div class="card-body p-4">
                
                <?php if ($mensaje_exito): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Exito!</strong> <?= htmlspecialchars($mensaje_exito) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Atención:</strong> <?= htmlspecialchars($mensaje_error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <h5 class="text-secondary border-bottom pb-2 mb-3">Datos de Identidad</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">DNI *</label>
                            <input type="text" name="dni" class="form-control" maxlength="8" required placeholder="Ej: 71234567">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nombres Completos *</label>
                            <input type="text" name="nombres" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Apellido Paterno *</label>
                            <input type="text" name="apellido_paterno" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nombre Padre Adicional <small class="text-muted">(Referencia complementaria)</small></label>
                            <input type="text" name="nombre_padre_adicional" class="form-control" placeholder="Ej: Juan Perez (Padre biológico)">
                        </div>
                    </div>

                    <h5 class="text-secondary border-bottom pb-2 mt-4 mb-3">Datos de Contacto y APAFA</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Dirección de Domicilio</label>
                            <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. Los Rosales 123">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-primary">Celular Actual (Principal)</label>
                            <input type="text" name="celular_actual" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Celular Padre</label>
                            <input type="text" name="celular_padre" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Celular Madre</label>
                            <input type="text" name="celular_madre" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="form-label fw-bold text-success">N° Tarjeta APAFA</label>
                            <input type="text" name="nro_tarjeta_apafa" class="form-control border-success" placeholder="Código de la tarjeta">
                        </div>
                    </div>

                    <div class="mt-5 text-end">
                        <a href="padres.php" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Guardar Apoderado</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Script de Bootstrap para alertas -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
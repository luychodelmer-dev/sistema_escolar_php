<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_auth(['asistente', 'director']);

$puedeRegistrar = has_permission('p_matricular');
$error = '';
$mensaje = isset($_GET['guardado']) ? 'Estudiante registrado correctamente.' : '';
$busqueda = trim($_GET['buscar'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('p_matricular');

    $datos = [
        'dni' => trim($_POST['dni'] ?? '') ?: null,
        'nombres' => trim($_POST['nombres'] ?? ''),
        'apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
        'apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
        'sexo' => trim($_POST['sexo'] ?? '') ?: null,
        'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? '') ?: null,
        'codigo_minedu' => trim($_POST['codigo_minedu'] ?? '') ?: null,
        'informacion_medica' => trim($_POST['informacion_medica'] ?? '') ?: null,
        'apoderado_id' => filter_input(INPUT_POST, 'apoderado_id', FILTER_VALIDATE_INT) ?: null,
        'vinculo' => trim($_POST['vinculo'] ?? '') ?: null,
    ];

    if ($datos['nombres'] === '' || $datos['apellido_paterno'] === '') {
        $error = 'Completa los nombres y el apellido paterno.';
    } elseif ($datos['dni'] !== null && !preg_match('/^\d{8}$/', $datos['dni'])) {
        $error = 'El DNI debe tener 8 números.';
    } elseif ($datos['apoderado_id'] && !$datos['vinculo']) {
        $error = 'Indica el vínculo con el apoderado.';
    } else {
        try {
            $pdo->beginTransaction();
            $estudianteStmt = $pdo->prepare(
                'INSERT INTO estudiantes
                    (dni, nombres, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, codigo_minedu, informacion_medica)
                 VALUES
                    (:dni, :nombres, :apellido_paterno, :apellido_materno, :sexo, :fecha_nacimiento, :codigo_minedu, :informacion_medica)
                 RETURNING id'
            );
            $estudianteStmt->execute([
                'dni' => $datos['dni'],
                'nombres' => $datos['nombres'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'],
                'sexo' => $datos['sexo'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'codigo_minedu' => $datos['codigo_minedu'],
                'informacion_medica' => $datos['informacion_medica'],
            ]);
            $estudianteId = $estudianteStmt->fetchColumn();

            if ($datos['apoderado_id']) {
                $relacionStmt = $pdo->prepare(
                    'INSERT INTO apoderado_estudiante
                        (apoderado_id, estudiante_id, vinculo, es_principal, vive_con_estudiante, autorizado_recoger)
                     VALUES (:apoderado_id, :estudiante_id, :vinculo, TRUE, FALSE, FALSE)'
                );
                $relacionStmt->execute([
                    'apoderado_id' => $datos['apoderado_id'],
                    'estudiante_id' => $estudianteId,
                    'vinculo' => $datos['vinculo'],
                ]);
            }

            $pdo->commit();
            registrar_auditoria('crear_estudiante', 'Registró al estudiante ' . $datos['nombres'] . ' ' . $datos['apellido_paterno'] . '.');
            header('Location: estudiantes.php?guardado=1');
            exit;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = str_contains($exception->getMessage(), 'duplicate key')
                ? 'El DNI o código MINEDU ya está registrado.'
                : 'No se pudo guardar el estudiante.';
        }
    }
}

$apoderados = $pdo->query(
    'SELECT id, nombres, apellido_paterno, apellido_materno, dni
     FROM apoderados ORDER BY apellido_paterno, nombres'
)->fetchAll();

$sql =
    'SELECT e.id, e.dni, e.nombres, e.apellido_paterno, e.apellido_materno,
            e.sexo, e.fecha_nacimiento, e.codigo_minedu,
            a.nombres AS apoderado_nombres, a.apellido_paterno AS apoderado_apellido
     FROM estudiantes e
     LEFT JOIN apoderado_estudiante ae ON ae.estudiante_id = e.id
     LEFT JOIN apoderados a ON a.id = ae.apoderado_id';
$params = [];
if ($busqueda !== '') {
    $sql .= ' WHERE e.dni ILIKE :buscar OR e.nombres ILIKE :buscar
              OR e.apellido_paterno ILIKE :buscar OR e.apellido_materno ILIKE :buscar';
    $params['buscar'] = "%$busqueda%";
}
$sql .= ' ORDER BY e.apellido_paterno, e.nombres';
$estudiantesStmt = $pdo->prepare($sql);
$estudiantesStmt->execute($params);
$estudiantes = $estudiantesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estudiantes - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f4f7fb; color: #172b4d; } .page-header { background: #172b4d; color: #fff; } .card { border: 0; border-radius: 12px; } .required::after { content: ' *'; color: #dc3545; } .institution-brand { display: flex; align-items: center; gap: .6rem; white-space: nowrap; } .institution-brand img { width: 42px; height: 42px; object-fit: contain; border-radius: 50%; background: #fff; } .institution-brand span { font-size: 1.1rem; white-space: nowrap; } .header-user-photo { width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(255, 255, 255, .85); border-radius: 50%; } @media (max-width: 768px) { .institution-brand span { font-size: .95rem; } }</style>
</head>
<body>
<?php render_app_header('panel.php', 'logout.php'); ?>
<main class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><h1 class="h3 mb-1">Estudiantes</h1><p class="text-muted mb-0">Registra estudiantes y relaciona a su apoderado principal.</p></div>
        <a href="panel.php" class="btn btn-outline-primary">Volver al panel</a>
    </div>
    <?php if ($mensaje !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($puedeRegistrar): ?><section class="card shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">Nuevo estudiante</h2><form method="POST" class="row g-3">
        <div class="col-md-3"><label class="form-label" for="dni">DNI</label><input class="form-control" id="dni" name="dni" inputmode="numeric" maxlength="8"></div>
        <div class="col-md-3"><label class="form-label required" for="nombres">Nombres</label><input class="form-control" id="nombres" name="nombres" required></div>
        <div class="col-md-3"><label class="form-label required" for="apellido_paterno">Apellido paterno</label><input class="form-control" id="apellido_paterno" name="apellido_paterno" required></div>
        <div class="col-md-3"><label class="form-label" for="apellido_materno">Apellido materno</label><input class="form-control" id="apellido_materno" name="apellido_materno"></div>
        <div class="col-md-3"><label class="form-label" for="sexo">Sexo</label><select class="form-select" id="sexo" name="sexo"><option value="">Seleccionar</option><option value="F">Femenino</option><option value="M">Masculino</option></select></div>
        <div class="col-md-3"><label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label><input class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" type="date"></div>
        <div class="col-md-3"><label class="form-label" for="codigo_minedu">Código MINEDU</label><input class="form-control" id="codigo_minedu" name="codigo_minedu"></div>
        <div class="col-md-3"><label class="form-label" for="vinculo">Vínculo</label><input class="form-control" id="vinculo" name="vinculo" placeholder="Madre, padre, tutor..."></div>
        <div class="col-md-8"><label class="form-label" for="apoderado_id">Apoderado principal</label><select class="form-select" id="apoderado_id" name="apoderado_id"><option value="">Sin asignar</option><?php foreach ($apoderados as $apoderado): ?><option value="<?= (int) $apoderado['id'] ?>"><?= htmlspecialchars($apoderado['apellido_paterno'].' '.$apoderado['apellido_materno'].', '.$apoderado['nombres'].' - DNI '.$apoderado['dni']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label" for="informacion_medica">Información médica</label><input class="form-control" id="informacion_medica" name="informacion_medica"></div>
        <div class="col-12"><button class="btn btn-success" type="submit">Guardar estudiante</button></div>
    </form></div></section><?php endif; ?>

    <section class="card shadow-sm"><div class="card-body p-4"><form method="GET" class="row g-2 mb-3"><div class="col-sm-9"><label class="visually-hidden" for="buscar">Buscar estudiante</label><input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por nombre o DNI"></div><div class="col-sm-3"><button class="btn btn-outline-primary w-100" type="submit">Buscar</button></div></form><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Estudiante</th><th>DNI</th><th>Fecha de nacimiento</th><th>Apoderado principal</th></tr></thead><tbody>
    <?php foreach ($estudiantes as $estudiante): ?><tr><td><?= htmlspecialchars($estudiante['apellido_paterno'].' '.$estudiante['apellido_materno'].', '.$estudiante['nombres']) ?></td><td><?= htmlspecialchars($estudiante['dni'] ?: '-') ?></td><td><?= htmlspecialchars($estudiante['fecha_nacimiento'] ?: '-') ?></td><td><?= htmlspecialchars($estudiante['apoderado_nombres'] ? $estudiante['apoderado_apellido'].' '.$estudiante['apoderado_nombres'] : 'Sin asignar') ?></td></tr><?php endforeach; ?>
    <?php if (!$estudiantes): ?><tr><td colspan="4" class="text-center text-muted py-4">No hay estudiantes para mostrar.</td></tr><?php endif; ?></tbody></table></div></div></section>
</main>
</body>
</html>

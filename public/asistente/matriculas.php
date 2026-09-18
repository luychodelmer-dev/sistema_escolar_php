<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_auth(['asistente', 'director']);

$error = '';
$mensaje = '';
$busqueda = trim($_GET['buscar'] ?? '');
$puedeMatricular = has_permission('p_matricular');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('p_matricular');
    $datos = [
        'nombres' => trim($_POST['nombres'] ?? ''),
        'apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
        'apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
        'dni' => trim($_POST['dni'] ?? ''),
        'nivel_id' => filter_input(INPUT_POST, 'nivel_id', FILTER_VALIDATE_INT),
        'grado' => filter_input(INPUT_POST, 'grado', FILTER_VALIDATE_INT),
        'seccion' => trim($_POST['seccion'] ?? ''),
        'anio' => (int) ($_POST['anio'] ?? date('Y')),
        'apoderado_id' => filter_input(INPUT_POST, 'apoderado_id', FILTER_VALIDATE_INT),
    ];

    if (in_array('', [$datos['nombres'], $datos['apellido_paterno'], $datos['apellido_materno'], $datos['dni'], $datos['seccion']], true) || !$datos['nivel_id'] || !$datos['grado'] || !$datos['apoderado_id']) {
        $error = 'Completa todos los campos obligatorios.';
    } elseif (!preg_match('/^\d{8}$/', $datos['dni'])) {
        $error = 'El DNI debe tener 8 números.';
    } elseif ($datos['anio'] < 2020 || $datos['anio'] > 2100) {
        $error = 'El año de matrícula no es válido.';
    } else {
        try {
            $pdo->beginTransaction();
            $apoderadoStmt = $pdo->prepare(
                "SELECT id FROM apoderados
                 WHERE id = :id
                                     AND COALESCE(NULLIF(celular_actual, ''), NULLIF(celular_padre, ''), NULLIF(celular_madre, ''), '') <> ''"
            );
            $apoderadoStmt->execute(['id' => $datos['apoderado_id']]);
            if (!$apoderadoStmt->fetchColumn()) {
                throw new RuntimeException('El apoderado debe tener al menos un número de celular registrado.');
            }

            $estudianteStmt = $pdo->prepare('SELECT id FROM estudiantes WHERE dni = :dni ORDER BY id LIMIT 1');
            $estudianteStmt->execute(['dni' => $datos['dni']]);
            $estudianteId = $estudianteStmt->fetchColumn();

            if (!$estudianteId) {
                $estudianteStmt = $pdo->prepare(
                    'INSERT INTO estudiantes (dni, nombres, apellido_paterno, apellido_materno)
                     VALUES (:dni, :nombres, :apellido_paterno, :apellido_materno)
                     RETURNING id'
                );
                $estudianteStmt->execute([
                    'dni' => $datos['dni'],
                    'nombres' => $datos['nombres'],
                    'apellido_paterno' => $datos['apellido_paterno'],
                    'apellido_materno' => $datos['apellido_materno'],
                ]);
                $estudianteId = $estudianteStmt->fetchColumn();
            }

            $matriculaStmt = $pdo->prepare(
                'INSERT INTO matriculas (estudiante_id, anio, nivel_id, grado, seccion, digitador_id)
                 VALUES (:estudiante_id, :anio, :nivel_id, :grado, :seccion, :digitador_id)'
            );
            $matriculaStmt->execute([
                'estudiante_id' => $estudianteId,
                'anio' => $datos['anio'],
                'nivel_id' => $datos['nivel_id'],
                'grado' => $datos['grado'],
                'seccion' => $datos['seccion'],
                'digitador_id' => $_SESSION['user_id'],
            ]);

            $relacionStmt = $pdo->prepare(
                'INSERT INTO apoderado_estudiante
                    (apoderado_id, estudiante_id, vinculo, es_principal, vive_con_estudiante, autorizado_recoger)
                 VALUES (:apoderado_id, :estudiante_id, :vinculo, TRUE, FALSE, FALSE)
                 ON CONFLICT (estudiante_id) DO UPDATE SET
                    apoderado_id = EXCLUDED.apoderado_id,
                    es_principal = TRUE'
            );
            $relacionStmt->execute([
                'apoderado_id' => $datos['apoderado_id'],
                'estudiante_id' => $estudianteId,
                'vinculo' => 'Apoderado',
            ]);
            $pdo->commit();
            registrar_auditoria('crear_matricula', 'Registró matrícula para DNI ' . $datos['dni'] . ' del año ' . $datos['anio'] . '.');
            header('Location: matriculas.php?guardado=1');
            exit;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = str_contains($exception->getMessage(), 'duplicate key')
                ? 'Ese estudiante ya tiene una matrícula registrada para ese año.'
                : 'No se pudo guardar la matrícula.';
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}

if (isset($_GET['guardado'])) {
    $mensaje = 'Matrícula registrada correctamente.';
}

$niveles = $pdo->query('SELECT id, nombre FROM niveles ORDER BY id')->fetchAll();
$apoderados = $pdo->query(
    "SELECT id, nombres, apellido_paterno, apellido_materno, dni,
            celular_actual, celular_padre, celular_madre
     FROM apoderados
    WHERE COALESCE(NULLIF(celular_actual, ''), NULLIF(celular_padre, ''), NULLIF(celular_madre, ''), '') <> ''
     ORDER BY apellido_paterno, nombres"
)->fetchAll();
$sql = 'SELECT m.id, m.anio, m.grado, m.seccion, m.estado_matricula,
               e.dni, e.nombres, e.apellido_paterno, e.apellido_materno,
               n.nombre AS nivel
        FROM matriculas m
        INNER JOIN estudiantes e ON e.id = m.estudiante_id
        INNER JOIN niveles n ON n.id = m.nivel_id';
$params = [];
if ($busqueda !== '') {
    $sql .= ' WHERE e.nombres ILIKE :busqueda OR e.apellido_paterno ILIKE :busqueda OR e.apellido_materno ILIKE :busqueda OR e.dni ILIKE :busqueda';
    $params['busqueda'] = "%$busqueda%";
}
$sql .= ' ORDER BY anio DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$matriculas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Matrículas - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f4f7fb; color: #172b4d; } .page-header { background: #172b4d; color: #fff; } .card { border: 0; border-radius: 12px; } .required::after { content: ' *'; color: #dc3545; } .institution-brand { display: flex; align-items: center; gap: .6rem; white-space: nowrap; } .institution-brand img { width: 42px; height: 42px; object-fit: contain; border-radius: 50%; background: #fff; } .institution-brand span { font-size: 1.1rem; white-space: nowrap; } .header-user-photo { width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(255, 255, 255, .85); border-radius: 50%; } @media (max-width: 768px) { .institution-brand span { font-size: .95rem; } }</style>
</head>
<body>
<?php render_app_header('panel.php', 'logout.php'); ?>
<main class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><h1 class="h3 mb-1">Alumnos y matrículas</h1><p class="text-muted mb-0">Registra matrículas por nivel, grado y sección.</p></div><a href="panel.php" class="btn btn-outline-primary">Volver al panel</a></div>
    <?php if ($mensaje !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($puedeMatricular && !$apoderados): ?><div class="alert alert-warning">Primero registra un apoderado con al menos un número de celular para poder guardar una matrícula.</div><?php endif; ?>
    <?php if ($puedeMatricular): ?><section class="card shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">Nueva matrícula</h2><form method="POST" class="row g-3">
        <div class="col-md-4"><label class="form-label required" for="nombres">Nombres</label><input class="form-control" id="nombres" name="nombres" required></div>
        <div class="col-md-4"><label class="form-label required" for="apellido_paterno">Apellido paterno</label><input class="form-control" id="apellido_paterno" name="apellido_paterno" required></div>
        <div class="col-md-4"><label class="form-label required" for="apellido_materno">Apellido materno</label><input class="form-control" id="apellido_materno" name="apellido_materno" required></div>
        <div class="col-md-3"><label class="form-label required" for="dni">DNI</label><input class="form-control" id="dni" name="dni" inputmode="numeric" maxlength="8" required></div>
        <div class="col-md-3"><label class="form-label required" for="nivel_id">Nivel</label><select class="form-select" id="nivel_id" name="nivel_id" required><option value="">Seleccionar</option><?php foreach ($niveles as $nivel): ?><option value="<?= (int) $nivel['id'] ?>"><?= htmlspecialchars(ucfirst($nivel['nombre'])) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label required" for="grado">Grado</label><input class="form-control" id="grado" name="grado" type="number" min="1" max="6" required></div>
        <div class="col-md-2"><label class="form-label required" for="seccion">Sección</label><input class="form-control" id="seccion" name="seccion" maxlength="10" required></div>
        <div class="col-md-2"><label class="form-label required" for="anio">Año</label><input class="form-control" id="anio" name="anio" type="number" min="2020" max="2100" value="<?= date('Y') ?>" required></div>
        <div class="col-md-8"><label class="form-label required" for="apoderado_id">Apoderado con celular</label><select class="form-select" id="apoderado_id" name="apoderado_id" required><option value="">Seleccionar</option><?php foreach ($apoderados as $apoderado): ?><option value="<?= (int) $apoderado['id'] ?>"><?= htmlspecialchars($apoderado['apellido_paterno'].' '.$apoderado['apellido_materno'].', '.$apoderado['nombres'].' - '.$apoderado['dni']) ?></option><?php endforeach; ?></select><small class="text-muted">Solo aparecen apoderados con al menos un número de celular.</small></div>
        <div class="col-12"><button class="btn btn-success" type="submit" <?= !$apoderados ? 'disabled' : '' ?>>Guardar matrícula</button></div>
    </form></div></section><?php endif; ?>
    <section class="card shadow-sm"><div class="card-body p-4"><form method="GET" class="row g-2 mb-3"><div class="col-sm-9"><label class="visually-hidden" for="buscar">Buscar alumno</label><input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por nombre o DNI"></div><div class="col-sm-3"><button class="btn btn-outline-primary w-100" type="submit">Buscar</button></div></form><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Alumno</th><th>DNI</th><th>Nivel</th><th>Grado</th><th>Sección</th><th>Año</th></tr></thead><tbody>
    <?php foreach ($matriculas as $matricula): ?><tr><td><?= htmlspecialchars($matricula['nombres'].' '.$matricula['apellido_paterno'].' '.$matricula['apellido_materno']) ?></td><td><?= htmlspecialchars($matricula['dni'] ?? '-') ?></td><td><?= htmlspecialchars(ucfirst($matricula['nivel'])) ?></td><td><?= htmlspecialchars($matricula['grado']) ?></td><td><?= htmlspecialchars($matricula['seccion']) ?></td><td><?= htmlspecialchars($matricula['anio']) ?></td></tr><?php endforeach; ?>
    <?php if (!$matriculas): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay matrículas para mostrar.</td></tr><?php endif; ?></tbody></table></div></div></section>
</main>
</body>
</html>

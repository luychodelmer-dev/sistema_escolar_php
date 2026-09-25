<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_auth(['asistente', 'director']);

$error = '';
$mensaje = '';
$busqueda = trim($_GET['buscar'] ?? '');
$puedeCobrar = has_permission('p_cobrar');
$materiasDisponibles = [
    'Matemática',
    'Comunicación',
    'Ciencia y Ambiente',
    'Historia',
    'Geografía',
    'Inglés',
    'Educación Física',
    'Arte',
    'Tecnología',
    'Religión',
    'Personal Social',
    'Desarrollo Personal',
    'Computación',
    'Biología',
    'Química',
    'Física',
    'Literatura',
    'Psicología',
    'Administración',
    'Contabilidad',
    'Otra',
];
$pagosTieneCampoMaterias = (bool) $pdo->query(
    "SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'pagos' AND column_name = 'materias')"
)->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('p_cobrar');
    $datos = [
        'matricula_id' => filter_input(INPUT_POST, 'matricula_id', FILTER_VALIDATE_INT),
        'concepto_id' => filter_input(INPUT_POST, 'concepto_id', FILTER_VALIDATE_INT),
        'monto_pagado' => trim($_POST['monto_pagado'] ?? ''),
        'fecha_pago' => trim($_POST['fecha_pago'] ?? ''),
        'nro_recibo' => trim($_POST['nro_recibo'] ?? ''),
        'talonario' => trim($_POST['talonario'] ?? ''),
        'materias' => trim($_POST['materias'] ?? ''),
    ];

    if (!$datos['matricula_id'] || !$datos['concepto_id'] || $datos['monto_pagado'] === '' || $datos['fecha_pago'] === '') {
        $error = 'Completa todos los campos obligatorios.';
    } elseif (!is_numeric($datos['monto_pagado']) || (float) $datos['monto_pagado'] <= 0) {
        $error = 'El monto debe ser un número válido.';
    } else {
        try {
            $pdo->beginTransaction();
            $deudaStmt = $pdo->prepare('SELECT id, monto_requerido FROM deudas WHERE matricula_id = :matricula_id AND concepto_id = :concepto_id LIMIT 1');
            $deudaStmt->execute([
                'matricula_id' => $datos['matricula_id'],
                'concepto_id' => $datos['concepto_id'],
            ]);
            $deuda = $deudaStmt->fetch();

            if (!$deuda) {
                $deudaStmt = $pdo->prepare(
                    'INSERT INTO deudas (matricula_id, concepto_id, monto_requerido, estado)
                     SELECT :matricula_id, id, monto_oficial, \'Pendiente\'
                     FROM conceptos_pago WHERE id = :concepto_id
                     RETURNING id, monto_requerido'
                );
                $deudaStmt->execute([
                    'matricula_id' => $datos['matricula_id'],
                    'concepto_id' => $datos['concepto_id'],
                ]);
                $deuda = $deudaStmt->fetch();
            }

            if (!$deuda) {
                throw new RuntimeException('La deuda seleccionada no existe.');
            }

            $camposPago = ['deuda_id', 'monto_pagado', 'nro_recibo', 'talonario', 'fecha_pago', 'registrado_por'];
            $valoresPago = [
                'deuda_id' => $deuda['id'],
                'monto_pagado' => $datos['monto_pagado'],
                'nro_recibo' => $datos['nro_recibo'] ?: null,
                'talonario' => $datos['talonario'] ?: null,
                'fecha_pago' => $datos['fecha_pago'],
                'registrado_por' => $_SESSION['user_id'],
            ];
            if ($pagosTieneCampoMaterias) {
                $camposPago[] = 'materias';
                $valoresPago['materias'] = $datos['materias'] !== '' ? $datos['materias'] : null;
            }

            $sqlInsertPago = 'INSERT INTO pagos (' . implode(', ', $camposPago) . ') VALUES (:' . implode(', :', $camposPago) . ')';
            $pagoStmt = $pdo->prepare($sqlInsertPago);
            $pagoStmt->execute($valoresPago);

            $totalStmt = $pdo->prepare('SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos WHERE deuda_id = :deuda_id');
            $totalStmt->execute(['deuda_id' => $deuda['id']]);
            $estado = (float) $totalStmt->fetchColumn() >= (float) $deuda['monto_requerido'] ? 'Pagada' : 'Pendiente';
            $estadoStmt = $pdo->prepare('UPDATE deudas SET estado = :estado WHERE id = :id');
            $estadoStmt->execute(['estado' => $estado, 'id' => $deuda['id']]);
            $pdo->commit();
            registrar_auditoria('crear_pago', 'Registró pago por monto ' . $datos['monto_pagado'] . '.');
            header('Location: pagos.php?guardado=1');
            exit;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'No se pudo guardar el pago.';
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}

if (isset($_GET['guardado'])) {
    $mensaje = 'Pago registrado correctamente.';
}

$conceptos = $pdo->query('SELECT id, descripcion, monto_oficial FROM conceptos_pago ORDER BY id')->fetchAll();
$matriculasDisponibles = $pdo->query(
    "SELECT m.id, m.anio, e.nombres, e.apellido_paterno, e.apellido_materno
     FROM matriculas m INNER JOIN estudiantes e ON e.id = m.estudiante_id
     WHERE m.estado_matricula IS NULL OR m.estado_matricula NOT IN ('Retirada', 'Anulada')
     ORDER BY e.apellido_paterno, e.nombres, m.anio DESC"
)->fetchAll();
$sql = 'SELECT p.id, p.monto_pagado, p.nro_recibo, p.talonario, p.fecha_pago,
               c.descripcion AS concepto, e.nombres, e.apellido_paterno,
               e.apellido_materno, m.anio' . ($pagosTieneCampoMaterias ? ', p.materias' : '') . '
        FROM pagos p
        INNER JOIN deudas d ON d.id = p.deuda_id
        INNER JOIN conceptos_pago c ON c.id = d.concepto_id
        INNER JOIN matriculas m ON m.id = d.matricula_id
        INNER JOIN estudiantes e ON e.id = m.estudiante_id';
$params = [];
if ($busqueda !== '') {
    $busquedaSql = ' WHERE e.nombres ILIKE :busqueda OR e.apellido_paterno ILIKE :busqueda OR c.descripcion ILIKE :busqueda OR p.nro_recibo ILIKE :busqueda';
    if ($pagosTieneCampoMaterias) {
        $busquedaSql .= ' OR p.materias ILIKE :busqueda';
    }
    $sql .= $busquedaSql;
    $params['busqueda'] = "%$busqueda%";
}
$sql .= ' ORDER BY fecha_pago DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pagos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagos - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f4f7fb; color: #172b4d; } .page-header { background: #172b4d; color: #fff; } .card { border: 0; border-radius: 12px; } .required::after { content: ' *'; color: #dc3545; } .institution-brand { display: flex; align-items: center; gap: .6rem; white-space: nowrap; } .institution-brand img { width: 42px; height: 42px; object-fit: contain; border-radius: 50%; background: #fff; } .institution-brand span { font-size: 1.1rem; white-space: nowrap; } .header-user-photo { width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(255, 255, 255, .85); border-radius: 50%; } @media (max-width: 768px) { .institution-brand span { font-size: .95rem; } }</style>
</head>
<body>
<?php render_app_header('panel.php', 'logout.php'); ?>
<main class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><h1 class="h3 mb-1">Control de pagos</h1><p class="text-muted mb-0">Registra cuotas, aportes y otros pagos escolares.</p></div><a href="panel.php" class="btn btn-outline-primary">Volver al panel</a></div>
    <?php if ($mensaje !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($puedeCobrar): ?><section class="card shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">Nuevo pago</h2><form method="POST" class="row g-3">
        <div class="col-md-5"><label class="form-label required" for="matricula_id">Alumno y matrícula</label><select class="form-select" id="matricula_id" name="matricula_id" required><option value="">Seleccionar</option><?php foreach ($matriculasDisponibles as $matricula): ?><option value="<?= (int) $matricula['id'] ?>"><?= htmlspecialchars($matricula['apellido_paterno'].' '.$matricula['apellido_materno'].', '.$matricula['nombres'].' - '.$matricula['anio']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label required" for="concepto_id">Concepto</label><select class="form-select" id="concepto_id" name="concepto_id" required><option value="">Seleccionar</option><?php foreach ($conceptos as $concepto): ?><option value="<?= (int) $concepto['id'] ?>"><?= htmlspecialchars($concepto['descripcion'].' (S/ '.$concepto['monto_oficial'].')') ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label required" for="monto_pagado">Monto</label><input class="form-control" id="monto_pagado" name="monto_pagado" type="number" min="0.01" step="0.01" required></div>
        <div class="col-md-2"><label class="form-label required" for="fecha_pago">Fecha</label><input class="form-control" id="fecha_pago" name="fecha_pago" type="date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-md-3"><label class="form-label" for="nro_recibo">N° recibo</label><input class="form-control" id="nro_recibo" name="nro_recibo" maxlength="50"></div>
        <div class="col-md-3"><label class="form-label" for="talonario">Talonario</label><input class="form-control" id="talonario" name="talonario" maxlength="50"></div>
        <div class="col-md-4"><label class="form-label" for="materias">Materias</label><select class="form-select" id="materias" name="materias"><option value="">Seleccionar</option><?php foreach ($materiasDisponibles as $materia): ?><option value="<?= htmlspecialchars($materia) ?>"><?= htmlspecialchars($materia) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><button class="btn btn-warning" type="submit">Guardar pago</button></div>
    </form></div></section><?php endif; ?>
    <section class="card shadow-sm"><div class="card-body p-4"><form method="GET" class="row g-2 mb-3"><div class="col-sm-9"><label class="visually-hidden" for="buscar">Buscar pago</label><input class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por alumno, concepto, recibo o materias"></div><div class="col-sm-3"><button class="btn btn-outline-primary w-100" type="submit">Buscar</button></div></form><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Alumno</th><th>Concepto</th><th>Monto</th><th>Fecha</th><th>Recibo</th><th>Talonario</th><?php if ($pagosTieneCampoMaterias): ?><th>Materias</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($pagos as $pago): ?><tr><td><?= htmlspecialchars($pago['nombres'].' '.$pago['apellido_paterno'].' '.$pago['apellido_materno']) ?></td><td><?= htmlspecialchars($pago['concepto']) ?></td><td>S/ <?= htmlspecialchars(number_format((float) $pago['monto_pagado'], 2)) ?></td><td><?= htmlspecialchars($pago['fecha_pago']) ?></td><td><?= htmlspecialchars($pago['nro_recibo'] ?: '-') ?></td><td><?= htmlspecialchars($pago['talonario'] ?: '-') ?></td><?php if ($pagosTieneCampoMaterias): ?><td><?= htmlspecialchars($pago['materias'] ?: '-') ?></td><?php endif; ?></tr><?php endforeach; ?>
    <?php if (!$pagos): ?><tr><td colspan="<?= $pagosTieneCampoMaterias ? 7 : 6 ?>" class="text-center text-muted py-4">No hay pagos para mostrar.</td></tr><?php endif; ?></tbody></table></div></div></section>
</main>
</body>
</html>

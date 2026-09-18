<?php
// Arrancamos la sesión
session_start();

// 1. Requerimos la configuración y la autenticación usando TU estructura de carpetas
require_once '../../config/database.php';
require_once '../../app/auth.php';

// Validar seguridad (ajusta esto si tu auth.php lo maneja distinto)
require_auth(['asistente', 'director']);

$puedeEditar = has_permission('p_editar');

// Lógica de búsqueda
$busqueda = $_GET['q'] ?? '';
if ($busqueda) {
    $stmt = $pdo->prepare("
        SELECT * FROM apoderados 
        WHERE dni ILIKE :q OR nombres ILIKE :q OR apellido_paterno ILIKE :q 
        ORDER BY id DESC
    ");
    $stmt->execute(['q' => "%$busqueda%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM apoderados ORDER BY id DESC LIMIT 50");
}
$apoderados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Apoderados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navegación simple -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <!-- Volvemos al panel.php que está en tu misma carpeta de asistente -->
            <a class="navbar-brand" href="panel.php">⬅ Volver al Panel</a>
            <span class="text-white">Módulo de Familias y Apoderados</span>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold">Directorio de Apoderados</h2>
            <?php if ($puedeEditar): ?><a href="nuevo_padre.php" class="btn btn-success">+ Registrar Nuevo</a><?php endif; ?>
        </div>

        <!-- Buscador -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control me-2" placeholder="Buscar por DNI o Apellido..." value="<?= htmlspecialchars($busqueda) ?>">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <a href="padres.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                </form>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>DNI</th>
                                <th>Apellidos y Nombres</th>
                                <th>Celulares (P/M)</th>
                                <th>N° Tarjeta APAFA</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($apoderados) > 0): ?>
                                <?php foreach ($apoderados as $row): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($row['dni']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['apellido_paterno'] . ' ' . $row['apellido_materno'] . ', ' . $row['nombres']) ?>
                                        <br><small class="text-muted">Padre/Tutor: <?= htmlspecialchars($row['nombre_padre_adicional'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        📞 Act: <?= htmlspecialchars($row['celular_actual'] ?? '-') ?><br>
                                        <small class="text-muted">P: <?= htmlspecialchars($row['celular_padre'] ?? '-') ?> | M: <?= htmlspecialchars($row['celular_madre'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($row['nro_tarjeta_apafa'] ?? 'Sin asignar') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($puedeEditar): ?><a href="editar_padre.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-warning">Editar</a><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No se encontraron apoderados registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
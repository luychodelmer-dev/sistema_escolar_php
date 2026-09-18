<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
require_permission('p_editar');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Registro inválido.');
}

$stmt = $pdo->prepare('SELECT * FROM apoderados WHERE id = :id');
$stmt->execute(['id' => $id]);
$apoderado = $stmt->fetch();
if (!$apoderado) {
    http_response_code(404);
    exit('Apoderado no encontrado.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'direccion' => trim($_POST['direccion'] ?? ''),
        'celular_actual' => trim($_POST['celular_actual'] ?? ''),
        'celular_padre' => trim($_POST['celular_padre'] ?? ''),
        'celular_madre' => trim($_POST['celular_madre'] ?? ''),
        'id' => $id,
    ];

    $celularValido = static function (string $celular): bool {
        return $celular === '' || preg_match('/^9\d{8}$/', $celular) === 1;
    };

    if ($datos['direccion'] === '' || $datos['celular_actual'] === '') {
        $error = 'La dirección y el celular actual son obligatorios.';
    } elseif (!$celularValido($datos['celular_actual']) || !$celularValido($datos['celular_padre']) || !$celularValido($datos['celular_madre'])) {
        $error = 'Los celulares deben tener 9 dígitos y comenzar con 9.';
    } else {
        try {
        $stmt = $pdo->prepare(
            'UPDATE apoderados
             SET direccion = :direccion, celular_actual = :celular_actual,
                 celular_padre = :celular_padre, celular_madre = :celular_madre
             WHERE id = :id'
        );
        $stmt->execute($datos);
        registrar_auditoria('editar_apoderado', 'Actualizó dirección y teléfonos del apoderado ID ' . $id . '.');
        header('Location: padres.php?actualizado=1');
        exit;
        } catch (PDOException $exception) {
            $error = 'No se pudo actualizar el apoderado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar apoderado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <section class="card shadow-sm border-0 mx-auto" style="max-width: 720px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-1">Editar contacto del apoderado</h1>
            <p class="text-muted mb-4"><?= htmlspecialchars($apoderado['nombres'].' '.$apoderado['apellido_paterno']) ?></p>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <div class="col-12"><label class="form-label" for="direccion">Dirección *</label><input class="form-control" id="direccion" name="direccion" value="<?= htmlspecialchars($apoderado['direccion'] ?? '') ?>" required></div>
                <div class="col-md-4"><label class="form-label" for="celular_actual">Celular actual *</label><input class="form-control" id="celular_actual" name="celular_actual" inputmode="numeric" pattern="9[0-9]{8}" minlength="9" maxlength="9" value="<?= htmlspecialchars($apoderado['celular_actual'] ?? '') ?>" required></div>
                <div class="col-md-4"><label class="form-label" for="celular_padre">Celular padre</label><input class="form-control" id="celular_padre" name="celular_padre" inputmode="numeric" pattern="9[0-9]{8}" maxlength="9" value="<?= htmlspecialchars($apoderado['celular_padre'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label" for="celular_madre">Celular madre</label><input class="form-control" id="celular_madre" name="celular_madre" inputmode="numeric" pattern="9[0-9]{8}" maxlength="9" value="<?= htmlspecialchars($apoderado['celular_madre'] ?? '') ?>"></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a href="padres.php" class="btn btn-secondary">Cancelar</a><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
            </form>
        </div>
    </section>
</main>
</body>
</html>

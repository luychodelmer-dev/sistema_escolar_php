<?php
require_once __DIR__ . '/../config/database.php';

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS permisos_usuario (
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    permiso VARCHAR(50) NOT NULL,
    otorgado BOOLEAN NOT NULL DEFAULT FALSE,
    otorgado_por INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, permiso)
)
SQL);

$pdo->exec(<<<'SQL'
INSERT INTO permisos_usuario (usuario_id, permiso, otorgado, actualizado_en)
SELECT id, 'p_editar', permiso_editar, CURRENT_TIMESTAMP
FROM usuarios
ON CONFLICT (usuario_id, permiso) DO NOTHING;

INSERT INTO permisos_usuario (usuario_id, permiso, otorgado, actualizado_en)
SELECT id, 'p_matricular', permiso_matricular, CURRENT_TIMESTAMP
FROM usuarios
ON CONFLICT (usuario_id, permiso) DO NOTHING;

INSERT INTO permisos_usuario (usuario_id, permiso, otorgado, actualizado_en)
SELECT id, 'p_cobrar', permiso_cobrar, CURRENT_TIMESTAMP
FROM usuarios
ON CONFLICT (usuario_id, permiso) DO NOTHING;
SQL);

echo "Tabla permisos_usuario lista." . PHP_EOL;

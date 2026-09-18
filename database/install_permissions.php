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

echo "Tabla permisos_usuario lista." . PHP_EOL;

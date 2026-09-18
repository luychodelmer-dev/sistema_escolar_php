<?php
session_start();
require_once __DIR__ . '/../../app/auth.php';
require_permission('p_editar');

require __DIR__ . '/editar_apoderado.php';

<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_auth(['asistente', 'director']);

$resumen = [
    'estudiantes' => (int) $pdo->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn(),
    'matriculas' => (int) $pdo->query('SELECT COUNT(*) FROM matriculas WHERE anio = ' . (int) date('Y'))->fetchColumn(),
    'deudas_pendientes' => (int) $pdo->query("SELECT COUNT(*) FROM deudas WHERE estado = 'Pendiente'")->fetchColumn(),
    'pagos' => (int) $pdo->query('SELECT COUNT(*) FROM pagos')->fetchColumn(),
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Asistente - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-panel {
            display: flex;
            align-items: center;
            gap: 0;
            width: 100%;
            min-height: 62px;
            padding: .35rem;
            border: 1px solid #e2e2e2;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(23, 43, 77, .08);
        }

        .content-shell {
            max-width: 1180px;
            margin: 0 auto;
        }

        .catalog-logo {
            flex: 0 0 auto;
            width: 150px;
            height: 54px;
            margin: 0 1rem .0rem .55rem;
            object-fit: contain;
        }

        .catalog-search {
            display: flex;
            flex: 1;
            min-width: 0;
            align-items: center;
            height: 54px;
            overflow: hidden;
            border: 1px solid #dedede;
            border-radius: 5px;
        }

        .catalog-search .form-select,
        .catalog-search .form-control {
            height: 100%;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .catalog-search .form-select {
            flex: 0 0 190px;
            border-right: 1px solid #e5e5e5;
            color: #333;
        }

        .catalog-search .form-control {
            min-width: 0;
            font-size: 1.1rem;
        }

        .catalog-search .form-control::placeholder {
            color: #a8a8a8;
        }

        .catalog-search-icon {
            flex: 0 0 48px;
            color: #1261a0;
            font-size: 1.25rem;
            text-align: center;
        }

        .catalog-button {
            align-self: stretch;
            min-width: 125px;
            margin-left: .35rem;
            border: 0;
            border-radius: 5px;
            color: #ffffff;
            background: #f0180d;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .catalog-button:hover {
            color: #ffffff;
            background: #c91008;
        }

        .navbar {
            min-height: 64px;
        }

        .navbar .container-fluid {
            gap: 1rem;
        }

        .navbar .d-flex {
            flex-shrink: 0;
        }

        .assistant-photo {
            width: 64px;
            height: 64px;
            margin-right: .65rem;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, .85);
            border-radius: 50%;
        }

        @media (max-width: 768px) {
            .search-panel {
                flex-wrap: wrap;
            }

            .catalog-logo {
                width: 125px;
                margin-right: auto;
            }

            .catalog-search {
                flex-basis: 100%;
                order: 3;
            }

            .catalog-button {
                flex: 0 0 125px;
                height: 54px;
            }

            .navbar .container-fluid {
                align-items: flex-start;
                flex-direction: column;
            }

            .navbar .d-flex {
                width: 100%;
                justify-content: space-between;
            }

            .assistant-photo {
                width: 54px;
                height: 54px;
            }
        }

        .institution-brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            color: #ffffff;
            font-weight: 600;
            line-height: 1.15;
        }

        .institution-brand:hover {
            color: #ffffff;
        }

        .institution-brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            border-radius: 50%;
            background: #ffffff;
        }

        .institution-brand span {
            max-width: none;
            font-size: 1rem;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .institution-brand span {
                font-size: .95rem;
            }
        }

        .catalog-search .form-control:focus,
        .catalog-search .form-select:focus {
            border-color: #2bb3a3;
            box-shadow: 0 0 0 .2rem rgba(43, 179, 163, .16);
        }

        .module-card {
            height: 100%;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .module-card .card-body {
            display: flex;
            flex-direction: column;
        }

        .module-card .card-body > div {
            flex: 1;
        }

        .module-card .btn {
            align-self: stretch;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 .75rem 1.5rem rgba(23, 43, 77, .12) !important;
        }

        #noResults {
            display: none;
        }

        .page-header { background: #172b4d; color: #fff; }
        .header-user-photo { width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(255, 255, 255, .85); border-radius: 50%; }
    </style>
</head>
<body class="bg-light">
    <?php render_app_header('panel.php', 'logout.php'); ?>

    <!-- Contenido Principal -->
    <div class="container content-shell py-5">
        <div class="search-panel mb-4">
            <img class="catalog-logo" src="../assets/images/logo-institucion.jpg.jpg" alt="Logo de la Institución Educativa Divino Maestro">
            <div class="catalog-search">
                <span class="catalog-search-icon" aria-hidden="true">&#128269;</span>
                <label for="moduleSearch" class="visually-hidden">Buscar</label>
                <input type="search" class="form-control" id="moduleSearch" aria-label="Buscar en el catálogo" autocomplete="off">
            </div>
            <button type="button" class="catalog-button" id="searchButton">BUSCAR</button>
        </div>

        <div class="row g-3 mb-4" aria-label="Resumen del sistema">
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Estudiantes</small><div class="fs-3 fw-bold text-primary"><?= $resumen['estudiantes'] ?></div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Matrículas <?= date('Y') ?></small><div class="fs-3 fw-bold text-success"><?= $resumen['matriculas'] ?></div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Deudas pendientes</small><div class="fs-3 fw-bold text-warning"><?= $resumen['deudas_pendientes'] ?></div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Pagos registrados</small><div class="fs-3 fw-bold text-dark"><?= $resumen['pagos'] ?></div></div></div></div>
        </div>

        <!-- Módulos de Acceso Rápido -->
        <div class="row align-items-stretch text-center g-4" id="moduleList">
            <div class="col-md-4 module-item" data-search="padres apoderados responsables datos personales direccion apafa">
                <div class="card module-card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column justify-content-between p-4">
                        <div>
                            <h4 class="card-title text-primary fw-bold">Padres / Apoderados</h4>
                            <p class="card-text text-muted">Registrar datos personales, direcciones y números de tarjeta APAFA de los responsables.</p>
                        </div>
                        <a href="padres.php" class="btn btn-primary mt-3">Gestionar Padres</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 module-item" data-search="alumnos matriculas matrícula niveles inicial primaria secundaria secciones">
                <div class="card module-card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column justify-content-between p-4">
                        <div>
                            <h4 class="card-title text-success fw-bold">Alumnos y Matrículas</h4>
                            <p class="card-text text-muted">Matrículas anuales estructuradas por niveles (Inicial, Primaria, Secundaria) y secciones.</p>
                        </div>
                        <a href="matriculas.php" class="btn btn-success mt-3">Gestionar Matrículas</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 module-item" data-search="pagos cuotas apafa qaliwarma internet multas gestion">
                <div class="card module-card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column justify-content-between p-4">
                        <div>
                            <h4 class="card-title text-warning fw-bold">Control de Pagos</h4>
                            <p class="card-text text-muted">Registro detallado de cuotas APAFA, Qaliwarma, internet, multas y apoyo a la gestión.</p>
                        </div>
                        <a href="pagos.php" class="btn btn-warning text-dark mt-3">Gestionar Pagos</a>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-center text-muted mt-4" id="noResults">No encontramos un módulo para esa búsqueda.</p>
    </div>

    <script>
        const moduleSearch = document.getElementById('moduleSearch');
        const searchButton = document.getElementById('searchButton');
        const moduleItems = [...document.querySelectorAll('.module-item')];
        const noResults = document.getElementById('noResults');

        const filterModules = () => {
            const query = moduleSearch.value.trim().toLocaleLowerCase();
            let visibleModules = 0;

            moduleItems.forEach((item) => {
                const matches = !query || item.dataset.search.includes(query);
                item.classList.toggle('d-none', !matches);
                if (matches) visibleModules++;
            });

            noResults.style.display = visibleModules ? 'none' : 'block';
        };

        moduleSearch.addEventListener('input', filterModules);
        searchButton.addEventListener('click', filterModules);
    </script>

</body>
</html>
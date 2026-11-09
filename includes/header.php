<?php
$titulo  = $titulo  ?? 'Gestión de Empleados';
$pestana = $pestana ?? 'empleados';
$flash   = flash_tomar();
$total   = (int) $cx->query('SELECT COUNT(*) FROM empleado')->fetch_row()[0];
?>
<!doctype html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Gestión de Empleados</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="pantalla-<?= e($pestana) ?>">
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand-block" aria-label="Gestión de Empleados, inicio">
            <span class="brand-mark" aria-hidden="true">GE</span>
            <span class="brand-text">
                <span class="eyebrow">Panel interno · Recursos Humanos</span>
                <span class="brand-title">Gestión de Empleados</span>
            </span>
        </a>
        <nav class="tabs" aria-label="Secciones">
            <a href="index.php" <?= $pestana === 'empleados' ? 'class="active" aria-current="page"' : '' ?>><span class="tab-number">01</span> Empleados</a>
            <a href="consultas.php" <?= $pestana === 'consultas' ? 'class="active" aria-current="page"' : '' ?>><span class="tab-number">02</span> Consultas</a>
        </nav>
        <div class="header-status">
            <span class="status-dot" aria-hidden="true"></span>
            <strong><?= $total ?></strong> <span>registros activos</span>
        </div>
    </div>
    <div class="energy-ticker" aria-hidden="true">
        <div>
            <?php for ($i = 0; $i < 10; $i++): ?><span>Personas</span><span>Datos</span><span>Bienestar</span><?php endfor; ?>
        </div>
    </div>
</header>

<main class="page">
<?php if ($flash): ?>
    <div class="mensaje mensaje--<?= $flash['tipo'] === 'exito' ? 'exito' : 'error' ?>" role="status">
        <strong><?= $flash['tipo'] === 'exito' ? '¡LISTO!' : '¡OJO!' ?></strong> <?= e($flash['texto']) ?>
    </div>
<?php endif; ?>

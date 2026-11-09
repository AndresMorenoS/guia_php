<?php
require_once __DIR__ . '/includes/bootstrap.php';

$res = $cx->query('SELECT documento, nombre, sexo, fechaingreso, sueldobasico FROM empleado ORDER BY nombre ASC');
$filas = $res->fetch_all(MYSQLI_ASSOC);
$n = count($filas);

function iniciales(string $nombre): string
{
    $p = preg_split('/\s+/u', trim($nombre));
    $s = mb_substr($p[0], 0, 1, 'UTF-8');
    if (count($p) > 1) {
        $s .= mb_substr(end($p), 0, 1, 'UTF-8');
    }
    return mb_strtoupper($s, 'UTF-8');
}

$titulo  = 'Empleados';
$pestana = 'empleados';
require __DIR__ . '/includes/header.php';
?>
<section class="heroe">
    <div class="heroe-numero" aria-hidden="true"><?= $n ?></div>
    <div class="heroe-texto">
        <h2>Empleados</h2>
        <p><?= $n ?> <?= $n === 1 ? 'empleado' : 'empleados' ?>, en orden alfabético.</p>
    </div>
    <a href="crear.php" class="button primary boton-grande">+ Nuevo empleado</a>
</section>

<?php if ($n === 0): ?>
    <div class="empty-card">
        <h3>Aún no hay empleados registrados</h3>
        <p>Crea el primero para empezar.</p>
        <a href="crear.php" class="button primary">Nuevo empleado</a>
    </div>
<?php else: ?>
    <h3 class="sr-only">Lista de empleados</h3>
    <ul class="muro">
        <?php foreach ($filas as $i => $f): $d = urlencode($f['documento']); ?>
            <li class="ficha ficha-<?= $i % 4 ?>" style="--i:<?= $i ?>">
                <div class="ficha-cabeza">
                    <span class="ficha-ini" aria-hidden="true"><?= e(iniciales($f['nombre'])) ?></span>
                    <span class="tag"><?= e(sexo_texto($f['sexo'])) ?></span>
                </div>
                <h3 class="ficha-nombre"><?= e($f['nombre']) ?></h3>
                <dl class="ficha-datos">
                    <div><dt>Documento</dt><dd class="tabular"><?= e($f['documento']) ?></dd></div>
                    <div><dt>Ingreso</dt><dd class="tabular"><?= e(fecha_co($f['fechaingreso'])) ?></dd></div>
                </dl>
                <p class="ficha-sueldo"><span class="sr-only">Sueldo básico: </span><?= e(dinero((float) $f['sueldobasico'])) ?></p>
                <div class="ficha-acciones">
                    <a href="editar.php?documento=<?= $d ?>" class="button secondary">Editar</a>
                    <a href="eliminar.php?documento=<?= $d ?>" class="button">Eliminar</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

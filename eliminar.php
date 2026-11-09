<?php
require_once __DIR__ . '/includes/bootstrap.php';

function buscar_para_borrar(mysqli $cx, string $doc): ?array
{
    $st = $cx->prepare('SELECT documento, nombre FROM empleado WHERE documento = ?');
    $st->bind_param('s', $doc);
    $st->execute();
    $fila = $st->get_result()->fetch_assoc();
    $st->close();
    return $fila ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc = trim((string) ($_POST['documento'] ?? ''));

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión del formulario expiró. No se eliminó a nadie.');
        redirigir('index.php');
    }
    $emp = buscar_para_borrar($cx, $doc);
    if (!$emp) {
        flash('error', 'No existe un empleado con el documento ' . $doc . '.');
        redirigir('index.php');
    }

    $st = $cx->prepare('DELETE FROM empleado WHERE documento = ?');
    $st->bind_param('s', $doc);
    $st->execute();
    $borrados = $st->affected_rows;
    $st->close();

    if ($borrados === 1) {
        flash('exito', "Empleado {$emp['nombre']} eliminado.");
    } else {
        flash('error', 'No se pudo eliminar al empleado.');
    }
    redirigir('index.php');
}

// GET: solo confirmación, nunca borra.
$doc = trim((string) ($_GET['documento'] ?? ''));
$emp = buscar_para_borrar($cx, $doc);
if (!$emp) {
    flash('error', 'No existe un empleado con el documento ' . $doc . '.');
    redirigir('index.php');
}

$titulo  = 'Eliminar empleado';
$pestana = 'empleados';
require __DIR__ . '/includes/header.php';
?>
<div class="confirmation-page">
    <section class="confirmation-card">
        <span class="danger-label">¿SEGURO?</span>
        <h2>Eliminar empleado</h2>
        <p>Vas a eliminar a <strong><?= e($emp['nombre']) ?></strong> (documento <?= e($emp['documento']) ?>). Esta acción no se puede deshacer.</p>
        <form method="post" action="eliminar.php" class="form-actions">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="documento" value="<?= e($emp['documento']) ?>">
            <button type="submit" class="button danger">Sí, eliminar</button>
            <a href="index.php" class="button secondary" autofocus>Cancelar</a>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/bootstrap.php';

function buscar_empleado(mysqli $cx, string $doc): ?array
{
    $st = $cx->prepare('SELECT documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico FROM empleado WHERE documento = ?');
    $st->bind_param('s', $doc);
    $st->execute();
    $fila = $st->get_result()->fetch_assoc();
    $st->close();
    return $fila ?: null;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc = trim((string) ($_POST['documento'] ?? ''));

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión del formulario expiró. Inténtalo de nuevo.');
        redirigir('index.php');
    }
    if (!buscar_empleado($cx, $doc)) {
        flash('error', 'No existe un empleado con el documento ' . $doc . '.');
        redirigir('index.php');
    }

    [$datos, $errores] = validar_empleado($_POST, false, $cx);

    if (!$errores) {
        try {
            $st = $cx->prepare('UPDATE empleado SET nombre=?, sexo=?, domicilio=?, fechaingreso=?, fechanacimiento=?, sueldobasico=? WHERE documento=?');
            $st->bind_param('sssssds', $datos['nombre'], $datos['sexo'], $datos['domicilio'],
                $datos['fechaingreso'], $datos['fechanacimiento'], $datos['sueldobasico'], $datos['documento']);
            $st->execute();
            $st->close();
            flash('exito', 'Cambios guardados.');
            redirigir('index.php');
        } catch (mysqli_sql_exception $ex) {
            if (in_array($ex->getCode(), [4025, 3819], true)) {
                $errores['general'] = 'Los datos no cumplen las reglas de la base de datos.';
            } else {
                throw $ex;
            }
        }
    }
} else {
    $doc = trim((string) ($_GET['documento'] ?? ''));
    $emp = buscar_empleado($cx, $doc);
    if (!$emp) {
        flash('error', 'No existe un empleado con el documento ' . $doc . '.');
        redirigir('index.php');
    }
    $datos = $emp;
    $datos['sueldo_texto'] = number_format((float) $emp['sueldobasico'], 2, ',', '');
}

$titulo  = 'Editar empleado';
$pestana = 'empleados';
$modo    = 'editar';
$accion  = 'editar.php';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <a href="index.php" class="back-link">← Volver a empleados</a>
        <h2>Editar empleado</h2>
        <p>Actualiza los datos. El documento no se puede cambiar.</p>
    </div>
</div>
<?php require __DIR__ . '/includes/form_empleado.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

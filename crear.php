<?php
require_once __DIR__ . '/includes/bootstrap.php';

$datos   = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión del formulario expiró. Inténtalo de nuevo.');
        redirigir('crear.php');
    }

    [$datos, $errores] = validar_empleado($_POST, true, $cx);

    if (!$errores) {
        try {
            $st = $cx->prepare('INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $st->bind_param('ssssssd', $datos['documento'], $datos['nombre'], $datos['sexo'], $datos['domicilio'],
                $datos['fechaingreso'], $datos['fechanacimiento'], $datos['sueldobasico']);
            $st->execute();
            $st->close();
            flash('exito', "Empleado {$datos['nombre']} guardado.");
            redirigir('index.php');
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {
                $errores['documento'] = "Ya existe un empleado con el documento {$datos['documento']}.";
            } elseif (in_array($ex->getCode(), [4025, 3819], true)) {
                $errores['general'] = 'Los datos no cumplen las reglas de la base de datos.';
            } else {
                throw $ex;
            }
        }
    }
}

$titulo  = 'Nuevo empleado';
$pestana = 'empleados';
$modo    = 'crear';
$accion  = 'crear.php';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <a href="index.php" class="back-link">← Volver a empleados</a>
        <h2>Nuevo empleado</h2>
        <p>Completa los datos personales y laborales.</p>
    </div>
</div>
<?php require __DIR__ . '/includes/form_empleado.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

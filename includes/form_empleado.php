<?php
// Recibe: $datos, $errores, $modo ('crear'|'editar'), $accion
$editar = $modo === 'editar';
$v = fn(string $k): string => e((string) ($datos[$k] ?? ''));

$campo = function (string $id) use ($errores): string {
    $ids = [$id . '-ayuda'];
    if (isset($errores[$id])) {
        $ids[] = $id . '-error';
    }
    return 'aria-describedby="' . implode(' ', $ids) . '"' . (isset($errores[$id]) ? ' aria-invalid="true"' : '');
};
$error = function (string $id) use ($errores): void {
    if (isset($errores[$id])) {
        echo '<small class="error-text error-campo" id="' . $id . '-error">' . e($errores[$id]) . '</small>';
    }
};
$cls = fn(string $id): string => isset($errores[$id]) ? ' con-error field-error' : '';
$hoy = new DateTimeImmutable('today');
?>
<form method="post" action="<?= e($accion) ?>" class="form-card" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <?php if ($editar): ?><input type="hidden" name="documento" value="<?= $v('documento') ?>"><?php endif; ?>

    <?php if ($errores): ?>
        <div class="mensaje mensaje--error resumen-errores" role="alert">
            <strong>¡OJO!</strong> Revisa estos campos:
            <ul>
                <?php foreach ($errores as $k => $msg): ?>
                    <li><a href="#<?= e($k) ?>"><?= e($msg) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="form-section">
        <h3>Datos personales</h3>
        <p class="leyenda">* Campo obligatorio</p>
        <div class="form-grid">
            <div class="campo<?= $cls('documento') ?>">
                <label for="documento"><span>Documento *</span></label>
                <input type="text" id="documento" class="field<?= $editar ? ' field-locked' : '' ?>"
                       <?= $editar ? 'name="documento_ro" readonly' : 'name="documento" required' ?>
                       value="<?= $v('documento') ?>" inputmode="numeric" pattern="[0-9]{6,10}" maxlength="10"
                       placeholder="1023456789" <?= $campo('documento') ?>>
                <small id="documento-ayuda"><?= $editar ? 'El documento no se puede cambiar' : 'Solo números, de 6 a 10 dígitos' ?></small>
                <?php $error('documento'); ?>
            </div>

            <div class="campo<?= $cls('nombre') ?>">
                <label for="nombre"><span>Nombre completo *</span></label>
                <input type="text" id="nombre" name="nombre" class="field" value="<?= $v('nombre') ?>" maxlength="60"
                       placeholder="Ana María Gómez" required autocomplete="name" <?= $campo('nombre') ?>>
                <small id="nombre-ayuda">De 3 a 60 caracteres: letras, espacios, ' y - <span class="contador" data-para="nombre" data-max="60">0/60</span></small>
                <?php $error('nombre'); ?>
            </div>

            <div class="campo<?= $cls('sexo') ?>">
                <label for="sexo"><span>Sexo *</span></label>
                <select id="sexo" name="sexo" class="field" required <?= $campo('sexo') ?>>
                    <option value="" disabled <?= ($datos['sexo'] ?? '') === '' ? 'selected' : '' ?>>Selecciona…</option>
                    <option value="f" <?= ($datos['sexo'] ?? '') === 'f' ? 'selected' : '' ?>>Femenino</option>
                    <option value="m" <?= ($datos['sexo'] ?? '') === 'm' ? 'selected' : '' ?>>Masculino</option>
                </select>
                <small id="sexo-ayuda">Femenino o masculino</small>
                <?php $error('sexo'); ?>
            </div>

            <div class="campo<?= $cls('fechanacimiento') ?>">
                <label for="fechanacimiento"><span>Fecha de nacimiento *</span></label>
                <input type="date" id="fechanacimiento" name="fechanacimiento" class="field" value="<?= $v('fechanacimiento') ?>"
                       min="<?= $hoy->modify('-100 years')->format('Y-m-d') ?>" max="<?= $hoy->modify('-18 years')->format('Y-m-d') ?>"
                       required <?= $campo('fechanacimiento') ?>>
                <small id="fechanacimiento-ayuda">Debe ser mayor de edad (18 años o más)</small>
                <?php $error('fechanacimiento'); ?>
            </div>

            <div class="campo wide-field<?= $cls('domicilio') ?>">
                <label for="domicilio"><span>Domicilio *</span></label>
                <input type="text" id="domicilio" name="domicilio" class="field" value="<?= $v('domicilio') ?>" maxlength="80"
                       placeholder="Calle 45 # 12-30" required <?= $campo('domicilio') ?>>
                <small id="domicilio-ayuda">De 5 a 80 caracteres: letras, números, espacios y # - . , ° <span class="contador" data-para="domicilio" data-max="80">0/80</span></small>
                <?php $error('domicilio'); ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <h3>Datos laborales</h3>
        <div class="form-grid">
            <div class="campo<?= $cls('fechaingreso') ?>">
                <label for="fechaingreso"><span>Fecha de ingreso *</span></label>
                <input type="date" id="fechaingreso" name="fechaingreso" class="field" value="<?= $v('fechaingreso') ?>"
                       max="<?= $hoy->format('Y-m-d') ?>" required <?= $campo('fechaingreso') ?>>
                <small id="fechaingreso-ayuda">No puede ser futura; el empleado debía tener 18 años cumplidos</small>
                <?php $error('fechaingreso'); ?>
            </div>

            <div class="campo<?= $cls('sueldobasico') ?>">
                <label for="sueldobasico"><span>Sueldo básico *</span></label>
                <div class="money-field">
                    <span aria-hidden="true">$</span>
                    <input type="text" id="sueldobasico" name="sueldobasico" class="field" value="<?= $v('sueldo_texto') ?>" inputmode="decimal"
                           maxlength="13" placeholder="2.500.000,00" required <?= $campo('sueldobasico') ?>>
                </div>
                <small id="sueldobasico-ayuda">En pesos colombianos (COP). Mínimo: <?= e(dinero(SALARIO_MINIMO)) ?> · Formato 1.250.500,50</small>
                <?php $error('sueldobasico'); ?>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="button primary"><?= $editar ? 'Guardar cambios' : 'Guardar' ?></button>
        <a href="index.php" class="button secondary">Cancelar</a>
    </div>
</form>
<script>window.SALARIO_MINIMO = <?= json_encode(SALARIO_MINIMO) ?>;</script>

<?php
require_once __DIR__ . '/includes/bootstrap.php';

$mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
if (!$mes) {
    $mes = 5;
}
$nombres_mes = meses();

// 1a. Rosas y corbatas
$st = $cx->prepare("SELECT COALESCE(SUM(sexo = 'f'), 0) AS rosas, COALESCE(SUM(sexo = 'm'), 0) AS corbatas FROM empleado WHERE MONTH(fechanacimiento) = ?");
$st->bind_param('i', $mes);
$st->execute();
$regalos = $st->get_result()->fetch_assoc();
$st->close();

// 1b. Quiénes cumplen
$st = $cx->prepare('SELECT nombre, sexo, DAY(fechanacimiento) AS dia FROM empleado WHERE MONTH(fechanacimiento) = ? ORDER BY dia, nombre');
$st->bind_param('i', $mes);
$st->execute();
$cumples = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// 2. Empleados por sexo (siempre las dos categorías)
$porSexo = ['f' => 0, 'm' => 0];
foreach ($cx->query('SELECT sexo, COUNT(*) AS cantidad FROM empleado GROUP BY sexo') as $r) {
    $porSexo[$r['sexo']] = (int) $r['cantidad'];
}

// 3. Simulación de aumento
$aum = AUMENTO_SIMULADO;
$opciones = $cx->query('SELECT documento, nombre FROM empleado ORDER BY nombre ASC')->fetch_all(MYSQLI_ASSOC);
$emp = trim((string) ($_GET['emp'] ?? ''));
if (!in_array($emp, array_column($opciones, 'documento'), true)) {
    $emp = '';
}
$sqlSim = 'SELECT nombre, sueldobasico, ROUND(sueldobasico * ?, 2) AS aumento, ROUND(sueldobasico * (1 + ?), 2) AS nuevo FROM empleado'
        . ($emp !== '' ? ' WHERE documento = ?' : '') . ' ORDER BY nombre ASC';
$st = $cx->prepare($sqlSim);
if ($emp !== '') {
    $st->bind_param('dds', $aum, $aum, $emp);
} else {
    $st->bind_param('dd', $aum, $aum);
}
$st->execute();
$simulacion = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
$tot = ['actual' => 0.0, 'aumento' => 0.0, 'nuevo' => 0.0];
foreach ($simulacion as $s) {
    $tot['actual']  += (float) $s['sueldobasico'];
    $tot['aumento'] += (float) $s['aumento'];
    $tot['nuevo']   += (float) $s['nuevo'];
}

// 4. Nómina por sexo
$nomina = ['f' => 0.0, 'm' => 0.0];
foreach ($cx->query('SELECT sexo, SUM(sueldobasico) AS nomina FROM empleado GROUP BY sexo') as $r) {
    $nomina[$r['sexo']] = (float) $r['nomina'];
}
$nominaTotal = array_sum($nomina);

// 5. Ganan más del mínimo
$minimo = SALARIO_MINIMO;
$st = $cx->prepare('SELECT COUNT(*) AS cantidad FROM empleado WHERE sueldobasico > ?');
$st->bind_param('d', $minimo);
$st->execute();
$masDelMinimo = (int) $st->get_result()->fetch_assoc()['cantidad'];
$st->close();

$st = $cx->prepare('SELECT nombre, sueldobasico FROM empleado WHERE sueldobasico > ? ORDER BY nombre ASC');
$st->bind_param('d', $minimo);
$st->execute();
$ganaMas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Extra. Aniversarios laborales del mes (años cumplidos este año; hito cada 5 años)
$anio = (int) date('Y');
$st = $cx->prepare('SELECT nombre, DAY(fechaingreso) AS dia, ? - YEAR(fechaingreso) AS anios FROM empleado WHERE MONTH(fechaingreso) = ? AND ? - YEAR(fechaingreso) >= 1 ORDER BY dia, nombre');
$st->bind_param('iii', $anio, $mes, $anio);
$st->execute();
$aniversarios = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
$hitos = count(array_filter($aniversarios, fn($a) => (int) $a['anios'] % 5 === 0));

$pct = (int) round($aum * 100);
$titulo  = 'Consultas';
$pestana = 'consultas';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Consultas</h2>
        <p>Las 5 consultas del ejercicio, calculadas en vivo desde la base de datos.</p>
    </div>
</div>

<div class="dashboard">
    <section class="metric-card birthday-card area-cumple" aria-labelledby="t-cumple">
        <form method="get" action="consultas.php" class="card-title-row">
            <?php if ($emp !== ''): ?><input type="hidden" name="emp" value="<?= e($emp) ?>"><?php endif; ?>
            <div><span class="consulta-n">Consulta 1</span><h3 id="t-cumple">Cumpleaños del mes: rosas y corbatas</h3></div>
            <div class="month-select">
                <label class="sr-only" for="mes">Seleccionar mes</label>
                <select id="mes" name="mes">
                    <?php foreach ($nombres_mes as $n => $nom): ?>
                        <option value="<?= $n ?>" <?= $n === $mes ? 'selected' : '' ?>><?= e($nom) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button secondary">Ver</button>
            </div>
        </form>
        <div class="birthday-counts">
            <div><strong data-valor="<?= (int) $regalos['rosas'] ?>"><?= (int) $regalos['rosas'] ?></strong> <span>ramos de rosas</span></div>
            <div><strong data-valor="<?= (int) $regalos['corbatas'] ?>"><?= (int) $regalos['corbatas'] ?></strong> <span>corbatas</span></div>
        </div>
        <?php if ($cumples): ?>
            <p class="feliz">¡FELIZ CUMPLE!</p>
            <ul class="birthday-list">
                <?php foreach ($cumples as $c): ?>
                    <li><span><?= e($c['nombre']) ?></span><span><?= (int) $c['dia'] ?> de <?= e(mb_strtolower($nombres_mes[$mes], 'UTF-8')) ?> – <?= $c['sexo'] === 'f' ? 'rosas' : 'corbata' ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-results">Nadie cumple años este mes.</p>
        <?php endif; ?>
    </section>

    
        <section class="metric-card area-sexo" aria-labelledby="t-sexo">
            <span class="consulta-n">Consulta 2</span><h3 id="t-sexo">Empleados por sexo</h3>
            <div class="split-metrics">
                <div><span>Femenino</span><strong data-valor="<?= $porSexo['f'] ?>"><?= $porSexo['f'] ?></strong></div>
                <div><span>Masculino</span><strong data-valor="<?= $porSexo['m'] ?>"><?= $porSexo['m'] ?></strong></div>
            </div>
        </section>

        <section class="metric-card area-nomina" aria-labelledby="t-nomina">
            <span class="consulta-n">Consulta 4</span><h3 id="t-nomina">Nómina por sexo</h3>
            <dl class="summary-list">
                <div><dt>Femenino</dt><dd><?= e(dinero($nomina['f'])) ?></dd></div>
                <div><dt>Masculino</dt><dd><?= e(dinero($nomina['m'])) ?></dd></div>
                <div class="summary-total"><dt>Total</dt><dd><?= e(dinero($nominaTotal)) ?></dd></div>
            </dl>
        </section>

        <section class="metric-card area-min" aria-labelledby="t-min">
            <span class="consulta-n">Consulta 5</span><h3 id="t-min">Ganan más del salario mínimo</h3>
            <div class="single-metric"><strong data-valor="<?= $masDelMinimo ?>"><?= $masDelMinimo ?></strong> <span>empleados</span></div>
            <?php if ($ganaMas): ?>
                <ul class="lista-quien">
                    <?php foreach ($ganaMas as $g): ?>
                        <li><span><?= e($g['nombre']) ?></span><span class="tabular"><?= e(dinero((float) $g['sueldobasico'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p class="note">Salario mínimo usado: <?= e(dinero($minimo)) ?>. Quien gana exactamente el mínimo no cuenta.</p>
        </section>

    <section class="metric-card raise-card area-aumento" aria-labelledby="t-aum">
        <span class="consulta-n">Consulta 3</span><h3 id="t-aum">Simulación de aumento del <?= $pct ?> %</h3>
        <p class="note">Simulación: no modifica los sueldos guardados.</p>
        <form method="get" action="consultas.php" class="filtro-emp">
            <input type="hidden" name="mes" value="<?= $mes ?>">
            <label for="emp">Empleado</label>
            <select id="emp" name="emp" data-autoenviar>
                <option value="">Todos (<?= count($opciones) ?>)</option>
                <?php foreach ($opciones as $o): ?>
                    <option value="<?= e($o['documento']) ?>" <?= $o['documento'] === $emp ? 'selected' : '' ?>><?= e($o['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button secondary">Ver</button>
        </form>
        <div class="table-scroll scroll-alto">
            <table>
                <caption class="sr-only">Simulación de aumento del <?= $pct ?> % por empleado</caption>
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col" class="numeric">Sueldo actual</th>
                        <th scope="col" class="numeric">Aumento</th>
                        <th scope="col" class="numeric">Nuevo sueldo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($simulacion as $i => $s): ?>
                    <tr style="--i:<?= $i ?>">
                        <td class="employee-name"><?= e($s['nombre']) ?></td>
                        <td class="numeric"><?= e(dinero((float) $s['sueldobasico'])) ?></td>
                        <td class="numeric"><?= e(dinero((float) $s['aumento'])) ?></td>
                        <td class="numeric"><?= e(dinero((float) $s['nuevo'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($emp === ''): ?>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="numeric"><?= e(dinero($tot['actual'])) ?></td>
                        <td class="numeric"><?= e(dinero($tot['aumento'])) ?></td>
                        <td class="numeric"><?= e(dinero($tot['nuevo'])) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </section>

    <section class="metric-card area-aniv" aria-labelledby="t-aniv">
        <span class="consulta-n">Extra</span>
        <h3 id="t-aniv">Aniversarios laborales de <?= e(mb_strtolower($nombres_mes[$mes], 'UTF-8')) ?></h3>
        <p class="note">Años en la empresa que se cumplen este mes (según la fecha de ingreso). Cada 5 años es un hito para reconocer. Cambia el mes en la Consulta 1.</p>
        <?php if ($aniversarios): ?>
            <p class="aniv-resumen"><strong data-valor="<?= count($aniversarios) ?>"><?= count($aniversarios) ?></strong> <?= count($aniversarios) === 1 ? 'aniversario' : 'aniversarios' ?> · <strong data-valor="<?= $hitos ?>"><?= $hitos ?></strong> <?= $hitos === 1 ? 'hito' : 'hitos' ?></p>
            <ul class="aniv-lista">
                <?php foreach ($aniversarios as $a): $hito = (int) $a['anios'] % 5 === 0; ?>
                    <li class="<?= $hito ? 'es-hito' : '' ?>">
                        <span class="aniv-anios"><?= (int) $a['anios'] ?><small>años</small></span>
                        <span class="aniv-quien"><?= e($a['nombre']) ?><small><?= (int) $a['dia'] ?> de <?= e(mb_strtolower($nombres_mes[$mes], 'UTF-8')) ?></small></span>
                        <?php if ($hito): ?><span class="aniv-hito">¡HITO!</span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-results">Nadie cumple aniversario laboral este mes.</p>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

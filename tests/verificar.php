<?php
// Verificación automática: resultados esperados de las consultas y reglas de validación.
// Uso: php tests/verificar.php   (variables opcionales: DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/funciones.php';
date_default_timezone_set('America/Bogota');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$cx = new mysqli(
    getenv('DB_HOST') ?: DB_HOST,
    getenv('DB_USER') ?: DB_USER,
    getenv('DB_PASS') !== false ? getenv('DB_PASS') : DB_PASS,
    getenv('DB_NAME') ?: DB_NAME,
    (int) (getenv('DB_PORT') ?: 3306)
);
$cx->set_charset('utf8mb4');

$fallos = 0;
function verificar(string $nombre, $obtenido, $esperado): void
{
    global $fallos;
    $ok = $obtenido == $esperado;
    if (!$ok) {
        $fallos++;
    }
    printf("[%s] %s%s\n", $ok ? 'OK' : 'FALLO', $nombre, $ok ? '' : " (esperado " . json_encode($esperado) . ", obtenido " . json_encode($obtenido) . ")");
}
function uno(mysqli $cx, string $sql, array $params = [], string $tipos = ''): array
{
    $st = $cx->prepare($sql);
    if ($params) {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    $fila = $st->get_result()->fetch_assoc();
    $st->close();
    return $fila;
}

// --- Consultas (datos originales del script SQL) ---
verificar('9 empleados cargados', (int) uno($cx, 'SELECT COUNT(*) n FROM empleado')['n'], 9);

$mes = 5;
$r = uno($cx, "SELECT COALESCE(SUM(sexo='f'),0) rosas, COALESCE(SUM(sexo='m'),0) corbatas FROM empleado WHERE MONTH(fechanacimiento)=?", [$mes], 'i');
verificar('Mayo: rosas', (int) $r['rosas'], 1);
verificar('Mayo: corbatas', (int) $r['corbatas'], 2);

$mes = 3;
$r = uno($cx, "SELECT COALESCE(SUM(sexo='f'),0) rosas, COALESCE(SUM(sexo='m'),0) corbatas FROM empleado WHERE MONTH(fechanacimiento)=?", [$mes], 'i');
verificar('Marzo: rosas', (int) $r['rosas'], 0);
verificar('Marzo: corbatas', (int) $r['corbatas'], 2);

verificar('Femenino', (int) uno($cx, "SELECT COUNT(*) n FROM empleado WHERE sexo='f'")['n'], 4);
verificar('Masculino', (int) uno($cx, "SELECT COUNT(*) n FROM empleado WHERE sexo='m'")['n'], 5);
verificar('Nómina femenino', (float) uno($cx, "SELECT SUM(sueldobasico) s FROM empleado WHERE sexo='f'")['s'], 12200000.00);
verificar('Nómina masculino', (float) uno($cx, "SELECT SUM(sueldobasico) s FROM empleado WHERE sexo='m'")['s'], 14480905.00);

$min = SALARIO_MINIMO;
verificar('Ganan más del mínimo (estricto)', (int) uno($cx, 'SELECT COUNT(*) n FROM empleado WHERE sueldobasico > ?', [$min], 'd')['n'], 8);

$aum = AUMENTO_SIMULADO;
$r = uno($cx, 'SELECT SUM(ROUND(sueldobasico*(1+?),2)) nuevo, SUM(ROUND(sueldobasico*?,2)) aumento FROM empleado', [$aum, $aum], 'dd');
verificar('Simulación: total nuevo', round((float) $r['nuevo'], 2), 29348995.50);
verificar('Simulación: total aumento', round((float) $r['aumento'], 2), 2668090.50);

// --- Validación (sin tocar la base) ---
$base = ['documento' => '1023456789', 'nombre' => '  José   Ñúñez ', 'sexo' => 'm', 'fechanacimiento' => '1990-01-01',
         'domicilio' => 'Calle 45 # 12-30', 'fechaingreso' => '2020-01-01', 'sueldobasico' => '2.500.000,50'];
function errores(array $cambios, bool $nuevo = false): array
{
    global $base, $cx;
    return validar_empleado(array_merge($base, $cambios), $nuevo, $cx)[1];
}
[$datos, $errs] = validar_empleado($base, false, $cx);
verificar('Válido sin errores', $errs, []);
verificar('Nombre normalizado', $datos['nombre'], 'José Ñúñez');
verificar('Sueldo normalizado', $datos['sueldobasico'], 2500000.50);
verificar('Documento duplicado', errores(['documento' => '22333111'], true)['documento'] ?? '', 'Ya existe un empleado con el documento 22333111.');
verificar('Documento con letras', errores(['documento' => '12ab45'])['documento'] ?? '', 'Solo se permiten números.');
verificar('Documento corto', errores(['documento' => '12345'])['documento'] ?? '', 'Debe tener entre 6 y 10 dígitos.');
verificar('Nombre con número', errores(['nombre' => 'Juan3'])['nombre'] ?? '', "Usa solo letras y espacios (se permiten ' y -).");
verificar("Nombre O'Brien válido", errores(['nombre' => "O'Brien"]), []);
verificar('Sexo manipulado', errores(['sexo' => 'x'])['sexo'] ?? '', 'Selecciona el sexo.');
verificar('Fecha imposible', errores(['fechanacimiento' => '2023-02-30'])['fechanacimiento'] ?? '', 'Escribe una fecha válida.');
verificar('Menor de edad', errores(['fechanacimiento' => date('Y-m-d', strtotime('-17 years'))])['fechanacimiento'] ?? '', 'El empleado debe ser mayor de edad.');
verificar('Ingreso futuro', errores(['fechaingreso' => date('Y-m-d', strtotime('+1 day'))])['fechaingreso'] ?? '', 'La fecha de ingreso no puede ser futura.');
verificar('Ingreso siendo menor', errores(['fechanacimiento' => '1980-03-29', 'fechaingreso' => '1980-09-01'])['fechaingreso'] ?? '', 'El empleado debía tener 18 años cumplidos al ingresar.');
verificar('Domicilio con HTML', errores(['domicilio' => 'Calle <b>1</b>'])['domicilio'] ?? '', 'Usa solo letras, números, espacios y # - . , °');
verificar('Sueldo bajo el mínimo', isset(errores(['sueldobasico' => '1.750.904'])['sueldobasico']), true);
verificar('Sueldo igual al mínimo', errores(['sueldobasico' => '1.750.905']), []);
verificar('Sueldo con punto decimal', errores(['sueldobasico' => '550.5'])['sueldobasico'] ?? '', 'Usa el formato 1.250.500,50 (coma para decimales).');
verificar('Sueldo sobre el máximo', isset(errores(['sueldobasico' => '100.000.000'])['sueldobasico']), true);

echo $fallos === 0 ? "\nTodo correcto.\n" : "\n$fallos verificación(es) fallaron.\n";
exit($fallos === 0 ? 0 : 1);

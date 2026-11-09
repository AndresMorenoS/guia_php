<?php
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function dinero(float $v): string
{
    return '$' . number_format($v, 2, ',', '.') . ' COP';
}

function fecha_co(string $ymd): string
{
    $f = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return $f ? $f->format('d/m/Y') : $ymd;
}

function sexo_texto(string $s): string
{
    return $s === 'f' ? 'Femenino' : 'Masculino';
}

function meses(): array
{
    return [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
}

function normalizar_sueldo(string $txt): ?float
{
    $t = str_replace(['$', ' '], '', trim($txt));
    if (!preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $t) && !preg_match('/^\d+(,\d{1,2})?$/', $t)) {
        return null;
    }
    return (float) str_replace(',', '.', str_replace('.', '', $t));
}

function fecha_valida(string $ymd): ?DateTimeImmutable
{
    $f = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return ($f && $f->format('Y-m-d') === $ymd) ? $f : null;
}

function edad_en(DateTimeImmutable $nac, DateTimeImmutable $dia): int
{
    return $nac->diff($dia)->y;
}

function validar_empleado(array $in, bool $esNuevo, mysqli $cx): array
{
    $errores = [];
    $hoy = new DateTimeImmutable('today');

    $doc = trim((string) ($in['documento'] ?? ''));
    if ($doc === '') {
        $errores['documento'] = 'Escribe el documento.';
    } elseif (!preg_match('/^[0-9]+$/', $doc)) {
        $errores['documento'] = 'Solo se permiten números.';
    } elseif (strlen($doc) < 6 || strlen($doc) > 10) {
        $errores['documento'] = 'Debe tener entre 6 y 10 dígitos.';
    } elseif ($esNuevo) {
        $st = $cx->prepare('SELECT 1 FROM empleado WHERE documento = ?');
        $st->bind_param('s', $doc);
        $st->execute();
        if ($st->get_result()->num_rows > 0) {
            $errores['documento'] = "Ya existe un empleado con el documento $doc.";
        }
        $st->close();
    }

    $nombre = trim(preg_replace('/\s+/u', ' ', (string) ($in['nombre'] ?? '')));
    $len = mb_strlen($nombre, 'UTF-8');
    if ($nombre === '') {
        $errores['nombre'] = 'Escribe el nombre.';
    } elseif ($len < 3 || $len > 60) {
        $errores['nombre'] = 'Debe tener entre 3 y 60 caracteres.';
    } elseif (!preg_match("/^[\\p{L}' -]+$/u", $nombre)) {
        $errores['nombre'] = "Usa solo letras y espacios (se permiten ' y -).";
    }

    $sexo = (string) ($in['sexo'] ?? '');
    if ($sexo !== 'f' && $sexo !== 'm') {
        $errores['sexo'] = 'Selecciona el sexo.';
    }

    $dom = trim((string) ($in['domicilio'] ?? ''));
    $len = mb_strlen($dom, 'UTF-8');
    if ($dom === '') {
        $errores['domicilio'] = 'Escribe el domicilio.';
    } elseif ($len < 5 || $len > 80) {
        $errores['domicilio'] = 'Debe tener entre 5 y 80 caracteres.';
    } elseif (!preg_match('/^[\p{L}\p{N} #\-.,°]+$/u', $dom)) {
        $errores['domicilio'] = 'Usa solo letras, números, espacios y # - . , °';
    }

    $nacTxt = trim((string) ($in['fechanacimiento'] ?? ''));
    $nac = fecha_valida($nacTxt);
    if (!$nac) {
        $errores['fechanacimiento'] = 'Escribe una fecha válida.';
    } elseif ($nac > $hoy) {
        $errores['fechanacimiento'] = 'La fecha de nacimiento no puede ser futura.';
    } elseif ($nac < $hoy->modify('-100 years')) {
        $errores['fechanacimiento'] = 'La fecha de nacimiento no puede ser de hace más de 100 años.';
    } elseif (edad_en($nac, $hoy) < 18) {
        $errores['fechanacimiento'] = 'El empleado debe ser mayor de edad.';
    }

    $ingTxt = trim((string) ($in['fechaingreso'] ?? ''));
    $ing = fecha_valida($ingTxt);
    if (!$ing) {
        $errores['fechaingreso'] = 'Escribe una fecha válida.';
    } elseif ($ing > $hoy) {
        $errores['fechaingreso'] = 'La fecha de ingreso no puede ser futura.';
    } elseif ($nac && !isset($errores['fechanacimiento']) && edad_en($nac, $ing) < 18) {
        $errores['fechaingreso'] = 'El empleado debía tener 18 años cumplidos al ingresar.';
    }

    $sueldoTxt = trim((string) ($in['sueldobasico'] ?? ''));
    $sueldo = null;
    if ($sueldoTxt === '') {
        $errores['sueldobasico'] = 'Escribe el sueldo.';
    } else {
        $sueldo = normalizar_sueldo($sueldoTxt);
        if ($sueldo === null) {
            $errores['sueldobasico'] = 'Usa el formato 1.250.500,50 (coma para decimales).';
        } elseif ($sueldo < SALARIO_MINIMO) {
            $errores['sueldobasico'] = 'El sueldo no puede ser menor al salario mínimo (' . dinero(SALARIO_MINIMO) . ').';
        } elseif ($sueldo > SUELDO_MAXIMO) {
            $errores['sueldobasico'] = 'El sueldo no puede superar ' . dinero(SUELDO_MAXIMO) . '.';
        }
    }

    $datos = [
        'documento'       => $doc,
        'nombre'          => $nombre,
        'sexo'            => $sexo,
        'domicilio'       => $dom,
        'fechanacimiento' => $nacTxt,
        'fechaingreso'    => $ingTxt,
        'sueldobasico'    => $sueldo,
        'sueldo_texto'    => $sueldoTxt,
    ];
    return [$datos, $errores];
}

function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function flash_tomar(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(?string $t): bool
{
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

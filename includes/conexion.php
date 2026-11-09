<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $cx = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $cx->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    error_log('Conexión fallida: ' . $ex->getMessage());
    http_response_code(500);
    echo '<!doctype html><html lang="es-CO"><head><meta charset="utf-8"><title>Sin conexión</title></head>'
       . '<body style="font-family:system-ui,sans-serif;padding:2rem"><h1>¡OJO!</h1>'
       . '<p>No se pudo conectar con la base de datos. Revisa que MySQL esté encendido en XAMPP.</p></body></html>';
    exit;
}

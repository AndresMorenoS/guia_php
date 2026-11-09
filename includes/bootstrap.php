<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('America/Bogota');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';

<?php

// Bloquear acceso directo
if (!defined('ACCESS_ALLOWED')) {
    die('Acesso negado');
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'calebito_admin');
define('DB_PASS', 'ProdFeb10**');
define('DB_NAME', 'calebito_transporte_db');

// Charset recomendado
define('DB_CHARSET', 'utf8mb4');
?>
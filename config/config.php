<?php
// Configurações principais do sistema para ambiente local (XAMPP).
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bruno_sistema');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'Sistema Comercial');
define('BASE_URL', '/bruno');

// Fuso horário padrão para relatórios e dashboard.
date_default_timezone_set('America/Sao_Paulo');

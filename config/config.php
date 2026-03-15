<?php
// Configurações principais do sistema para ambiente local (XAMPP).
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bruno_sistema');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'Sistema Comercial');
define('BASE_URL', '/bruno');

// Configurações de impressão térmica 80mm.
define('PRINT_EMPRESA_NOME', 'BoldriniSystem');
define('PRINT_EMPRESA_TELEFONE', '(00) 00000-0000');
define('PRINT_EMPRESA_INSTAGRAM', '@boldrinisystem');
define('PRINT_CREDIARIO_SEGUNDA_VIA', true);
define('PRINT_RODAPE_TEXTO', 'Obrigado pela preferência!');

// Fuso horário padrão para relatórios e dashboard.
date_default_timezone_set('America/Sao_Paulo');

<?php
// includes/config.php

// Verifica se as constantes já foram definidas para evitar redefinição
if (!defined('BASE_URL')) {
    // DEFINA MANUALMENTE A BASE_URL PARA O SEU AMBIENTE
    // Para desenvolvimento local com XAMPP:
    define('BASE_URL', 'http://localhost/leaf/');
    
    // Para produção (exemplo):
    // define('BASE_URL', 'https://seusite.com/');
    
    // Configurações do banco de dados
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'leaf_market');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Função para gerar URLs absolutas (apenas se não existir)
if (!function_exists('url')) {
    function url($path = '') {
        // Remove qualquer barra inicial duplicada
        $path = ltrim($path, '/');
        return BASE_URL . $path;
    }
}
?>
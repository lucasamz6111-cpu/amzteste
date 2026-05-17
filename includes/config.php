<?php
/**
 * AmazonGest Pro - Configurações Globais
 * Sistema Profissional de Gestão
 */

// Configurações do Ambiente
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

// Diretórios de Dados
define('DATA_DIR', __DIR__ . '/../data/');
define('ASSETS_DIR', __DIR__ . '/../assets/');
define('ICON_DIR', ASSETS_DIR . 'icons/');

// Arquivos de Dados
define('PEDIDOS_FILE', DATA_DIR . 'pedidos.json');
define('PRODUTOS_FILE', DATA_DIR . 'produtos.json');
define('CLIENTES_FILE', DATA_DIR . 'clientes.json');
define('CONFIG_FILE', DATA_DIR . 'config.json');
define('API_KEYS_FILE', DATA_DIR . 'api-keys.json');

// Configurações de API
define('API_VERSION', 'v1');
define('API_TIMEOUT', 30);
define('MAX_REQUESTS_PER_HOUR', 1000);

// Validações
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Temas Disponíveis
define('THEMES_AVAILABLE', ['dark', 'light', 'purple']);
define('DEFAULT_THEME', 'dark');

// Configurações de Cache
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hora

// Garante que o diretório de dados exista
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}

// Configurações CORS (se necessário)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
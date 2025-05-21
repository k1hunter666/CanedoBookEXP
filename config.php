<?php
// Mostrar erros para facilitar debug em ambiente local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminho do arquivo .env
$envPath = __DIR__ . '/.env';

// Verifica se o arquivo .env existe
if (!file_exists($envPath)) {
    die('Erro: Arquivo .env não encontrado.');
}

// Carrega variáveis do .env (formato ini, sem aspas)
$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);
if ($env === false) {
    die('Erro ao carregar o arquivo .env');
}

// Lista das variáveis obrigatórias
$requiredEnv = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'];

// Valida se todas as variáveis obrigatórias existem no $env
foreach ($requiredEnv as $envVar) {
    if (!array_key_exists($envVar, $env)) {
        throw new Exception("Erro: A variável '$envVar' não está definida no arquivo .env");
    }
}

// Define constantes para facilitar o uso no restante da aplicação
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('DB_CHARSET', $env['DB_CHARSET']);

define('APP_ENV', $env['APP_ENV'] ?? 'production');
define('APP_DEBUG', filter_var($env['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

// Configurações de email (opcionais)
define('MAIL_HOST', $env['MAIL_HOST'] ?? '');
define('MAIL_PORT', $env['MAIL_PORT'] ?? '');
define('MAIL_USERNAME', $env['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $env['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM', $env['MAIL_FROM'] ?? '');
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME'] ?? 'CanedoBooks');

/**
 * Retorna uma conexão PDO com o banco de dados
 * @return PDO
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Erro na conexão com o banco de dados: " . $e->getMessage());
    }
}

<?php
// Carga de variables .env simples
function env($key, $default = null) {
  static $vars = null;
  if ($vars === null) {
    $vars = [];
    $envPath = __DIR__ . '/.env';
    if (is_readable($envPath)) {
      foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $vars[$k] = $v;
      }
    }
  }
  return $vars[$key] ?? $default;
}

function db() : PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', env('PG_HOST','127.0.0.1'), env('PG_PORT','5432'), env('PG_DB','bolsa_trabajo'));
  $pdo = new PDO($dsn, env('PG_USER','postgres'), env('PG_PASS','postgres'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function base_url(): string {
  return env('APP_BASE', '/postulantes.php');
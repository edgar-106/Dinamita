<?php
/**
 * Cargador ligero de variables de entorno (.env)
 * INFONATEC Inmobiliaria
 */

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $pos = strpos($trimmed, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($trimmed, 0, $pos));
            $val = trim(substr($trimmed, $pos + 1));

            if ($key === '') {
                continue;
            }

            // Quitar comillas simples o dobles envolventes si existen
            $len = strlen($val);
            if ($len >= 2) {
                $first = $val[0];
                $last = $val[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            // No sobrescribir variables ya definidas en el entorno del proceso/servidor
            if (getenv($key) === false && !array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return (string) $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return (string) $val;
        }
        return $default;
    }
}

loadEnvFile(dirname(__DIR__, 2) . '/.env');

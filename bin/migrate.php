#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

function envOrDefault(string $key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function getDbConnection(): PDO {
    $dbHost = envOrDefault('DB_HOST', '');
    $dbName = envOrDefault('DB_NAME', '');
    $dbUser = envOrDefault('DB_USER', '');
    $dbPass = envOrDefault('DB_PASS', '');
    return new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function ensureLeadLifecycleColumns(PDO $pdo): void {
    $checks = [
        'contactos_revelados' => [
            ['column' => 'retention_stage', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN retention_stage VARCHAR(20) NOT NULL DEFAULT 'activo'"],
            ['column' => 'activo_hasta', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN activo_hasta DATETIME NULL"],
            ['column' => 'papelera_desde', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN papelera_desde DATETIME NULL"],
            ['column' => 'papelera_hasta', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN papelera_hasta DATETIME NULL"],
            ['column' => 'respaldado_at', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN respaldado_at DATETIME NULL"],
            ['column' => 'estado_updated_at', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN estado_updated_at DATETIME NULL"],
            ['column' => 'abogado_vio_at', 'sql' => "ALTER TABLE contactos_revelados ADD COLUMN abogado_vio_at DATETIME NULL"],
        ],
    ];

    foreach ($checks as $table => $columns) {
        foreach ($columns as $entry) {
            if (!dbColumnExists($pdo, $table, $entry['column'])) {
                $pdo->exec($entry['sql']);
            }
        }
    }

    if (!dbColumnExists($pdo, 'contactos_revelados', 'estado_updated_at')) {
        $pdo->exec("UPDATE contactos_revelados SET estado_updated_at = COALESCE(fecha_cierre, fecha_revelado, created_at, NOW()) WHERE estado_updated_at IS NULL");
    }
}

function ensureWebMetricsTable(PDO $pdo): void {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS web_metric_events (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                event_name VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                path VARCHAR(255) NULL,
                content_type VARCHAR(48) NULL,
                content_id INT NULL,
                content_slug VARCHAR(191) NULL,
                user_id INT NULL,
                role VARCHAR(32) NULL,
                session_hash CHAR(64) NULL,
                ip VARCHAR(64) NULL,
                ip_hash CHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                referer VARCHAR(255) NULL,
                traffic_class ENUM('human','bot','admin_test') NOT NULL DEFAULT 'human',
                is_bot TINYINT(1) NOT NULL DEFAULT 0,
                raw_counted TINYINT(1) NOT NULL DEFAULT 1,
                human_counted TINYINT(1) NOT NULL DEFAULT 1,
                source VARCHAR(32) NULL,
                payload_json TEXT NULL,
                dedupe_key CHAR(64) NOT NULL,
                UNIQUE KEY uniq_dedupe (dedupe_key),
                KEY idx_event_created (event_name, created_at),
                KEY idx_traffic_created (traffic_class, created_at),
                KEY idx_content (content_type, content_id, created_at),
                KEY idx_ip_class (ip, traffic_class, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        fwrite(STDERR, "Failed to ensure web_metric_events table: {$e->getMessage()}\n");
    }
}

function dbColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetchColumn();
}

try {
    $pdo = getDbConnection();
    ensureLeadLifecycleColumns($pdo);
    ensureWebMetricsTable($pdo);
    echo "Schema checks passed.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

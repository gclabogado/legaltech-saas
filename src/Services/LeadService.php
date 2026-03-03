<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

class LeadService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function fetchLeadCounts(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                SUM(CASE WHEN COALESCE(retention_stage,'activo') = 'activo' THEN 1 ELSE 0 END) AS leads_activos,
                SUM(CASE WHEN COALESCE(retention_stage,'activo') = 'papelera' THEN 1 ELSE 0 END) AS leads_papelera,
                SUM(CASE WHEN abogado_vio_at IS NULL THEN 1 ELSE 0 END) AS leads_sin_ver
            FROM contactos_revelados
        ");
        return array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
    }

    public function listLeads(string $stage = '', bool $onlyUnseen = false, int $limit = 100): array
    {
        $sql = "
            SELECT cr.*, a.nombre AS abogado_nombre, a.email AS abogado_email, c.nombre AS cliente_nombre, c.whatsapp AS cliente_whatsapp
            FROM contactos_revelados cr
            LEFT JOIN abogados a ON a.id = cr.abogado_id
            LEFT JOIN abogados c ON c.id = cr.cliente_id
        ";
        $conditions = [];
        $params = [];
        if ($stage !== '') {
            $conditions[] = "COALESCE(cr.retention_stage,'activo') = ?";
            $params[] = $stage;
        }
        if ($onlyUnseen) {
            $conditions[] = "cr.abogado_vio_at IS NULL";
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY COALESCE(cr.fecha_revelado, cr.fecha_cierre) DESC, cr.id DESC LIMIT ?";
        $params[] = $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

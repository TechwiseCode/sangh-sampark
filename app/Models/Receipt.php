<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Receipt
{
    private static bool $tableEnsured = false;

    public function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS receipts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                family_id INT UNSIGNED NOT NULL,
                recipient_user_id INT UNSIGNED NOT NULL,
                receipt_no INT UNSIGNED NOT NULL DEFAULT 0,
                due_definition_id INT UNSIGNED NULL DEFAULT NULL,
                purpose VARCHAR(255) NOT NULL,
                description TEXT NULL,
                amount DECIMAL(12,2) NOT NULL,
                receipt_date DATE NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_receipts_org_fy (organization_id, financial_year),
                KEY idx_receipts_recipient (recipient_user_id),
                UNIQUE KEY uq_receipts_org_fy_no (organization_id, financial_year, receipt_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->ensureReceiptNoColumn();
        $this->ensureDueColumns();
        self::$tableEnsured = true;
    }

    public function create(
        int $organizationId,
        int $familyId,
        int $recipientUserId,
        ?int $dueDefinitionId,
        string $purpose,
        ?string $description,
        float $amount,
        string $receiptDate,
        string $financialYear,
        ?int $createdByUserId
    ): int {
        $this->ensureTable();
        $nextNo = $this->nextReceiptNo($organizationId, $financialYear);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO receipts
            (organization_id, family_id, recipient_user_id, receipt_no, due_definition_id, purpose, description, amount, receipt_date, financial_year, created_by_user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $organizationId,
            $familyId,
            $recipientUserId,
            $nextNo,
            $dueDefinitionId,
            $purpose,
            $description,
            $amount,
            $receiptDate,
            $financialYear,
            $createdByUserId,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array<string,mixed> $filters
     *  @return list<array<string,mixed>> */
    public function listByOrganization(int $organizationId, array $filters = []): array
    {
        $this->ensureTable();
        $sql = 'SELECT r.*, u.name AS recipient_name, u.phone AS recipient_phone, f.head_user_id,
                o.name AS organization_name,
                dd.due_type AS due_type, dd.charge_basis AS charge_basis, dd.amount AS due_rate
            FROM receipts r
            INNER JOIN users u ON u.id = r.recipient_user_id
            INNER JOIN families f ON f.id = r.family_id
            LEFT JOIN organizations o ON o.id = r.organization_id
            LEFT JOIN due_definitions dd ON dd.id = r.due_definition_id
            WHERE r.organization_id = ?';
        $params = [$organizationId];

        $financialYear = trim((string) ($filters['financial_year'] ?? ''));
        if ($financialYear !== '') {
            $sql .= ' AND r.financial_year = ?';
            $params[] = $financialYear;
        }

        $recipientUserId = (int) ($filters['recipient_user_id'] ?? 0);
        if ($recipientUserId > 0) {
            $sql .= ' AND r.recipient_user_id = ?';
            $params[] = $recipientUserId;
        }

        $listDueDefinitionId = (int) ($filters['list_due_definition_id'] ?? 0);
        if ($listDueDefinitionId > 0) {
            $sql .= ' AND r.due_definition_id = ?';
            $params[] = $listDueDefinitionId;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $searchParts = ['u.name LIKE ?', 'r.purpose LIKE ?', 'u.phone LIKE ?'];
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $phoneDigits = \normalize_phone($q);
            if ($phoneDigits !== null && $phoneDigits !== $q) {
                $searchParts[] = 'REPLACE(REPLACE(REPLACE(REPLACE(u.phone, " ", ""), "-", ""), "+", ""), ".", "") LIKE ?';
                $params[] = '%' . $phoneDigits . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $searchParts) . ')';
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $sql .= ' AND r.receipt_date >= ?';
            $params[] = $dateFrom;
        }
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $sql .= ' AND r.receipt_date <= ?';
            $params[] = $dateTo;
        }

        $sql .= ' ORDER BY r.receipt_date DESC, r.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdInOrganization(int $receiptId, int $organizationId): ?array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, u.name AS recipient_name, u.phone AS recipient_phone,
                o.name AS organization_name, o.org_code,
                dd.due_type AS due_type, dd.charge_basis AS charge_basis, dd.amount AS due_rate
            FROM receipts r
            INNER JOIN users u ON u.id = r.recipient_user_id
            INNER JOIN organizations o ON o.id = r.organization_id
            LEFT JOIN due_definitions dd ON dd.id = r.due_definition_id
            WHERE r.id = ? AND r.organization_id = ?
            LIMIT 1'
        );
        $stmt->execute([$receiptId, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function ensureReceiptNoColumn(): void
    {
        $pdo = Database::pdo();
        $existsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'receipts'
            AND COLUMN_NAME = 'receipt_no'"
        );
        $existsStmt->execute();
        $exists = (int) $existsStmt->fetchColumn() > 0;
        if (!$exists) {
            $pdo->exec("ALTER TABLE receipts ADD COLUMN receipt_no INT UNSIGNED NOT NULL DEFAULT 0 AFTER recipient_user_id");
            $pdo->exec(
                "ALTER TABLE receipts ADD UNIQUE KEY uq_receipts_org_fy_no (organization_id, financial_year, receipt_no)"
            );
            $rows = $pdo->query(
                "SELECT id, organization_id, financial_year
                FROM receipts
                ORDER BY organization_id ASC, financial_year ASC, receipt_date ASC, id ASC"
            )->fetchAll(\PDO::FETCH_ASSOC);
            $updateStmt = $pdo->prepare("UPDATE receipts SET receipt_no = ? WHERE id = ?");
            $counters = [];
            foreach ($rows as $row) {
                $key = (string) $row['organization_id'] . '|' . (string) $row['financial_year'];
                $counters[$key] = isset($counters[$key]) ? ($counters[$key] + 1) : 1;
                $updateStmt->execute([$counters[$key], (int) $row['id']]);
            }
        }

        $indexExistsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'receipts'
            AND INDEX_NAME = 'uq_receipts_org_fy_no'"
        );
        $indexExistsStmt->execute();
        $indexExists = (int) $indexExistsStmt->fetchColumn() > 0;
        if (!$indexExists) {
            $pdo->exec(
                "ALTER TABLE receipts ADD UNIQUE KEY uq_receipts_org_fy_no (organization_id, financial_year, receipt_no)"
            );
        }
    }

    private function ensureDueColumns(): void
    {
        $pdo = Database::pdo();
        $dueCol = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'receipts'
            AND COLUMN_NAME = 'due_definition_id'"
        );
        $dueCol->execute();
        if ((int) $dueCol->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE receipts ADD COLUMN due_definition_id INT UNSIGNED NULL DEFAULT NULL AFTER receipt_no");
        }
        $descCol = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'receipts'
            AND COLUMN_NAME = 'description'"
        );
        $descCol->execute();
        if ((int) $descCol->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE receipts ADD COLUMN description TEXT NULL AFTER purpose");
        }
    }

    private function nextReceiptNo(int $organizationId, string $financialYear): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(receipt_no), 0) + 1
            FROM receipts
            WHERE organization_id = ? AND financial_year = ?'
        );
        $stmt->execute([$organizationId, $financialYear]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<string> */
    public function listFinancialYears(int $organizationId): array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT financial_year
            FROM receipts
            WHERE organization_id = ?
            ORDER BY financial_year DESC'
        );
        $stmt->execute([$organizationId]);

        return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }

    /** @return list<array<string,mixed>> */
    public function listByFamily(int $organizationId, int $familyId, int $limit = 30): array
    {
        $this->ensureTable();
        $lim = max(1, min(100, $limit));
        $stmt = Database::pdo()->prepare(
            "SELECT r.*, u.name AS recipient_name, u.phone AS recipient_phone,
                o.name AS organization_name,
                dd.due_type AS due_type, dd.charge_basis AS charge_basis, dd.amount AS due_rate
            FROM receipts r
            INNER JOIN users u ON u.id = r.recipient_user_id
            LEFT JOIN organizations o ON o.id = r.organization_id
            LEFT JOIN due_definitions dd ON dd.id = r.due_definition_id
            WHERE r.organization_id = ? AND r.family_id = ?
            ORDER BY r.receipt_date DESC, r.id DESC
            LIMIT {$lim}"
        );
        $stmt->execute([$organizationId, $familyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Donation
{
    private static bool $tableEnsured = false;

    public function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS donations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                parent_id INT UNSIGNED NULL DEFAULT NULL,
                category_id INT UNSIGNED NOT NULL,
                donor_type ENUM(\'member\', \'guest\') NOT NULL DEFAULT \'member\',
                user_id INT UNSIGNED NULL DEFAULT NULL,
                family_id INT UNSIGNED NULL DEFAULT NULL,
                donor_name VARCHAR(191) NOT NULL,
                donor_phone VARCHAR(32) NULL DEFAULT NULL,
                committed_amount DECIMAL(12,2) NULL DEFAULT NULL,
                committed_date DATE NULL DEFAULT NULL,
                paid_amount DECIMAL(12,2) NULL DEFAULT NULL,
                payment_date DATE NULL DEFAULT NULL,
                financial_year VARCHAR(9) NOT NULL,
                payment_mode ENUM(\'cash\', \'upi\', \'bank\', \'cheque\') NULL DEFAULT NULL,
                reference_no VARCHAR(100) NULL DEFAULT NULL,
                bank_name VARCHAR(100) NULL DEFAULT NULL,
                cheque_date DATE NULL DEFAULT NULL,
                status ENUM(\'open\', \'partial\', \'fulfilled\', \'cancelled\') NULL DEFAULT NULL,
                notes TEXT NULL,
                created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_donations_org_fy (organization_id, financial_year),
                KEY idx_donations_parent (parent_id),
                KEY idx_donations_category (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$tableEnsured = true;
    }

    public static function financialYearForDate(string $date): string
    {
        $dt = new \DateTimeImmutable($date);
        $year = (int) $dt->format('Y');
        $month = (int) $dt->format('n');
        if ($month >= 4) {
            $start = $year;
            $end = $year + 1;
        } else {
            $start = $year - 1;
            $end = $year;
        }

        return (string) $start . '-' . substr((string) $end, -2);
    }

    /** @return list<string> */
    public function listFinancialYears(int $organizationId): array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT financial_year FROM donations
            WHERE organization_id = ?
            ORDER BY financial_year DESC'
        );
        $stmt->execute([$organizationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strval', $rows);
    }

    public function createCommitment(
        int $organizationId,
        int $categoryId,
        string $donorType,
        ?int $userId,
        ?int $familyId,
        string $donorName,
        ?string $donorPhone,
        float $committedAmount,
        string $committedDate,
        ?string $notes,
        ?int $createdByUserId
    ): int {
        $this->ensureTable();
        $fy = self::financialYearForDate($committedDate);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO donations
            (organization_id, parent_id, category_id, donor_type, user_id, family_id, donor_name, donor_phone,
             committed_amount, committed_date, financial_year, status, notes, created_by_user_id)
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'open\', ?, ?)'
        );
        $stmt->execute([
            $organizationId,
            $categoryId,
            $donorType,
            $userId,
            $familyId,
            $donorName,
            $donorPhone,
            $committedAmount,
            $committedDate,
            $fy,
            $notes,
            $createdByUserId,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function recordPayment(
        int $organizationId,
        int $parentId,
        float $paidAmount,
        string $paymentDate,
        string $paymentMode,
        ?string $referenceNo,
        ?string $bankName,
        ?string $chequeDate,
        ?string $notes,
        ?int $createdByUserId
    ): int {
        $this->ensureTable();
        $fy = self::financialYearForDate($paymentDate);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO donations
            (organization_id, parent_id, category_id, donor_type, user_id, family_id, donor_name, donor_phone,
             paid_amount, payment_date, financial_year, payment_mode, reference_no, bank_name, cheque_date,
             notes, created_by_user_id)
            SELECT organization_id, id, category_id, donor_type, user_id, family_id, donor_name, donor_phone,
                   ?, ?, ?, ?, ?, ?, ?, ?, ?
            FROM donations WHERE id = ? AND organization_id = ? AND parent_id IS NULL LIMIT 1'
        );
        $stmt->execute([
            $paidAmount,
            $paymentDate,
            $fy,
            $paymentMode,
            $referenceNo,
            $bankName,
            $chequeDate,
            $notes,
            $createdByUserId,
            $parentId,
            $organizationId,
        ]);
        if ($stmt->rowCount() < 1) {
            return 0;
        }
        $paymentId = (int) Database::pdo()->lastInsertId();
        $this->recalculateCommitmentStatus($parentId);

        return $paymentId;
    }

    public function recalculateCommitmentStatus(int $commitmentId): void
    {
        $stmt = Database::pdo()->prepare(
            'SELECT committed_amount, status FROM donations WHERE id = ? AND parent_id IS NULL LIMIT 1'
        );
        $stmt->execute([$commitmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($row['status'] ?? '') === 'cancelled') {
            return;
        }
        $committed = (float) ($row['committed_amount'] ?? 0);
        $paidStmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(paid_amount), 0) FROM donations WHERE parent_id = ?'
        );
        $paidStmt->execute([$commitmentId]);
        $paid = (float) $paidStmt->fetchColumn();
        $status = 'open';
        if ($paid > 0 && $paid < $committed) {
            $status = 'partial';
        } elseif ($paid >= $committed && $committed > 0) {
            $status = 'fulfilled';
        }
        $upd = Database::pdo()->prepare('UPDATE donations SET status = ? WHERE id = ?');
        $upd->execute([$status, $commitmentId]);
    }

    public function paidTotalForCommitment(int $commitmentId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(paid_amount), 0) FROM donations WHERE parent_id = ?'
        );
        $stmt->execute([$commitmentId]);

        return (float) $stmt->fetchColumn();
    }

    public function findCommitmentById(int $organizationId, int $id): ?array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT d.*, c.name_gu AS category_name
            FROM donations d
            INNER JOIN donation_categories c ON c.id = d.category_id
            WHERE d.id = ? AND d.organization_id = ? AND d.parent_id IS NULL
            LIMIT 1'
        );
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['paid_total'] = $this->paidTotalForCommitment($id);
        $row['balance'] = max(0, (float) ($row['committed_amount'] ?? 0) - (float) $row['paid_total']);

        return $row;
    }

    /** @param array<string,mixed> $filters
     *  @return list<array<string,mixed>> */
    public function listCommitments(int $organizationId, array $filters = []): array
    {
        $this->ensureTable();
        $sql = 'SELECT d.*, c.name_gu AS category_name, u.phone AS donor_user_phone,
            (SELECT COALESCE(SUM(p.paid_amount), 0) FROM donations p WHERE p.parent_id = d.id) AS paid_total
            FROM donations d
            INNER JOIN donation_categories c ON c.id = d.category_id
            LEFT JOIN users u ON u.id = d.user_id
            WHERE d.organization_id = ? AND d.parent_id IS NULL';
        $params = [$organizationId];

        $fy = trim((string) ($filters['financial_year'] ?? ''));
        if ($fy !== '') {
            $sql .= ' AND d.financial_year = ?';
            $params[] = $fy;
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $sql .= ' AND d.category_id = ?';
            $params[] = $categoryId;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['open', 'partial', 'fulfilled', 'cancelled'], true)) {
            $sql .= ' AND d.status = ?';
            $params[] = $status;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $sql .= ' AND (d.donor_name LIKE ? OR d.donor_phone LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY d.committed_date DESC, d.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $committed = (float) ($row['committed_amount'] ?? 0);
            $paid = (float) ($row['paid_total'] ?? 0);
            $row['balance'] = max(0, $committed - $paid);
        }
        unset($row);

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    public function listPaymentsForCommitment(int $commitmentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM donations WHERE parent_id = ? ORDER BY payment_date DESC, id DESC'
        );
        $stmt->execute([$commitmentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function dashboardByCategory(int $organizationId, string $financialYear): array
    {
        $this->ensureTable();
        (new DonationCategory())->seedForOrganization($organizationId);
        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.name_gu, c.sort_order,
                COALESCE((
                    SELECT SUM(d.committed_amount)
                    FROM donations d
                    WHERE d.organization_id = ? AND d.parent_id IS NULL AND d.category_id = c.id
                    AND d.status != \'cancelled\' AND d.financial_year = ?
                ), 0) AS pledged,
                COALESCE((
                    SELECT SUM(p.paid_amount)
                    FROM donations p
                    INNER JOIN donations parent ON parent.id = p.parent_id
                    WHERE parent.organization_id = ? AND parent.category_id = c.id
                    AND p.financial_year = ?
                ), 0) AS collected
            FROM donation_categories c
            WHERE c.organization_id = ? AND c.is_active = 1
            ORDER BY c.sort_order ASC, c.id ASC'
        );
        $stmt->execute([
            $organizationId,
            $financialYear,
            $organizationId,
            $financialYear,
            $organizationId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $pledged = (float) ($row['pledged'] ?? 0);
            $collected = (float) ($row['collected'] ?? 0);
            $row['balance'] = max(0, $pledged - $collected);
        }
        unset($row);

        return $rows;
    }

    public static function isValidPaymentMode(string $mode): bool
    {
        return in_array($mode, ['cash', 'upi', 'bank', 'cheque'], true);
    }
}

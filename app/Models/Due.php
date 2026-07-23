<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Due
{
    private const PAYMENT_EPSILON = 0.005;

    private static bool $tablesEnsured = false;

    public function ensureTables(): void
    {
        if (self::$tablesEnsured) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS due_definitions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                title VARCHAR(191) NOT NULL,
                due_type ENUM('membership','event','occasion','other') NOT NULL DEFAULT 'other',
                amount DECIMAL(12,2) NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                is_compulsory TINYINT(1) NOT NULL DEFAULT 1,
                created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_due_def_org_fy (organization_id, financial_year),
                CONSTRAINT fk_due_def_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_due_def_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS due_charges (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                due_definition_id INT UNSIGNED NOT NULL,
                organization_id INT UNSIGNED NOT NULL,
                family_id INT UNSIGNED NOT NULL,
                recipient_user_id INT UNSIGNED NOT NULL,
                amount_due DECIMAL(12,2) NOT NULL,
                amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
                status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
                last_paid_at DATE NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_due_charge (due_definition_id, recipient_user_id),
                KEY idx_due_charge_org_status (organization_id, status),
                CONSTRAINT fk_due_charge_def FOREIGN KEY (due_definition_id) REFERENCES due_definitions (id) ON DELETE CASCADE,
                CONSTRAINT fk_due_charge_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_due_charge_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
                CONSTRAINT fk_due_charge_user FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS due_payments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                due_charge_id INT UNSIGNED NOT NULL,
                receipt_id INT UNSIGNED NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_due_payment_receipt (receipt_id),
                KEY idx_due_payment_charge (due_charge_id),
                CONSTRAINT fk_due_pay_charge FOREIGN KEY (due_charge_id) REFERENCES due_charges (id) ON DELETE CASCADE,
                CONSTRAINT fk_due_pay_receipt FOREIGN KEY (receipt_id) REFERENCES receipts (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->ensureCompulsoryColumn();
        $this->ensureChargeBasisColumn();
        $this->ensureEventDateColumn();
        self::$tablesEnsured = true;
    }

    private function ensureEventDateColumn(): void
    {
        $pdo = Database::pdo();
        if ($pdo->query("SHOW COLUMNS FROM due_definitions LIKE 'event_date'")->fetch() === false) {
            $pdo->exec(
                'ALTER TABLE due_definitions ADD COLUMN event_date DATE NULL DEFAULT NULL AFTER financial_year'
            );
        }
    }

    private function ensureCompulsoryColumn(): void
    {
        $pdo = Database::pdo();
        if ($pdo->query("SHOW COLUMNS FROM due_definitions LIKE 'is_compulsory'")->fetch() === false) {
            $pdo->exec(
                'ALTER TABLE due_definitions ADD COLUMN is_compulsory TINYINT(1) NOT NULL DEFAULT 1 AFTER financial_year'
            );
        }
    }

    private function ensureChargeBasisColumn(): void
    {
        $pdo = Database::pdo();
        if ($pdo->query("SHOW COLUMNS FROM due_definitions LIKE 'charge_basis'")->fetch() === false) {
            $pdo->exec(
                "ALTER TABLE due_definitions ADD COLUMN charge_basis ENUM('per_family','per_person') NOT NULL DEFAULT 'per_family' AFTER amount"
            );
            $pdo->exec("UPDATE due_definitions SET charge_basis = 'per_person' WHERE due_type IN ('membership', 'event')");
        }
    }

    public function createDefinitionAndAssignHeads(
        int $organizationId,
        string $title,
        string $dueType,
        float $amount,
        string $financialYear,
        ?int $createdByUserId,
        bool $isCompulsory = true,
        string $chargeBasis = 'per_family',
        ?string $eventDate = null
    ): int {
        $this->ensureTables();
        $dueType = in_array($dueType, ['membership', 'event', 'occasion', 'other'], true) ? $dueType : 'other';
        if ($dueType === 'membership') {
            $isCompulsory = true;
            $chargeBasis = 'per_person';
        }
        $chargeBasis = $this->normalizeChargeBasis($chargeBasis);
        $eventDateNorm = null;
        if ($eventDate !== null && trim($eventDate) !== '' && strtotime($eventDate) !== false) {
            $eventDateNorm = date('Y-m-d', strtotime($eventDate));
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO due_definitions (organization_id, title, due_type, amount, charge_basis, financial_year, event_date, is_compulsory, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $organizationId,
                $title,
                $dueType,
                $amount,
                $chargeBasis,
                $financialYear,
                $eventDateNorm,
                $isCompulsory ? 1 : 0,
                $createdByUserId,
            ]);
            $definitionId = (int) $pdo->lastInsertId();

            if ($isCompulsory) {
                $def = [
                    'amount' => $amount,
                    'due_type' => $dueType,
                    'charge_basis' => $chargeBasis,
                    'is_compulsory' => 1,
                ];
                $this->assignChargesToAllHeads($pdo, $definitionId, $organizationId, $def);
            }
            $pdo->commit();

            return $definitionId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function listDefinitions(int $organizationId, string $financialYear): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM due_definitions
            WHERE organization_id = ? AND financial_year = ?
            ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$organizationId, $financialYear]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDefinitionByIdInOrganization(int $definitionId, int $organizationId): ?array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM due_definitions WHERE id = ? AND organization_id = ? LIMIT 1'
        );
        $stmt->execute([$definitionId, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findEventByIdInOrganization(int $definitionId, int $organizationId): ?array
    {
        $row = $this->findDefinitionByIdInOrganization($definitionId, $organizationId);
        if ($row === null || !$this->isEventDefinition($row)) {
            return null;
        }

        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function listEventsForOrganization(int $organizationId): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM due_definitions
             WHERE organization_id = ? AND due_type = 'event'
             ORDER BY COALESCE(event_date, DATE(created_at)) DESC, id DESC"
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countEventsForOrganization(int $organizationId): int
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM due_definitions
             WHERE organization_id = ? AND due_type = 'event'"
        );
        $stmt->execute([$organizationId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array<string,mixed>> */
    public function listEventOccasionForOrganization(int $organizationId): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            "SELECT id, title, due_type, financial_year, event_date
             FROM due_definitions
             WHERE organization_id = ? AND due_type IN ('event', 'occasion')
             ORDER BY COALESCE(event_date, DATE(created_at)) DESC, title ASC, id DESC"
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Calendar rows: events and occasions with a schedule date (event_date or created_at fallback). */
    public function listScheduledForCalendar(int $organizationId, string $rangeStart, string $rangeEnd): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            "SELECT id, title, due_type, event_date, created_at
             FROM due_definitions
             WHERE organization_id = ?
               AND due_type IN ('event', 'occasion')
               AND COALESCE(event_date, DATE(created_at)) BETWEEN ? AND ?
             ORDER BY COALESCE(event_date, DATE(created_at)) ASC, id ASC"
        );
        $stmt->execute([$organizationId, $rangeStart, $rangeEnd]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isCompulsoryDefinition(array $definition): bool
    {
        return !empty($definition['is_compulsory']);
    }

    public function normalizeChargeBasis(string $basis): string
    {
        return $basis === 'per_person' ? 'per_person' : 'per_family';
    }

    /** Charged as rate × login members in the household (membership, per-person events, etc.). */
    public function isPerPersonDefinition(array $definition): bool
    {
        if (strtolower((string) ($definition['charge_basis'] ?? '')) === 'per_person') {
            return true;
        }

        return strtolower((string) ($definition['due_type'] ?? '')) === 'membership';
    }

    /** @deprecated Use isPerPersonDefinition() */
    public function isPerMemberDefinition(array $definition): bool
    {
        return $this->isPerPersonDefinition($definition);
    }

    public function isEventDefinition(array $definition): bool
    {
        return strtolower((string) ($definition['due_type'] ?? '')) === 'event';
    }

    public function amountDueForFamily(array $definition, int $familyId): float
    {
        $rate = (float) ($definition['amount'] ?? 0);
        if ($rate <= 0) {
            return 0.0;
        }
        if (!$this->isPerPersonDefinition($definition)) {
            return round($rate, 2);
        }
        $count = (new Family())->householdMemberCount($familyId);

        return round($rate * $count, 2);
    }

    /** Recompute compulsory per-person charges when family size changes. */
    public function syncMembershipChargesForOrganization(int $organizationId): void
    {
        $this->syncPerPersonChargesForOrganization($organizationId);
    }

    public function syncPerPersonChargesForOrganization(int $organizationId): void
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM due_definitions WHERE organization_id = ? AND is_compulsory = 1'
        );
        $stmt->execute([$organizationId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!$this->isPerPersonDefinition($row)) {
                continue;
            }
            $this->syncCompulsoryChargesForDefinition($organizationId, (int) ($row['id'] ?? 0));
        }
    }

    /** @return array<string,mixed>|null */
    public function findChargeForRecipient(int $organizationId, int $definitionId, int $recipientUserId): ?array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM due_charges
             WHERE organization_id = ? AND due_definition_id = ? AND recipient_user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$organizationId, $definitionId, $recipientUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Ensures every current family head has a charge row for a compulsory due. */
    public function syncCompulsoryChargesForDefinition(int $organizationId, int $definitionId): void
    {
        $this->ensureTables();
        $def = $this->findDefinitionByIdInOrganization($definitionId, $organizationId);
        if ($def === null || !$this->isCompulsoryDefinition($def)) {
            return;
        }
        $pdo = Database::pdo();
        $this->assignChargesToAllHeads($pdo, $definitionId, $organizationId, $def);
        $this->refreshChargeStatusesForDefinition($pdo, $organizationId, $definitionId);
    }

    public function effectiveChargeStatus(float $amountDue, float $amountPaid): string
    {
        if ($amountPaid <= self::PAYMENT_EPSILON) {
            return 'unpaid';
        }
        if ($amountDue <= self::PAYMENT_EPSILON || $amountPaid + self::PAYMENT_EPSILON >= $amountDue) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * @return array{total:int, paid:int, partial:int, unpaid:int, is_compulsory:bool}
     */
    public function trackerSummary(int $organizationId, int $definitionId): array
    {
        $this->ensureTables();
        $def = $this->findDefinitionByIdInOrganization($definitionId, $organizationId);
        $isCompulsory = $def !== null && $this->isCompulsoryDefinition($def);
        $stmt = Database::pdo()->prepare(
            'SELECT amount_due, amount_paid FROM due_charges
             WHERE organization_id = ? AND due_definition_id = ?'
        );
        $stmt->execute([$organizationId, $definitionId]);
        $paid = 0;
        $partial = 0;
        $unpaid = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $st = $this->effectiveChargeStatus(
                (float) ($row['amount_due'] ?? 0),
                (float) ($row['amount_paid'] ?? 0)
            );
            if ($st === 'paid') {
                $paid++;
            } elseif ($st === 'partial') {
                $partial++;
            } else {
                $unpaid++;
            }
        }

        return [
            'total' => $paid + $partial + $unpaid,
            'paid' => $paid,
            'partial' => $partial,
            'unpaid' => $unpaid,
            'is_compulsory' => $isCompulsory,
        ];
    }

    private function refreshChargeStatusesForDefinition(\PDO $pdo, int $organizationId, int $definitionId): void
    {
        $stmt = $pdo->prepare(
            'SELECT id, amount_due, amount_paid, status FROM due_charges
             WHERE organization_id = ? AND due_definition_id = ?'
        );
        $stmt->execute([$organizationId, $definitionId]);
        $up = $pdo->prepare('UPDATE due_charges SET status = ? WHERE id = ?');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $effective = $this->effectiveChargeStatus(
                (float) ($row['amount_due'] ?? 0),
                (float) ($row['amount_paid'] ?? 0)
            );
            if ($effective !== (string) ($row['status'] ?? '')) {
                $up->execute([$effective, (int) ($row['id'] ?? 0)]);
            }
        }
    }

    private function assignChargesToAllHeads(\PDO $pdo, int $definitionId, int $organizationId, array $definition): void
    {
        $familyModel = new Family();
        $heads = $familyModel->listByOrganization($organizationId);
        $ins = $pdo->prepare(
            'INSERT INTO due_charges (due_definition_id, organization_id, family_id, recipient_user_id, amount_due)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                family_id = VALUES(family_id),
                amount_due = VALUES(amount_due),
                status = CASE
                    WHEN amount_paid >= VALUES(amount_due) THEN \'paid\'
                    WHEN amount_paid > 0 THEN \'partial\'
                    ELSE \'unpaid\'
                END'
        );
        foreach ($heads as $h) {
            $fid = (int) ($h['id'] ?? 0);
            $uid = (int) ($h['head_user_id'] ?? 0);
            if ($fid < 1 || $uid < 1) {
                continue;
            }
            $amount = $this->amountDueForFamily($definition, $fid);
            $ins->execute([$definitionId, $organizationId, $fid, $uid, $amount]);
        }
    }

    public function ensureChargeForRecipient(
        int $organizationId,
        int $definitionId,
        int $familyId,
        int $recipientUserId,
        float $amountDue
    ): int {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT id FROM due_charges
             WHERE organization_id = ? AND due_definition_id = ? AND recipient_user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$organizationId, $definitionId, $recipientUserId]);
        $existing = (int) $stmt->fetchColumn();
        if ($existing > 0) {
            return $existing;
        }
        $ins = Database::pdo()->prepare(
            'INSERT INTO due_charges (due_definition_id, organization_id, family_id, recipient_user_id, amount_due)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$definitionId, $organizationId, $familyId, $recipientUserId, $amountDue]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listChargeStatus(int $organizationId, int $definitionId): array
    {
        $this->ensureTables();
        $pdo = Database::pdo();
        $this->refreshChargeStatusesForDefinition($pdo, $organizationId, $definitionId);
        $stmt = $pdo->prepare(
            'SELECT dc.*, dd.title, dd.is_compulsory, dd.amount AS default_amount, u.name AS recipient_name, dd.financial_year
            FROM due_charges dc
            INNER JOIN due_definitions dd ON dd.id = dc.due_definition_id
            INNER JOIN users u ON u.id = dc.recipient_user_id
            WHERE dc.organization_id = ? AND dc.due_definition_id = ?
            ORDER BY u.name ASC'
        );
        $stmt->execute([$organizationId, $definitionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $def = $this->findDefinitionByIdInOrganization($definitionId, $organizationId);
        if ($def === null) {
            return $rows;
        }
        $perMember = $this->isPerPersonDefinition($def);
        $rate = (float) ($def['amount'] ?? 0);
        $familyModel = new Family();
        foreach ($rows as &$row) {
            $fid = (int) ($row['family_id'] ?? 0);
            $memberCount = $fid > 0 ? $familyModel->householdMemberCount($fid) : 1;
            $row['member_count'] = $memberCount;
            $row['rate_per_member'] = $perMember ? $rate : null;
            $row['due_type'] = (string) ($def['due_type'] ?? '');
            $row['status'] = $this->effectiveChargeStatus(
                (float) ($row['amount_due'] ?? 0),
                (float) ($row['amount_paid'] ?? 0)
            );
        }
        unset($row);

        return $rows;
    }

    public function applyReceiptPayment(
        int $organizationId,
        int $recipientUserId,
        string $purpose,
        float $amount,
        string $financialYear,
        int $receiptId,
        string $receiptDate
    ): void {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT dc.id AS charge_id
            FROM due_charges dc
            INNER JOIN due_definitions dd ON dd.id = dc.due_definition_id
            WHERE dc.organization_id = ?
            AND dc.recipient_user_id = ?
            AND dd.financial_year = ?
            AND LOWER(TRIM(dd.title)) = LOWER(TRIM(?))
            LIMIT 1'
        );
        $stmt->execute([$organizationId, $recipientUserId, $financialYear, $purpose]);
        $chargeId = (int) $stmt->fetchColumn();
        if ($chargeId < 1) {
            return;
        }
        $pdo = Database::pdo();
        $exists = $pdo->prepare('SELECT 1 FROM due_payments WHERE receipt_id = ? LIMIT 1');
        $exists->execute([$receiptId]);
        if ($exists->fetchColumn()) {
            return;
        }
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO due_payments (due_charge_id, receipt_id, amount) VALUES (?, ?, ?)');
            $ins->execute([$chargeId, $receiptId, $amount]);
            $this->addPaymentToCharge($pdo, $chargeId, $amount, $receiptDate);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function applyReceiptPaymentByDefinition(
        int $organizationId,
        int $recipientUserId,
        int $dueDefinitionId,
        float $amount,
        int $receiptId,
        string $receiptDate,
        ?int $familyId = null
    ): void {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT id FROM due_charges
            WHERE organization_id = ? AND recipient_user_id = ? AND due_definition_id = ?
            LIMIT 1'
        );
        $stmt->execute([$organizationId, $recipientUserId, $dueDefinitionId]);
        $chargeId = (int) $stmt->fetchColumn();
        if ($chargeId < 1) {
            $def = $this->findDefinitionByIdInOrganization($dueDefinitionId, $organizationId);
            if ($def === null || $familyId === null || $familyId < 1) {
                return;
            }
            $chargeId = $this->ensureChargeForRecipient(
                $organizationId,
                $dueDefinitionId,
                $familyId,
                $recipientUserId,
                $this->amountDueForFamily($def, $familyId)
            );
        }
        if ($chargeId < 1) {
            return;
        }
        $pdo = Database::pdo();
        $exists = $pdo->prepare('SELECT 1 FROM due_payments WHERE receipt_id = ? LIMIT 1');
        $exists->execute([$receiptId]);
        if ($exists->fetchColumn()) {
            return;
        }
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO due_payments (due_charge_id, receipt_id, amount) VALUES (?, ?, ?)');
            $ins->execute([$chargeId, $receiptId, $amount]);
            $this->addPaymentToCharge($pdo, $chargeId, $amount, $receiptDate);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function addPaymentToCharge(\PDO $pdo, int $chargeId, float $amount, string $receiptDate): void
    {
        $up = $pdo->prepare(
            'UPDATE due_charges
            SET amount_paid = amount_paid + ?,
                last_paid_at = ?
            WHERE id = ?'
        );
        $up->execute([$amount, $receiptDate, $chargeId]);

        $sel = $pdo->prepare('SELECT amount_due, amount_paid FROM due_charges WHERE id = ? LIMIT 1');
        $sel->execute([$chargeId]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return;
        }
        $status = $this->effectiveChargeStatus(
            (float) ($row['amount_due'] ?? 0),
            (float) ($row['amount_paid'] ?? 0)
        );
        $pdo->prepare('UPDATE due_charges SET status = ? WHERE id = ?')->execute([$status, $chargeId]);
    }
}

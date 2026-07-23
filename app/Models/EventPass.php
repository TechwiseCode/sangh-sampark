<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class EventPass
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
            "CREATE TABLE IF NOT EXISTS event_passes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                due_definition_id INT UNSIGNED NOT NULL,
                family_id INT UNSIGNED NOT NULL,
                recipient_user_id INT UNSIGNED NOT NULL,
                holder_user_id INT UNSIGNED NOT NULL,
                receipt_id INT UNSIGNED NOT NULL,
                pass_code VARCHAR(32) NOT NULL,
                status ENUM('active','redeemed','cancelled') NOT NULL DEFAULT 'active',
                issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                redeemed_at TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY uq_event_pass_code (pass_code),
                KEY idx_event_pass_family_event (due_definition_id, family_id, status),
                KEY idx_event_pass_receipt (receipt_id),
                KEY idx_event_pass_org (organization_id),
                KEY idx_event_pass_recipient (recipient_user_id),
                KEY idx_event_pass_holder (holder_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->ensureHolderUserIdColumn($pdo);
        $this->ensureReceiptIdNotUnique($pdo);
        $this->ensureHolderNotUniquePerEvent($pdo);
        self::$tablesEnsured = true;
    }

    private function ensureHolderUserIdColumn(PDO $pdo): void
    {
        if ($pdo->query("SHOW COLUMNS FROM event_passes LIKE 'holder_user_id'")->fetch() === false) {
            $pdo->exec('ALTER TABLE event_passes ADD COLUMN holder_user_id INT UNSIGNED NULL DEFAULT NULL AFTER recipient_user_id');
            $pdo->exec('UPDATE event_passes SET holder_user_id = recipient_user_id WHERE holder_user_id IS NULL');
            $pdo->exec('ALTER TABLE event_passes MODIFY holder_user_id INT UNSIGNED NOT NULL');
            $hasFamilyUq = $pdo->query("SHOW INDEX FROM event_passes WHERE Key_name = 'uq_event_pass_family'")->fetch();
            if ($hasFamilyUq !== false) {
                $pdo->exec('ALTER TABLE event_passes DROP INDEX uq_event_pass_family');
            }
        }
    }

    private function ensureReceiptIdNotUnique(PDO $pdo): void
    {
        $hasReceiptUq = $pdo->query("SHOW INDEX FROM event_passes WHERE Key_name = 'uq_event_pass_receipt'")->fetch();
        if ($hasReceiptUq !== false) {
            $pdo->exec('ALTER TABLE event_passes DROP INDEX uq_event_pass_receipt');
        }
        $hasReceiptIdx = $pdo->query("SHOW INDEX FROM event_passes WHERE Key_name = 'idx_event_pass_receipt'")->fetch();
        if ($hasReceiptIdx === false) {
            $pdo->exec('ALTER TABLE event_passes ADD KEY idx_event_pass_receipt (receipt_id)');
        }
    }

    private function ensureHolderNotUniquePerEvent(PDO $pdo): void
    {
        $hasHolderUq = $pdo->query("SHOW INDEX FROM event_passes WHERE Key_name = 'uq_event_pass_holder'")->fetch();
        if ($hasHolderUq !== false) {
            $pdo->exec('ALTER TABLE event_passes DROP INDEX uq_event_pass_holder');
        }
        $hasIdx = $pdo->query("SHOW INDEX FROM event_passes WHERE Key_name = 'idx_event_pass_family_event'")->fetch();
        if ($hasIdx === false) {
            $pdo->exec(
                'ALTER TABLE event_passes ADD KEY idx_event_pass_family_event (due_definition_id, family_id, status)'
            );
        }
    }

    /** Tickets covered: floor(amount_paid / rate_per_ticket). */
    public function passCountFromPayment(float $ratePerUnit, float $amountPaid): int
    {
        if ($amountPaid <= self::PAYMENT_EPSILON) {
            return 0;
        }
        if ($ratePerUnit <= self::PAYMENT_EPSILON) {
            return 1;
        }

        return (int) floor(($amountPaid + self::PAYMENT_EPSILON) / $ratePerUnit);
    }

    /**
     * Sync event passes from total amount paid on the family charge (after a receipt).
     *
     * @return list<string> Active pass codes for this family after sync
     */
    public function issueForPaidEventReceipt(
        int $organizationId,
        array $dueDefinition,
        int $familyId,
        int $recipientUserId,
        int $receiptId
    ): array {
        return $this->syncPassesForFamilyEvent(
            $organizationId,
            $dueDefinition,
            $familyId,
            $recipientUserId,
            $receiptId > 0 ? $receiptId : null
        );
    }

    /**
     * Align active passes with amount paid on the family charge (tickets = paid ÷ rate).
     *
     * @return list<string>
     */
    public function syncPassesForFamilyEvent(
        int $organizationId,
        array $dueDefinition,
        int $familyId,
        int $recipientUserId,
        ?int $receiptId = null
    ): array {
        $this->ensureTables();
        $dueDefinitionId = (int) ($dueDefinition['id'] ?? 0);
        if ($organizationId < 1 || $dueDefinitionId < 1 || $familyId < 1 || $recipientUserId < 1) {
            return [];
        }

        $dueModel = new Due();
        if (!$dueModel->isEventDefinition($dueDefinition)) {
            return [];
        }

        $charge = $dueModel->findChargeForRecipient($organizationId, $dueDefinitionId, $recipientUserId);
        if ($charge === null) {
            return [];
        }

        if ($receiptId === null || $receiptId < 1) {
            $receiptId = $this->latestReceiptIdForCharge((int) ($charge['id'] ?? 0));
        }
        if ($receiptId < 1) {
            return [];
        }

        $rate = (float) ($dueDefinition['amount'] ?? 0);
        $amountPaid = (float) ($charge['amount_paid'] ?? 0);

        if ($dueModel->isPerPersonDefinition($dueDefinition)) {
            $ticketCount = $this->passCountFromPayment($rate, $amountPaid);
            $holderIds = $this->orderedHouseholdUserIds($familyId, $recipientUserId);

            return $this->syncPassesForHolders(
                $organizationId,
                $dueDefinitionId,
                $familyId,
                $recipientUserId,
                $receiptId,
                $holderIds,
                $ticketCount
            );
        }

        $ticketCount = $this->passCountFromPayment($rate, $amountPaid) > 0 ? 1 : 0;
        $holderIds = [$recipientUserId];

        return $this->syncPassesForHolders(
            $organizationId,
            $dueDefinitionId,
            $familyId,
            $recipientUserId,
            $receiptId,
            $holderIds,
            $ticketCount
        );
    }

    private function latestReceiptIdForCharge(int $chargeId): int
    {
        if ($chargeId < 1) {
            return 0;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT receipt_id FROM due_payments WHERE due_charge_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$chargeId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Keep exactly $ticketCount active passes (tickets), round-robin across household members.
     *
     * @param list<int> $orderedHolderIds
     * @return list<string>
     */
    private function syncPassesForHolders(
        int $organizationId,
        int $dueDefinitionId,
        int $familyId,
        int $recipientUserId,
        int $receiptId,
        array $orderedHolderIds,
        int $ticketCount
    ): array {
        $ticketCount = max(0, $ticketCount);
        if ($orderedHolderIds === []) {
            $orderedHolderIds = [$recipientUserId];
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT id, pass_code FROM event_passes
             WHERE due_definition_id = ? AND family_id = ? AND status = \'active\'
             ORDER BY id ASC'
        );
        $stmt->execute([$dueDefinitionId, $familyId]);
        $active = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $activeCount = count($active);

        if ($activeCount > $ticketCount) {
            $toCancel = array_slice($active, $ticketCount);
            $cancel = $pdo->prepare("UPDATE event_passes SET status = 'cancelled' WHERE id = ?");
            foreach ($toCancel as $row) {
                $cancel->execute([(int) ($row['id'] ?? 0)]);
            }
            $active = array_slice($active, 0, $ticketCount);
        }

        $holderCount = count($orderedHolderIds);
        for ($i = $activeCount; $i < $ticketCount; $i++) {
            $holderId = (int) $orderedHolderIds[$i % $holderCount];
            $code = $this->insertPass(
                $organizationId,
                $dueDefinitionId,
                $familyId,
                $recipientUserId,
                $holderId,
                $receiptId
            );
            if ($code !== null) {
                $active[] = ['id' => 0, 'pass_code' => $code];
            }
        }

        $codes = [];
        foreach ($active as $row) {
            $codes[] = (string) ($row['pass_code'] ?? '');
        }

        return $codes;
    }

    /** @return list<int> */
    private function orderedHouseholdUserIds(int $familyId, int $headUserId): array
    {
        $members = (new Family())->membersWithUsers($familyId);
        usort($members, static function (array $a, array $b) use ($headUserId): int {
            $au = (int) ($a['user_id'] ?? 0);
            $bu = (int) ($b['user_id'] ?? 0);
            if ($au === $headUserId) {
                return -1;
            }
            if ($bu === $headUserId) {
                return 1;
            }

            return strcasecmp((string) ($a['user_name'] ?? ''), (string) ($b['user_name'] ?? ''));
        });
        $ids = [];
        foreach ($members as $member) {
            $uid = (int) ($member['user_id'] ?? 0);
            if ($uid > 0) {
                $ids[] = $uid;
            }
        }

        return $ids;
    }

    private function insertPass(
        int $organizationId,
        int $dueDefinitionId,
        int $familyId,
        int $recipientUserId,
        int $holderUserId,
        int $receiptId
    ): ?string {
        $pdo = Database::pdo();
        $passCode = $this->generateUniquePassCode($pdo, $dueDefinitionId);
        $ins = $pdo->prepare(
            'INSERT INTO event_passes
            (organization_id, due_definition_id, family_id, recipient_user_id, holder_user_id, receipt_id, pass_code)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $organizationId,
            $dueDefinitionId,
            $familyId,
            $recipientUserId,
            $holderUserId,
            $receiptId,
            $passCode,
        ]);

        return $passCode;
    }

    /**
     * Passes and payment summary for the user's household on one event.
     *
     * @return array<string,mixed>|null
     */
    public function householdSummaryForUserEvent(int $userId, int $organizationId, array $dueDefinition): ?array
    {
        $this->ensureTables();
        $dueDefinitionId = (int) ($dueDefinition['id'] ?? 0);
        if ($dueDefinitionId < 1) {
            return null;
        }

        $families = (new Family())->listFamiliesForUserInOrganization($userId, $organizationId);
        if ($families === []) {
            return null;
        }
        $familyRow = $families[0];
        $familyId = (int) ($familyRow['family_id'] ?? 0);
        $headUserId = (int) ($familyRow['head_user_id'] ?? 0);
        if ($familyId < 1 || $headUserId < 1) {
            return null;
        }

        $dueModel = new Due();
        $rate = (float) ($dueDefinition['amount'] ?? 0);
        $charge = $dueModel->findChargeForRecipient($organizationId, $dueDefinitionId, $headUserId);
        if ($charge !== null && $dueModel->isEventDefinition($dueDefinition)) {
            $this->syncPassesForFamilyEvent($organizationId, $dueDefinition, $familyId, $headUserId, null);
            $charge = $dueModel->findChargeForRecipient($organizationId, $dueDefinitionId, $headUserId);
        }
        $amountPaid = $charge !== null ? (float) ($charge['amount_paid'] ?? 0) : 0.0;
        $amountDue = $charge !== null
            ? (float) ($charge['amount_due'] ?? 0)
            : ($dueModel->isPerPersonDefinition($dueDefinition)
                ? $rate * (new Family())->householdMemberCount($familyId)
                : $rate);
        $ticketsFromPayment = $dueModel->isPerPersonDefinition($dueDefinition)
            ? $this->passCountFromPayment($rate, $amountPaid)
            : ($this->passCountFromPayment($rate, $amountPaid) > 0 ? 1 : 0);

        $passes = $this->listPassesForFamilyEvent($familyId, $dueDefinitionId);
        $activeCount = 0;
        $redeemedCount = 0;
        foreach ($passes as $p) {
            $st = (string) ($p['status'] ?? '');
            if ($st === 'active') {
                $activeCount++;
            } elseif ($st === 'redeemed') {
                $redeemedCount++;
            }
        }
        $myPass = null;
        foreach ($passes as $p) {
            if ((int) ($p['holder_user_id'] ?? 0) !== $userId) {
                continue;
            }
            if ($myPass === null || (string) ($p['status'] ?? '') === 'active') {
                $myPass = $p;
                if ((string) ($p['status'] ?? '') === 'active') {
                    break;
                }
            }
        }

        return [
            'family_id' => $familyId,
            'head_user_id' => $headUserId,
            'head_name' => (string) ($familyRow['head_name'] ?? ''),
            'my_role' => (string) ($familyRow['my_role'] ?? ''),
            'member_count' => (new Family())->householdMemberCount($familyId),
            'amount_paid' => $amountPaid,
            'amount_due' => $amountDue,
            'rate' => $rate,
            'tickets_from_payment' => $ticketsFromPayment,
            'pass_count' => $activeCount,
            'redeemed_count' => $redeemedCount,
            'passes' => $passes,
            'my_pass' => $myPass,
            'charge_status' => $charge !== null
                ? $dueModel->effectiveChargeStatus($amountDue, $amountPaid)
                : 'unpaid',
        ];
    }

    /** Active and redeemed passes for a household on one event. @return list<array<string,mixed>> */
    public function listPassesForFamilyEvent(int $familyId, int $dueDefinitionId): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT ep.*, u.name AS holder_name
            FROM event_passes ep
            INNER JOIN users u ON u.id = ep.holder_user_id
            WHERE ep.family_id = ? AND ep.due_definition_id = ?
            AND ep.status IN (\'active\', \'redeemed\')
            ORDER BY FIELD(ep.status, \'active\', \'redeemed\'), ep.issued_at ASC, u.name ASC'
        );
        $stmt->execute([$familyId, $dueDefinitionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array{active:int, redeemed:int, cancelled:int, total:int}
     */
    public function eventPassStats(int $organizationId, int $dueDefinitionId): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT status, COUNT(*) AS c FROM event_passes
             WHERE organization_id = ? AND due_definition_id = ?
             GROUP BY status'
        );
        $stmt->execute([$organizationId, $dueDefinitionId]);
        $stats = ['active' => 0, 'redeemed' => 0, 'cancelled' => 0, 'total' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $st = (string) ($row['status'] ?? '');
            $n = (int) ($row['c'] ?? 0);
            if (isset($stats[$st])) {
                $stats[$st] = $n;
            }
            $stats['total'] += $n;
        }

        return $stats;
    }

    /** @return list<array<string,mixed>> */
    public function listPassesForEvent(int $organizationId, int $dueDefinitionId, int $limit = 200): array
    {
        $this->ensureTables();
        $limit = max(1, min(500, $limit));
        $stmt = Database::pdo()->prepare(
            'SELECT ep.*, holder.name AS holder_name, head.name AS head_name
            FROM event_passes ep
            INNER JOIN users holder ON holder.id = ep.holder_user_id
            INNER JOIN families f ON f.id = ep.family_id
            INNER JOIN users head ON head.id = f.head_user_id
            WHERE ep.organization_id = ? AND ep.due_definition_id = ?
            ORDER BY ep.issued_at DESC, ep.id DESC
            LIMIT ' . $limit
        );
        $stmt->execute([$organizationId, $dueDefinitionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function normalizePassCode(string $passCode): string
    {
        return strtoupper(trim($passCode));
    }

    public function passCodeSuffix(string $passCode, int $length = 3): string
    {
        $passCode = $this->normalizePassCode($passCode);
        if ($passCode === '') {
            return '';
        }
        $length = max(1, $length);

        return strlen($passCode) <= $length ? $passCode : substr($passCode, -$length);
    }

    public function findPassByCodeInEvent(int $organizationId, int $dueDefinitionId, string $passCode): ?array
    {
        $this->ensureTables();
        $passCode = $this->normalizePassCode($passCode);
        if ($passCode === '') {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT ep.*, holder.name AS holder_name, head.name AS head_name
            FROM event_passes ep
            INNER JOIN users holder ON holder.id = ep.holder_user_id
            INNER JOIN families f ON f.id = ep.family_id
            INNER JOIN users head ON head.id = f.head_user_id
            WHERE ep.organization_id = ? AND ep.due_definition_id = ? AND ep.pass_code = ?
            LIMIT 1'
        );
        $stmt->execute([$organizationId, $dueDefinitionId, $passCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findPassByIdInEvent(int $organizationId, int $dueDefinitionId, int $passId): ?array
    {
        $this->ensureTables();
        if ($passId < 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT ep.*, holder.name AS holder_name, head.name AS head_name
            FROM event_passes ep
            INNER JOIN users holder ON holder.id = ep.holder_user_id
            INNER JOIN families f ON f.id = ep.family_id
            INNER JOIN users head ON head.id = f.head_user_id
            WHERE ep.organization_id = ? AND ep.due_definition_id = ? AND ep.id = ?
            LIMIT 1'
        );
        $stmt->execute([$organizationId, $dueDefinitionId, $passId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Search by full code or last characters (min 3), e.g. "E5B" matches EVT-3-180E5B.
     *
     * @return list<array<string,mixed>>
     */
    public function searchPassesInEvent(int $organizationId, int $dueDefinitionId, string $query, int $limit = 12): array
    {
        $this->ensureTables();
        $query = $this->normalizePassCode($query);
        $query = preg_replace('/[^A-Z0-9\-]/', '', $query);
        if (strlen($query) < 3) {
            return [];
        }
        $limit = max(1, min(20, $limit));
        $like = '%' . $query;
        $stmt = Database::pdo()->prepare(
            'SELECT ep.*, holder.name AS holder_name, head.name AS head_name
            FROM event_passes ep
            INNER JOIN users holder ON holder.id = ep.holder_user_id
            INNER JOIN families f ON f.id = ep.family_id
            INNER JOIN users head ON head.id = f.head_user_id
            WHERE ep.organization_id = ? AND ep.due_definition_id = ?
            AND ep.pass_code LIKE ?
            ORDER BY
                CASE WHEN ep.status = \'active\' THEN 0 ELSE 1 END,
                CASE WHEN ep.pass_code LIKE ? THEN 0 ELSE 1 END,
                ep.id DESC
            LIMIT ' . $limit
        );
        $endsWith = '%' . $query;
        $stmt->execute([$organizationId, $dueDefinitionId, $like, $endsWith]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $code = (string) ($row['pass_code'] ?? '');
            $row['code_suffix'] = $this->passCodeSuffix($code, 3);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array{ok:bool, error?:string, pass?:array<string,mixed>}
     */
    public function redeemById(int $organizationId, int $dueDefinitionId, int $passId): array
    {
        $pass = $this->findPassByIdInEvent($organizationId, $dueDefinitionId, $passId);
        if ($pass === null) {
            return ['ok' => false, 'error' => 'Pass not found for this event.'];
        }

        return $this->redeemPassRow($pass);
    }

    /**
     * @return array{ok:bool, error?:string, pass?:array<string,mixed>}
     */
    public function redeemByCode(int $organizationId, int $dueDefinitionId, string $passCode): array
    {
        $pass = $this->findPassByCodeInEvent($organizationId, $dueDefinitionId, $passCode);
        if ($pass === null) {
            $matches = $this->searchPassesInEvent($organizationId, $dueDefinitionId, $passCode, 5);
            $active = array_values(array_filter(
                $matches,
                static fn (array $row): bool => ($row['status'] ?? '') === 'active'
            ));
            if (count($active) === 1) {
                return $this->redeemPassRow($active[0]);
            }
            if (count($active) > 1) {
                return ['ok' => false, 'error' => 'Several passes match — pick one from the list below.'];
            }

            return ['ok' => false, 'error' => 'No pass found — try the last 3 characters of the code.'];
        }

        return $this->redeemPassRow($pass);
    }

    /**
     * @return array{ok:bool, error?:string, pass?:array<string,mixed>}
     */
    public function unredeemById(int $organizationId, int $dueDefinitionId, int $passId): array
    {
        $pass = $this->findPassByIdInEvent($organizationId, $dueDefinitionId, $passId);
        if ($pass === null) {
            return ['ok' => false, 'error' => 'Pass not found for this event.'];
        }

        return $this->unredeemPassRow($pass);
    }

    /**
     * @param array<string,mixed> $pass
     * @return array{ok:bool, error?:string, pass?:array<string,mixed>}
     */
    private function redeemPassRow(array $pass): array
    {
        $passId = (int) ($pass['id'] ?? 0);
        if ($passId < 1) {
            return ['ok' => false, 'error' => 'Invalid pass.'];
        }

        $status = (string) ($pass['status'] ?? '');
        if ($status === 'cancelled') {
            return ['ok' => false, 'error' => 'This pass is cancelled.', 'pass' => $pass];
        }
        if ($status !== 'active') {
            $fresh = $this->findPassByIdInEvent(
                (int) ($pass['organization_id'] ?? 0),
                (int) ($pass['due_definition_id'] ?? 0),
                $passId
            );
            if ($fresh !== null) {
                $pass = $fresh;
            }

            return $this->redeemPassAlreadyUsedError($pass);
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE event_passes
             SET status = 'redeemed', redeemed_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'active'"
        );
        $stmt->execute([$passId]);
        if ($stmt->rowCount() < 1) {
            $fresh = $this->findPassByIdInEvent(
                (int) ($pass['organization_id'] ?? 0),
                (int) ($pass['due_definition_id'] ?? 0),
                $passId
            );

            return $this->redeemPassAlreadyUsedError($fresh ?? $pass);
        }

        $pass['status'] = 'redeemed';
        $pass['redeemed_at'] = date('Y-m-d H:i:s');

        return ['ok' => true, 'pass' => $pass];
    }

    /**
     * @param array<string,mixed> $pass
     * @return array{ok:bool, error?:string, pass?:array<string,mixed>}
     */
    private function unredeemPassRow(array $pass): array
    {
        $passId = (int) ($pass['id'] ?? 0);
        if ($passId < 1) {
            return ['ok' => false, 'error' => 'Invalid pass.'];
        }

        $status = (string) ($pass['status'] ?? '');
        if ($status === 'cancelled') {
            return ['ok' => false, 'error' => 'Cancelled passes cannot be restored.', 'pass' => $pass];
        }
        if ($status !== 'redeemed') {
            return ['ok' => false, 'error' => 'Only redeemed passes can be marked active again.', 'pass' => $pass];
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE event_passes
             SET status = 'active', redeemed_at = NULL
             WHERE id = ? AND status = 'redeemed'"
        );
        $stmt->execute([$passId]);
        if ($stmt->rowCount() < 1) {
            $fresh = $this->findPassByIdInEvent(
                (int) ($pass['organization_id'] ?? 0),
                (int) ($pass['due_definition_id'] ?? 0),
                $passId
            );

            return [
                'ok' => false,
                'error' => 'Pass status changed — refresh and try again.',
                'pass' => $fresh ?? $pass,
            ];
        }

        $pass['status'] = 'active';
        $pass['redeemed_at'] = null;

        return ['ok' => true, 'pass' => $pass];
    }

    /**
     * @param array<string,mixed> $pass
     * @return array{ok:bool, error?:string, pass?:array<string,mixed>}
     */
    private function redeemPassAlreadyUsedError(array $pass): array
    {
        $status = (string) ($pass['status'] ?? '');
        if ($status === 'redeemed') {
            $when = !empty($pass['redeemed_at'])
                ? format_pretty_datetime((string) $pass['redeemed_at'])
                : '';

            return [
                'ok' => false,
                'error' => 'Already redeemed' . ($when !== '' ? ' (' . $when . ')' : ''),
                'pass' => $pass,
            ];
        }
        if ($status === 'cancelled') {
            return ['ok' => false, 'error' => 'This pass is cancelled.', 'pass' => $pass];
        }

        return ['ok' => false, 'error' => 'This pass cannot be redeemed.', 'pass' => $pass];
    }

    private function generateUniquePassCode(PDO $pdo, int $dueDefinitionId): string
    {
        for ($i = 0; $i < 20; $i++) {
            $suffix = strtoupper(bin2hex(random_bytes(3)));
            $code = 'EVT-' . $dueDefinitionId . '-' . $suffix;
            $chk = $pdo->prepare('SELECT 1 FROM event_passes WHERE pass_code = ? LIMIT 1');
            $chk->execute([$code]);
            if ($chk->fetchColumn() === false) {
                return $code;
            }
        }

        return 'EVT-' . $dueDefinitionId . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}

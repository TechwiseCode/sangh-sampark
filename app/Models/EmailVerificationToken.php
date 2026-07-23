<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class EmailVerificationToken
{
    /** @return string plaintext token */
    public function createForUser(int $userId, int $ttlHours = 48): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))'
            );
            $stmt->execute([$userId, $hash, $ttlHours]);
        } catch (PDOException $e) {
            if ($this->isMissingTable($e)) {
                return '';
            }
            throw $e;
        }

        return $token;
    }

    public function consumeToken(string $token): ?int
    {
        $hash = hash('sha256', $token);
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT id, user_id
                FROM email_verification_tokens
                WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW()
                ORDER BY id DESC
                LIMIT 1'
            );
            $stmt->execute([$hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($this->isMissingTable($e)) {
                return null;
            }
            throw $e;
        }
        if (!$row) {
            return null;
        }
        try {
            $up = Database::pdo()->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE id = ?');
            $up->execute([(int) $row['id']]);
        } catch (PDOException $e) {
            if (!$this->isMissingTable($e)) {
                throw $e;
            }
        }

        return (int) $row['user_id'];
    }

    private function isMissingTable(PDOException $e): bool
    {
        $msg = (string) $e->getMessage();

        return strpos($msg, 'Base table or view not found') !== false
            || strpos($msg, '1146') !== false
            || strpos($msg, 'email_verification_tokens') !== false;
    }
}


<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

use function process_pending_deferred_emails;
use function json_response;

final class MailQueueController extends Controller
{
    public function process(Request $request): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $sent = process_pending_deferred_emails(5);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'sent' => $sent]);
        exit;
    }

    public function ping(Request $request): void
    {
        json_response(['ok' => true, 'mail_pipeline' => 'v8-git']);
    }
}

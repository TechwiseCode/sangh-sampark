<?php

declare(strict_types=1);

/**
 * Member → admin session chat routes.
 * Included only when member_admin_chat_enabled() is true.
 *
 * @var \App\Core\Router $router
 * @var list<class-string> $mwOrgPortal
 */

use App\Controllers\MemberAdminChatController;

$router->add('GET', '/organization/member-chat/messages', MemberAdminChatController::class, 'memberMessages', $mwOrgPortal);
$router->add('POST', '/organization/member-chat/send', MemberAdminChatController::class, 'memberSend', $mwOrgPortal);
$router->add('GET', '/organization/member-messages', MemberAdminChatController::class, 'adminIndex', $mwOrgPortal);
$router->add('POST', '/organization/member-messages/reply', MemberAdminChatController::class, 'adminReply', $mwOrgPortal);

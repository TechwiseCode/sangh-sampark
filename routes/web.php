<?php

declare(strict_types=1);

use App\Controllers\AuthApiController;
use App\Controllers\AuthController;
use App\Controllers\FamilyApiController;
use App\Controllers\OrganizationApiController;
use App\Controllers\MailQueueController;
use App\Controllers\OrganizationCommitteeController;
use App\Controllers\OrganizationPortalController;
use App\Controllers\SuperadminController;
use App\Core\Request;
use App\Core\Router;
use App\Middleware\RequireAuth;
use App\Middleware\RequireOrganizationMember;
use App\Middleware\RequireSuperadmin;
use App\Middleware\VerifyCsrf;

$router = new Router();

$mwAuth = [RequireAuth::class, VerifyCsrf::class];
$mwSuperadmin = [RequireAuth::class, RequireSuperadmin::class, VerifyCsrf::class];
$mwOrgPortal = [RequireAuth::class, RequireOrganizationMember::class, VerifyCsrf::class];
$mwCsrf = [VerifyCsrf::class];

$router->add('GET', '/', AuthController::class, 'home', []);
$router->add('GET', '/login', AuthController::class, 'showLogin', []);
$router->add('GET', '/login/superadmin', AuthController::class, 'showSuperadminLogin', []);
$router->add('GET', '/login/organization', AuthController::class, 'showOrganizationLogin', []);
$router->add('GET', '/forgot-password', AuthController::class, 'showForgotPassword', []);
$router->add('POST', '/forgot-password', AuthController::class, 'forgotPasswordStore', $mwCsrf);
$router->add('POST', '/mail/process-queue', MailQueueController::class, 'process', $mwCsrf);
$router->add('GET', '/mail/ping', MailQueueController::class, 'ping', []);
$router->add('GET', '/logout', AuthController::class, 'logout', []);
$router->add('POST', '/logout', AuthController::class, 'logout', $mwCsrf);
$router->add('GET', '/verify-email', AuthController::class, 'verifyEmail', []);
$router->add('GET', '/locale', AuthController::class, 'setLocale', []);
$router->add('POST', '/locale', AuthController::class, 'setLocale', $mwCsrf);
$router->add('GET', '/manifest.json', AuthController::class, 'manifest', []);
$router->add('GET', '/pwa/status', AuthController::class, 'pwaStatus', $mwAuth);

$router->add('POST', '/register', AuthApiController::class, 'register', $mwCsrf);
$router->add('POST', '/login', AuthApiController::class, 'login', $mwCsrf);

$router->add('GET', '/superadmin', SuperadminController::class, 'dashboard', $mwSuperadmin);
$router->add('GET', '/superadmin/admins', SuperadminController::class, 'adminsIndex', $mwSuperadmin);
$router->add('GET', '/superadmin/admins/new', SuperadminController::class, 'adminsNew', $mwSuperadmin);
$router->add('POST', '/superadmin/admins', SuperadminController::class, 'adminsStore', $mwSuperadmin);
$router->add('GET', '/superadmin/check-email', SuperadminController::class, 'checkEmailAvailability', $mwSuperadmin);
$router->add('GET', '/superadmin/check-phone', SuperadminController::class, 'checkPhoneAvailability', $mwSuperadmin);
$router->add('GET', '/superadmin/check-org-code', SuperadminController::class, 'checkOrgCodeAvailability', $mwSuperadmin);

$router->add('GET', '/superadmin/organizations', SuperadminController::class, 'organizationsIndex', $mwSuperadmin);
$router->add('GET', '/superadmin/members', SuperadminController::class, 'membersIndex', $mwSuperadmin);
$router->add('GET', '/superadmin/organizations/new', SuperadminController::class, 'organizationsNew', $mwSuperadmin);
$router->add('POST', '/superadmin/organizations', SuperadminController::class, 'organizationsStore', $mwSuperadmin);
$router->add('GET', '/superadmin/import', SuperadminController::class, 'importIndex', $mwSuperadmin);
$router->add('GET', '/superadmin/import/families', SuperadminController::class, 'familiesImport', $mwSuperadmin);
$router->add('GET', '/superadmin/import/families/sample', SuperadminController::class, 'familiesImportSample', $mwSuperadmin);
$router->add('POST', '/superadmin/import/families/preview', SuperadminController::class, 'familiesImportPreview', $mwSuperadmin);
$router->add('POST', '/superadmin/import/families/apply', SuperadminController::class, 'familiesImportApply', $mwSuperadmin);
$router->add('GET', '/superadmin/import/panchang', SuperadminController::class, 'panchangImport', $mwSuperadmin);
$router->add('POST', '/superadmin/import/panchang/preview', SuperadminController::class, 'panchangImportPreview', $mwSuperadmin);
$router->add('POST', '/superadmin/import/panchang/apply', SuperadminController::class, 'panchangImportApply', $mwSuperadmin);
$router->add('GET', '/superadmin/organization/family', SuperadminController::class, 'organizationFamilyShow', $mwSuperadmin);
$router->add('GET', '/superadmin/organization', SuperadminController::class, 'organizationShow', $mwSuperadmin);
$router->add('POST', '/superadmin/organization/update', SuperadminController::class, 'organizationUpdate', $mwSuperadmin);
$router->add('POST', '/superadmin/organization/set-active', SuperadminController::class, 'organizationSetActive', $mwSuperadmin);
$router->add('POST', '/superadmin/organization/update-admin', SuperadminController::class, 'organizationUpdateAdmin', $mwSuperadmin);
$router->add('POST', '/superadmin/organization/add-user', SuperadminController::class, 'organizationAddUser', $mwSuperadmin);
$router->add('POST', '/superadmin/organization/create-user', SuperadminController::class, 'organizationCreateUser', $mwSuperadmin);
$router->add('POST', '/organization/create', OrganizationApiController::class, 'create', $mwSuperadmin);

$router->add('GET', '/superadmin/holidays', SuperadminController::class, 'holidaysIndex', $mwSuperadmin);
$router->add('GET', '/superadmin/holidays/new', SuperadminController::class, 'holidaysNew', $mwSuperadmin);
$router->add('POST', '/superadmin/holidays', SuperadminController::class, 'holidaysStore', $mwSuperadmin);
$router->add('GET', '/superadmin/holidays/edit', SuperadminController::class, 'holidaysEdit', $mwSuperadmin);
$router->add('POST', '/superadmin/holidays/update', SuperadminController::class, 'holidaysUpdate', $mwSuperadmin);
$router->add('POST', '/superadmin/holidays/delete', SuperadminController::class, 'holidaysDelete', $mwSuperadmin);
$router->add('GET', '/superadmin/mail-test', SuperadminController::class, 'mailTestShow', $mwSuperadmin);
$router->add('POST', '/superadmin/mail-test', SuperadminController::class, 'mailTestSend', $mwSuperadmin);

$router->add('GET', '/organization/dashboard', OrganizationPortalController::class, 'dashboard', $mwOrgPortal);
$router->add('GET', '/organization/presence', OrganizationPortalController::class, 'presenceIndex', $mwOrgPortal);
$router->add('POST', '/organization/presence', OrganizationPortalController::class, 'presenceStore', $mwOrgPortal);
$router->add('GET', '/organization/sadhvis', OrganizationPortalController::class, 'sadhvisIndex', $mwOrgPortal);
$router->add('GET', '/organization/sadhvis/search', OrganizationPortalController::class, 'sadhvisSearch', $mwOrgPortal);
$router->add('GET', '/organization/calendar', OrganizationPortalController::class, 'calendarIndex', $mwOrgPortal);
$router->add('GET', '/organization/calendar/feed', OrganizationPortalController::class, 'calendarFeed', $mwOrgPortal);
$router->add('GET', '/organization/calendar-days', OrganizationPortalController::class, 'calendarDaysIndex', $mwOrgPortal);
$router->add('GET', '/organization/calendar-days/new', OrganizationPortalController::class, 'calendarDaysNew', $mwOrgPortal);
$router->add('POST', '/organization/calendar-days', OrganizationPortalController::class, 'calendarDaysStore', $mwOrgPortal);
$router->add('GET', '/organization/calendar-days/edit', OrganizationPortalController::class, 'calendarDaysEdit', $mwOrgPortal);
$router->add('POST', '/organization/calendar-days/update', OrganizationPortalController::class, 'calendarDaysUpdate', $mwOrgPortal);
$router->add('POST', '/organization/calendar-days/delete', OrganizationPortalController::class, 'calendarDaysDelete', $mwOrgPortal);
$router->add('GET', '/organization/committee', OrganizationCommitteeController::class, 'index', $mwOrgPortal);
$router->add('POST', '/organization/committee', OrganizationCommitteeController::class, 'store', $mwOrgPortal);
$router->add('POST', '/organization/committee/update', OrganizationCommitteeController::class, 'update', $mwOrgPortal);
$router->add('POST', '/organization/committee/delete', OrganizationCommitteeController::class, 'delete', $mwOrgPortal);
$router->add('GET', '/organization/check-email', OrganizationPortalController::class, 'checkEmailAvailability', $mwOrgPortal);
$router->add('GET', '/organization/check-phone', OrganizationPortalController::class, 'checkPhoneAvailability', $mwOrgPortal);
$router->add('GET', '/organization/pincode-lookup', OrganizationPortalController::class, 'pincodeLookup', $mwOrgPortal);
$router->add('GET', '/organization/resolve-identity', OrganizationPortalController::class, 'resolveIdentity', $mwOrgPortal);
$router->add('GET', '/organization/families', OrganizationPortalController::class, 'familiesIndex', $mwOrgPortal);
$router->add('GET', '/organization/members/import', OrganizationPortalController::class, 'membersImportIndex', $mwOrgPortal);
$router->add('GET', '/organization/members/import/sample', OrganizationPortalController::class, 'membersImportSample', $mwOrgPortal);
$router->add('POST', '/organization/members/import/preview', OrganizationPortalController::class, 'membersImportPreview', $mwOrgPortal);
$router->add('POST', '/organization/members/import/apply', OrganizationPortalController::class, 'membersImportApply', $mwOrgPortal);
$router->add('GET', '/organization/families/new', OrganizationPortalController::class, 'familyNew', $mwOrgPortal);
$router->add('POST', '/organization/families', OrganizationPortalController::class, 'familyCreateStore', $mwOrgPortal);
$router->add('GET', '/organization/family/relationship-request', OrganizationPortalController::class, 'relationshipRequestForm', $mwOrgPortal);
$router->add('POST', '/organization/family/relationship-request', OrganizationPortalController::class, 'relationshipRequestStore', $mwOrgPortal);
$router->add('GET', '/organization/family', OrganizationPortalController::class, 'familyShow', $mwOrgPortal);
$router->add('GET', '/organization/my-family', OrganizationPortalController::class, 'myFamily', $mwOrgPortal);
$router->add('GET', '/organization/family/history', OrganizationPortalController::class, 'familyHistory', $mwOrgPortal);
$router->add('POST', '/organization/family/add-member', OrganizationPortalController::class, 'familyAddMemberStore', $mwOrgPortal);
$router->add('POST', '/organization/family/split', OrganizationPortalController::class, 'familySplitStore', $mwOrgPortal);
$router->add('POST', '/organization/family/member-update', OrganizationPortalController::class, 'familyMemberUpdateStore', $mwOrgPortal);
$router->add('POST', '/organization/family/member-active', OrganizationPortalController::class, 'familyMemberSetActive', $mwOrgPortal);
$router->add('GET', '/organization/schemes', OrganizationPortalController::class, 'schemesIndex', $mwOrgPortal);
$router->add('GET', '/organization/receipts', OrganizationPortalController::class, 'receiptsIndex', $mwOrgPortal);
$router->add('GET', '/organization/my-receipts', OrganizationPortalController::class, 'myReceiptsIndex', $mwOrgPortal);
$router->add('POST', '/organization/receipts', OrganizationPortalController::class, 'receiptsStore', $mwOrgPortal);
$router->add('GET', '/organization/donations', OrganizationPortalController::class, 'donationsIndex', $mwOrgPortal);
$router->add('POST', '/organization/donations/commitment', OrganizationPortalController::class, 'donationsCommitmentStore', $mwOrgPortal);
$router->add('POST', '/organization/donations/payment', OrganizationPortalController::class, 'donationsPaymentStore', $mwOrgPortal);
$router->add('POST', '/organization/donations/categories', OrganizationPortalController::class, 'donationsCategoryStore', $mwOrgPortal);
$router->add('POST', '/organization/donations/categories/toggle', OrganizationPortalController::class, 'donationsCategoryToggle', $mwOrgPortal);
$router->add('POST', '/organization/dues', OrganizationPortalController::class, 'duesCreateStore', $mwOrgPortal);
$router->add('GET', '/organization/receipt/print', OrganizationPortalController::class, 'receiptPrint', $mwOrgPortal);
$router->add('GET', '/organization/schemes/new', OrganizationPortalController::class, 'schemesNew', $mwOrgPortal);
$router->add('POST', '/organization/schemes', OrganizationPortalController::class, 'schemesCreateStore', $mwOrgPortal);
$router->add('GET', '/organization/schemes/edit', OrganizationPortalController::class, 'schemesEdit', $mwOrgPortal);
$router->add('POST', '/organization/schemes/update', OrganizationPortalController::class, 'schemesUpdateStore', $mwOrgPortal);
$router->add('POST', '/organization/schemes/delete', OrganizationPortalController::class, 'schemesDeleteStore', $mwOrgPortal);
$router->add('GET', '/organization/scheme', OrganizationPortalController::class, 'schemeShow', $mwOrgPortal);
$router->add('POST', '/organization/scheme/mark-done', OrganizationPortalController::class, 'schemeMarkDone', $mwOrgPortal);
$router->add('GET', '/organization/passes', OrganizationPortalController::class, 'passesIndex', $mwOrgPortal);
$router->add('GET', '/organization/events', OrganizationPortalController::class, 'eventsIndex', $mwOrgPortal);
$router->add('GET', '/organization/notices', OrganizationPortalController::class, 'noticesIndex', $mwOrgPortal);
$router->add('POST', '/organization/notices', OrganizationPortalController::class, 'noticesStore', $mwOrgPortal);
$router->add('POST', '/organization/notices/delete', OrganizationPortalController::class, 'noticesDelete', $mwOrgPortal);
$router->add('POST', '/organization/notices/pin', OrganizationPortalController::class, 'noticesPin', $mwOrgPortal);
$router->add('POST', '/organization/notices/active', OrganizationPortalController::class, 'noticesSetActive', $mwOrgPortal);
$router->add('GET', '/organization/notices/file', OrganizationPortalController::class, 'noticesFile', $mwOrgPortal);
$router->add('GET', '/organization/event', OrganizationPortalController::class, 'eventsShow', $mwOrgPortal);
$router->add('GET', '/organization/event/pass-search', OrganizationPortalController::class, 'eventsPassSearch', $mwOrgPortal);
$router->add('POST', '/organization/event/redeem', OrganizationPortalController::class, 'eventsRedeemStore', $mwOrgPortal);
$router->add('POST', '/organization/event/unredeem', OrganizationPortalController::class, 'eventsUnredeemStore', $mwOrgPortal);
$router->add('GET', '/organization/profile', OrganizationPortalController::class, 'profile', $mwOrgPortal);
$router->add('POST', '/organization/profile/update', OrganizationPortalController::class, 'profileUpdate', $mwOrgPortal);
$router->add('POST', '/organization/profile/photo', OrganizationPortalController::class, 'profilePhotoStore', $mwOrgPortal);
$router->add('POST', '/organization/switch-org', OrganizationPortalController::class, 'switchOrganization', $mwOrgPortal);
$router->add('GET', '/organization/settings', OrganizationPortalController::class, 'settings', $mwOrgPortal);
$router->add('GET', '/organization/settings/password', OrganizationPortalController::class, 'settingsPassword', $mwOrgPortal);
$router->add('GET', '/organization/settings/notifications', OrganizationPortalController::class, 'settingsNotifications', $mwOrgPortal);
$router->add('GET', '/organization/settings/language', OrganizationPortalController::class, 'settingsLanguage', $mwOrgPortal);
$router->add('POST', '/organization/settings/change-password', OrganizationPortalController::class, 'settingsChangePassword', $mwOrgPortal);
$router->add('GET', '/organization/notifications', OrganizationPortalController::class, 'notificationsIndex', $mwOrgPortal);
$router->add('GET', '/organization/notifications/preview', OrganizationPortalController::class, 'notificationsPreview', $mwOrgPortal);
$router->add('GET', '/organization/notifications/list', OrganizationPortalController::class, 'notificationsList', $mwOrgPortal);
$router->add('POST', '/organization/notifications/mark-read', OrganizationPortalController::class, 'notificationsMarkRead', $mwOrgPortal);
$router->add('GET', '/organization/notifications/broadcast/recipients', OrganizationPortalController::class, 'notificationsBroadcastRecipients', $mwOrgPortal);
$router->add('POST', '/organization/notifications/broadcast', OrganizationPortalController::class, 'notificationsBroadcastStore', $mwOrgPortal);
$router->add('GET', '/organization/notifications/push/vapid-public-key', OrganizationPortalController::class, 'pushVapidPublicKey', $mwOrgPortal);
$router->add('GET', '/organization/notifications/push/status', OrganizationPortalController::class, 'pushSubscriptionStatus', $mwOrgPortal);
$router->add('POST', '/organization/notifications/push/subscribe', OrganizationPortalController::class, 'pushSubscribe', $mwOrgPortal);
$router->add('POST', '/organization/notifications/push/unsubscribe', OrganizationPortalController::class, 'pushUnsubscribe', $mwOrgPortal);
$router->add('POST', '/organization/membership-request/respond', OrganizationPortalController::class, 'membershipRequestRespond', $mwOrgPortal);

if (member_admin_chat_enabled()) {
    require __DIR__ . '/member_admin_chat.php';
}

if (is_control_plane()) {
    $router->add('POST', '/organization/add-admin', OrganizationApiController::class, 'addAdmin', $mwAuth);
}

$router->add('POST', '/family/create', FamilyApiController::class, 'create', $mwAuth);
$router->add('POST', '/family/add-member', FamilyApiController::class, 'addMember', $mwAuth);
$router->add('GET', '/family/list', FamilyApiController::class, 'list', $mwAuth);
$router->add('GET', '/family/details', FamilyApiController::class, 'details', $mwAuth);

$request = Request::fromGlobals();
$router->dispatch($request);

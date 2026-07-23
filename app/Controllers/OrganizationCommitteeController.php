<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\OrganizationPortalTrait;
use App\Core\Request;
use App\Models\OrgCommitteeMember;
use App\Models\Organization;
use App\Services\Access;

use function base_url;
use function committee_designation_options;
use function flash_set;
use function normalize_committee_designation_key;
use function page_title;
use function redirect;
use function t;

/**
 * Organization committee list (display only — not admin login roles).
 */
final class OrganizationCommitteeController extends Controller
{
    use OrganizationPortalTrait;

    public function index(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $canManage = (new Access())->canManageOrganization($bundle['user'], $orgId);
        if (!$canManage) {
            redirect(base_url() . '/organization/dashboard#committee');
        }
        $editId = (int) ($_GET['edit'] ?? 0);
        $this->renderPage($bundle, true, $editId > 0 ? $editId : null, null, null);
    }

    public function store(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->requireAdmin($bundle['user'], $orgId);
        $this->storeOrUpdate($orgId, $bundle, null);
    }

    public function update(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->requireAdmin($bundle['user'], $orgId);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('committee.error_invalid'));
            redirect(base_url() . '/organization/committee');
        }
        $this->storeOrUpdate($orgId, $bundle, $id);
    }

    public function delete(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->requireAdmin($bundle['user'], $orgId);
        $id = (int) ($_POST['id'] ?? 0);
        $model = new OrgCommitteeMember();
        if ($id < 1 || $model->findByIdInOrganization($id, $orgId) === null) {
            flash_set('error', t('committee.error_not_found'));
            redirect(base_url() . '/organization/committee');
        }
        $model->delete($id, $orgId);
        flash_set('ok', t('committee.deleted'));
        redirect(base_url() . '/organization/committee');
    }

    /** @param array<string,mixed> $user */
    private function requireAdmin(array $user, int $orgId): void
    {
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', t('committee.error_forbidden'));
            redirect(base_url() . '/organization/dashboard');
        }
    }

    /**
     * @param array{memberships:list<array<string,mixed>>,current:?array<string,mixed>,orgId:int,user:array<string,mixed>} $bundle
     */
    private function storeOrUpdate(int $orgId, array $bundle, ?int $id): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $designationKey = normalize_committee_designation_key((string) ($_POST['designation_key'] ?? ''));

        $drafts = [
            'user_id' => $userId,
            'designation_key' => (string) ($_POST['designation_key'] ?? ''),
        ];

        $renderError = function (string $message) use ($bundle, $id, $drafts): void {
            $this->renderPage($bundle, true, $id, $message, $drafts);
        };

        if ($userId < 1) {
            $renderError(t('committee.error_member'));
            return;
        }
        if ($designationKey === null) {
            $renderError(t('committee.error_designation'));
            return;
        }

        $orgUser = (new Organization())->findOrgUserForCommittee($orgId, $userId);
        if ($orgUser === null) {
            $renderError(t('committee.error_member'));
            return;
        }

        $personName = trim((string) ($orgUser['name'] ?? ''));
        if ($personName === '') {
            $renderError(t('committee.error_member'));
            return;
        }

        $model = new OrgCommitteeMember();
        $payload = [
            'user_id' => $userId,
            'person_name' => $personName,
            'designation_key' => $designationKey,
        ];

        if ($id !== null && $id > 0) {
            if ($model->findByIdInOrganization($id, $orgId) === null) {
                flash_set('error', t('committee.error_not_found'));
                redirect(base_url() . '/organization/committee');
            }
            $model->update($id, $orgId, $payload);
            flash_set('ok', t('committee.updated'));
            redirect(base_url() . '/organization/committee?edit=' . $id);
        }

        $createdBy = (int) ($bundle['user']['id'] ?? 0);
        $model->create([
            'organization_id' => $orgId,
            'user_id' => $payload['user_id'],
            'person_name' => $payload['person_name'],
            'designation_key' => $payload['designation_key'],
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);
        flash_set('ok', t('committee.created'));
        redirect(base_url() . '/organization/committee');
    }

    /**
     * @param array{memberships:list<array<string,mixed>>,current:?array<string,mixed>,orgId:int,user:array<string,mixed>} $bundle
     * @param array<string,mixed>|null $drafts
     */
    private function renderPage(array $bundle, bool $canManage, ?int $editId, ?string $formError, ?array $drafts): void
    {
        $orgId = (int) $bundle['orgId'];
        $model = new OrgCommitteeMember();
        $members = $model->listForOrganization($orgId);
        $resolvedEditId = $editId !== null && $editId > 0 ? $editId : 0;
        $editing = null;
        if ($canManage && $resolvedEditId > 0) {
            $editing = $model->findByIdInOrganization($resolvedEditId, $orgId);
            if ($editing === null) {
                flash_set('error', t('committee.error_not_found'));
                redirect(base_url() . '/organization/committee');
            }
        }

        if ($drafts !== null) {
            $userIdDraft = (int) ($drafts['user_id'] ?? 0);
            $designationDraft = (string) ($drafts['designation_key'] ?? '');
        } elseif ($editing !== null) {
            $userIdDraft = (int) ($editing['user_id'] ?? 0);
            $designationDraft = (string) ($editing['designation_key'] ?? '');
        } else {
            $userIdDraft = 0;
            $designationDraft = '';
        }

        $pickerUsers = $canManage
            ? (new Organization())->listUsersForCommitteePicker($orgId)
            : [];

        $this->render('organization', 'committee/index.php', [
            'pageTitle' => page_title(t('committee.title')),
            'navActive' => 'committee',
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'user' => $bundle['user'],
        ], [
            'canManage' => $canManage,
            'members' => $members,
            'pickerUsers' => $pickerUsers,
            'designationOptions' => committee_designation_options(),
            'editId' => $resolvedEditId,
            'formError' => $formError,
            'userIdDraft' => $userIdDraft,
            'designationDraft' => $designationDraft,
        ]);
    }
}

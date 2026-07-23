# Member → admin session chat (optional module)

Floating **Ask admin** chat for members. Admins get notifications and reply from **Member messages** in the sidebar. Member replies also arrive as **Notifications**.

Session-scoped: each browser tab/window gets its own token in `sessionStorage`; closing the tab clears the chat UI. Admin replies while the member is still online appear in the widget; otherwise the member sees the reply in Notifications.

## Enable

1. Run migration (once):

   ```bash
   mysql -u USER -p DATABASE < database/migrations/025_member_admin_chat.sql
   ```

   Tables are also auto-created on first use via `MemberAdminChat` model.

2. In `.env` (default is **on** if unset):

   ```env
   MEMBER_ADMIN_CHAT=true
   ```

3. Upload changed files to production (see file list below).

## Disable without removing code

Set in `.env`:

```env
MEMBER_ADMIN_CHAT=false
```

This removes routes, sidebar link, and the member widget. No DB changes required.

## Fully revert (remove integration)

1. Set `MEMBER_ADMIN_CHAT=false` and redeploy.
2. Remove the hook in `routes/web.php`:

   ```php
   if (member_admin_chat_enabled()) {
       require __DIR__ . '/member_admin_chat.php';
   }
   ```

3. Remove layout hooks in `app/Views/organization/layout.php` (CSS link, sidebar nav, widget include).
4. Optionally drop tables:

   ```sql
   DROP TABLE IF EXISTS org_member_admin_chat_messages;
   DROP TABLE IF EXISTS org_member_admin_chat_threads;
   ```

5. Delete module files (all optional once disabled):

   | Path |
   |------|
   | `app/Models/MemberAdminChat.php` |
   | `app/Controllers/MemberAdminChatController.php` |
   | `routes/member_admin_chat.php` |
   | `app/Views/partials/member_admin_chat_widget.php` |
   | `app/Views/organization/member_admin_chat/index.php` |
   | `themes/js/member-admin-chat.js` |
   | `themes/css/member-admin-chat.css` |
   | `database/migrations/025_member_admin_chat.sql` |
   | `docs/MEMBER_ADMIN_CHAT.md` |

6. Remove from `config/app.php`, `app/helpers.php` (`member_admin_chat_enabled`), `lang/en.php` / `lang/gu.php` keys, and notification icon branch in `notification_inbox_item.php`.

## Files added by this module

- `app/Models/MemberAdminChat.php`
- `app/Controllers/MemberAdminChatController.php`
- `routes/member_admin_chat.php`
- `app/Views/partials/member_admin_chat_widget.php`
- `app/Views/organization/member_admin_chat/index.php`
- `themes/js/member-admin-chat.js`
- `themes/css/member-admin-chat.css`
- `database/migrations/025_member_admin_chat.sql`

## Routes

| Method | Path | Who |
|--------|------|-----|
| GET | `/organization/member-chat/messages` | Member (JSON) |
| POST | `/organization/member-chat/send` | Member (JSON) |
| GET | `/organization/member-messages` | Org admin |
| POST | `/organization/member-messages/reply` | Org admin |

## Notification types

- `member_admin_chat` — to admins when a member sends a message
- `member_admin_chat_reply` — to member when admin replies

Both trigger **in-app notifications** and **web push** (if VAPID keys are in `.env` and the user allowed notifications on their device).

| Recipient | Push opens |
|-----------|------------|
| Admin | Member messages thread (`/organization/member-messages?thread=…`) |
| Member | Notifications page (reply text) |

Push permission is requested automatically on org pages when this module is enabled and push is configured. Users can also enable it under **Settings → Notifications**.

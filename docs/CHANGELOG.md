# Development change log

---

## Uploaded to server — 19 May 2026

**Phase 1**
- `app/Models/EventPass.php`
- `app/helpers.php`
- `app/Controllers/OrganizationPortalController.php`
- `routes/web.php`
- `app/Views/organization/events/show.php`
- `app/Views/organization/schemes/show.php`
- `app/Views/organization/schemes/index.php`

**Phase 2**
- `app/helpers.php`
- `app/Models/User.php`
- `app/Controllers/OrganizationPortalController.php`
- `routes/web.php`
- `app/Views/organization/profile/show.php`
- `app/Views/organization/layout.php`
- `database/migrations/008_user_profile_photo.sql`
- `database/schema.sql`
- `public/uploads/profile-photos/.gitkeep`

**Server steps after upload (Phase 2 only)**
- [ ] Run `database/migrations/008_user_profile_photo.sql` on live DB (if not already)
- [ ] Create / chmod `public/uploads/profile-photos/` (writable by web server)
- [ ] `.env` unchanged unless `APP_URL` / `ASSET_URL` differ on server

**Quick test**
- [ ] Event: redeem pass; second admin gets “already redeemed”
- [ ] Event: Undo on redeemed pass
- [ ] Scheme: mark done → date + time shown
- [ ] Profile: upload photo; header avatar updates

---

## Phase 1 — Bugs & small fixes (pass redemption + scheme datetime)

**Date:** May 2026  
**Scope:** Race-safe pass redeem, undo mistaken redemption, scheme benefit date + time display.

| File | What changed |
|------|----------------|
| `app/Models/EventPass.php` | Atomic redeem (`UPDATE … WHERE status = 'active'`); `unredeemById()` / `unredeemPassRow()`; clearer “already redeemed” errors with datetime |
| `app/helpers.php` | Added `format_pretty_datetime()` |
| `app/Controllers/OrganizationPortalController.php` | Added `eventsUnredeemStore()` for admin undo redeem |
| `routes/web.php` | Added `POST /organization/event/unredeem` |
| `app/Views/organization/events/show.php` | Redeemed time with datetime; **Undo** button on redeemed passes |
| `app/Views/organization/schemes/show.php` | Claimed column shows date + time |
| `app/Views/organization/schemes/index.php` | Member view: **Benefitted at** column with date + time |

**Not in this batch:** member photos, calendar, attendance, i18n.

---

## Phase 2 — Member profile photo (self-upload)

**Date:** May 2026  
**Scope:** Members upload/remove their own photo on **My profile**; avatar in header.

| File | What changed |
|------|----------------|
| `app/helpers.php` | `user_photo_storage_dir()`, `user_photo_url()`, `user_photo_initials()`, `save_user_profile_photo()`, `delete_user_profile_photo()` |
| `app/Models/User.php` | `photo_path` column support; `updatePhotoPath()`; `ensurePhotoPathColumn()`; `toSessionArray()` includes `photo_path` |
| `app/Controllers/OrganizationPortalController.php` | `profilePhotoStore()`; `profile()` refreshes user from DB for photo |
| `routes/web.php` | Added `POST /organization/profile/photo` |
| `app/Views/organization/profile/show.php` | Profile photo card: preview, upload, remove |
| `app/Views/organization/layout.php` | Nav avatar (photo or initials) beside user name |
| `database/migrations/008_user_profile_photo.sql` | **New** — `users.photo_path` |
| `database/schema.sql` | `users.photo_path` on fresh installs |
| `public/uploads/profile-photos/.gitkeep` | **New** — upload directory placeholder |

**Deploy note:** Run migration `008_user_profile_photo.sql` on existing DBs; ensure `public/uploads/profile-photos/` is writable.

**Overlap with Phase 1:** `app/helpers.php`, `OrganizationPortalController.php`, and `routes/web.php` were edited in both phases (different features in the same files).

---

## Next (not uploaded yet)

1. Member photos on members list / family views
2. i18n — English / Gujarati header toggle
3. Attendance module (classes, sessions, manual mark)
4. Org calendar (events, schemes, class dates)
5. NFC attendance (later)

---

## Fresh local changes — EN/GU i18n (not uploaded yet)

- `lang/en.php` (new)
- `lang/gu.php` (new)
- `app/helpers.php`
- `app/Controllers/AuthController.php`
- `routes/web.php`
- `app/Views/organization/layout.php`
- `app/Views/superadmin/layout.php`
- `app/Views/organization/dashboard.php`
- `app/Views/organization/settings/index.php`
- `app/Views/organization/events/index.php`
- `app/Views/organization/events/show.php`
- `app/Views/organization/schemes/index.php`
- `app/Views/organization/schemes/show.php`
- `app/Views/organization/profile/show.php`
- `app/Views/organization/families/index.php`

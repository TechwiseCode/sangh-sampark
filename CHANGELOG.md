# Changelog

Files changed are listed per change from this file onward.

## 2026-06-08 — Rebrand to SanghSampark

**Change:** Replaced “Techwise” branding with “SanghSampark” across page titles, auth screens, and emails. App name is configurable via `APP_NAME` in `.env`.

**Files changed:**
- `config/app.php` — `app_name`
- `app/helpers.php` — `app_name()`, `page_title()`
- `app/Controllers/SuperadminController.php`, `OrganizationPortalController.php`
- `lang/en.php`, `lang/gu.php`
- `application/views/login.php`, `dashboard.php`
- `CHANGELOG.md` — this entry

## 2026-06-08 — Member name parts and required phone

**Change:** Users now have separate first, middle, and last name fields (middle optional). Phone is required when creating or updating members, family heads, and org admins. Admin Members table shows name in three columns. Profile and family forms updated.

**Migration:** `database/migrations/017_user_name_parts.sql`

**Files changed:**
- `database/migrations/017_user_name_parts.sql`
- `database/schema.sql`
- `app/helpers.php` — name/phone helpers
- `app/Models/User.php`
- `app/Services/UserProvisionService.php`
- `app/Models/Organization.php`
- `app/Controllers/OrganizationPortalController.php`
- `app/Controllers/SuperadminController.php`
- `app/Views/partials/person_name_fields.php`, `phone_field.php`
- `app/Views/organization/profile/show.php`
- `app/Views/organization/families/new.php`, `show.php`, `members.php`, `index.php`
- `app/Views/superadmin/admins/form.php`, `organizations/form.php`, `organizations/show.php`
- `lang/en.php`, `lang/gu.php`
- `CHANGELOG.md` — this entry

## 2026-06-08 — Admin Members directory

**Change:** Org admin sidebar tab renamed from “Family” to “Members”. Admins see all organization members in a table with filters for all members or family heads only. Regular members still see “Family” and the existing family list.

**Files changed:**
- `app/Models/Organization.php` — `listMembersDirectory()`
- `app/Controllers/OrganizationPortalController.php` — admin members view
- `app/Views/organization/families/members.php` — new members table + filter
- `app/Views/organization/layout.php` — nav label by role
- `lang/en.php`, `lang/gu.php`
- `CHANGELOG.md` — this entry

## 2026-06-08 — Platform holidays (superadmin)

**Change:** Superadmin can add holidays, Paryushan, and religious days platform-wide. All org admins and members see them on the dashboard calendar (gold dots).

**Migration:** `database/migrations/016_platform_holidays.sql`

**Files changed:**
- `database/migrations/016_platform_holidays.sql`
- `app/Models/PlatformHoliday.php`
- `app/Controllers/SuperadminController.php` — holidays CRUD
- `app/Controllers/OrganizationPortalController.php` — calendar feed
- `app/helpers.php` — holiday title/category helpers
- `app/Views/superadmin/holidays/index.php`, `form.php`
- `app/Views/superadmin/layout.php` — nav
- `app/Views/organization/partials/calendar_widget.php`
- `routes/web.php`
- `themes/css/app.css`
- `lang/en.php`, `lang/gu.php`
- `CHANGELOG.md` — this entry

## 2026-06-08 — Calendar: tithi removed (parked)

**Change:** Removed tithi labels from the dashboard calendar. Accurate Gujarati panchang needs a paid/verified data source; revisit when a suitable API or library is available.

**Files changed:**
- `app/Views/organization/partials/calendar_widget.php` — removed tithi UI and client panchang
- `themes/css/app.css` — reverted tithi cell styling
- `.env.example` — removed `PANCHANG_*` options
- `CHANGELOG.md` — this entry

## 2026-06-08 — Calendar: tithi without API account

**Change:** Tithi is computed in the browser with `@mera-vansh/ms-panchang` (no signup). Colloquial Gujarati labels; optional `PANCHANG_CITY` in `.env` (default Ahmedabad). Requires internet on first load (CDN cached by browser).

**Files changed:**
- `app/Views/organization/partials/calendar_widget.php` — client-side panchang
- `app/Controllers/OrganizationPortalController.php` — removed server tithi/API
- `app/Services/TithiService.php` — removed
- `.env.example` — `PANCHANG_CITY` only
- `CHANGELOG.md` — this entry

## 2026-06-08 — Calendar: accurate tithi via TathaAstu API

**Change:** Replaced local tithi approximation with TathaAstu Panchang API (Lahiri / Drik). Monthly results are file-cached. Set `PANCHANG_API_KEY` in `.env` (free signup at tathaastuapi.com).

**Files changed:**
- `app/Services/TithiService.php` — API client, cache, colloquial Gujarati labels
- `.env.example` — `PANCHANG_API_KEY`
- `storage/cache/panchang/.gitkeep`
- `CHANGELOG.md` — this entry

## 2026-06-08 — Calendar: tithi names on dates

**Change:** Each calendar date shows the Hindu tithi name at sunrise (Gujarati or English per locale). Location defaults to Ahmedabad; override with `PANCHANG_LAT` / `PANCHANG_LON` in `.env`.

**Files changed:**
- `app/Services/TithiService.php` — sunrise-based tithi calculation
- `app/Controllers/OrganizationPortalController.php` — `tithiByDay` on dashboard and calendar feed
- `app/Views/organization/partials/calendar_widget.php` — render tithi under day number
- `themes/css/app.css` — tithi label styling
- `.env.example` — optional panchang coordinates
- `CHANGELOG.md` — this entry

## 2026-06-08 — Admin calendar: member birthdays

**Change:** Organization admins see member and family dependent birthdays on the dashboard calendar (pink dots), with age and a link to the family page.

**Files changed:**
- `app/Models/Organization.php` — `listBirthdaysForCalendarMonth()`
- `app/Controllers/OrganizationPortalController.php` — birthdays in `buildCalendarItems()` for admins
- `app/Views/organization/partials/calendar_widget.php` — birthday legend, dots, day panel meta
- `themes/css/app.css` — birthday dot and badge styles
- `lang/en.php`, `lang/gu.php` — calendar birthday strings
- `CHANGELOG.md` — this entry

## 2026-06-15 — Skip profile completion for org admins

**Problem:** Organization admins were treated like members and forced to complete profile on login.

**Fix:** Skip profile requirement when user is an org admin (`users.role = admin` or `Access::canManageOrganization`).

**Files changed:**
- `app/Controllers/OrganizationPortalController.php` — `memberNeedsProfileCompletion()` skips admins
- `app/Controllers/AuthApiController.php` — login no longer sets `must_complete_profile` for org admins
- `CHANGELOG.md` — this file (change tracking)

## 2026-06-15 — Blood group: Unknown option

**Change:** Added **Unknown** as the last blood group option on profile and in validation/import.

**Files changed:**
- `app/helpers.php` — `blood_group_options()`, `normalize_blood_group()`, `is_valid_blood_group()`
- `app/Controllers/OrganizationPortalController.php` — profile save uses normalized blood group
- `app/Views/organization/profile/show.php` — dropdown includes Unknown (last)
- `app/Views/organization/families/show.php` — display Unknown label
- `app/Controllers/SuperadminController.php` — CSV import normalizes blood group
- `lang/en.php`, `lang/gu.php` — `profile.blood_unknown`
- `CHANGELOG.md` — this entry

## 2026-06-15 — Gender: Other option

**Change:** Profile gender dropdown includes **Other** (third option after Male, Female). DB ENUM updated for older databases; import accepts `other` case-insensitively.

**Files changed:**
- `app/helpers.php` — `normalize_gender()`, `is_valid_gender()` via normalize
- `app/Models/UserProfile.php` — ensure gender ENUM includes Other on save
- `app/Controllers/OrganizationPortalController.php` — profile save normalizes gender
- `app/Controllers/SuperadminController.php` — CSV import normalizes gender
- `database/migrations/014_gender_other.sql` — ALTER gender ENUM
- `CHANGELOG.md` — this entry

## 2026-06-15 — Marital Status values

**Change:** Label **Marital Status**; allowed values **Single, Married, Widowed, Divorced** only (removed Separated; legacy Separated mapped to Divorced).

**Files changed:**
- `app/helpers.php` — `marital_status_options()`, `normalize_marital_status()`
- `app/Models/UserProfile.php` — ENUM alignment on save
- `app/Controllers/OrganizationPortalController.php` — profile save normalizes marital status
- `database/migrations/015_marital_status_values.sql`
- `database/schema.sql`
- `lang/en.php`, `lang/gu.php`
- `CHANGELOG.md` — this entry

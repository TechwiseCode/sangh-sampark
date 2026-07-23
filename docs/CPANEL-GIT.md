# Deploy Sangh Sampark with Git on GoDaddy cPanel

Repo: https://github.com/TechwiseCode/sangh-sampark  
Live URL pattern: `https://techwise-apps.com/sanghsampark/public`

This guide uses **cPanel → Git Version Control** (no SSH terminal required).

---

## Before you start

1. **Backup** the current `sanghsampark` folder in File Manager (Compress → download).
2. Keep a copy of the server **`.env`** (File Manager → show hidden files → download `.env`).
3. Confirm the GitHub repo is reachable: https://github.com/TechwiseCode/sangh-sampark

---

## A. First-time Git setup on cPanel

### 1. Open Git Version Control

cPanel → search **Git Version Control** → **Create**.

### 2. Clone the repo

| Field | Value |
|--------|--------|
| Clone a Repository | **ON** |
| Clone URL | `https://github.com/TechwiseCode/sangh-sampark.git` |
| Repository Path | e.g. `sanghsampark` (under your home / `public_html`) |
| Repository Name | `Sangh Sampark` |

Examples of **Repository Path** (use your real home path shown in cPanel):

- `public_html/sanghsampark`  
  or  
- `sanghsampark` (if that is already under `public_html`)

Click **Create**.

> If the folder already has files, either:
> - clone into a **new empty folder** (e.g. `sanghsampark-git`), then swap after testing, **or**
> - empty/move the old files first (after backup), then clone into `sanghsampark`.

### 3. Restore `.env` (required — not in Git)

1. File Manager → open the cloned folder.  
2. Enable **Show Hidden Files**.  
3. Upload your production `.env` into the **project root** (same level as `app/`, `public/`, `composer.json`).  
4. Confirm SMTP / DB settings still match production (Techwise mail, MySQL user, etc.).

### 4. Install PHP dependencies (`vendor/`)

`vendor/` is **not** in Git. On the server you need it once:

**If cPanel has Terminal / Composer:**

```bash
cd ~/public_html/sanghsampark
composer install --no-dev --optimize-autoloader
```

**If no Composer:**  
On your PC (WAMP project folder), zip the local `vendor/` folder and upload/extract it into the server project root so you have `sanghsampark/vendor/…`.

### 5. Permissions

In File Manager, set writable where needed:

- `storage/deferred_emails` → `775` (or `755` if that works)
- any upload folders your host already used

### 6. Document root

Your site must continue to serve **`…/sanghsampark/public`** (as today).  
Do **not** point the domain at the repo root unless you change hosting config.

### 7. Quick checks

- https://techwise-apps.com/sanghsampark/public/mail/ping  
- Superadmin login  
- **Test email** page (if deployed)

---

## B. Updating the live site later (pull)

Whenever you push new commits from your PC:

1. cPanel → **Git Version Control**  
2. Open **Sangh Sampark**  
3. **Pull or Deploy** → **Update from Remote** (or **Pull**)  
4. Confirm branch is **`main`**

If your host shows a **Deploy** button with a deployment path, use that only if it copies into the live folder you intend. Prefer cloning **directly into** the live `sanghsampark` path so Pull = update live code.

After pull:

- `.env` is untouched (good)  
- If `composer.json` / `composer.lock` changed, run `composer install` again (or re-upload `vendor/`)

---

## C. Optional: auto-deploy file

If Git Version Control offers **Manage → Deploy / .cpanel.yml**, you can add `.cpanel.yml` later.  
For most GoDaddy shared plans, **manual Pull** after each push is enough and safer.

---

## D. Local → GitHub → cPanel flow

```
PC (WAMP)  →  git push origin main  →  GitHub
                                      ↓
                         cPanel Git → Pull / Update
                                      ↓
                         Live: techwise-apps.com/sanghsampark
```

On your PC after changes:

```bash
git add -A
git commit -m "Describe the change"
git push origin main
```

Then on cPanel: **Pull**.

---

## Common problems

| Problem | Fix |
|---------|-----|
| Clone fails (folder not empty) | Clone to empty folder or move old files aside |
| Site white screen after pull | Missing `vendor/` — install/upload Composer packages |
| Mail / login broken | `.env` missing or wrong after clone — restore it |
| “Permission denied” writing emails | Fix `storage/deferred_emails` permissions |
| Private repo later | Use GitHub deploy key or HTTPS personal access token in cPanel |

---

## Security reminders

- Never commit `.env`  
- Never put DB / mail passwords in GitHub  
- Prefer **Pull** only from `TechwiseCode/sangh-sampark` `main`

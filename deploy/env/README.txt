SanghSampark — environment files
==================================

You need a .env file on EACH machine (local WAMP and live server).
They are NOT the same file — URLs and database differ.
Gmail SMTP can be the SAME on both (same sanghsamparkadmin@gmail.com + app password).

HOW TO USE
----------
1. Pick the template for your target:
   - .env.local.template   → your WAMP PC (localhost)
   - .env.server.template  → production server

2. Copy it to the project root as ".env" (rename, no .template):
   copy .env.server.template  C:\path\to\szvs-tenant\.env   (local test)
   OR upload to server: /path/to/sanghsampark/.env

3. Fill in the blanks:
   - SMTP_PASS = Gmail App Password (16 chars from Google Account)
   - DATABASE_* on server = your hosting MySQL details
   - VAPID_* = run on server: php bin/generate-vapid-keys.php

4. Never commit .env to git. Never share SMTP_PASS in chat.

5. Test mail on that machine:
   php scripts/test_mail.php your-email@gmail.com

LOCAL vs SERVER — what changes
------------------------------
| Setting        | Local (WAMP)              | Server (production)        |
|----------------|---------------------------|----------------------------|
| APP_ENV        | development               | production                 |
| APP_URL        | http://localhost/.../public | https://techwise-apps.com/sanghsampark/public |
| ASSET_URL      | http://localhost/szvs-tenant | https://techwise-apps.com/sanghsampark |
| DATABASE_*     | root / empty / szvs       | hosting DB user & password |
| SMTP_*         | same Gmail account        | same Gmail account         |

Gmail App Password
------------------
Google Account → Security → 2-Step Verification ON
→ App passwords → Mail → copy 16-char password into SMTP_PASS

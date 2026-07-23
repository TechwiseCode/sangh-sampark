REQUIRED for Chrome "Install app" dialog.

Upload this entire icons/ folder to the server:
  public/icons/icon-192.png   (192×192)
  public/icons/icon-512.png   (512×512)

Verify after upload:
  https://YOUR-DOMAIN/sanghsampark/public/icons/icon-192.png  → must NOT be 404
  https://YOUR-DOMAIN/sanghsampark/public/pwa/status          → icons_ok: true

Without these files Chrome will NEVER show the native install popup.

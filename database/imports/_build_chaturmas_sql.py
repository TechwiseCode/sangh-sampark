# -*- coding: utf-8 -*-
from pathlib import Path
import json

base = Path(__file__).resolve().parent
rows = json.loads((base / "chaturmas_abad_2026_extract.json").read_text(encoding="utf-8"))


def esc(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "''")


lines: list[str] = []
lines.append("-- Chaturmas Suchi Abad 2026 — org + current presence import")
lines.append("-- Source: ilovepdf_merged.pdf (24 entries)")
lines.append("-- Safe to re-run: skips existing org_code; closes open presence lists before inserting")
lines.append("SET NAMES utf8mb4;")
lines.append("START TRANSACTION;")
lines.append("")
lines.append("CREATE TABLE IF NOT EXISTS org_presence_lists (")
lines.append("  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,")
lines.append("  organization_id INT UNSIGNED NOT NULL,")
lines.append("  effective_from DATETIME NOT NULL,")
lines.append("  effective_until DATETIME NULL DEFAULT NULL,")
lines.append("  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,")
lines.append("  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,")
lines.append("  KEY idx_presence_org_current (organization_id, effective_until)")
lines.append(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;")
lines.append("")
lines.append("CREATE TABLE IF NOT EXISTS org_presence_members (")
lines.append("  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,")
lines.append("  presence_list_id INT UNSIGNED NOT NULL,")
lines.append("  display_name VARCHAR(191) NOT NULL,")
lines.append("  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,")
lines.append("  KEY idx_presence_members_list (presence_list_id, sort_order)")
lines.append(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;")
lines.append("")

for r in rows:
    code = r["org_code"]
    name = esc(r["name"])
    nick = esc(r.get("nickname") or "")
    addr = esc(r.get("address") or "")
    lines.append(f"-- Entry {r['entry_no']}: {r['name']}")
    lines.append("INSERT INTO organizations (name, nickname, address, org_code, created_by)")
    lines.append(f"SELECT '{name}', '{nick}', '{addr}', '{code}', NULL")
    lines.append("FROM DUAL")
    lines.append(f"WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = '{code}' LIMIT 1);")
    lines.append(f"SET @org_id := (SELECT id FROM organizations WHERE org_code = '{code}' LIMIT 1);")
    lines.append(
        "UPDATE org_presence_lists SET effective_until = NOW() "
        "WHERE organization_id = @org_id AND effective_until IS NULL;"
    )
    lines.append(
        "INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) "
        "VALUES (@org_id, NOW(), NULL);"
    )
    lines.append("SET @list_id := LAST_INSERT_ID();")
    for i, person in enumerate(r["people"]):
        lines.append(
            "INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) "
            f"VALUES (@list_id, '{esc(person)}', {i});"
        )
    lines.append("")

lines.append("COMMIT;")
lines.append("")
lines.append(f"-- Organizations codes CM01..CM{len(rows):02d}")
lines.append(f"-- Total people/presence rows: {sum(len(r['people']) for r in rows)}")

sql_path = base / "030_chaturmas_abad_2026_orgs_presence.sql"
sql_path.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(f"wrote {sql_path} ({sql_path.stat().st_size} bytes, {len(lines)} lines)")

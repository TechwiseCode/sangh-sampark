# -*- coding: utf-8 -*-
"""Build + import second Chaturmas batch (C25-C40)."""
from __future__ import annotations

import json
import subprocess
from pathlib import Path

base = Path(r"c:\wamp64\www\szvs-tenant\database\imports")
mysql = Path(r"C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql.exe")

rows = [
    {
        "entry_no": 34,
        "org_code": "C25",
        "name": "શ્રી શાહીબાગ સ્થાનકવાસી જૈન સંઘ",
        "nickname": "શાહીબાગ ઉપાશ્રય",
        "address": "એ ૧/૨, સુજાતા ફલેટ તેરાપંથ ભવન સામે, કેમ્પ રોડ, શાહીબાગ, અમદાવાદ - ૩૮૦૦૦૪",
        "people": [
            "મનોરમાજી મ.સ.",
            "નીરૂપમાજી મ.સ.",
            "કૃપાલીજી મ.સ.",
            "નીરાલીજી મ.સ.",
            "ભાવીજ્ઞાજી મ.સ.",
        ],
    },
    {
        "entry_no": 35,
        "org_code": "C26",
        "name": "શ્રી ગીરધરનગર શાહીબાગ સ્થા. જૈન સંઘ",
        "nickname": "ગીરધરનગર ઉપાશ્રય",
        "address": "૧૦, સુભાષનગર, બેન્ક ઓફ બરોડાની ગલીમાં, ગીરધરનગર, અમદાવાદ - ૪",
        "people": [
            "હર્ષિતાબાઈ મ.સ.",
            "રશ્મિતાબાઈ મ.સ.",
            "નિપુણાબાઈ મ.સ.",
            "કિરણબાઈ મ.સ.",
            "પ્રતિક્ષાબાઈ મ.સ.",
        ],
    },
    {
        "entry_no": 36,
        "org_code": "C27",
        "name": "શ્રી રાજસ્થાન સ્થાનકવાસી જૈન સંઘ (શાહીબાગ)",
        "nickname": "રાજસ્થાન ઉપાશ્રય શાહીબાગ",
        "address": "હથીભાઈ કી વાડી સામે, શાહીબાગ રોડ, અમદાવાદ - ૩૮૦૦૦૪",
        "people": [
            "સંયમ પ્રભાજી મ.સા.",
            "કિરણ પ્રભાજી મ.સા.",
            "શશિ પ્રભાજી મ.સા.",
        ],
    },
    {
        "entry_no": 37,
        "org_code": "C28",
        "name": "શ્રી સ્થાનકવાસી જૈન સંઘ - બાપુનગર",
        "nickname": "બાપુનગર ઉપાશ્રય",
        "address": "આદિનાથ ફ્લેટ, ઈન્દુલાલ યાજ્ઞિક હોલ પાછળ, વિમલનાથ જૈન દેરાસર રોડ, બાપુનગર, અંબર સિનેમા પાસે, અમદાવાદ - ૨૪",
        "people": [
            "કૌશલ્યાજી મ.સ.",
            "અર્પીતાજી મ.સ.",
            "નિધિબાઈ મ.સ.",
            "યશાજી મ.સ.",
            "ભવ્યાજી મ.સ.",
        ],
    },
    {
        "entry_no": 38,
        "org_code": "C29",
        "name": "શ્રી કૃષ્ણનગર સ્થાનકવાસી જૈન સંઘ",
        "nickname": "કૃષ્ણનગર ઉપાશ્રય",
        "address": "૯૨, ૯૪ જયાંનદ સોસાયટી, પ્રિયા સિનેમાં પાસે, ને.હા. ૮, કૃષ્ણનગર નરોડા રોડ, અમદાવાદ - ૩૮૨૩૪૬",
        "people": [
            "હર્ષાજી મ.સ.",
            "મીરાજી મ.સ.",
            "અલ્કાજી મ.સ.",
            "ખંતીજી મ.સ.",
            "રુચિજી મ.સ.",
        ],
    },
    {
        "entry_no": 39,
        "org_code": "C30",
        "name": "શ્રી દરિયાપુરી આઠ કોટી સ્થા. જૈન સંઘ (છીપાપોળ)",
        "nickname": "છીપાપોળ ઉપાશ્રય",
        "address": "સ્વામિનારાયણ મંદિર રોડ, છીપાપોળ, કાલુપુર, અમદાવાદ - ૩૮૦૦૦૧",
        "people": ["સુલોચનાબાઈ મ.સ.", "દક્ષાબાઈ મ.સ."],
    },
    {
        "entry_no": 40,
        "org_code": "C31",
        "name": "શ્રી શ્વેતામ્બર સ્થાનકવાસી જૈન સંઘ (સાબરમતી)",
        "nickname": "સાબરમતી / હીરાણીનગર",
        "address": "હીરાણીનગર, કોમ્યુનીટી હોલની સામે, પોસ્ટ ઓફિસની પાછળ, સાબરમતી, અમદાવાદ - ૩૮૦૦૦૫",
        "people": [
            "વંદિતાજી મ.સ.",
            "ચાંદનીજી મ.સ.",
            "રોશનીજી મ.સ.",
            "પ્રિયાંશીજી મ.સ.",
            "કાવ્યાંશીજી મ.સ.",
            "હિતાંશીજી મ.સ.",
        ],
    },
    {
        "entry_no": 25,
        "org_code": "C32",
        "name": "શ્રી વિજયનગર સ્થાનકવાસી જૈન મંડળ",
        "nickname": "વિજયનગર ઉપાશ્રય",
        "address": "૮૯/૫૨૯, વિજયનગર ફલેટ, નારણપુરા વિજયનગર, અમદાવાદ - ૧૩",
        "people": [
            "કરૂણાબાઈ મ.સ.",
            "રેખાબાઈ મ.સ.",
            "ધર્મિષ્ઠાબાઈ મ.સ.",
            "સિધ્ધિબાઈ મ.સ.",
            "પ્રતિતિબાઈ મ.સ.",
        ],
    },
    {
        "entry_no": 26,
        "org_code": "C33",
        "name": "અજરામર સ્થા. જૈન સંઘ અંકુર, અમદાવાદ",
        "nickname": "અંકુર અજરામર ઉપાશ્રય",
        "address": "૨, કામદુર્ગા સોસાયટી, વિ-૨, અંકુર ચાર રસ્તા પાસે, અંકુર, નારણપુરા, અમદાવાદ - ૧૩",
        "people": [
            "અમરલતાજી આર્યાજી",
            "ભક્તિકુમારી આર્યાજી",
            "પ્રતિક્ષાકુમારી આર્યાજી",
            "તિતીક્ષાકુમારી આર્યાજી",
            "ઉર્જાકુમારી આર્યાજી",
            "પ્રિયંવદાજી આર્યાજી",
        ],
    },
    {
        "entry_no": 27,
        "org_code": "C34",
        "name": "શ્રી નારણપુરા ઉપાશ્રય",
        "nickname": "નારણપુરા ઉપાશ્રય",
        "address": "૨૮/૨૯, સ્થા. જૈન સોસાયટી, રેલ્વે ક્રોસીંગ પાસે, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩",
        "people": [
            "મધુબાઈ મ.સ.",
            "રંજનબાઈ મ.સ.",
            "ભાવનાબાઈ મ.સ.",
            "હિતજ્ઞાબાઈ મ.સ.",
            "મોક્ષબાઈ મ.સ.",
            "મિતેષાબાઈ મ.સ.",
        ],
    },
    {
        "entry_no": 28,
        "org_code": "C35",
        "name": "શ્રી તારાબાઈ આર્યાજી સિદ્ધાંત ટ્રસ્ટ",
        "nickname": "તારાબાઈ આર્યાજી ટ્રસ્ટ",
        "address": "ઝવેરી પાર્ક, સ્થાનકવાસી જૈન ધર્મસ્થાનક પાસે, નારણપુરા રેલ્વે ક્રોસીંગ પાસે, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩",
        "people": [
            "હર્ષાબાઈ મ.સ.",
            "જયશ્રીબાઈ મ.સ.",
            "પ્રેરણાબાઈ મ.સ.",
            "નયનાબાઈ મ.સ.",
        ],
    },
    {
        "entry_no": 29,
        "org_code": "C36",
        "name": "શ્રી બોટાદ સંપ્રદાય સ્થા. જૈન સંઘ",
        "nickname": "બોટાદ સંપ્રદાય નારણપુરા",
        "address": "૧૬/ર દેવેન્દ્રપાર્ક સોસા., અમીકુંજ સોસા પાસે, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩",
        "people": [
            "મધુબાઈ મ.સ.",
            "હંસાબાઈ મ.સ.",
            "રેણુકાબાઈ મ.સ.",
            "દિપીકાબાઈ મ.સ.",
            "જિનાજ્ઞાબાઈ મ.સ.",
        ],
    },
    {
        "entry_no": 30,
        "org_code": "C37",
        "name": "શ્રી સ્થાનકવાસી જૈન સંઘ - નવા વાડજ",
        "nickname": "નવાવાડજ ઉપાશ્રય",
        "address": "ચંપકનગર ફ્લેટ પાસે, નવા વાડજ, સ્વસ્તિક સ્કૂલ સામે, અમદાવાદ - ૩૮૦૦૧૩",
        "people": [
            "ચારૂબાઈ મ.સ.",
            "અનિલાબાઈ મ.સ.",
            "ભાવનાબાઈ મ.સ.",
            "હેમાબાઈ મ.સ.",
            "પ્રિયંકરાબાઈ મ.સ.",
            "વીરહેમશ્રીજી મ.સ.",
        ],
    },
    {
        "entry_no": 31,
        "org_code": "C38",
        "name": "શ્રી દેવભૂમિ સ્થાનકવાસી જૈન સંઘ",
        "nickname": "દેવભૂમિ સંઘ",
        "address": "ડૉ. કસ્તુરચંદ બાલાભાઈ આરાધનાભવન, દેવભૂમિ એપાર્ટમેન્ટની બાજુમાં, સત્તાધાર ચાર રસ્તા પાસે, ઘાટલોડીયા, અમદાવાદ - ૬૧",
        "people": [
            "અર્પણબાઈ મ.સ.",
            "હંસાબાઈ મ.સ.",
            "કવિજ્ઞાબાઈ મ.સ.",
        ],
    },
    {
        "entry_no": 32,
        "org_code": "C39",
        "name": "શ્રી અજરામર સ્થાનકવાસી જૈન સંઘ - ચાણક્યપુરી",
        "nickname": "ચાણક્યપુરી અજરામર",
        "address": "ગ્રાઉન્ડ ફલોર, એલ.બી ટાવર, સેક્ટર નં.-૬, જે.જે. હોસ્પિટલ પાસે, ચાણક્યપુરી, અમદાવાદ - ૬૧",
        "people": ["તરુલતાજી મ.સ.", "ઉષાકુમારી મ.સ."],
    },
    {
        "entry_no": 33,
        "org_code": "C40",
        "name": "શ્રી ઘનશ્યામનગર સ્થાનકવાસી જૈન સંઘ",
        "nickname": "ઘનશ્યામનગર ઉપાશ્રય",
        "address": "વાય-૪, ઘનશ્યામનગર સોસાયટી, સુભાષબ્રિજના નાકે આર.ટી.ઓ. સામે, આશ્રમ રોડ, અમદાવાદ - ૩૮૦૦૨૭",
        "people": [],
    },
]


def esc(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "''")


(base / "chaturmas_abad_2026_batch2.json").write_text(
    json.dumps(rows, ensure_ascii=False, indent=2), encoding="utf-8"
)

lines: list[str] = [
    "-- Chaturmas batch 2 — org + presence (C25-C40)",
    "-- Source: ilovepdf_merged (1).pdf",
    "SET NAMES utf8mb4;",
    "START TRANSACTION;",
    "",
]

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
    if r["people"]:
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
lines.append(f"-- Orgs {len(rows)}, people {sum(len(r['people']) for r in rows)}")

sql_path = base / "031_chaturmas_abad_2026_batch2.sql"
sql_path.write_text("\n".join(lines) + "\n", encoding="utf-8")
print("wrote", sql_path)

cmd = [
    str(mysql),
    "-u",
    "root",
    "--default-character-set=utf8mb4",
    "szvs",
    "-e",
    f"source {sql_path.as_posix()}",
]
proc = subprocess.run(cmd, capture_output=True, text=True, encoding="utf-8", errors="replace")
print("import_exit", proc.returncode)
if proc.stderr:
    print(proc.stderr[:2000])

check = subprocess.run(
    [
        str(mysql),
        "-u",
        "root",
        "--default-character-set=utf8mb4",
        "szvs",
        "-e",
        "SELECT COUNT(*) AS orgs FROM organizations; "
        "SELECT COUNT(*) AS batch2 FROM organizations WHERE org_code REGEXP '^C(2[5-9]|3[0-9]|40)$'; "
        "SELECT COUNT(*) AS people FROM org_presence_members m "
        "INNER JOIN org_presence_lists l ON l.id=m.presence_list_id "
        "INNER JOIN organizations o ON o.id=l.organization_id "
        "WHERE o.org_code REGEXP '^C(2[5-9]|3[0-9]|40)$' AND l.effective_until IS NULL; "
        "SELECT org_code, LEFT(name,50) name FROM organizations WHERE org_code REGEXP '^C(2[5-9]|3[0-9]|40)$' ORDER BY org_code;",
    ],
    capture_output=True,
    text=True,
    encoding="utf-8",
    errors="replace",
)
print(check.stdout)

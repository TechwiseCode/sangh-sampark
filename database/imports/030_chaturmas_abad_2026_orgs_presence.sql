-- Chaturmas Suchi Abad 2026 — org + current presence import
-- Source: ilovepdf_merged.pdf (24 entries)
-- Safe to re-run: skips existing org_code; closes open presence lists before inserting
SET NAMES utf8mb4;
START TRANSACTION;

CREATE TABLE IF NOT EXISTS org_presence_lists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  effective_from DATETIME NOT NULL,
  effective_until DATETIME NULL DEFAULT NULL,
  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_presence_org_current (organization_id, effective_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS org_presence_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  presence_list_id INT UNSIGNED NOT NULL,
  display_name VARCHAR(191) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_presence_members_list (presence_list_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entry 1: શ્રી મણિનગર સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી મણિનગર સ્થાનકવાસી જૈન સંઘ', 'મણિનગર ઉપાશ્રય', 'રાવજીભાઈ ટાવર પાસે, કૃષ્ણબાગ, મણિનગર, અમદાવાદ - ૮', 'C01', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C01' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C01' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'આજ્ઞાકુમારીજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'યશસ્વિનીકુમારીજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સ્વીકૃતિકુમારીજી મ.સ.', 2);

-- Entry 2: શ્રી રાજસ્થાન સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી રાજસ્થાન સ્થાનકવાસી જૈન સંઘ', 'રાજસ્થાન એસ.એસ. જૈન સંઘ', 'મહાવીર ભવન, ૯-એ, ૧૦-બી શ્રી હરીનગર કો.ઓ. હા.સો., ઢોર બજાર કાંકરીયા, અમદાવાદ - ૨૨', 'C02', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C02' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C02' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મહાસાધ્વી ડૉ. સુપ્રભાજી મ.સા. સુધા', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી ડૉ. ઉદિતપ્રભાજી મ.સા. ઉષા', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી ડૉ. હેમપ્રભાજી મ.સા. હિમાંશુ', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી ડૉ. ઇમિતપ્રભાજી મ.સા.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી ઉન્નતિપ્રભાજી મ.સા.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી નીલેશ પ્રભાજી મ.સા.', 5);

-- Entry 3: શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ (વાસણા)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ (વાસણા)', 'વર્ધમાન સંઘ વાસણા', 'શ્રી જશવંતલાલ ગીરધરલાલ શાહ (ટુવાવાળા) શ્રી સંત સતીજી આવાસ, ૭, સુંદરવન સોસાયટી, બંસીધર સોસાયટી સામે, જવાહરનગર, વાસણા, અમદાવાદ - ૭', 'C03', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C03' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C03' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સંગીતાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાવનાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વિરતીબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રેક્ષાબાઈ મ.સ.', 3);

-- Entry 4: શ્રી એલિસબ્રીજ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી એલિસબ્રીજ સ્થાનકવાસી જૈન સંઘ', 'એલિસબ્રીજ ઉપાશ્રય', 'શેઠ શ્રી ચમનલાલ ઉમેદચંદ શાહ પાટડીવાળા જૈન ઉપાશ્રય, નૂતન નાગરીક બેન્કની સામે, નારાયણનગર રોડ, શાંતિવન, પાલડી, અમદાવાદ - ૩૮૦૦૦૭', 'C04', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C04' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C04' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રેમલભગવાન', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ચંદ્રેશાબાઇ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાવેશાબાઇ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ધૈર્યતાબાઇ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સૌમ્યતાબાઇ મ.સ.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સ્વાતિબાઇ મ.સ.', 5);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પવિત્રાબાઇ મ.સ.', 6);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પૂર્વાંશીબાઇ મ.સ.', 7);

-- Entry 5: પ. શ્રી નવલ-પ્રકાશ જૈન ઉપાશ્રય
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'પ. શ્રી નવલ-પ્રકાશ જૈન ઉપાશ્રય', 'નવલ-પ્રકાશ ઉપાશ્રય', 'ઋષભ ફ્લેટની બાજુમાં, એચ.કે. કોમ્પલેક્ષની ગલીમાં, વિ.આર. શાહ સ્કુલ પાસે, ભઠ્ઠા પાલડી, અમદાવાદ - ૩૮૦૦૦૭', 'C05', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C05' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C05' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ધર્મેચ્છાકુમારીજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ધન્યતાકુમારીજી મ.સ.', 1);

-- Entry 6: શ્રી ચંપકગુરુ સ્થા. જૈન આરાધના ભવન
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી ચંપકગુરુ સ્થા. જૈન આરાધના ભવન', 'ચંપકગુરુ આરાધના ભવન', 'ગ્રા. ફ્લોર, રાજ્યશ, સિલ્વર ફ્લોરા પાસે, ન્યુ વાસણા, અમદાવાદ - ૩૮૨૪૬૦', 'C06', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C06' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C06' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મૃદુતાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'દિવ્યતાજી મ.સ.', 1);

-- Entry 7: શ્રી લાવણ્ય ઉપાશ્રય
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી લાવણ્ય ઉપાશ્રય', 'લાવણ્ય ઉપાશ્રય', '૪૨, લાવણ્ય સોસાયટી, વાસણા, અમદાવાદ - ૭', 'C07', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C07' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C07' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નેહાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'શ્રેયાબાઈ મ.સ.', 1);

-- Entry 8: શ્રી સ્થાનકવાસી જૈન સંઘ (જીવરાજ પાર્ક)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ (જીવરાજ પાર્ક)', 'જીવરાજપાર્ક ઉપાશ્રય', 'આશાપુરી ફલેટ પાસે, જીવરાજ પાર્ક, અમદાવાદ - ૩૮૦૦૫૧', 'C08', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C08' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C08' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુજ્ઞાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હર્ષજ્ઞાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મિતજ્ઞાબાઈ મ.સ.', 2);

-- Entry 9: શ્રી સ્થાનકવાસી જૈન સંઘ, આંબાવાડી
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ, આંબાવાડી', 'આંબાવાડી ઉપાશ્રય', '૨, આમ્રકુંજ સોસાયટી, સ્નેહકુંજ બસ સ્ટેન્ડ પાસે, નહેરુનગર પાસે, આંબાવાડી, અમદાવાદ - ૧૫', 'C09', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C09' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C09' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હંસાબાઈ મહાસતીજી (મોટા)', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી રસીલાકુમારી આર્યાજી', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી અંજલીકુમારી આર્યાજી', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી નયનાકુમારી આર્યાજી', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધ્વી દર્શનાશ્રી આર્યાજી', 4);

-- Entry 10: શ્રી સ્થાનકવાસી જૈન સંઘ - સેટેલાઇટ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ - સેટેલાઇટ', 'સેટેલાઇટ ઉપાશ્રય', 'અશ્વમેધ, વિ-૪/બી, એક્ષ્ટેન્શન, સૃષ્ટિ બંગલા પાસે, સેટેલાઇટ, ૧૩૨ ફૂટ રીંગ રોડ, અમદાવાદ - ૩૮૦૦૧૫', 'C10', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C10' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C10' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રતિભાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ફાલ્ગુનીબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કૃપાલીબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નીપાબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ખુશાલીબાઈ મ.સ.', 4);

-- Entry 11: શ્રી સ્થાનકવાસી જૈન સંઘ જોધપુર અમદાવાદ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ જોધપુર અમદાવાદ', 'પ્રેરણા વિરાજ / જોધપુર', 'પ્રેરણા વિરાજ ટાવર, ૧ એ-વિંગ, ઔડા પમ્પીંગ સ્ટેશન પાસે, રોજવુડ એસ્ટેટ સામે, જોધપુર, સેટેલાઈટ, અમદાવાદ - ૧૫', 'C11', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C11' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C11' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાનુબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાવનાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મનીષાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રત્નેષાબાઈ મ.સ.', 3);

-- Entry 12: ધર્માલય સ્થાનકવાસી જૈન ઉપાશ્રય
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'ધર્માલય સ્થાનકવાસી જૈન ઉપાશ્રય', 'ધર્માલય ઉપાશ્રય', 'કિર્તીસાગર ફલેટની બાજુમાં, ઓમકારેશ્વર મહાદેવ પાસે, મહારાજ કોમ્પલેક્ષની સામે, બાલાજી ચાર રસ્તા, જોધપુર-સેટેલાઈટ, અમદાવાદ - ૩૮૦૦૧૫', 'C12', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C12' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C12' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુધાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જ્યોત્સનાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મીનાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જાગૃતિબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રભુતાજી મ.સ.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સૌમ્યતાજી મ.સ.', 5);

-- Entry 13: શ્રી આનંદનગર (સેટેલાઈટ) ઉપાશ્રય
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી આનંદનગર (સેટેલાઈટ) ઉપાશ્રય', 'આનંદનગર ઉપાશ્રય', 'શેલ પેટ્રોલ પંપની સામેની ગલીમાં, ઈશાન-૩ની બાજુમાં, યુરોકિડ સ્કૂલની સામે, આનંદ નગર સેટેલાઈટ, અમદાવાદ - ૩૮૦૦૧૫', 'C13', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C13' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C13' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઉષાજી મ.સા.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નમ્રતાજી મ.સા.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુજાતાજી મ.સા.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કૌમુદિનીજી મ.સા.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જ્યોતિજી મ.સા.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હિતસ્વિનીજી મ.સા.', 5);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રિદ્ધિજી મ.સા.', 6);

-- Entry 14: શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ ટ્રસ્ટ (આંબલી-બોપલ-ઘુમા)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ ટ્રસ્ટ (આંબલી-બોપલ-ઘુમા)', 'બોપલ વર્ધમાન સંઘ', 'પ્લોટ નં. ૭, રાજપથનગર, દીપકલા એન્ક્લેવ સામે, રવિ સ્કૂલ પાસે, એચપીસીએલ પેટ્રોલપંપની ગલીમાં, બોપલ, અમદાવાદ - ૫૮', 'C14', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C14' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C14' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સવિતાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઉર્મિલાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મૌલિબાઈ મ.સ.', 2);

-- Entry 15: શ્રી સાઉથ બોપલ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સાઉથ બોપલ સ્થાનકવાસી જૈન સંઘ', 'સાઉથ બોપલ સંઘ', 'એ-૪૦, સ્પ્રિંગ મિડોઝ બંગ્લોઝ, રામદેવ ઘંટીની પાસે, ગાલા જીમખાના રોડ, સી.બી. સેન્ટર ચાર રસ્તા, સાઉથ બોપલ, અમદાવાદ - ૩૮૦૦૫૮', 'C15', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C15' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C15' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અમિતાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વિભાબાઈ મ.સ.', 1);

-- Entry 16: શ્રી ધર્મનાથ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી ધર્મનાથ સ્થાનકવાસી જૈન સંઘ', 'કાઠિયાવાડ સ્થા. જૈન સમાજ', 'શિવાલિક સત્યમેવની પાછળ, વકીલ સાહેબ બ્રિજ પાસે, મેકડોનાલ્ડ્સની ગલીમાં, હરિઓમ બંગલોની બાજુમાં, આંબલી-બોપલ ક્રોસ રોડ, બોપલ, અમદાવાદ - ૩૮૦૦૫૮', 'C16', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C16' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C16' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વિનમ્ર મુનિ મ.સા.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પવિત્ર મુનિ મ.સા.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સોહમ મુનિ મ.સા.', 2);

-- Entry 17: શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ-વસ્ત્રાપુર
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ-વસ્ત્રાપુર', 'વસ્ત્રાપુર ઉપાશ્રય', '૨૦૩, નહેરુપાર્ક, સરદાર સેન્ટર પાછળ, હિલ્લોરા રેસીડેન્સીની બાજુમાં, વસ્ત્રાપુર, અમદાવાદ - ૩૮૦૦૧૫', 'C17', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C17' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C17' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રામધ્વનિ શ્રીજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રામનિધિ શ્રીજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રામનેમિ શ્રીજી મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રામસ્મૃતિ શ્રીજી મ.સ.', 3);

-- Entry 18: શ્રી થલતેજ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી થલતેજ સ્થાનકવાસી જૈન સંઘ', 'થલતેજ ઉપાશ્રય', '૩, સર્વેશ્વર ટાવર, લવકુશ ટાવરની બાજુમાં, જયઅંબેનગરની સામે, થલતેજ ટેકરા, અમદાવાદ - ૩૮૦૦૫૪', 'C18', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C18' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C18' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પુષ્પાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભારતીબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'તુભ્યવીબાઈ મ.સ.', 2);

-- Entry 19: શ્રી મેમનગર સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી મેમનગર સ્થાનકવાસી જૈન સંઘ', 'મેમનગર ઉપાશ્રય', 'જૈનાચાર્ય અજરામર માર્ગ સુભાષચોક, મેમનગર, અમદાવાદ - ૫૨', 'C19', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C19' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C19' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુશીલાબાઈ મ.સા.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જ્યોતિબાઈ મ.સા.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભારતીબાઈ મ.સા.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રિધ્ધિબાઈ મ.સા.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ધ્રુવીબાઈ મ.સા.', 4);

-- Entry 20: શ્રી સ્થાનકવાસી જૈન સંઘ, નવરંગપુરા
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ, નવરંગપુરા', 'નવરંગપુરા ઉપાશ્રય', 'સ્વપ્નીલ ૫ ની ગલીમાં મેટ્રો સ્ટેશન પાસે, કોમર્સ કોલેજ છ રસ્તા ડ્રાઈવઈન રોડ, નવરંગપુરા, અમદાવાદ', 'C20', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C20' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C20' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ગીતાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુદિશાજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઋજુતાજી મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઉન્નતિજી મ.સ.', 3);

-- Entry 21: શ્રી સોલારોડ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સોલારોડ સ્થાનકવાસી જૈન સંઘ', 'સોલા ઉપાશ્રય', 'સુંદરવન એપાર્ટમેન્ટ પાછળ, પેટ્રોલ પંપ સામેની ગલીમાં, આદિનાથ જૈન દેરાસર સામે, સોલારોડ, અમદાવાદ - ૬૩', 'C21', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C21' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C21' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઈલાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ડૉ. નીલાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રંજનબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સાધનાબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વીણાબાઈ મ.સ.', 4);

-- Entry 22: શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ (ઘાટલોડીયા)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ (ઘાટલોડીયા)', 'ઘાટલોડીયા ઉપાશ્રય', 'એ-૮૩, શ્રીકુંજ શોપીંગ સેન્ટર, સમર્પણ ટાવર સામે, પંચેશ્વર મંદિર, ઘાટલોડીયા, અમદાવાદ - ૬૧', 'C22', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C22' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C22' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભારતીજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જીજ્ઞાસાજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કલ્યાણીજી મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ધૈર્યતાજી મ.સ.', 3);

-- Entry 23: નિર્ણયનગર સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'નિર્ણયનગર સ્થાનકવાસી જૈન સંઘ', 'નિર્ણયનગર ઉપાશ્રય', 'બી-૧૬, કામધેનુ કોમ્પ્લેક્ષ, અર્જુન આશ્રમ રોડ, ગ્રીન સીટી સામે, નિર્ણયનગર રોડ, અમદાવાદ - ૩૮૨૪૮૧', 'C23', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C23' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C23' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અક્ષિતાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અંકિતાજી મ.સ.', 1);

-- Entry 24: શ્રી મીરામ્બીકારોડ સ્થા. જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી મીરામ્બીકારોડ સ્થા. જૈન સંઘ', 'કલ્પતરુ ઉપાશ્રય', '૩, કલ્પતરુ સોસાયટી, મીરામ્બીકા સ્કૂલ રોડ, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩', 'C24', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C24' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C24' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રેક્ષાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'દ્રષ્ટાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુહાનીબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વંદિનીબાઈ મ.સ.', 3);

COMMIT;

-- Organizations codes CM01..CM24
-- Total people/presence rows: 96

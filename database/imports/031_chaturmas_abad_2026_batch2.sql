-- Chaturmas batch 2 — org + presence (C25-C40)
-- Source: ilovepdf_merged (1).pdf
SET NAMES utf8mb4;
START TRANSACTION;

-- Entry 34: શ્રી શાહીબાગ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી શાહીબાગ સ્થાનકવાસી જૈન સંઘ', 'શાહીબાગ ઉપાશ્રય', 'એ ૧/૨, સુજાતા ફલેટ તેરાપંથ ભવન સામે, કેમ્પ રોડ, શાહીબાગ, અમદાવાદ - ૩૮૦૦૦૪', 'C25', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C25' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C25' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મનોરમાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નીરૂપમાજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કૃપાલીજી મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નીરાલીજી મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાવીજ્ઞાજી મ.સ.', 4);

-- Entry 35: શ્રી ગીરધરનગર શાહીબાગ સ્થા. જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી ગીરધરનગર શાહીબાગ સ્થા. જૈન સંઘ', 'ગીરધરનગર ઉપાશ્રય', '૧૦, સુભાષનગર, બેન્ક ઓફ બરોડાની ગલીમાં, ગીરધરનગર, અમદાવાદ - ૪', 'C26', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C26' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C26' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હર્ષિતાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રશ્મિતાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નિપુણાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કિરણબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રતિક્ષાબાઈ મ.સ.', 4);

-- Entry 36: શ્રી રાજસ્થાન સ્થાનકવાસી જૈન સંઘ (શાહીબાગ)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી રાજસ્થાન સ્થાનકવાસી જૈન સંઘ (શાહીબાગ)', 'રાજસ્થાન ઉપાશ્રય શાહીબાગ', 'હથીભાઈ કી વાડી સામે, શાહીબાગ રોડ, અમદાવાદ - ૩૮૦૦૦૪', 'C27', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C27' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C27' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સંયમ પ્રભાજી મ.સા.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કિરણ પ્રભાજી મ.સા.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'શશિ પ્રભાજી મ.સા.', 2);

-- Entry 37: શ્રી સ્થાનકવાસી જૈન સંઘ - બાપુનગર
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ - બાપુનગર', 'બાપુનગર ઉપાશ્રય', 'આદિનાથ ફ્લેટ, ઈન્દુલાલ યાજ્ઞિક હોલ પાછળ, વિમલનાથ જૈન દેરાસર રોડ, બાપુનગર, અંબર સિનેમા પાસે, અમદાવાદ - ૨૪', 'C28', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C28' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C28' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કૌશલ્યાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અર્પીતાજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નિધિબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'યશાજી મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભવ્યાજી મ.સ.', 4);

-- Entry 38: શ્રી કૃષ્ણનગર સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી કૃષ્ણનગર સ્થાનકવાસી જૈન સંઘ', 'કૃષ્ણનગર ઉપાશ્રય', '૯૨, ૯૪ જયાંનદ સોસાયટી, પ્રિયા સિનેમાં પાસે, ને.હા. ૮, કૃષ્ણનગર નરોડા રોડ, અમદાવાદ - ૩૮૨૩૪૬', 'C29', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C29' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C29' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હર્ષાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મીરાજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અલ્કાજી મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ખંતીજી મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રુચિજી મ.સ.', 4);

-- Entry 39: શ્રી દરિયાપુરી આઠ કોટી સ્થા. જૈન સંઘ (છીપાપોળ)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી દરિયાપુરી આઠ કોટી સ્થા. જૈન સંઘ (છીપાપોળ)', 'છીપાપોળ ઉપાશ્રય', 'સ્વામિનારાયણ મંદિર રોડ, છીપાપોળ, કાલુપુર, અમદાવાદ - ૩૮૦૦૦૧', 'C30', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C30' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C30' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સુલોચનાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'દક્ષાબાઈ મ.સ.', 1);

-- Entry 40: શ્રી શ્વેતામ્બર સ્થાનકવાસી જૈન સંઘ (સાબરમતી)
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી શ્વેતામ્બર સ્થાનકવાસી જૈન સંઘ (સાબરમતી)', 'સાબરમતી / હીરાણીનગર', 'હીરાણીનગર, કોમ્યુનીટી હોલની સામે, પોસ્ટ ઓફિસની પાછળ, સાબરમતી, અમદાવાદ - ૩૮૦૦૦૫', 'C31', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C31' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C31' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વંદિતાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ચાંદનીજી મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રોશનીજી મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રિયાંશીજી મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કાવ્યાંશીજી મ.સ.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હિતાંશીજી મ.સ.', 5);

-- Entry 25: શ્રી વિજયનગર સ્થાનકવાસી જૈન મંડળ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી વિજયનગર સ્થાનકવાસી જૈન મંડળ', 'વિજયનગર ઉપાશ્રય', '૮૯/૫૨૯, વિજયનગર ફલેટ, નારણપુરા વિજયનગર, અમદાવાદ - ૧૩', 'C32', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C32' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C32' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કરૂણાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રેખાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ધર્મિષ્ઠાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'સિધ્ધિબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રતિતિબાઈ મ.સ.', 4);

-- Entry 26: અજરામર સ્થા. જૈન સંઘ અંકુર, અમદાવાદ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'અજરામર સ્થા. જૈન સંઘ અંકુર, અમદાવાદ', 'અંકુર અજરામર ઉપાશ્રય', '૨, કામદુર્ગા સોસાયટી, વિ-૨, અંકુર ચાર રસ્તા પાસે, અંકુર, નારણપુરા, અમદાવાદ - ૧૩', 'C33', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C33' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C33' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અમરલતાજી આર્યાજી', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભક્તિકુમારી આર્યાજી', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રતિક્ષાકુમારી આર્યાજી', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'તિતીક્ષાકુમારી આર્યાજી', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઉર્જાકુમારી આર્યાજી', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રિયંવદાજી આર્યાજી', 5);

-- Entry 27: શ્રી નારણપુરા ઉપાશ્રય
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી નારણપુરા ઉપાશ્રય', 'નારણપુરા ઉપાશ્રય', '૨૮/૨૯, સ્થા. જૈન સોસાયટી, રેલ્વે ક્રોસીંગ પાસે, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩', 'C34', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C34' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C34' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મધુબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રંજનબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાવનાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હિતજ્ઞાબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મોક્ષબાઈ મ.સ.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મિતેષાબાઈ મ.સ.', 5);

-- Entry 28: શ્રી તારાબાઈ આર્યાજી સિદ્ધાંત ટ્રસ્ટ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી તારાબાઈ આર્યાજી સિદ્ધાંત ટ્રસ્ટ', 'તારાબાઈ આર્યાજી ટ્રસ્ટ', 'ઝવેરી પાર્ક, સ્થાનકવાસી જૈન ધર્મસ્થાનક પાસે, નારણપુરા રેલ્વે ક્રોસીંગ પાસે, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩', 'C35', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C35' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C35' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હર્ષાબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જયશ્રીબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રેરણાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'નયનાબાઈ મ.સ.', 3);

-- Entry 29: શ્રી બોટાદ સંપ્રદાય સ્થા. જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી બોટાદ સંપ્રદાય સ્થા. જૈન સંઘ', 'બોટાદ સંપ્રદાય નારણપુરા', '૧૬/ર દેવેન્દ્રપાર્ક સોસા., અમીકુંજ સોસા પાસે, નારણપુરા, અમદાવાદ - ૩૮૦૦૧૩', 'C36', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C36' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C36' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'મધુબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હંસાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'રેણુકાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'દિપીકાબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'જિનાજ્ઞાબાઈ મ.સ.', 4);

-- Entry 30: શ્રી સ્થાનકવાસી જૈન સંઘ - નવા વાડજ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી સ્થાનકવાસી જૈન સંઘ - નવા વાડજ', 'નવાવાડજ ઉપાશ્રય', 'ચંપકનગર ફ્લેટ પાસે, નવા વાડજ, સ્વસ્તિક સ્કૂલ સામે, અમદાવાદ - ૩૮૦૦૧૩', 'C37', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C37' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C37' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ચારૂબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અનિલાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ભાવનાબાઈ મ.સ.', 2);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હેમાબાઈ મ.સ.', 3);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'પ્રિયંકરાબાઈ મ.સ.', 4);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'વીરહેમશ્રીજી મ.સ.', 5);

-- Entry 31: શ્રી દેવભૂમિ સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી દેવભૂમિ સ્થાનકવાસી જૈન સંઘ', 'દેવભૂમિ સંઘ', 'ડૉ. કસ્તુરચંદ બાલાભાઈ આરાધનાભવન, દેવભૂમિ એપાર્ટમેન્ટની બાજુમાં, સત્તાધાર ચાર રસ્તા પાસે, ઘાટલોડીયા, અમદાવાદ - ૬૧', 'C38', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C38' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C38' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'અર્પણબાઈ મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'હંસાબાઈ મ.સ.', 1);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'કવિજ્ઞાબાઈ મ.સ.', 2);

-- Entry 32: શ્રી અજરામર સ્થાનકવાસી જૈન સંઘ - ચાણક્યપુરી
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી અજરામર સ્થાનકવાસી જૈન સંઘ - ચાણક્યપુરી', 'ચાણક્યપુરી અજરામર', 'ગ્રાઉન્ડ ફલોર, એલ.બી ટાવર, સેક્ટર નં.-૬, જે.જે. હોસ્પિટલ પાસે, ચાણક્યપુરી, અમદાવાદ - ૬૧', 'C39', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C39' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C39' LIMIT 1);
UPDATE org_presence_lists SET effective_until = NOW() WHERE organization_id = @org_id AND effective_until IS NULL;
INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id) VALUES (@org_id, NOW(), NULL);
SET @list_id := LAST_INSERT_ID();
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'તરુલતાજી મ.સ.', 0);
INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (@list_id, 'ઉષાકુમારી મ.સ.', 1);

-- Entry 33: શ્રી ઘનશ્યામનગર સ્થાનકવાસી જૈન સંઘ
INSERT INTO organizations (name, nickname, address, org_code, created_by)
SELECT 'શ્રી ઘનશ્યામનગર સ્થાનકવાસી જૈન સંઘ', 'ઘનશ્યામનગર ઉપાશ્રય', 'વાય-૪, ઘનશ્યામનગર સોસાયટી, સુભાષબ્રિજના નાકે આર.ટી.ઓ. સામે, આશ્રમ રોડ, અમદાવાદ - ૩૮૦૦૨૭', 'C40', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE org_code = 'C40' LIMIT 1);
SET @org_id := (SELECT id FROM organizations WHERE org_code = 'C40' LIMIT 1);

COMMIT;
-- Orgs 16, people 68

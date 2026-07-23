-- Mark platform default donation categories vs organization-specific custom ones.

ALTER TABLE donation_categories
  ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order;

UPDATE donation_categories
SET is_default = 1
WHERE name_gu IN (
  'શ્રી આયંબીલ કાયમી તિથી ફંડ',
  'શ્રી જૈન-શાળા જમણ કાયમી તિથી ફંડ',
  'શ્રી જૈન-શાળા પ્રભાવના કાયમી તિથી ફંડ',
  'શ્રી સાધુ-સાધ્વીજી વૈયા-વચ્ચ ફંડ',
  '(કૉપર્સ - ઉપજ ફંડ)',
  'શ્રી ધર્મકરણી ખાતે',
  'શ્રી સાધારણ / શુભ ખાતે',
  'શ્રી સ્વામી વાતસલ્ય ખાતે',
  'શ્રી સાધર્મિક ભક્તિ (મહેમાન રસોડા) ખાતે',
  'શ્રી જીવદયા ખાતે',
  'શ્રી માનવ રાહત ખાતે',
  'શ્રી શિબિર આયોજન ખાતે',
  'શ્રી શિક્ષણ / મેડીકલ સહાય ફંડ ખાતે',
  'શ્રી પ્રભાવના ખાતે',
  'શ્રી નિભાવ ફંડ ખાતે',
  'શ્રી અન્ય'
);

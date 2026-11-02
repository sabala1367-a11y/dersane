# Student Portal + Admin Dashboard (MySQL: `db-student_portal`)

## Jinsi vinavyounganishwa
- `login_student-portal.php` ni ya **WANAFUNZI TU**. Inatuma kwa `auth_student-portal.php`, ambayo inaangalia jedwali `users` (role = student) kisha inapeleka `Student_Portal_student-portal.php` (anaona taarifa zake tu, kulingana na `reg_no`).
- **Admin HANA login.** `admin_dashboard_student-portal.php` na `api_student-portal.php` zinafunguka tu kutoka IP zilizoruhusiwa (`SP_ADMIN_IPS` kwenye `config_student-portal.php`; chaguo-msingi: kompyuta ya seva yenyewe, localhost). Ongeza IP nyingine kwenye `config_student-portal.local.php`:
  `define('SP_ADMIN_IPS', ['127.0.0.1', '::1', '203.0.113.10']);`
- Dashboard na portal zinasoma/zinaandika **database moja** (`student_store_student-portal.php` + `api_student-portal.php`), kwa hiyo ukihifadhi kwenye dashboard mwanafunzi anaona mara moja.
- Logout: `logout_student-portal.php` (kitufe kipo kwenye upau wa juu na kwenye menyu ya `Student_Portal_student-portal.php`).
- **Nenosiri la mwanafunzi = namba yake ya usajili** (inayotengenezwa na server kwenye dashboard, mf. `DSN/2026/0001`). Akaunti hutengenezwa otomatiki mwanafunzi anapohifadhiwa. Jina la kuingia ni barua pepe yake (au namba ya usajili). Mwanafunzi anaweza kulibadilisha kwenye portal (Change Password).
- Kwa wanafunzi waliopo tayari: `php reset_passwords_student-portal.php` inaweka nenosiri la kila mmoja kuwa namba yake ya usajili.

## Kuweka (hatua 4)
1. **Database**: ingiza `database/schema_student-portal.sql` (phpMyAdmin → Import, au `mysql -u root -p < database/schema_student-portal.sql`). Inatengeneza `db-student_portal` na meza `students, users, payments, quizzes, settings`.
2. **Muunganisho**: hariri `DB_USER` / `DB_PASS` kwenye `config_student-portal.php` (au nakili `config_student-portal.local.php.example` → `config_student-portal.local.php`).
3. **Hamisha wanafunzi waliopo** (mara moja): `php import_json_student-portal.php` (nenosiri la kila mmoja = namba yake ya usajili).
4. Wanafunzi wanaingia kupitia `login_student-portal.php`. Admin anafungua `admin_dashboard_student-portal.php` moja kwa moja (kutoka IP iliyoruhusiwa). Kama ulikuwa na admin wa zamani kwenye database: `DELETE FROM users WHERE role = 'admin';`

## Maelezo
- Kuingia kunakubali barua pepe au namba ya usajili (wanafunzi tu). Kama barua pepe moja ipo kwa wanafunzi wawili, wa pili huingia kwa namba ya usajili.
- Majaribio 5 mabaya ya nenosiri → akaunti inafungwa dakika 10. Session ya mwanafunzi inaisha baada ya dakika 15 bila shughuli .
- `payment_config_student-portal.php`: badilisha `secret` kabla ya kutumia webhook. Folda `data/` si lazima tena isipokuwa `students_student-portal.json` kwa uhamishaji (unaweza kuifuta baada ya kuhamisha).
- `.htaccess.example` = jina jipya la faili `htaccess` ya zamani (ibadilishe kuwa `.htaccess` kama unatumia Apache).

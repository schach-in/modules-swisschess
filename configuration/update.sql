/**
 * swisschess module
 * SQL updates
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */

/* 2024-05-24-1 */	UPDATE _settings SET setting_key = 'swisschess_upload_swt' WHERE setting_key = 'tournaments_upload_swt';
/* 2024-05-24-2 */	UPDATE _settings SET setting_key = 'swisschess_job_swt_path' WHERE setting_key = 'tournaments_job_swt_path';
/* 2024-05-24-3 */	UPDATE access SET access_key  = 'swisschess_upload' WHERE access_key = 'tournaments_swisschess';
/* 2024-05-24-4 */	UPDATE access SET access_key  = 'swisschess_debug' WHERE access_key = 'tournaments_swisschess_debug';
/* 2024-05-24-5 */	UPDATE access SET access_key  = 'swisschess_lst' WHERE access_key = 'tournaments_swisschess_lst';
/* 2024-05-24-6 */	UPDATE webpages SET parameters = REPLACE(parameters, '&access=tournaments_swisschess_debug', '&access=swisschess_debug') WHERE parameters LIKE "%&access=tournaments_swisschess_debug%";
/* 2024-05-24-7 */	UPDATE webpages SET parameters = REPLACE(parameters, '&access=tournaments_swisschess_lst', '&access=swisschess_lst') WHERE parameters LIKE "%&access=tournaments_swisschess_lst%";
/* 2024-05-24-8 */	UPDATE webpages SET parameters = REPLACE(parameters, '&access=tournaments_swisschess', '&access=swisschess_upload') WHERE parameters LIKE "%&access=tournaments_swisschess%";
/* 2024-05-24-9 */	UPDATE tournaments SET urkunde_parameter = REPLACE(urkunde_parameter, '&swisschess[ignore_ids]=1', '&swisschess_ignore_ids=1') WHERE urkunde_parameter LIKE '%&swisschess[ignore_ids]=1%';
/* 2024-05-24-10 */	UPDATE tournaments SET urkunde_parameter = REPLACE(urkunde_parameter, 'swisschess[ignore_ids]=1', '&swisschess_ignore_ids=1') WHERE urkunde_parameter LIKE '%swisschess[ignore_ids]=1%';
/* 2024-05-26-1 */	INSERT INTO categories (`category`, `description`, `main_category_id`, `path`, `parameters`, `sequence`, `last_update`) VALUES ('Swiss-Chess', 'Dateiname für Swiss-Chess-Export', /*_ID categories identifiers _*/, 'identifiers/swiss-chess', "&alias=identifiers/swiss-chess&ournaments_identifier=1", NULL, NOW());
/* 2024-05-26-2 */	INSERT INTO tournaments_identifiers (tournament_id, identifier, identifier_category_id) SELECT tournament_id, turnierkennung, /*_ID categories identifiers/swiss-chess _*/ FROM tournaments WHERE NOT ISNULL(turnierkennung);

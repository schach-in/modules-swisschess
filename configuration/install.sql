/**
 * swisschess module
 * SQL for installation
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


INSERT INTO categories (`category`, `description`, `main_category_id`, `path`, `parameters`, `sequence`, `last_update`) VALUES ('Swiss-Chess', 'Dateiname für Swiss-Chess-Export', /*_ID categories identifiers _*/, 'identifiers/swiss-chess', "&alias=identifiers/swiss-chess&ournaments_identifier=1", NULL, NOW());

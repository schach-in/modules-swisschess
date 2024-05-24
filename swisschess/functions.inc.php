<?php 

/**
 * swisschess module
 * common functions for tournaments (not always included)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get tournament type
 *
 * @param array $data parsed file
 * @return string
 */
function mf_swisschess_tournament_type($data) {
	$field_names = swtparser_get_field_names('de');
	$key = array_search('Mannschaftsturnier', $field_names);
	if ($data[$key] === 1) return 'team';
	return 'single';
}

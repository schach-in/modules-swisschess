<?php 

/**
 * swisschess module
 * file functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * send an SWT file
 *
 * @param array $params
 * @return array
 */
function mf_swisschess_sendfile_swt($params) {
	$filename = implode('/', $params);
	array_shift($params); // folder
	if (count($params) !== 2) wrap_quit(404);
	$params[1] = substr($params[1], 0, strrpos($params[1], '.')); // remove extension

	$rights = vsprintf('event:%d/%s', $params);
	if (!wrap_access('swisschess_download', $rights)) wrap_quit(403);

	$file['send_as'] = mf_swisschess_file_send_as($params);
	$file['caching'] = false; // never cache swiss chess files

	$file['name'] = wrap_setting('media_folder').'/'.$filename;
	return wrap_file_send($file);
}

/**
 * get filename for download
 *
 * @param array $params
 * @return string
 */
function mf_swisschess_file_send_as($params) {
	$sql = 'SELECT tournaments_identifiers.identifier
			, events.event, IFNULL(events.event_year, YEAR(events.date_begin)) AS year
		FROM tournaments
		JOIN events USING (event_id)
		JOIN tournaments_identifiers USING (tournament_id)
		WHERE events.identifier = "%d/%s"
		AND tournaments_identifiers.identifier_category_id = /*_ID categories identifiers/swiss-chess _*/';
	$sql = sprintf($sql, $params[0], wrap_db_escape($params[1]));
	$event = wrap_db_fetch($sql);
	if ($event['identifier']) return $event['identifier'];
	return sprintf('%s %d', $event['event'], $event['year']);
}

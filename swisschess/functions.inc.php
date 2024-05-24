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
 * parse a swisschess file
 *
 * @param array $filename
 * @return array
 */
function mf_swisschess_parse($filename) {
	wrap_lib('swtparser');
	// @todo unterstütze Parameter für UTF-8-Codierung
	$tournament = swtparser($filename, wrap_setting('character_set'));
	return $tournament['out'];
}

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

/**
 * get year of tournament begin
 *
 * @param array $data parsed file
 * @return string
 */
function mf_swisschess_tournament_year($data) {
	wrap_include_files('validate', 'zzform');
	$field_names = swtparser_get_field_names('de');
	$key = array_search('Datum Start', $field_names);
	$key = zz_check_date($data[$key]);
	if (!$key) return '';
	return substr($key, 0, strpos($key, '-'));

}

/**
 * check, whether SWT file matches tournament
 *
 * @param array $event
 * @param string $event
 * @param array $data
 * return string error message
 */ 
function mf_swisschess_filematch($event, $data) {
	if (mf_swisschess_tournament_type($data) === 'single' AND !wrap_setting('tournaments_type_single'))
		return 'Turnier wurde als Mannschaftsturnier angelegt, die SWT-Datei ist aber für ein Einzelturnier!';
	if (mf_swisschess_tournament_type($data) === 'team' AND !wrap_setting('tournaments_type_team'))
		return 'Turnier wurde als Einzelturnier angelegt, die SWT-Datei ist aber für ein Mannschaftsturnier!';

	$event_year = substr($event['date_begin'], 0, 4);
	$file_year = mf_swisschess_tournament_year($data);
	if ($file_year !== $event_year)
		return wrap_text(
			'This tournament file belongs to a tournament from %d, but the tournament will take place in %d.',
			['values' => [$file_year, $event_year]]);

	// no further check possible if IDs in Swiss Chess must not be used
	if (wrap_setting('swisschess_ignore_ids')) return '';
	
	if (mf_swisschess_tournament_type($data) === 'single') {
		$sql = 'SELECT person_id FROM participations
			LEFT JOIN persons USING (contact_id)
			WHERE usergroup_id = %d AND event_id = %d';
		$sql = sprintf($sql,
			wrap_id('usergroups', 'spieler'), $event['event_id']
		);
		$db_ids = wrap_db_fetch($sql, '_dummy_', 'single value');
		$check_data = 'Spieler';
		$id_field_name = 'person_id';
		$field_key = 2038;
	} else {
		$sql = 'SELECT team_id
			FROM teams
			WHERE event_id = %d
			AND team_status = "Teilnehmer"
			ORDER BY fremdschluessel';
		$sql = sprintf($sql, $event['event_id']);
		$db_ids = wrap_db_fetch($sql, '_dummy_', 'single value');
		$check_data = 'Teams';
		$id_field_name = 'team_id';
		$field_key = 1046;
	}
	$swt_ids = [];
	foreach ($data[$check_data] as $line) {
		// geht nur, wenn team_id oder person_id gesetzt ist
		if (empty($line[$field_key])) continue;
		parse_str($line[$field_key], $fields);
		if (empty($fields[$id_field_name])) continue;
		$swt_ids[] = $fields[$id_field_name];
	}
	$diff = array_diff($db_ids, $swt_ids);
	if ($db_ids AND $swt_ids AND $diff === $db_ids)
		// Kein Team in Datenbank passt zu Teams aus SWT
		return sprintf('SWT-Import: Turnierdatei passt nicht zum Turnier. Bitte lade die richtige Datei hoch! (%s)', $event['identifier']);
	return '';
}

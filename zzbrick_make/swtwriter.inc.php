<?php 

/**
 * swisschess module
 * write data chunks to SWT files
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2013-2016, 2019-2024, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Schreiben von einzelnen Daten in eine SWT-Datei
 * insbesondere der person_id und team_id in das Feld 'Info4'
 *
 * @param array $vars
 *		int [0]: year
 *		int [1]: event identifier
 * @return array $page
 * @todo gemeinsame Elemente mit SWT-Import abstrahieren
 * @todo Einzel- oder Mannschaftsturnier aus Termine auslesen
 * @todo Datenherkunft aus Turniere
 */
function mod_swisschess_make_swtwriter($vars, $settings, $event) {
	ignore_user_abort(1);
	ini_set('max_execution_time', 120);

	$writer = [];
	$writer['identifier'] = implode('/', $vars);
	if (count($vars) !== 2) {
		wrap_error(sprintf('SWT-Import: Falsche Zahl von Parametern: %s', $writer['identifier']));
		return false;
	}
	wrap_setting('log_filename', $writer['identifier']);
	
	// Variante 1: Direkt SWT-Datei auslesen
	$swt = $event['identifier'].'.swt';
	$filename = wrap_setting('media_folder').'/swt/'.$swt;
	if (!file_exists($filename))
		wrap_quit(404, wrap_text('SWT file `%s` does not exist. Please upload a file first.', ['values' => [$filename]]));
	
	// Ein paar Sekunden warten, bevor nach Upload die Datei geschrieben
	// werden kann (kann sonst zu schnell sein, neue IDs noch nicht in DB)
	$last_changed = filemtime($filename);
	$time_diff = time() - $last_changed;
	$seconds_to_wait = 45;
	$time_diff -= $seconds_to_wait;
	if ($time_diff < 0) {
		sleep(-$time_diff);
	}

	if (!is_writable($filename)) {
		wrap_log(sprintf('Datei swt/%s ist nicht schreibbar', $swt));
		wrap_setting('error_prefix', '');
		return false;
	}

    if (!$handle = fopen($filename, "r+b")) {
		wrap_log(sprintf('Datei swt/%s ist nicht öffenbar', $swt));
		wrap_setting('error_prefix', '');
		return false;
    }

	// SWT-Parser einbinden
	wrap_lib('swtparser');
	// @todo unterstütze Parameter für UTF-8-Codierung
	$tournament = swtparser($filename, wrap_setting('character_set'));
	$field_names = swtparser_get_field_names('de');

	if (isset($_GET['delete'])) {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$writer['request_delete'] = true;
			$page['query_strings'][] = 'delete';
			$page['text'] = wrap_template('swtwriter', $writer);
			return $page;
		}
		switch ($_GET['delete']) {
			// Spieler und Team Info4
			case 'teams': $to_delete = [1046]; break;
			case 'spieler': $to_delete = [2038]; break;
			default: $to_delete = [1046, 2038]; break;
		}
		$writer['deletions'] = 0;
		foreach ($tournament['bin'] as $index => $token) {
			if (in_array($token['content'], $to_delete)) {
				$result = mod_swisschess_make_swtwriter_delete($handle, $token);
				if ($result) $writer['deletions']++;
			}
		}
	} else {
		$writer['changes_team_id'] = 0;
		$writer['changes_person_id'] = 0;
		foreach ($tournament['bin'] as $index => $token) {
			// 1046, 2038
			if ($token['content'] == 1012) {
				// 1012 MNr. Rangliste
				$team_id = mod_swisschess_make_swtwriter_read_id($handle, $token, 'team_id', $event['event_id']);
			} elseif ($token['content'] == 1046) {
				// 1046 Team Info4
				$result = mod_swisschess_make_swtwriter_write($handle, $token, 'team_id', $team_id);
				if ($result) $writer['changes_team_id']++;
			} elseif ($token['content'] == 2020) {
				// 2020 Spieler TNr.-ID hex
				$person_id = mod_swisschess_make_swtwriter_read_id($handle, $token, 'person_id', $event['event_id']);
			} elseif ($token['content'] == 2038) {
				// 2038 Spieler Info4
				$result = mod_swisschess_make_swtwriter_write($handle, $token, 'person_id', $person_id);
				if ($result) $writer['changes_person_id']++;
			}
		}
		if ($writer['changes_team_id'] OR $writer['changes_person_id']) {
			$writer['changes'] = true;
		}
	}
    fclose($handle);
	
	wrap_setting('error_prefix', '');
	if (!empty($writer['changes'])) {
		wrap_log(sprintf('SWT-Writer für %s: %d Personen, %d Teams geschrieben.',
			$swt, $writer['changes_person_id'], $writer['changes_team_id']
		));
	}

	$page['query_strings'] = ['delete'];
	$page['breadcrumbs'][]['title'] = 'SWT-Writer';
	$page['text'] = wrap_template('swtwriter', $writer);
	return $page;
}

/**
 * Liest ID aus der SWT-Datei
 *
 * @param resource $handle
 * @param array $token
 * @param string $field
 * @param int $event_id
 * @return int $id
 */
function mod_swisschess_make_swtwriter_read_id($handle, $token, $field, $event_id) {
	fseek($handle, $token['begin']);
	$string = fread($handle, $token['end'] - $token['begin'] + 1);
	switch ($field) {
	case 'team_id':
		$sql = 'SELECT team_id
			FROM teams
			WHERE event_id = %d AND fremdschluessel = %d';
		break;
	case 'person_id':
		$sql = 'SELECT person_id
			FROM participations
			LEFT JOIN persons USING (contact_id)
			WHERE event_id = %d AND fremdschluessel = %d';
		break;
	}
	$sql = sprintf($sql, $event_id, hexdec(bin2hex(strrev($string))));
	$id = wrap_db_fetch($sql, '', 'single value');
	return $id;
}

/**
 * Schreibe Daten in die SWT-Datei
 *
 * @param resource $handle
 * @param array $token
 * @param string $field
 * @param int $id
 * @return bool true: something was written, false: nothing was written
 */
function mod_swisschess_make_swtwriter_write($handle, $token, $field, $id) {
	fseek($handle, $token['begin']);
	$string = fread($handle, $token['end'] - $token['begin'] + 1);
	parse_str($string, $info4);
	if ($id) {
		// nur, wenn wir schon eine ID haben
		$code = sprintf('%s=%d', $field, $id);
	} else {
		$code = '';
	}
	if (isset($info4[$field]) AND trim($info4[$field]) == $id) {
		return false;
	} elseif (!$id AND empty($info4[$field])) {
		return false;
	}
	// schreibe Wert + 00 für alle weiteren Felder
	fseek($handle, $token['begin']);
	fwrite($handle, $code);
	$repeat = $token['end'] - $token['begin'] + 1 - strlen($code);
	fwrite($handle, str_repeat(chr(0), $repeat));

	$string = fread($handle, $token['end'] - $token['begin'] + 1);

	return true;
}

function mod_swisschess_make_swtwriter_delete($handle, $token) {
	fseek($handle, $token['begin']);
	$repeat = $token['end'] - $token['begin'] + 1;
	fwrite($handle, str_repeat(chr(0), $repeat));
	return true;
}

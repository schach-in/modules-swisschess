<?php 

/**
 * swisschess module
 * export of players to Swiss-Chess
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2012-2014, 2016-2017, 2019-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_swisschess_get_swisschess($vars) {
	// Land wurde zugunsten der Gastspielergenehmigung fallen gelassen
	//		, SUBSTRING(landesverbaende.contact_abbr, 1, 3) AS land

	$where = 'spielberechtigt != "nein"';
	if (array_key_exists('alle', $_GET)) $where = '(ISNULL(spielberechtigt) OR spielberechtigt != "nein")';

	wrap_db_charset('latin1');
	// Abfrage Spalte 2, 3: erste Zeile für MM, zweite für EM
	$sql = 'SELECT
			SUBSTRING(CONCAT(IFNULL(CONCAT(t_namenszusatz, " "), ""), t_nachname, ",", t_vorname), 1, 32) AS name
			, IFNULL(
				CONCAT(SUBSTRING(teams.team, 1, 29), IFNULL(CONCAT(" ", teams.team_no), SUBSTRING(teams.team, 30, 3))),
				t_verein				
			) AS verein
			, IF(NOT ISNULL(team_id),
				IF(gastspieler = "ja", "G", ""),
				landesverbaende.contact_abbr
			) AS land
			, t_elo AS elo
			, t_dwz AS dwz
			, t_fidetitel AS fidetitel
			, DATE_FORMAT(persons.date_of_birth, "%%d.%%m.%%Y") AS geburtsdatum
			, pkz.identifier AS pkz
			, fide.identifier AS fide_kennzahl
			, SUBSTRING_INDEX(pk.identifier, "-", -1) AS teilnehmerkennung
			, IF(persons.sex = "female", "w", "m") AS teilnehmerattribut
			, IF(spielberechtigt = _utf8mb4"vorläufig nein", "N", NULL) AS selektionszeichen
			, SUBSTRING(pk.identifier, 1, 3) AS verband
			, SUBSTRING_INDEX(pk.identifier, "-", 1) AS zps_verein
			, SUBSTRING_INDEX(pk.identifier, "-", -1) AS zps_spieler
			, IF(spielberechtigt = _utf8mb4"vorläufig nein", SUBSTRING(REPLACE(participations.remarks, "\n", "/"), 1, 40), NULL) AS teilnehmer_info_1
			, NULL AS teilnehmer_info_2
			, NULL AS teilnehmer_info_3
			, CONCAT("person_id=", persons.person_id, IFNULL(CONCAT("&team_id=", team_id), "")) AS teilnehmer_info_4
		FROM participations
		LEFT JOIN persons USING (contact_id)
		LEFT JOIN teams USING (team_id)
		LEFT JOIN contacts organisationen
			ON teams.club_contact_id = organisationen.contact_id
		LEFT JOIN events
			ON participations.event_id = events.event_id
		LEFT JOIN contacts_identifiers pk
			ON persons.contact_id = pk.contact_id
			AND pk.current = "yes"
			AND pk.identifier_category_id = /*_ID categories identifiers/pass_dsb _*/
		LEFT JOIN contacts_identifiers fide
			ON persons.contact_id = fide.contact_id
			AND fide.current = "yes"
			AND fide.identifier_category_id = /*_ID categories identifiers/id_fide _*/
		LEFT JOIN contacts_identifiers pkz
			ON persons.contact_id = pkz.contact_id
			AND pkz.current = "yes"
			AND pkz.identifier_category_id = /*_ID categories identifiers/pass_dsb _*/
		LEFT JOIN contacts_identifiers v_ok
			ON IFNULL(organisationen.contact_id, participations.club_contact_id) = v_ok.contact_id
			AND v_ok.current = "yes"
		LEFT JOIN contacts_identifiers lv_ok
			ON CONCAT(SUBSTRING(v_ok.identifier, 1, 1), "00") = lv_ok.identifier
			AND lv_ok.current = "yes"
		LEFT JOIN contacts landesverbaende
			ON lv_ok.contact_id = landesverbaende.contact_id
		WHERE events.identifier = "%d/%s"
		AND usergroup_id = /*_ID usergroups spieler _*/
		AND %s
		AND (ISNULL(teams.team_id) OR teams.team_status = "Teilnehmer")
		AND participations.status_category_id IN (
			/*_ID categories participation-status/subscribed _*/,
			/*_ID categories participation-status/verified _*/,
			/*_ID categories participation-status/participant _*/
		)
		ORDER BY team, team_no, rang_no, t_nachname, t_vorname';
	$sql = sprintf($sql
		, $vars[0], wrap_db_escape($vars[1])
		, $where
	);
	$data = wrap_db_fetch($sql, 'teilnehmer_info_4');
	if (!$data) {
		wrap_db_charset('utf8mb4');
		return false;
	}
	wrap_setting('character_set', 'windows-1252');

	$data['_filename'] = mf_swisschess_file_send_as($vars);
	$data['_extension'] = 'lst';
	$data['_query_strings'] = ['alle'];
	
	wrap_setting('export_csv_show_empty_cells', true);
	wrap_setting('export_csv_heading', false);
	return $data;
}

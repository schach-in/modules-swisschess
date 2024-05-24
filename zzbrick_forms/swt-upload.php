<?php 

/**
 * swisschess module
 * form script: upload an SWT file
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2017, 2019-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!wrap_setting('swisschess_upload_swt'))
	wrap_quit(403, 'SWT-Upload ist auf dieser Plattform nicht erlaubt.');

$zz = zzform_include('tournaments');

$zz['title'] = 'SWT-Upload';
$zz['where']['event_id'] = $brick['data']['event_id'];
$zz['access'] = 'add_then_edit';

$zz['page']['referer'] = '../';

unset($zz['fields'][25]);
unset($zz['fields'][26]);

$zz['fields'][22]['title'] = 'SWT-Datei';
$zz['fields'][22]['field_name'] = 'swt';
$zz['fields'][22]['dont_show_missing'] = true;
$zz['fields'][22]['type'] = 'upload_image';
$zz['fields'][22]['path'] = [
	'root' => wrap_setting('media_folder').'/swt/',
	'webroot' => wrap_setting('media_internal_path').'/swt/',
	'field1' => 'event_identifier', 
	'string2' => '.swt'
];
$zz['fields'][22]['input_filetypes'] = ['swt'];
$zz['fields'][22]['link'] = [
	'string1' => wrap_setting('media_internal_path').'/swt/',
	'field1' => 'event_identifier',
	'string2' => '.swt'
];
$zz['fields'][22]['optional_image'] = true;
$zz['fields'][22]['image'][0]['title'] = 'gro&szlig;';
$zz['fields'][22]['image'][0]['field_name'] = 'gross';
$zz['fields'][22]['image'][0]['path'] = $zz['fields'][22]['path'];
$zz['fields'][22]['if'][1]['separator'] = 'text <div class="separator">Für Mannschaftsturniere</div>';
$zz['fields'][22]['if'][2]['separator'] = true;
$zz['fields'][22]['if']['add']['hide_in_form'] = true;
$zz['fields'][22]['title_tab'] = 'Dateien';

$zz['fields'][22]['if'][1]['separator'] = false;
$zz['fields'][22]['if'][2]['separator'] = false;
$zz['fields'][22]['explanation'] = 'SWT wird sofort nach Upload importiert.';

// Nur SWT-Upload-Feld anzeigen
$fields = [22];
foreach (array_keys($zz['fields']) as $no) {
	if (!in_array($no, $fields)) $zz['fields'][$no]['hide_in_form'] = true;
}

$zz['vars']['event'] = $brick['data'];
$zz['hooks']['after_validation'][] = 'mf_swisschess_filematch_hook';
$zz['hooks']['after_upload'][] = 'mf_swisschess_swtimport';

wrap_text_set('Edit a record', '');
wrap_text_set('Record was not updated (no changes were made)', 'Datei wurde hochgeladen');
wrap_text_set('edit', 'Erneut hochladen');
wrap_text_set('Update record', 'Datei hochladen');

$zz['footer']['text'] = '<p><strong>Achtung:</strong> Nach Hinzufügen, Löschen oder dem Ändern von Spielern ist es bei Turnieren, bei denen nicht
jede Spielerin und jeder Spieler entweder eine ZPS-Nummer, eine FIDE-ID oder eine DSB-Personenkennziffer hat, sinnvoll, die
Personen-IDs aus der Datenbank als Identifikation in die SWT-Datei zurückzuschreiben (Feld Info4). Das geht automatisch über:</p>

<p><a href="../swtwriter/">Personen-IDs in SwissChess-Datei schreiben und herunterladen</a> (Verfügbar erst kurze Zeit nach Upload)</p>';

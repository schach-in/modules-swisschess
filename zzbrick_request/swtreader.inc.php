<?php 

/**
 * swisschess module
 * Output an SWT file
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014, 2017, 2019, 2021, 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Anzeige einer SWT-Datei
 *
 * @param array $params
 *		int [0]: year
 *		int [1]: event identifier
 * @return array $page
 */
function mod_swisschess_swtreader($params, $settings, $event) {
	ob_start();
	$dir = wrap_setting('media_folder').'/swt/'.$params[0].'/';
	$filename = $params[1].'.swt';
	if (!file_exists($dir.$filename))
		wrap_quit(404, wrap_text('SWT file `%s` does not exist. Please upload a file first.', ['values' => [$dir.$filename]]));

	$own = './';
	require wrap_setting('lib').'/swtparser/example.php';
	$page['text'] = ob_get_contents();
	$page['query_strings'] = ['view'];
	$page['dont_show_h1'] = true;
	$page['title'] = sprintf('SWT-Ansicht für %s %d', $event['event'], $event['year']);
	$page['breadcrumbs'][]['title'] = 'SWT-Ansicht';
	ob_end_clean();
	return $page;
}

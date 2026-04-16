<?php 

/**
 * swisschess module
 * send an SWT file from data folder
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * send an SWT file from data folder
 *
 * @param array $params
 * @return array
 */
function mod_swisschess_swtfile($params) {
	array_shift($params); // folder
	$filename = implode('/', $params);
	if (count($params) !== 2) wrap_quit(404);
	$params[1] = substr($params[1], 0, strrpos($params[1], '.')); // remove extension

	$rights = vsprintf('event:%d/%s', $params);
	if (!wrap_access('swisschess_download', $rights)) wrap_quit(403);

	wrap_include('functions', 'swisschess');
	$file['send_as'] = mf_swisschess_file_send_as($params);
	$file['caching'] = false; // never cache swiss chess files
	$file['name'] = wrap_setting('swisschess_dir').'/'.$filename;
	wrap_send_file($file);
}

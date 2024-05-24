<?php 

/**
 * swisschess module
 * functions that are called before or after changing a record
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/swisschess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * hook to start swisschess file import in the background
 *
 * @param array $ops
 */
function mf_swisschess_swtimport($ops) {
	$event = wrap_static('zzform', 'event');
	if (!$event) return [];
	$url = wrap_path('swisschess_job_swt', $event['identifier'], false);
	if (!$url) return [];
	// there might be access restrictions on swtimport URL, therefore use robot username here
	wrap_setting('log_username', wrap_setting('robot_username'));
	wrap_job($url, ['trigger' => 1, 'job_category_id' => wrap_category_id('jobs/swt')]);
	return [];
}

/**
 * hook to check if uploaded file matches tournament
 *
 * @param array $ops
 */
function mf_swisschess_filematch_hook($ops) {
	$event = wrap_static('zzform', 'event');
	if (!$event) return [];
	
	if (empty($ops['uploads'])) return [];
	$change = [];
	foreach ($ops['uploads'][0] as $upload) {
		if (empty($upload['tmp_name'])) continue;
		if ($upload['error'] !== 0) continue;
		$data = mf_swisschess_parse($upload['tmp_name']);
		$error_msg = mf_swisschess_filematch($event, $data);
		if (!$error_msg) continue;
		$change['no_validation'] = true;
		$change['valdidation_msg'] = $error_msg;
		$change['validation_fields'][0] = [
			$upload['field_name'] => [
				'class' => 'error',
				'explanation' => '<br>'.$error_msg
			]
		];
	}
	return $change;
}
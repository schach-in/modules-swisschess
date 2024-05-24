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


function mf_swisschess_swtimport($ops) {
	$event = wrap_static('zzform', 'event');
	if (!$event) return [];
	$url = wrap_path('tournaments_job_swt', $event['identifier'], false);
	if (!$url) return [];
	// there might be access restrictions on swtimport URL, therefore use robot username here
	wrap_setting('log_username', wrap_setting('robot_username'));
	wrap_job($url, ['trigger' => 1, 'job_category_id' => wrap_category_id('jobs/swt')]);
	return [];
}

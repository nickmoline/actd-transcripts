<?php

/**
 * Check an array key to make sure it exists (and is optionally numeric value) without triggering PHP Notices
 *
 * @author  nickmoline
 * @since  2013-03-12
 * @version  2013-08-23
 * @param  string  $key     array key to check for
 * @param  array   $array   array to check for the existance of $key in
 * @param  boolean $numeric if true value must be numeric to return
 * @return mixed $array[$key] if it exists (and passes $numeric check) or null if not
 */
function jcheck_array_val($key, $array = array(), $numeric = false) {
	if (!is_array($array)) return null;
	if (!isset($array[$key])) return null;
	if ($numeric) {
		if (is_numeric($array[$key])) return $array[$key];
	} else {
		return $array[$key];
	}
	return null;
}

/**
 * Check $_REQUEST for a given key and return it if it exists (and optionally passes numeric test)
 *
 * @author  nickmoline
 * @since 2013-03-12
 * @version  2013-03-12
 * @param  string  $key     array key to check for
 * @param  boolean $numeric if true value must be numeric to return
 * @return mixed $_REQUEST[$key] if it exists (and passes $numeric check) or null if not
 */
function jcheck_request($key, $numeric = false) {
	return jcheck_array_val($key, $_REQUEST, $numeric);
}

/**
 * Check $_SERVER for a given key and return it if it exists (and optionally passes numeric test)
 *
 * @author  nickmoline
 * @since 2013-05-08
 * @version  2013-05-08
 * @param  string  $key     array key to check for
 * @param  boolean $numeric if true value must be numeric to return
 * @return mixed $_SERVER[$key] if it exists (and passes $numeric check) or null if not
 */
function jcheck_server($key, $numeric = false) {
	return jcheck_array_val($key, $_SERVER, $numeric);
}

/**
 * Check $_GET for a given key and return it if it exists (and optionally passes numeric test)
 *
 * @author  nickmoline
 * @since 2013-03-12
 * @version  2013-03-12
 * @param  string  $key     array key to check for
 * @param  boolean $numeric if true value must be numeric to return
 * @return mixed $_GET[$key] if it exists (and passes $numeric check) or null if not
 */
function jcheck_get($key, $numeric = false) {
	return jcheck_array_val($key, $_GET, $numeric);
}

/**
 * Check $_POST for a given key and return it if it exists (and optionally passes numeric test)
 *
 * @author  nickmoline
 * @since 2013-03-12
 * @version  2013-03-12
 * @param  string  $key     array key to check for
 * @param  boolean $numeric if true value must be numeric to return
 * @return mixed $_POST[$key] if it exists (and passes $numeric check) or null if not
 */
function jcheck_post($key, $numeric = false) {
	return jcheck_array_val($key, $_POST, $numeric);
}

/**
 * Check $_COOKIE for a given key and return it if it exists (and optionally passes numeric test)
 *
 * @author  nickmoline
 * @since 2013-03-12
 * @version  2013-03-12
 * @param  string  $key     array key to check for
 * @param  boolean $numeric if true value must be numeric to return
 * @return mixed $_COOKIE[$key] if it exists (and passes $numeric check) or null if not
 */
function jcheck_cookie($key, $numeric = false) {
	return jcheck_array_val($key, $_COOKIE, $numeric);
}

/**
 * Checks if the current user is using SSL to access the site (either directly or via a load balancer)
 *
 * @author  nickmoline
 * @since  2013-09-06
 * @version  2013-09-06
 * @return  bool true if SSL, false if not
 */
function jcheck_ssl() {
	$https = jcheck_server('HTTPS');
	if ($https && strtolower($https) != 'off') return true;

	$forwarded_proto = jcheck_server('HTTP_X_FORWARDED_PROTO');
	if ($forwarded_proto && strtolower($forwarded_proto) == 'https') return true;

	$forwarded_ssl = jcheck_server('HTTP_X_FORWARDED_SSL');
	if ($forwarded_ssl && strtolower($forwarded_ssl) == 'on') return true;

	return false;
}

function jcommon_friendlyize($string, $delim = '-') {
	$string = jcommon_transliterate_string($string);
	return trim(preg_replace('@[^A-Za-z0-9]+@msi',$delim,strtolower(trim($string))),$delim);
}

function jcommon_transliterate_string($string) {
	$string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');
	setlocale(LC_ALL, "en_US.UTF-8");
	return iconv('UTF-8', 'ASCII//TRANSLIT', $string);
}

function jcommon_normalize_string($string) {
	$string = html_entity_decode($string,ENT_COMPAT,'UTF-8');
	return mb_convert_encoding($string, 'HTML-ENTITIES', 'auto');
}

function convert_roman_numeral($roman) {
	$romans = array(
		'M' => 1000,
		'CM' => 900,
		'D' => 500,
		'CD' => 400,
		'C' => 100,
		'XC' => 90,
		'L' => 50,
		'XL' => 40,
		'X' => 10,
		'IX' => 9,
		'V' => 5,
		'IV' => 4,
		'I' => 1,
	);

	$result = 0;

	foreach ($romans as $key => $value) {
		while (strpos($roman, $key) === 0) {
			$result += $value;
			$roman = substr($roman, strlen($key));
		}
	}
	return $result;
}

function convert_int_to_roman($num) {
	$n = intval($num); 
	$res = ''; 

	/*** roman_numerals array  ***/ 
	$roman_numerals = array( 
		'M'  => 1000, 
		'CM' => 900, 
		'D'  => 500, 
		'CD' => 400, 
		'C'  => 100, 
		'XC' => 90, 
		'L'  => 50, 
		'XL' => 40, 
		'X'  => 10, 
		'IX' => 9, 
		'V'  => 5, 
		'IV' => 4, 
		'I'  => 1); 

	foreach ($roman_numerals as $roman => $number){ 
		/*** divide to get  matches ***/ 
		$matches = intval($n / $number); 

		/*** assign the roman char * $matches ***/ 
		$res .= str_repeat($roman, $matches); 

		/*** substract from the number ***/ 
		$n = $n % $number; 
	} 

	/*** return the res ***/ 
	return $res; 
}

function get_transcript_list($folder, $default_date_raw = null) {
	if (!$default_date_raw) $default_date_raw = jcheck_get('date');

	$transcript_list = array();

	$default_date = date("Y-m-d",strtotime($default_date_raw));

	if (is_dir($folder)) {
		if ($dh = opendir($folder)) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match("@#ST_StationMissions_(\d{4})(\d{2})(\d{2}).log@msi", $file, $file_match)) {
					$file_date_string = "{$file_match[1]}-{$file_match[2]}-{$file_match[3]}";
					$ts = strtotime($file_date_string);
					$sd_year = date("y",$ts) + 100;
					$transcript = array(
						"stardate"	=>	"{$sd_year}".date("m.d",$ts),
						"date"		=>	$file_date_string,
						"selected"	=>	($file_date_string == $default_date)?' selected="selected" class="selected_transcript"':'',
						"filename"	=>	$file,
						"full_path"	=>	"{$folder}{$file}",
					);
					$transcript_list[$file_date_string] = $transcript;
				}
			}
		}
	}
	krsort($transcript_list);
	if (!$default_date) {
		$most_recent = current($transcript_list);
		$default_date = $most_recent['date'];
		$transcript_list[$default_date]['selected'] = ' selected="selected"';
	}
	return $transcript_list;
}
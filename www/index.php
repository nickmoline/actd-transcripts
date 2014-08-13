<?php
setlocale(LC_ALL, "en_US.UTF-8");
mb_http_output( "UTF-8" );
ob_start("mb_output_handler");
header('Content-type: text/html; charset=utf-8'); 
$base_folder = dirname(dirname(__FILE__));
require_once("{$base_folder}/vendor/autoload.php");
require_once("{$base_folder}/include.php");
Twig_Autoloader::register();

$twig_loader = new Twig_Loader_Filesystem("{$base_folder}/templates/");
$twig = new Twig_Environment($twig_loader,
	array(
		"autoescape"    =>  false,
		//"cache"         =>  "{$base_folder}/templates/cache/"
	)
);

$infolder = "{$base_folder}/log/";
$raw_transcript_date = jcheck_get('date');

$transcript_list = get_transcript_list($infolder, $raw_transcript_date);

if (!$raw_transcript_date) {
	$first_entry = current($transcript_list);
	$raw_transcript_date = $first_entry['date'];
}

$transcript_date = date("Y-m-d", strtotime($raw_transcript_date));

$transcript_info = $transcript_list[$transcript_date];

$date_ts = strtotime($transcript_date);

$in_file = $transcript_info['full_path'];
$date_display = $transcript_info['stardate'];

$positions = array(
	"co"        =>  "command",
	"xo"        =>  "command",
	"fco"       =>  "command",
	"regent"    =>  "command",
	"adm"       =>  "command",
	"capt"      =>  "command",
	
	"cto"       =>  "tactical",
	"to"        =>  "tactical",
	"csec"      =>  "tactical",
	
	"ceo"       =>  "engineering",
	"eo"        =>  "engineering",
	"ops"       =>  "engineering",
	
	"cmo"       =>  "medical",
	"mo"        =>  "medical",
	"cns"       =>  "medical",
	
	"cso"       =>  "science",
	"so"        =>  "science",
	
	"civ"       =>  "civilian",
);

//$output .=  $in_file;

$raw_lines = file($in_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$transcript_lines = array();

$last_who = "";

$output = "";

$attendance = array();

$arc_title = "";
$episode_number = "";
$episode_title = "";

foreach ($raw_lines as $key => $line) {
	// [20:10:27] <CIV_Nyira> ::heads to her office to send a message to the CO letting him know she is available when ever he needs:: 
	// [19:40:42] <Pira> <landing pad operator> *FCO*: Sorry Ensign, the pilot seems to have had a bit too much to drink at Nyira's, he's passed out on the floor of his shuttle.

	if (preg_match("@^\[(\d+:\d+:\d+)\]\s*<([^>]+)>\s*(.*)$@msi", trim($line), $matches)) {
		//$output .=  "Found {$key} {$line}\n\n";
		//print_r($matches);
		
		$timestamp      = $matches[1];
		$who            = $matches[2];
		$raw_message    = $matches[3];
		
		$position = 'normal';
		
		$name_parts = explode("_", $who);
		
		$position_key = strtolower($name_parts[0]);
		if (array_key_exists($position_key, $positions)) $position = $positions[$position_key];
		
		$formatted_name = implode(" ", $name_parts);

		$normalized_message = jcommon_normalize_string($raw_message);

		$encoded_message    = str_replace(array('<','>'),array('&lt;','&gt;'), $normalized_message);
		
		if (preg_match('@^<([A-Za-z0-9\-\_\s]+)>[:\s]*(.+)$@msi', trim($raw_message), $npc_match)) {
			$npc_who = "npc_".jcommon_friendlyize($npc_match[1],"_").'_'.jcommon_friendlyize($who,'_');
			$npc_name_parts = preg_split('@[^A-Za-z0-9\']+@msi', $npc_match[1]);
			
			$position = 'normal';
			$position_key = strtolower($npc_name_parts[0]);
			if (array_key_exists($position_key, $positions)) $position = $positions[$position_key];
			
			$npc_formatted_name = implode(" ", $npc_name_parts);
			if (!isset($attendance[$npc_who])) {
				$attendance[$npc_who] = array(
					"name"			=>	$npc_who,
					"display_name"	=>	"{$npc_formatted_name} ({$formatted_name})",
					"position"		=>	$position,
					"lines"			=>	0,
				);
			}
			$who = $npc_who;
			$formatted_name = "{$npc_formatted_name} ({$formatted_name})";

			$normalized_message = jcommon_normalize_string($npc_match[2]);
			$encoded_message    = htmlentities($normalized_message);

		}

		if ($last_who && strtolower($who) != $last_who) $output .= "</div>\n\n";
		if (strtolower($who) != $last_who) {
			$output .=  "<div id=\"line_{$key}\" class=\"chatline {$who}\">\n";
			$output .=  "\t<div id=\"line_{$key}_who\" class=\"who {$position} {$who}\">{$formatted_name} says:</div>\n";
			$last_who = strtolower($who);
			if (!isset($attendance[$last_who])) {
				$attendance[$last_who] = array(
					"name"          =>  $who,
					"display_name"  =>  $formatted_name,
					"position"      =>  $position,
					"lines"         =>  0,
				);
			}
		}
		$output .= "<!-- {$line} -->\n";		

		$attendance[$last_who]['lines']++;	

		if (preg_match("@<<<+([^>]+)>>>+@msi", $raw_message, $divider_message_match)) {
			
			$new_raw_message = jcommon_normalize_string(jcommon_transliterate_string($divider_message_match[1]));
			$encoded_message = str_replace(array('<','>'),array('&lt;','&gt;'), $new_raw_message);

			if (preg_match("@[\"\']([^\'\"]+)[\"\']\s*\-*\s*Episode\s*([\dIVXLCDM]+)@msi", $new_raw_message, $begin_line_matches)) {
				$arc_title = $begin_line_matches[1];
				$episode_number = $begin_line_matches[2];
			}
			
			$output .= "\t<h4 id=\"line_{$key}_divider_message\" class=\"divider_message\">{$encoded_message}</h4>\n\n";
		} else {
			if (preg_match("@Episode Title: (.*)@msi", $raw_message, $episode_title_match)) {
				$episode_title = $episode_title_match[1];
			}
			$output .=  "\t<p id=\"line_{$key}_message\" class=\"message\">{$encoded_message}</p>\n";
		}
		

	} else {
		$output .= "<!-- {$line} -->\n";
		// if ($last_who) {
		// 	$output .= "</div>\n\n";
		// 	$last_who = "";
		// }
		// $new_raw_message = jcommon_normalize_string(jcommon_transliterate_string($line));
		// $encoded_message = str_replace(array('<','>'),array('&lt;','&gt;'), $new_raw_message);
		// $output .= "<div class=\"raw_transcript_line\">{$encoded_message}</div>\n";
	}
}
$output .=  "</div>\n\n";

$episode_description = array();

if ($arc_title) {
	$episode_description[] = "&ldquo;{$arc_title}&rdquo;";
	if ($episode_number) {
		$episode_description[] = "Episode {$episode_number}";
	}
	if ($episode_title) {
		$episode_description[] = "&ldquo;{$episode_title}&rdquo;";
	}
	
}

echo $twig->render("index.twig", 
	array(
		"transcript_list"       =>  $transcript_list,
		"stardate"              =>  $date_display,
		"attendance"            =>  $attendance,
		"episode_description"   =>  $episode_description,
		"transcript"            =>  $output,
	)
);


?>
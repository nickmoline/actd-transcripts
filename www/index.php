<?php
require_once(dirname(__FILE__)."/vendor/autoload.php");
Twig_Autoloader::register();
setlocale(LC_ALL, "en_US.UTF-8");

$base_folder = dirname(__FILE__);

$twig_loader = new Twig_Loader_Filesystem("{$base_folder}/templates/");
$twig = new Twig_Environment($twig_loader,
    array(
        "autoescape"    =>  false,    
    )
);


$date_ts = strtotime("2014-08-04");

$date_display = date("y",$date_ts) + 100;
$date_display .= date("m.d", $date_ts);

$file_date_string = date("Ymd",$date_ts);

$infolder = "{$base_folder}/log/";
$outfolder = "{$base_folder}/output/";

$base_in_filename = "#ST_StationMissions_";

function jcommon_transliterate_string($string) {
	$string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');
	setlocale(LC_ALL, "en_US.UTF-8");
	return iconv('UTF-8', 'ASCII//TRANSLIT', $string);
}


$in_file = "{$infolder}{$base_in_filename}{$file_date_string}.log";

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
        
        // CO_Capt_Bodine
        /*\
            array(
                [0] => "CO",
                [1] => "Capt",
                [2] => "Bodine
            )
        */
        $name_parts = explode("_", $who);
        
        $position_key = strtolower($name_parts[0]);
        if (array_key_exists($position_key, $positions)) $position = $positions[$position_key];
        
        $formatted_name = implode(" ", $name_parts);


        $encoded_message    = htmlentities($raw_message);
        
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


        $attendance[$last_who]['lines']++;

        $output .=  "\t<!-- {$timestamp} -->\n";
        
    

        if (preg_match("@<<<+([^>]+)>>>+@msi", $raw_message, $divider_message_match)) {
            
            $new_raw_message = jcommon_transliterate_string($divider_message_match[1]);
            $encoded_message = htmlentities($new_raw_message);
            if (preg_match("@[\"\']([^\'\"]+)[\"\']\s*\-*\s*Episode\s*([\dIVXLCDM]+)@msi", $new_raw_message, $begin_line_matches)) {
                $arc_title = $begin_line_matches[1];
                $episode_number = $begin_line_matches[2];
            }
            
            $output .= "\t<p id=\"line_{$key}_divider_message\" class=\"divider_message\">{$encoded_message}</p>\n\n";
        } else {
            if (preg_match("@Episode Title: (.*)@msi", $raw_message, $episode_title_match)) {
                $episode_title = $episode_title_match[1];
            }
            $output .=  "\t<p id=\"line_{$key}_message\" class=\"message\">{$encoded_message}</p>\n";
        }
        

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
        "stardate"              =>  $date_display,
        "attendance"            =>  $attendance,
        "episode_description"   =>  $episode_description,
        "transcript"            =>  $output,
    )
)


?>
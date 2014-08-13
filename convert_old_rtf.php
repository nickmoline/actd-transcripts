<?php
$base_folder = dirname(__FILE__);
require_once("{$base_folder}/vendor/autoload.php");
require_once("{$base_folder}/include.php");
Twig_Autoloader::register();

$twig_loader = new Twig_Loader_Filesystem("{$base_folder}/templates/");
$twig = new Twig_Environment($twig_loader,
	array(
		"autoescape"    =>  false,
		//"cache"       =>  "{$base_folder}/templates/cache/"
	)
);

$in_folder = "{$base_folder}/www/old/";
$raw_folder = "{$base_folder}/log/";
$out_folder = "{$base_folder}/www/archive/";

$files = array();
$nomatch = array();

$dh = opendir($in_folder);
while (($old_filename = readdir($dh)) !== false) {
	if (preg_match("@Arcadia(1?\d{2})(\d{2})(\d{2})\.rtf@msi", $old_filename, $matches)) {
		$year = $matches[1] + 1900;
		$month = $matches[2];
		$day = $matches[3];

		$date = "{$year}-{$month}-{$day}";

		$new_filename = sprintf("%03d", $year - 1900).sprintf("%02d",$month).".".sprintf("%02d", $day).".html";

		$stardate = sprintf("%02d", $year - 1900).sprintf("%02d",$month).".".sprintf("%02d", $day);

		echo "{$old_filename}\t{$date}\t{$new_filename}\n";

		passthru("unrtf {$in_folder}{$old_filename} > {$out_folder}{$new_filename}");

		echo "\n\n";

		$date_filename = "{$year}{$month}{$day}";

		$raw_filename = "#ST_StationMissions_{$date_filename}.log";

		$files[$new_filename] = array(
			"stardate"			=>	$stardate,
			"real_date_sort"	=>	$date,
			"real_date"			=>	date("F j, Y",strtotime($date)),
			"filename"			=>	$new_filename,
			"raw_link"			=>	"",
		);

		if (file_exists("{$raw_folder}{$raw_filename}")) {
			$files[$new_filename]['raw_link'] = "/?date={$date}";
		}

	} else {
		$nomatch[] = $old_filename;
	}
}

ksort($files);

$outfile = "{$out_folder}index.html";

$html_list = $twig->render("archive.twig",array("file_list" => $files));
$out = fopen($outfile,"w");
fwrite($out, $html_list);
fclose($out);

echo $html_list;

print_r($files);

print_r($nomatch);
?>
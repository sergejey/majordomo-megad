<?
$src = file_get_contents("source.txt");
$src = explode("\n", $src);
$cur_letter = "";
$my_code = "";
mb_internal_encoding('UTF-8');

for ( $i = 0; $i < count($src); $i++ )
{
	if ( !empty($letter) )
	{
		$my_code .= "	'$letter' => array ( ".preg_replace("/,$/", "", preg_replace("/\s|\n|\r/", "", $src[$i]))."),\n";
		$letter = "";
	}
	if ( preg_match("/\'/", $src[$i]) )
	{
		$letter = preg_replace("/.*\s\'(.)\'\s.*/u", "$1", $src[$i]);
		//echo $letter."| $i";
	}
	
}

echo $my_code;

?>
<?php
/* Load SQL commands out of a stream and apply them. c_restore.php could also
 * use this function (that's why it takes an $fp instead of a filename) */
function update_apply_db($mysqli, $fp)
{
	$sql = '';
	while(!feof($fp)) {
		/* Multiline read support */
		$line = trim(fgets($fp));
		if(strlen($line) == 0) continue;

		if($line[0] == '#') {
			continue;
		}
		if($line[0] == '-' && $line[1] == '-') {
			continue;
		}

		/* Fixme add support for -- and C-style slash-star star-slash comments  */

		$sql .= $line;
		if($line[strlen($line)-1] == ';') {
			$mysqli->real_query($sql);
//			print("$sql\n");
			if($mysqli->error != '') {
				print("SQL command failed.  SQL: $sql\n");
				print("Error: {$mysqli->error}\n");
			}
			$sql = '';
		}
	}
}

?>

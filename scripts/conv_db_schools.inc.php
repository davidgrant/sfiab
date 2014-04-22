<?php


$schools_map = array();

function conv_schools_user($mysqli_old, $id, &$na, &$em, &$ph)
{
	$q = $mysqli_old->query("SELECT * FROM users WHERE id='$id'");
	$na = '';
	$em = '';
	$ph = '';
	
	if($q->num_rows > 0) {
		$r = $q->fetch_assoc();
		$na = trim($r['salutation'].' '.$r['firstname']. ' '.$r['lastname']);
		$em = $r['email'];
		$ph = $r['phonework'];
	}
}

function conv_schools_filter_sql_text($mysqli, $text)
{
	if($text == '') 
		return "NULL";
	else
		return "'".$mysqli->real_escape_string($text)."'";
}

function conv_schools($mysqli, $mysqli_old, $year)
{
	global $schools_map;
	print("Convert Schools for $year...\n");

	/* Delete existing */
	$mysqli->query("DELETE FROM schools WHERE year='$year' )");

	$q=$mysqli_old->query("SELECT * FROM schools WHERE year='$year'");
	while($r=$q->fetch_assoc()) {

		$p_na = '';
		$p_em = '';
		$p_ph= '';
		$sh_na= '';
		$sh_em= '';
		$sh_ph= '';

		if($r['principal_uid'] > 0) 
			conv_schools_user($mysqli_old, $r['principal_uid'], $p_na, $p_em, $p_ph);
		if($r['sciencehead_uid'] > 0) 
			conv_schools_user($mysqli_old, $r['sciencehead_uid'], $sh_na, $sh_em, $sh_ph);

		
		$mysqli->query("INSERT INTO schools (school,schoollang,schoollevel,board,district,phone,fax,email,
				address,city,province,postalcode,principal,
				principal_email,principal_phone,sciencehead, sciencehead_email, sciencehead_phone,
				junior,intermediate,senior,
				registration_password,projectlimit,projectlimitper,year) VALUES (
			'".$mysqli->real_escape_string($r['school'])."',
			'".$mysqli->real_escape_string($r['schoollang'])."',
			'".$mysqli->real_escape_string($r['schoollevel'])."',
			'".$mysqli->real_escape_string($r['board'])."',
			'".$mysqli->real_escape_string($r['district'])."',
			'".$mysqli->real_escape_string($r['phone'])."',
			'".$mysqli->real_escape_string($r['fax'])."',
			'".$mysqli->real_escape_string($r['schoolemail'])."',
			'".$mysqli->real_escape_string($r['address'])."',
			'".$mysqli->real_escape_string($r['city'])."',
			'".$mysqli->real_escape_string($r['province_code'])."',
			'".$mysqli->real_escape_string($r['postalcode'])."',".
			conv_schools_filter_sql_text($mysqli, $p_na).','.
			conv_schools_filter_sql_text($mysqli, $p_em).','.
			conv_schools_filter_sql_text($mysqli, $p_ph).','.
			conv_schools_filter_sql_text($mysqli, $sh_na).','.
			conv_schools_filter_sql_text($mysqli, $sh_em).','.
			conv_schools_filter_sql_text($mysqli, $sh_ph).",
			'".$mysqli->real_escape_string($r['junior'])."',
			'".$mysqli->real_escape_string($r['intermediate'])."',
			'".$mysqli->real_escape_string($r['senior'])."',
			'".$mysqli->real_escape_string($r['registration_password'])."',
			'".$mysqli->real_escape_string($r['projectlimit'])."',
			'".$mysqli->real_escape_string($r['projectlimitper'])."',
			'$year')");

		$school_id = $mysqli->insert_id;

		$schools_map[(int)$r['id']] = $school_id;

	}
}

?>

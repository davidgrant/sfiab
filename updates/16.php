<?php
function pre_16($mysqli)
{
	print("Salting all passwords...\n");
	$q = $mysqli->query("SELECT `uid`,`password` FROM `users` WHERE `password`!='' AND `enabled`='1'");
	$c = 0;
	while($u=$q->fetch_assoc()) {
		$salt = base64_encode(mcrypt_create_iv(96, MCRYPT_DEV_URANDOM));
		$salted_pw = hash('sha512', $u['password'].$salt);
		$uid = $u['uid'];

		$mysqli->real_query("UPDATE `users` SET `password`='$salted_pw', `salt`='$salt' WHERE `uid`='$uid'");
		$c += 1;
	}
	print("Salted $c passwords.\n");

}
?>

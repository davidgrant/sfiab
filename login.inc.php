<?php

function login_logout($mysqli, $u) 
{
	if(sfiab_session_is_active()) {
		sfiab_session_start();
	}
	sfiab_log($mysqli, "logout", $u, 1);
	// Unset all session values
	$_SESSION = array();
	// get session parameters 
	$params = session_get_cookie_params();
	// Delete the actual cookie.
	setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
	// Destroy session
	session_destroy();
}

?>

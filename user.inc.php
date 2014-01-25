<?php
require_once('filter.inc.php');

function user_load($mysqli, $uid=-1, $unique_uid=-1, $username=NULL)
{
	if((int)$uid > 0) {
		$id = (int)$uid;
		$r = $mysqli->query("SELECT * FROM users WHERE uid=$id LIMIT 1");
	} else if((int)$unique_uid > 0) {
		$id = (int)$unique_uid;
		$r = $mysqli->query("SELECT * FROM users WHERE unique_uid=$id ORDER BY `year` DESC LIMIT 1");
	} else if($username != NULL) {
		$username = $mysqli->real_escape_string($username);
		$r = $mysqli->query("SELECT * FROM users WHERE `username`='$username' ORDER BY `year` DESC LIMIT 2");
	} else {
		if(sfiab_session_is_active()) {
			if($_SESSION['uid'] > 0) {
				$r = $mysqli->query("SELECT * FROM users WHERE uid={$_SESSION['uid']} LIMIT 1");
			} else {
				return NULL;
			}
		} else {
			return NULL;
		}
	}

	if($r->num_rows == 0) {
		return NULL;
	}

	$u = $r->fetch_assoc();

	/* There can be at most 2 entries for same username in the same year.
	 * If the entry we just loaded is 'deleted', then grab the next
	 * one, and see if it's in the same year (results are returned
	 * sorted by year).  If it is, return the new one since it's
	 * the valid one */
	if($r->num_rows == 2 && $u['state'] == 'deleted') {
		$u2 = $r->fetch_assoc();
		if($u2['year'] == $u['year']) 
			$u = $u2;
		/* If it's not the same year, return the deleted entry */
	}

	/* Sanitize some fields */
	$u['uid'] = (int)$u['uid'];
	$u['name'] = ($u['firstname'] ? "{$u['firstname']} " : '').$u['lastname'];
	$u['roles'] = explode(",", $u['roles']);
	$u['password_expired'] = ((int)$u['password_expired'] == 1) ? true : false;

	/* Student filtering */
	filter_int_or_null($u['schools_id']);
	filter_int_or_null($u['grade']);
	/* Clear out invalid input so the placeholder is shown again */
	if($u['birthdate'] == '0000-00-00') $u['birthdate'] = NULL;



	/* Judge filtering */

	filter_int_or_null($u['j_pref_div1']);
	filter_int_or_null($u['j_pref_div2']);
	filter_int_or_null($u['j_pref_div3']);
	filter_int_or_null($u['j_pref_cat']);
	filter_int_or_null($u['j_years_school']);
	filter_int_or_null($u['j_years_regional']);
	filter_int_or_null($u['j_years_national']);
	filter_bool_or_null($u['j_sa_only']);
	filter_bool_or_null($u['j_willing_lead']);
	filter_bool_or_null($u['j_dinner']);
	filter_bool_or_null($u['j_mentored']);
	if($u['j_rounds'] === NULL) 
		$u['j_rounds'] = array(NULL,NULL);
	else {
		$a = explode(',',$u['j_rounds']);
		$u['j_rounds'] = array(0,0);
		foreach($a as $r) {
			$u['j_rounds'][$r] = 1;
		}
	}

	if($u['j_sa'] === NULL)
		$u['j_sa'] = array(NULL,NULL,NULL);
	else {
		$a = explode(',',$u['j_sa']);
		$u['j_sa'] = array(NULL, NULL, NULL);
		$i = 0;
		foreach($a as $id) {
			$u['j_sa'][$i] = $id;
			$i++;
		}
	}

	if($u['j_languages'] === NULL)
		$u['j_languages'] = array();
	else 
		$u['j_languages'] = unserialize($u['j_languages']);
	foreach(array('en','fr') as $l) {
		if(!array_key_exists($l, $u['j_languages'])) {
			$u['j_languages'][$l] = NULL;
		}
	}


	/* Store an original copy so save() can figure out what (if anything) needs updating */
	unset($u['original']);
	$original = $u;
	$u['original'] = $original;

	return $u;
}

function user_load_by_username($mysqli, $username)
{
	return user_load($mysqli, -1, -1, $username);
}


function user_save($mysqli, &$u) 
{
	global $sfiab_roles;
	/* Find any fields that changed */
	/* Construct a query to update just those fields */
	/* Always save in the current year */
	$set = "";
	foreach($u as $key=>$val) {
		if($key == 'original') continue;
		if(!array_key_exists($key, $u['original'])) continue;

		if($val !== $u['original'][$key]) {
			/* Key changed */
			if($set != '') $set .= ',';

			switch($key) {
			case 'roles':
				/* Make a list of comma-separated roles, sanity checking
				 * them all first */
				foreach($u['roles'] as $r) {
					if(!array_key_exists($r, $sfiab_roles)) {
						print("Error 1002: $r");
						exit();
					}
				}
				/* It's all ok, join it with commas so the query
				 * looks like ='teacher,committee,judge' */
				$v = implode(',', $r);
				break;

			case 'j_rounds':
				$a = array();
				foreach($val as $id=>$enabled) {
					if($enabled == 1) $a[] = $id;
				}
				print_r($a);
				$v = implode(',', $a);
				break;

			 case 'j_sa':
				$a = array();
				foreach($val as $index=>$id) {
					if($id !== NULL) $a[] = $id;
				}
				$v = implode(',', $a);
				break;


			default:
				/* Serialize any non-special arrays */
				if(is_array($val)) 
					$v = serialize($val);
				else if(is_null($val)) 
					$v = NULL;
				else 
					$v = $val;
				break;
			}

			if(is_null($v)) {
				$set .= "`$key`=NULL";
			} else {
				$v = stripslashes($v);
				$v = $mysqli->real_escape_string($v);
				$set .= "`$key`='$v'";
			}

			/* Set the original to the unprocessed value */
			$u['original'][$key] = $val;
		}
	}

//	print_r($u);

	if($set != '') {
		$query = "UPDATE users SET $set WHERE uid='{$u['uid']}'";
//		print($query);
		$mysqli->query($query);
	}
}

?>

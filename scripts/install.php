<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2009 James Grant <james@lightbox.org>

   This program is free software; you can redistribute it and/or
   modify it under the terms of the GNU General Public
   License as published by the Free Software Foundation, version 2.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; see the file COPYING.  If not, write to
   the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
   Boston, MA 02111-1307, USA.
*/

print("SFIAB Install Script\n");


if(array_key_exists('SERVER_ADDR', $_SERVER)) {
	/* Don't run from the webserver */
	exit();
}

function check_opts(&$opts, $name, $must_exist)
{
	if(array_key_exists($name, $opts)) {
		return $opts[$name];
	} else {
		if($must_exist) {
			print("Argument --$name is missing\n");
			exit();
		}
		return NULL;
	}
}

function check_database($host, $user, $pass, $db)
{
	$mysqli = new mysqli($host, $user, $pass, $db);

	if($mysqli->connect_errno) {
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		return NULL;
	}
	return $mysqli;
}

$opts = getopt("nphu", array("db_name::", "db_pass::", "db_host::", "db_user::",
			"admin_name::", "admin_user::", "admin_pass::",
			"fair_name::", "fair_abbr::", "year::",
			"convert", "force", "old_config::") );

/* Check args */
if(count($_SERVER['argv']) == 1 || array_key_exists('h', $opts) || array_key_exists('help', $opts)) {
?>
Help:
Must use '=' for option values, that's how PHP's argument parser works

To create a new configuration, specify database information with:
--db_name=[db name] --db_user=[username] --db_pass-[password] --db_host=[server]
OR, create a configuration from an existing old config file:
--old_config=[path/to/config.inc.php]

Specify new fair parameters with (create a new fair and admin account):
--admin_name=[name] --admin_user=[user] --admin_pass=[password] --fair_name=[fair name]
OR, convert from an existing database:
--convert

To convert from a previous sfiab install, just use --old_config --convert.
That will rename all the tables in the database and prefix them with "zold_",
then it will import a new database and fill it.  After doing this, upgrade 
to the latest SFIAB3 version and run the update script.

<?php
exit();
}




$convert = array_key_exists('convert', $opts) ? true : false;

$dbuser = NULL;
$dbpassword = NULL;
$dbhost = NULL;
$dbdatabase = NULL;

print("Checking Database Connection...\n");
if(file_exists("config.inc.php")) {
	global $dbname;
	print("   config.inc.php exists.\n");
	/* Check the database connection */
	require_once('config.inc.php');
	$mysqli = check_database($dbhost, $dbuser, $dbpassword, $dbdatabase);
	if($mysqli === NULL) {
		print("   but doesn't work.\n");
		print("\nconfig.inc.php exists, but doesn't seem to work, please correct before continuing.\n");
	}
} else {
	print("   config.inc.php doesn't exist, trying to create...\n");

	$old_config = check_opts($opts, 'old_config', false);
	if(file_exists($old_config)) {
		print("   reading old database config out of $old_config...\n");
		$fp = fopen($old_config, "rt");
	 	while($line = fgets($fp)) {
			$line = trim($line);
			if(preg_match("/DBHOST=\"([^\"]*)\";/", $line, $matches)) $dbhost = $matches[1];
			if(preg_match("/DBUSER=\"([^\"]*)\";/", $line, $matches)) $dbuser = $matches[1];
			if(preg_match("/DBPASS=\"([^\"]*)\";/", $line, $matches)) $dbpassword = $matches[1];
			if(preg_match("/DBNAME=\"([^\"]*)\";/", $line, $matches)) $dbdatabase = $matches[1];
		}
		if($dbuser === NULL) {
			print("   old config is missing \$DBUSER\n");
			exit();
		}
		if($dbpassword === NULL) {
			print("   old config is missing \$DBPASS\n");
			exit();
		}
		if($dbhost === NULL) {
			print("   old config is missing \$DBHOST\n");
			exit();
		}
		if($dbdatabase === NULL) {
			print("   old config is missing \$DBNAME\n");
			exit();
		}
		print("   checking if old user,pass,host,name works...\n");

	} else {
		print("   checking if supplied user,pass,host,name works...\n");
		$dbuser = check_opts($opts, 'db_user', true);
		$dbpassword = check_opts($opts, 'db_pass', true);
		$dbhost = check_opts($opts, 'db_host', true);
		$dbdatabase = check_opts($opts, 'db_name', true);
	}
	
	$mysqli = check_database($dbhost, $dbuser, $dbpassword, $dbdatabase);
	if($mysqli === NULL) {
		print("   nope, unable to use supplied credentials to connect to a database\n");
	} else {
		print("   connected!\n");
		print("   Trying to write a config.inc.php...\n");
		$fp = fopen("config.inc.php", "wt");
		if(!$fp) {
			print("\n\nUnable to open \"config.inc.php\" for writing.  Please create a file called config.inc.php with this in it, and upload it to the webserver where your SFIAB his hosted.:\n");
			$fp = STDOUT;
		} else {
			print("   written.\n");
		}
		fwrite($fp, "<?php\n");
		fwrite($fp, "\t\$dbhost = \"$dbhost\";\n");
		fwrite($fp, "\t\$dbuser = \"$dbuser\";\n");
		fwrite($fp, "\t\$dbpassword = \"$dbpassword\";\n");
		fwrite($fp, "\t\$dbdatabase = \"$dbdatabase\";\n");
		fwrite($fp, "?>\n");
		fclose($fp);

		if($fp == STDOUT) {
			exit();
		}
	}
}

if(!$mysqli) {
	exit();
}

print("Checking database `$dbdatabase` state...\n");


require_once('scripts/conv_db.inc.php');
require_once('db.inc.php');


/* See if the database is old/new/or empty */
$q = $mysqli->query("SHOW TABLES");
if($q->num_rows == 0) {
	print("   Database is empty");
} else {
	/* Check config for old DB */
	$q = $mysqli->query("SELECT val FROM config WHERE var='DBVERSION'");
	if($q && $q->num_rows) {
		$r = $q->fetch_row();
		$v = (int)$r[0];
		print("   Found a current previous SFIAB database with version $v\n");

		print("   Renaming old tables to zold_*\n");
		/* Move old tables out of the way */
		conv_rename_old_tables($mysqli, 'zold_');
	} else {
		print("   Old SFIAB database not found.\n");
	}
	/* Else, drop out and just check for a new database and missing user */
}

/* Check for an old database */
$old_db_version = NULL;
$q = $mysqli->query("SELECT val FROM zold_config WHERE var='DBVERSION'");
if($q && $q->num_rows) {
	$r = $q->fetch_row();
	$old_db_version = (int)$r[0];
	print("   Found a renamed previous SFIAB database with version $old_db_version\n");
}


/* Check for new database */
$new_db_version = NULL;
$q = $mysqli->query("SELECT val FROM config WHERE var='db_version'");
if(!$q || !$q->num_rows) {
	print("   New SFIAB database not found, installing...\n");
	/* Install the new database */
	$fp = gzopen('updates/full_16.sql.gz', 'r');
	db_apply_update($mysqli, $fp);
	gzclose($fp);
} else {
	$r = $q->fetch_row();
	$new_db_version = (int)$r[0];
	print("   Found new SFIAB with verison $new_db_version.\n");
	print("   Nothing to install.\n");
}

require_once('common.inc.php');
require_once('user.inc.php');

sfiab_load_config($mysqli);

/* Convert or install a new user and setup the new database for the current year? */
if($convert) {
	if($old_db_version === NULL) {
		print("Database conversion requested, but no old database found.  Cannot convert.\n");
		exit();
	}

	print("Starting database conversion.\n");
	conv_db($mysqli, "zold_");

}  else {

	/* If we're at year 0, we need to do a rollover */
	if($config['year'] == 0) {
		$new_year = check_opts($opts, 'year', true);
		if($new_year <=0) {
			print("Can't roll fair year to $new_year, use a 4-digit year\n");
			exit();
		}
		db_roll($mysqli, $new_year);
		sfiab_load_config($mysqli);
	}


	$q = $mysqli->query("SELECT val FROM config WHERE var='fair_name' and val='Default Regional Science Fair'");
	if($q->num_rows != 0) {
		$fair_name = $mysqli->real_escape_string(check_opts($opts, 'fair_name', true));
		$fair_abbr = $mysqli->real_escape_string(check_opts($opts, 'fair_abbr', true));
		print("Fair name hasn't been set yet\n");
		print("   Setting fair name: $fair_name\n");
		print("   Setting fair abbreviation: $fair_abbr\n");
		$mysqli->real_query("UPDATE `config` SET `val`='$fair_name' WHERE `var`='fair_name'");
		$mysqli->real_query("UPDATE `config` SET `val`='$fair_abbr' WHERE `var`='fair_abbreviation'");
	} else {
		print("Fair name has already been set.  Use the config page in SFIAB to change it.\n");
	}
	
	/* If there is only one committee user, and the year is zero, it hasn't been touched yet. */
	$q = $mysqli->query("SELECT * FROM users WHERE FIND_IN_SET('committee',`roles`)>0");

	$data = $q->fetch_assoc();

	if($data['year'] == 0) {
		print("Configuring an admin user...\n");
		/* Read config variables we need */
		$admin_name = check_opts($opts, 'admin_name', true);
		$admin_user = check_opts($opts, 'admin_user', true);
		$admin_pass = check_opts($opts, 'admin_pass', true);

		$u = user_load_from_data($mysqli, $data);
		$u['username'] = $admin_user;
		$u['firstname'] = $admin_name;
		user_change_password($mysqli, $u, $admin_pass);
		user_save($mysqli, $u);

		print("   Admin user: $admin_user\n");
	} else {
		print("Admin user already set.\n");
	}
}


/* Copy logos */

print("Creating a blank logo...\n");
system("cp files/logo-original-blank.png files/logo-original.png");
system("cp files/logo-blank.jpg files/logo.jpg");
system("cp files/logo-100-blank.jpg files/logo-100.jpg");
system("cp files/logo-500-blank.jpg files/logo-500.jpg");

print("All Done.\n");

?>

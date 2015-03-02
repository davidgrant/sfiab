Install options:

Get v16:
GIT_SSL_NO_VERIFY=true  git clone -b v3.16 https://github.com/davidgrant/sfiab

Install New:
php scripts/install.php --db_user=dave --db_pass=this --db_host=localhost --db_name=sfiab_new 
	--admin_name=admin --admin_user=admin --admin_pass=admin 
	--fair_name=fair --fair_abbr=RTF --year=2015


Install new using an existing config:
php scripts/install.php --old_config=../gvrsfold/data/config.inc.php 
	--admin_name=admin --admin_user=admin --admin_pass=admin 
	--fair_name=fair --fair_abbr=RTF --year=2015


Convert an existing SFIAB database:
php scripts/install.php --old_config=../gvrsfold/data/config.inc.php --convert



#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>

#include <glib.h>

#include "anneal.h"
#include "db.h"
#include "tours.h"
#include "students.h"
#include "judges.h"
#include "exhibithall.h"
#include "sfiab.h"

int main(int argc, char **argv) 
{
	struct _db_data *db;

	db = db_connect();

	config_load(db);

	if(strcmp(argv[1], "tours") == 0) {
		printf("Tours\n");
		tours_anneal(db, config.year);
	} else if(strcmp(argv[1], "judges") == 0) {
		printf("Judges\n");
		judges_anneal(db, config.year);
	} else if(strcmp(argv[1], "eh") == 0) {
		printf("Exhibit Hall\n");
		exhibithall_anneal(db, config.year);
	}

	db_close(db);

	return 0;

}

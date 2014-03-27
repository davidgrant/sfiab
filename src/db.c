#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <unistd.h>

#include "db.h"

const char *db_host = "127.0.0.1";
const char *db_user = "sfiab";
const char *db_pass = "gvrsf2013";
const char *db_db   = "sfiab_new";

int db_init(struct _db_data *db)
{
	mysql_init(&db->mysql);
	return 1;
}

struct _db_data *db_connect(void)
{
	struct _db_data *ret;
	ret = malloc(sizeof(struct _db_data));
	mysql_init(&ret->mysql);
	if(!mysql_real_connect(&ret->mysql, db_host, db_user, 
				db_pass, db_db, 0, NULL, 0)) {
		fprintf(stderr, "Failed to connect to database: Error: %s\n",
		mysql_error(&ret->mysql));
		free(ret);
		return NULL;
	}
	return ret;
}

int db_close(struct _db_data *db)
{
	mysql_close(&db->mysql);
	free(db);
	return 1;
}

static struct _db_row *db_fetch_row(struct _db_result *result, struct _db_row *row)
{
	row->row = mysql_fetch_row(result->result);
	/* Yes, these are suppsoed to be here, and yes they take
	 * the result parameter, not the row parameter */
	row->lengths = mysql_fetch_lengths(result->result);
	row->num_fields = mysql_num_fields(result->result);

	return row;
}


struct _db_result *db_query(struct _db_data *db, char *msg, ...)
{
	va_list args;
	char str[1024];
	struct _db_result *result;

	if(!db) {
		printf("db.c: db_query: ERROR! db parameter is NULL!\n");
		exit(-1);
	}
        va_start(args,msg);
        vsnprintf(str, 1023, msg, args);
        va_end(args);

	mysql_query(&db->mysql, str);

	result = malloc(sizeof(struct _db_result));
	result->result = mysql_store_result(&db->mysql);
	if(result->result) {
		result->rows = mysql_num_rows(result->result);
		result->fields = mysql_fetch_fields(result->result);
	} else {
/*		printf("***\n");
		printf("Warning! db_query is about to return NULL!\n");
		printf("  Query: [%s]\n", str);
		printf("***\n");*/
		result->rows = 0;
		result->fields = NULL;
	}

	/* Expand all rows */
	if(result->rows) {
		int x;
		result->row = malloc(sizeof(struct _db_row) * result->rows);
		for(x=0; x<result->rows; x++) {
			db_fetch_row(result, &result->row[x]);
		}
	}
	
	result->freed = 0;
	return result;
}

int db_insert_id(struct _db_data *db)
{
	return mysql_insert_id(&db->mysql);
}


int db_field_index(struct _db_result *result, char *field)
{
	int x;
	for(x=0;x<result->row[0].num_fields; x++) {
		if(strcmp(field, (char *)result->fields[x].name) == 0) {
			return x;
		}
	}
	return -1;
}

char *db_fetch_row_field(struct _db_result *result, int row_num, char *field)
{
	int x = db_field_index(result, field);
	if(x != -1) {
		return (char *)result->row[row_num].row[x];
	}
	printf("Could not find field '%s'\n", field);
	return NULL;
}

int db_fetch_row_field_int(struct _db_result *result, int row_num, char *field)
{
	char *p = db_fetch_row_field(result, row_num, field);
	if(p == NULL) {
		printf("db_fetch_row_field_int retrieved a NULL pointer\n");
		return 0;
	} 
	return atoi(p);

}

char *db_fetch_row_field_index(struct _db_result *result, int row_num, int field_index)
{
	return (char *)result->row[row_num].row[field_index];
}

void db_free_result(struct _db_result *result)
{
	if(!result) return;
	if(result->freed) {
		printf("db.c: trying to free an already freed result, expect random segfaults.\n");
		return;
	}

	/* Not all results have rows, so don't free them if it doesn't */
	if(result->result) mysql_free_result(result->result);

	/* Free row data, if it exists */
	if(result->rows) free(result->row);
	
	/* Signal that this is freed */
	result->freed = 1;
	free(result);
}

char *db_str(char *str, char free_old)
{
#if 0
	GString *s; 
	char *ret;
	char *orig_str = str;
	
	if(!str) { 
		return strdup("");
	}

	s = g_string_sized_new(strlen(str));

	while(*str) {
		if(strchr("'\"\\", *str)) {
			g_string_append_c(s, '\\');
		}
		g_string_append_c(s, *str);
		str++;
	}
	ret = s->str;
	g_string_free(s, FALSE);
	if(free_old) free(orig_str);

	return ret;
	#endif
	return NULL;
}

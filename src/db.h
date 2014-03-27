#ifndef DB_H
#define DB_H

#include <mysql/mysql.h>

/* This is so we can change databases just in case */
struct _db_data {
	MYSQL mysql;
};

struct _db_row {
	MYSQL_ROW row;
	int num_fields;
	unsigned long *lengths;
};

struct _db_result {
	MYSQL_RES *result;
	MYSQL_FIELD *fields;
	char freed;

	int rows;
	struct _db_row *row;
};

int db_init(struct _db_data *db);
struct _db_data *db_connect(void);
int db_close(struct _db_data *db);
struct _db_result *db_query(struct _db_data *db, char *msg, ...);
char *db_fetch_row_field(struct _db_result *result, int row_num, char *field);
int db_fetch_row_field_int(struct _db_result *result, int row_num, char *field);
void db_free_result(struct _db_result *result);
char *db_str(char *str, char free_old);
char *db_fetch_row_field_index(struct _db_result *result, int row_num, int field_index);
int db_field_index(struct _db_result *result, char *field);
int db_insert_id(struct _db_data *db);


#define dbize db_str


#endif

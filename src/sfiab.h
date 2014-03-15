#ifndef SFIAB_H
#define SFIAB_H

#include <glib.h>

#include "db.h"

struct _category
{
	int id;
	char *name;
	char *shortform;
};

struct _isef_division
{
	int id;
	int parent;
	int num_similar;
	int *similar;
	int *similar_mask;
	char *name;
	char *div;
};

extern GPtrArray *categories;
extern GPtrArray *isef_divisions;

void categories_load(struct _db_data *db, int year);
struct _category *category_find(int cat_id);

void isef_divisions_load(struct _db_data *db, int year);

#endif

#ifndef TOURS_H
#define TOURS_H

#include <glib.h>

struct _tour {
	int id;
	char *name;
	int grade_min, grade_max;
	int capacity_min, capacity_max;
	int index;
};

extern GPtrArray *tours;

void tours_anneal(struct _db_data *db, int year);
void tours_load(struct _db_data *db, int year);

#endif

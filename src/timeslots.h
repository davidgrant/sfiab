#ifndef TIMESLOTS_H
#define TIMESLOTS_H

#include <glib.h>
#include <db.h>

#define TIMESLOT_SPECIAL (-1)
#define TIMESLOT_CUSP (-2)
#define TIMESLOT_BREAK (-3)
#define TIMESLOT_UNAVAILABLE (-4)

struct _timeslot {
	int id;
	char *name;
	int year;
	int round;
	int start;
	int num_timeslots;
	int timeslot_length;
};

struct _timeslot_matrix {
	int **ts;
	int num_timeslots;
	int num_projects;
};

extern GPtrArray *timeslots;

struct _timeslot_matrix *timeslot_matrix_alloc(int num_projects, int num_timeslots);

int timeslot_fill(struct _timeslot_matrix *timeslot_matrix, int num_judges);
int timeslot_adjust_for_unavailable_slot(struct _timeslot_matrix *timeslot_matrix, int unavailble_project, int unavailable_timeslot);
void timeslots_load(struct _db_data *db, int year);
struct _timeslot *timeslot_find_for_round(int round);

#endif


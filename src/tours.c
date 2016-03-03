#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <math.h>

#include <glib.h>

#include "anneal.h"
#include "db.h"
#include "tours.h"
#include "students.h"

GPtrArray *tours;
static int current_year = 0;

static void scheduler_log(struct _db_data *db, int result, char *msg, ...)
{
	va_list args;
	char buffer[1024];
        va_start(args,msg);
        vsprintf(buffer, msg, args);
        va_end(args);

	db_query(db, "INSERT INTO log (`ip`,`time`,`year`,`type`,`data`,`result`) "
					"VALUES ('commandline',NOW(),'%d','tour_scheduler','%s','%d')",
					current_year, buffer, result);
}

struct _tour *tour_find(int id)
{
	int x;
	for(x=0;x<tours->len;x++) {
		struct _tour *t = g_ptr_array_index(tours, x);
		if(t->id == id) return t;
	}
	return NULL;
}


int tours_propose_move(struct _annealer *annealer, struct _anneal_move *move)
{
	float f = (float)rand() / (float)RAND_MAX;
	int i, x, worst_rank;
	struct _tour *t;
	struct _student *s1, *s2;
	struct _anneal_bucket *bucket;
	if(f > 0.2) {
		return -1;
	}

	move->b1 = rand() % (annealer->num_buckets);
	t = g_ptr_array_index(tours, move->b1);

	worst_rank = -1;
	bucket = &annealer->buckets[move->b1];
	s1 = NULL;
	for(x=0;x<bucket->items->len; x++) {
		struct _student *s = g_ptr_array_index(bucket->items, x);

		for(i=0;i<3;i++) {
			/* Find a student not assigned to their top tour choice */
			if(s->tour_id_pref[i] == t->id && i != 0) {
				if(i > worst_rank) {
					worst_rank = i;
					s1 = s;
					break;
				}
			}
		}
		if(i==3) {
			/* This student doesn't have any of their top choices */
			worst_rank = 3;
			s1 = s;
		}
	}

	if(s1 == NULL) return -1;

	move->p1 = s1;
	move->i1 = s1->index;

	/* Now pick a to bucket in thier top choices */
	t = NULL;
/*	if(f > 0.15 && s1->tour_id_pref[2] > 0) {
		t = tour_find(s1->tour_id_pref[2]);
	} else if(f > 0.10 && s1->tour_id_pref[1] > 0) {
		t = tour_find(s1->tour_id_pref[1]);
	} else */ if(s1->tour_id_pref[0] > 0) {
		t = tour_find(s1->tour_id_pref[0]);
	}

	if(t == NULL) {
		return -1;
	}


	move->b2 = t->index;

	if(move->b1 == move->b2) return -1;

	f = (float)rand() / (float)RAND_MAX;
	if(f > 0.5) {
		i = rand() % annealer->buckets[move->b2].items->len;
		s2 = g_ptr_array_index(annealer->buckets[move->b2].items, i);
		move->i2 = s2->index;
		move->p2 = s2;
	} else {
		move->i2 = -1;
		move->p2 = NULL;
	}
	return 0;
}

float tours_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
/* The cost function is:
	- Foreach student in a tour
		+15 - Above the grade level
		+25 - Below the grade level
		+2 - Noone from the same school
		If ranked (rank=1,2,3,4,...):
		+(rank*rank*5 - 5) = +0, +15, +40, +75
		If not ranked and max choices specified
		+(max_choices*max_choices*5) (always greater than ranked)
		else max choices not specified 
		+((max_choices-1)*(max_choices-1)*5)
	- Foreach tour
		+100 for each student above the capacity
		+200 for each student below 1/4 the capacity,but
			zero if the tour is empty

Notes:
	- If a student doesn't fill in all their choices, we don't want to give
	  them an unfair scheduling advantage.  They'll significantly increase
	  the cost if they don't get their chosen tour, whereas someone who
	  specifies all the choices will gradually increase the cost.  So, we
	  want to make it "more ok" for the annealer to place someone who
	  hasn't ranked their max number of tours in any tour, and make it
	  "less ok" for someone who has specified all the rankings to be placed
	  anywhere. 
*/


	int x;
	float cost = 0;

	/* Each bucket is a tour that maps 1:1 to the tours list */
	struct _tour *t = g_ptr_array_index(tours, bucket_id);

	if(bucket->len < t->capacity_min) {
		/* Under capacity */
		int under_by = t->capacity_min - bucket->len;
		cost += 200 * under_by;
	}

	if(bucket->len > t->capacity_max) {
		/* Over capacity */
		int over_by = bucket->len - t->capacity_max;
		cost += 100 * over_by;
	}

//	TRACE("Under min=$min, over max=$max\n");
//	TRACE("($bucket_id) {$t['id']} #{$t['num']} {$t['name']}  (cap:{$t['capacity']} grade:{$t['grade_min']}-{$t['grade_max']})\n");
//
	/* Buckets are students, compute the cost of this bucket */
	for(x=0;x<bucket->len;x++) {
		struct _student *s = g_ptr_array_index(bucket, x);
		int i, match;
		int rank_cost = -1;

		/* See if this student has ranked this tour */
		for(i=0;i<3;i++) {
			if(s->tour_id_pref[i] == t->id || s->tour_id_pref[i] == -1) {
				/* Yes (or unranked slot found) */
				rank_cost = i * i * 5;
				break;
			}
		}

		if(rank_cost == -1) {
			/* No match and no empty slots, this student really shoudln't be here */
			rank_cost = 3 * 3 * 5;
		}

		cost += rank_cost;

		if(s->grade < t->grade_min) cost += 15;
		if(s->grade > t->grade_max) cost += 25;

		/* Find another student from the same school */
		match = 0;
		for(i=0;i<bucket->len;i++) {
			struct _student *s2 = g_ptr_array_index(bucket, i);
			if(x==i) continue;
			if(s->schools_id == s2->schools_id) {
				match = 1;
				break;
			}
		}
		if(!match) {
			cost += 2;
		}
	}
	return cost;
}

void tours_load(struct _db_data *db, int year)
{
	int x, index;
	struct _db_result *result;

	tours = g_ptr_array_new();
	/* Load tours  */
	result = db_query(db, "SELECT * FROM tours WHERE year='%d'", year);
	index = 0;
	for(x=0;x<result->rows; x++) {
		struct _tour *t = malloc(sizeof(struct _tour));
		char *name = db_fetch_row_field(result, x, "name");
		if(name == NULL) continue;
		t->name = strdup(name);
		t->id = atoi(db_fetch_row_field(result, x, "id"));
		t->grade_min = atoi(db_fetch_row_field(result, x, "grade_min"));
		t->grade_max = atoi(db_fetch_row_field(result, x, "grade_max"));
		t->capacity_min = atoi(db_fetch_row_field(result, x, "capacity_min"));
		t->capacity_max = atoi(db_fetch_row_field(result, x, "capacity_max"));
		if(t->capacity_max == 0) continue;
		t->index = index++;
		printf("%d: grade %d-%d, capacity %d-%d, %s\n", 
			t->id, t->grade_min, t->grade_max, t->capacity_min, t->capacity_max, t->name);
		g_ptr_array_add(tours, t);
	}
	db_free_result(result);
}

static struct _db_data *global_db;
void tours_progress_callback(float progress)
{
	static float last_progress = 0.0;

	if(progress - last_progress > 0.1) {
		int percent = 10 + (int)(80 * progress);
		last_progress = progress;

		scheduler_log(global_db, percent, "Assigning Tours");
//		printf("Progress: %d%%\n", percent);
	}
}



void tours_anneal(struct _db_data *db, int year)
{
	int x, i;
	int rank_count[4] = {0, 0, 0, 0};
	GPtrArray **tour_assignments;

	scheduler_log(db, 5, "Loading Data");

	global_db = db;
	current_year = year;

	tours_load(db, year);
	students_load(db, year);

	/* Assign students to tours */
	scheduler_log(db, 10, "Assigning Tours");
	tour_assignments = NULL;
	anneal(NULL, &tour_assignments, tours->len, students, 
			&tours_cost, &tours_propose_move, &tours_progress_callback);

	for(x=0;x<tours->len;x++) {
		GPtrArray *ta = tour_assignments[x];
		struct _tour *t = g_ptr_array_index(tours, x);
		printf("%d: grade %d-%d, students %d, capacity %d-%d, %s\n", 
			t->id, t->grade_min, t->grade_max, ta->len, t->capacity_min, t->capacity_max, t->name);
		for(i=0; i<ta->len; i++) {
			struct _student *s = g_ptr_array_index(ta, i);
			int j, r;
			printf("    %s: grade %d, school %d,  ", s->name, s->grade, s->schools_id);
			r = -1;
			for(j=0;j<3;j++) {
				if(s->tour_id_pref[j] == t->id) {
					r = j;
					break;
				}
			}
			rank_count[r == -1 ? 3 : r] += 1;
			printf(" ranked %d,   (%d %d %d) id=%d\n",r, s->tour_id_pref[0], s->tour_id_pref[1], s->tour_id_pref[2], s->id);
		}
	}
	printf("Students who got their first, second, third, no choice: %d, %d, %d, %d = %d\n", 
			rank_count[0], rank_count[1], rank_count[2], rank_count[3], 
			rank_count[0] + rank_count[1] + rank_count[2] + rank_count[3]);

	scheduler_log(db, 90, "Writing back results.");

	/* Write results back to db */
	printf("Writing tours back to students\n");
	for(x=0;x<tours->len;x++) {
		GPtrArray *ta = tour_assignments[x];
		struct _tour *t = g_ptr_array_index(tours, x);
		for(i=0; i<ta->len; i++) {
			struct _student *s = g_ptr_array_index(ta, i);
			db_query(db, "UPDATE users SET tour_id='%d' WHERE uid='%d'", t->id, s->id);
		}
	}
	scheduler_log(db, 100, "Done.");
	
	printf("All done!\n");
}


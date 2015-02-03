#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <assert.h>

#include <glib.h>

#include "anneal.h"
#include "db.h"
#include "students.h"
#include "sfiab.h"
#include "timeslots.h"

GPtrArray *timeslots = NULL;


struct _timeslot_matrix *timeslot_matrix_alloc(int num_projects, int num_timeslots)
{
	void *d = malloc(sizeof(struct _timeslot_matrix)
			+ sizeof(int) * num_projects * num_timeslots
			+ sizeof(int *) * num_projects);
	struct _timeslot_matrix *m;
	int iproject, itimeslot;


	m = d;
	m->num_projects = num_projects;
	m->num_timeslots = num_timeslots;

	d += sizeof(struct _timeslot_matrix);
	m->ts = d;

	d += sizeof(int *)*num_projects;

	for(iproject = 0; iproject < num_projects; iproject++) {
		m->ts[iproject] = (int *)d;
		d += sizeof(int) * num_timeslots;

		for(itimeslot = 0; itimeslot<num_timeslots; itimeslot++) {
			m->ts[iproject][itimeslot] = TIMESLOT_UNAVAILABLE;
		}
	}
	return m;
}

void timeslot_matrix_free(struct _timeslot_matrix *m)
{
	free(m);
}

/* The schedule:
 * - require that projects <= timeslots
 * - Spread out the num_judges divisional judges as much as possible
 * - Then alternate between break and special filling in the rest
 *        
 * - For 9 timeslots, 3 judges, and 7 projects it looks like this:
 *   J0 S - J1 S - J2 S -
 *   */

int timeslot_create_schedule(int *schedule, int num_timeslots, int num_judges, int num_projects)
{
	float stride;
	float stride_total;
	int next_type;
	int t, j, i;

	/* Create a schedule given the number of timeslots, judges, and projects */
	assert(num_projects <= num_timeslots);

	for(i=0;i<num_timeslots;i++) {
		schedule[i] = -1;
	}

	stride_total = 0;
	stride = (float)(num_timeslots) / (float)(num_judges);

//	printf("Stride: %f\n", stride);
	for(j=0; j<num_judges; j++) {
		int t = (int)(stride_total + 0.5);
		schedule[t] = j;
//		printf("j[%d] at sched[%d], totla = %f, rounded=%d\n", j, t, stride_total, t);
		stride_total += stride;
	}

	next_type = TIMESLOT_SPECIAL;
	for(t=0;t<num_timeslots;t++) {
		if(schedule[t] == -1) {
			schedule[t] = next_type;
			next_type = (next_type == TIMESLOT_SPECIAL) ? TIMESLOT_BREAK : TIMESLOT_SPECIAL;
		}
	}

	printf("Schedule:");
	for(t=0;t<num_timeslots;t++) {
		switch(schedule[t]) {
		case TIMESLOT_SPECIAL:
			printf(" S");
			break;
		case TIMESLOT_BREAK:
			printf(" -");
			break;
		default:
			printf(" %d", schedule[t]);
			break;
		}
	}
	printf("\n");
	return 1;
}


int timeslot_has_conflict(struct _timeslot_matrix *m, int itimeslot, int type)
{
	int iproject;

	if(type < 0) {
		/* Special types never conflict */
		return 0;
	}

	/* Does the timeslot itimeslot have something else on this row of type? */
	for(iproject=0; iproject<m->num_projects; iproject++) {
		if(m->ts[iproject][itimeslot] == type) {
			return 1;
		}
	}
	return 0;
}

/* filling the timeslots takes the schedule above and lays it out vertically starting with
 * the first project at timesslot 0, for this example,  7 projects, 3 judges, 9 timeslots
 * the schedule is: J0 S - J1 S - J2 S -
 * But to make it interesting and to create artifical conflicts, let's say
 * the schedule is 0 1 - - - - - - 2
 *
 * Stamp out the schedule vertically first
 *
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 
 *      ts 1 > 1
 *      ts 2 > -
 *      ts 3 > -
 *      ts 4 > -
 *      ts 5 > -
 *      ts 6 > -
 *      ts 7 > - 
 *      ts 8 > 2
 *             ^ Schedule
 *
 *  Then move on to the next judge at timeslot 0, realign the schedule to start at the
 *  index of judge 1, and move down stamping out the schedule, wrap around when at the bottom
 *
 *  Do the same in timeslot 0 until we run out of judges
 *
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 1 2 
 *      ts 1 > 1 - 0
 *      ts 2 > - - 1
 *      ts 3 > - - -
 *      ts 4 > - - -
 *      ts 5 > - - -
 *      ts 6 > - - -
 *      ts 7 > - 2 -
 *      ts 8 > 2 0 -
 *               ^ Schedule starting at sched[1] for judge 1
 *
 *  When we run out of judges, go back to judge 0 (so the schedule starts at schedule[0]), but
 *  then move down one timeslot.  Check that we can actually start a new schedule for project 4
 *  with judge 0 in timeslot 1 (we can't), so skip it and try judge 1 (can't) skip and try judge 2
 *
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 1 2 -
 *      ts 1 > 1 - 0 2
 *      ts 2 > - - 1 0
 *      ts 3 > - - - 1
 *      ts 4 > - - - -
 *      ts 5 > - - - -
 *      ts 6 > - - - -
 *      ts 7 > - 2 - -
 *      ts 8 > 2 0 - -
 *                   ^ Schedule starting at sched[8] for judge 2, shifted down one timeslot
 *
 *  Now we've hit judge 2 on timeslot 1, move to timeslot 2, and start the checks at judge 0
 *  (can't) judge 1 (can't) and judge 2 again..  Repeat until we run out of projects.
 *                   
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 1 2 - - - - 
 *      ts 1 > 1 - 0 2 - - -
 *      ts 2 > - - 1 0 2 - - 
 *      ts 3 > - - - 1 0 2 -
 *      ts 4 > - - - - 1 0 2
 *      ts 5 > - - - - - 1 0 
 *      ts 6 > - - - - - - 1
 *      ts 7 > - 2 - - - - - 
 *      ts 8 > 2 0 - - - - - 
 *
 *  This is guaranteed to never put the same judge in the same timeslot for two different
 *  projects.  We could just as easily have started with project 1 and timeslot 0, then proceeded
 *  vertically down always starting with judge 0, but 
 *  - that wouldn't give judge 0 any breaks, unless we stride, which we could do
 *  - It would put more breaks at the beginning of the scheulde, we'd prefer to front-load
 *    the judges, e.g, always having all 3 judges busy in timeslot 0
 *  - it would also front-load breaks in the schedule.. we want them near the
 *    end as much as possible.
 */

int timeslot_fill(struct _timeslot_matrix *timeslot_matrix, int num_judges)
{
	int *schedule = malloc(timeslot_matrix->num_timeslots * sizeof(int));
	int *judge_start_index = malloc(num_judges * sizeof(int));
	int iproject, itimeslot;
	int start_timeslot_offset, start_judge;

	timeslot_create_schedule(schedule, timeslot_matrix->num_timeslots, num_judges, timeslot_matrix->num_projects);

	/* Find the index where each judge starts*/
	for(itimeslot=0;itimeslot<timeslot_matrix->num_timeslots;itimeslot++) {
		if(schedule[itimeslot] >= 0) {
			judge_start_index[schedule[itimeslot]] = itimeslot;
		}
	}

	/* Fill everything with unavailable */
	for(iproject=0; iproject<timeslot_matrix->num_projects;iproject++) {
		for(itimeslot=0;itimeslot<timeslot_matrix->num_timeslots;itimeslot++) {
			timeslot_matrix->ts[iproject][itimeslot] = TIMESLOT_UNAVAILABLE;
		}
	}

	start_timeslot_offset = 0;
	start_judge = 0;
	for(iproject=0; iproject<timeslot_matrix->num_projects; iproject++) {
		int t;

		/* Stop if things are misconfigured and there is no solution */
		assert(start_timeslot_offset < timeslot_matrix->num_timeslots);

		/* See if start_judge is allowed to start on this row */
		if(!timeslot_has_conflict(timeslot_matrix, start_timeslot_offset, start_judge)) {
			/* The first item in the row is the judge start index - the timeslot offset */
			t = judge_start_index[start_judge] - start_timeslot_offset;
			/* Might be negative, wrap backwards if so */
			if(t < 0) t += timeslot_matrix->num_timeslots;

			for(itimeslot=0; itimeslot<timeslot_matrix->num_timeslots; itimeslot++) {
				timeslot_matrix->ts[iproject][itimeslot] = schedule[t];
//				printf("Timeslot [%d][%d] (%d) = [%d] %d\n", i, j, index, t, schedule[t]);
				t++;
				if(t == timeslot_matrix->num_timeslots) t=0;
			}
		} else {
			/* Skip assignment, retry this project with the next judge */
			iproject--;
		}

		start_judge++;
		if(start_judge == num_judges) {
			start_judge = 0;
			start_timeslot_offset++;
		}
	}


	free(schedule);
	free(judge_start_index);

	return 1;

}


int timeslot_adjust_for_unavailable_slot(struct _timeslot_matrix *timeslot_matrix, int unavailable_project, int  unavailable_timeslot)
{
	/* Mark timeslot=unavailable_timeslot project=unavaible_project as unavilable.
	 * Try to shuffle around the schedule to accommodate */
	int curr_type, itimeslot, iproject;

	curr_type = timeslot_matrix->ts[unavailable_project][unavailable_timeslot];
	timeslot_matrix->ts[unavailable_project][unavailable_timeslot] = TIMESLOT_UNAVAILABLE;

	printf("   Resolving unavailable slot (p=%d,ts=%d)=%d\n", unavailable_project, unavailable_timeslot, curr_type);

	if(curr_type == TIMESLOT_BREAK || curr_type == TIMESLOT_UNAVAILABLE) {
		/* No need to adjust anything */
		printf("      Slot is a break slot, marked as unavailable\n");
		return 1;
	}

	/* First attempt, just move whatever is here to an empty timeslot for the same
	 * project that doesn't conflict with any other project */
	for(itimeslot=0; itimeslot<timeslot_matrix->num_timeslots; itimeslot++) {
		int type = timeslot_matrix->ts[unavailable_project][itimeslot];

		if(type == TIMESLOT_BREAK) {
			/* Is curr_type allow on this row? */
			if(!timeslot_has_conflict(timeslot_matrix, itimeslot, curr_type)) {
				/* There is no conflict, put it here */
				timeslot_matrix->ts[unavailable_project][itimeslot] = curr_type;
				printf("      Moved to (p=%d,ts=%d) with no conflict\n", unavailable_project, itimeslot);
				return 1;
			}
		}
	}

	/* Ok, now we have to do something more complicated and get other projects involved, we're going
	 * to attempt a 4pt move:
	 *    un_project,un_timeslot => conflicting un_project,itimeslot 
	 *    iproject,itimeslot that was conflicting => iproject,un_timeslot only if it is a break
	 *    iproject,itimeslot becomes the break for iproject
	 *    */
	for(itimeslot=0; itimeslot<timeslot_matrix->num_timeslots; itimeslot++) {
		int type = timeslot_matrix->ts[unavailable_project][itimeslot];

		/* Find a break slot for the unavaiable project */
		if(type != TIMESLOT_BREAK)
			continue;

		/* We know it's not allowed here for a conflict, so, 
		 * search all projects for the conflict and see if the conflict
		 * for another project can be moved to the original unavailable timeslot */
		for(iproject=0; iproject<timeslot_matrix->num_projects; iproject++) {

			int isecond_timeslot;

			/* Skip current project, find the other project with the same type in this timeslot */
			if(iproject == unavailable_project) 
				continue;

			if(timeslot_matrix->ts[iproject][itimeslot] != curr_type) 
				continue;

			/* Found it, [iproject][itimeslot] has the same type as [unavailable_project][itimeslot].
			 * Search all timeslots to see if there's another spot we can put 
			 * ts[iproject][itimeslot] without conflict */
			for(isecond_timeslot = 0; isecond_timeslot < timeslot_matrix->num_timeslots; isecond_timeslot++) {

				/* Skip current timeslot */
				if(isecond_timeslot == itimeslot) 
					continue;

				if(timeslot_matrix->ts[iproject][isecond_timeslot] != TIMESLOT_BREAK) 
					continue;

				/* Check for conflict if we were to move timeslot_matrix[iproject][itimeslot] here 
				 * and overwrite the break in [iproject][unavailable_timeslot] */
				if(timeslot_has_conflict(timeslot_matrix, 
							isecond_timeslot,
							timeslot_matrix->ts[iproject][itimeslot])) {
					/* Yes, there's a conflct, we can't do it */
					continue;
				}

				/* Do the move */
				timeslot_matrix->ts[iproject][isecond_timeslot] = timeslot_matrix->ts[iproject][itimeslot];
				timeslot_matrix->ts[iproject][itimeslot] = TIMESLOT_BREAK;
				timeslot_matrix->ts[unavailable_project][itimeslot] = curr_type;

				printf("      Did a multiswap: (p=%d,ts=%d) %d <=> (p=%d,ts=%d) BREAK; (p=%d,ts=%d) %d <=> (p=%d,ts=%d) BREAK\n",
							unavailable_project, unavailable_timeslot, curr_type,
							unavailable_project, itimeslot,
							iproject, itimeslot, timeslot_matrix->ts[iproject][isecond_timeslot],
							iproject, isecond_timeslot);
				return 1;
			}
		}
	}

	printf("Unable to find a resolution, probably have to write an annealer\n");
	assert(0);

}


void timeslots_load(struct _db_data *db, int year)
{
	int x;
	struct _db_result *result;
	timeslots = g_ptr_array_new();
	/* Load judges  */
	result = db_query(db, "SELECT * FROM timeslots WHERE year='%d'", year);
	for(x=0;x<result->rows; x++) {
		struct _timeslot *t = malloc(sizeof(struct _timeslot));
		t->name = strdup(db_fetch_row_field(result, x, "name"));
		t->id = atoi(db_fetch_row_field(result, x, "id"));
		t->year = atoi(db_fetch_row_field(result, x, "year"));
		t->start = atoi(db_fetch_row_field(result, x, "start"));
		t->round = atoi(db_fetch_row_field(result, x, "round"));
		t->num_timeslots = atoi(db_fetch_row_field(result, x, "num_timeslots"));
		t->timeslot_length = atoi(db_fetch_row_field(result, x, "timeslot_length"));

		printf("%d: %s: %d timeslots, round id %d\n", 
			t->id, t->name, t->num_timeslots, t->round);
		g_ptr_array_add(timeslots, t);
	}
	db_free_result(result);
}

struct _timeslot *timeslot_find_for_round(int round)
{
	int x;
	for(x=0; x<timeslots->len; x++) {
		struct _timeslot *ts = g_ptr_array_index(timeslots, x);
		if(ts->round == round) {
			return ts;
		}
	}
	return NULL;
}


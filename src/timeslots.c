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

void l_timeslot_print_schedule(int *schedule, int num_timeslots);



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
//	assert(num_projects <= num_timeslots);

	for(i=0;i<num_timeslots;i++) {
		schedule[i] = -1;
	}

	stride_total = 0;
	stride = (float)(num_timeslots) / (float)(num_judges);

	/* Populate div judges first so we are guaranteed to send all the judges, spreading them out
	 * as much as possible */
//	printf("Stride: %f\n", stride);
	for(j=0; j<num_judges; j++) {
		int t = (int)(stride_total + 0.5);
		schedule[t] = j;
//		printf("j[%d] at sched[%d], totla = %f, rounded=%d\n", j, t, stride_total, t);
		stride_total += stride;
	}

	/* Alternate special/break in all remaining timeslots */
	next_type = TIMESLOT_SPECIAL;
	for(t=0;t<num_timeslots;t++) {
		if(schedule[t] == -1) {
			schedule[t] = next_type;
			next_type = (next_type == TIMESLOT_SPECIAL) ? TIMESLOT_BREAK : TIMESLOT_SPECIAL;
		}
	}
	l_timeslot_print_schedule(schedule, num_timeslots);
	return 1;
}

void l_timeslot_print_schedule(int *schedule, int num_timeslots)
{
	int itimeslot;
	printf("Schedule:");
	for(itimeslot=0; itimeslot<num_timeslots; itimeslot++) {
		switch(schedule[itimeslot]) {
		case TIMESLOT_SPECIAL:
			printf(" S");
			break;
		case TIMESLOT_BREAK:
			printf(" -");
			break;
		default:
			printf(" %d", schedule[itimeslot]);
			break;
		}
	}
	printf("\n");
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
 * the project schedule is: 0 S - 1 S - 2 S -    ( 0 == judge 0, S = special, - = =break )
 * But to make it interesting and to create artificial conflicts, let's say
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
 *  Increment the start_timeslot to schedule index 1, line that up at timeslot 0, and stamp it out again
 *  Do the same again with schedule index 2
 *
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 1 - 
 *      ts 1 > 1 - -
 *      ts 2 > - - -
 *      ts 3 > - - -
 *      ts 4 > - - -
 *      ts 5 > - - -
 *      ts 6 > - - 2
 *      ts 7 > - 2 0
 *      ts 8 > 2 0 1
 *               
 *  Keep moving the schedule start index forward and stamping it out starting at timeslot 0
 *                   
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 1 - - - - - 
 *      ts 1 > 1 - - - - - -
 *      ts 2 > - - - - - - 2 
 *      ts 3 > - - - - - 2 0
 *      ts 4 > - - - - 2 0 1
 *      ts 5 > - - - 2 0 1 - 
 *      ts 6 > - - 2 0 1 - -
 *      ts 7 > - 2 0 1 - - - 
 *      ts 8 > 2 0 1 - - - - 
 *
 *   One optimization that we used to have wth the more convoluted judge layout schedule is that we could
 *   keep all the judges busy in ts=0.  IN this example, we could detect that we won't to through all the schedule
 *   rotations (num projects < timeslots) so we could skip up to 2 (timeslots=0 - projects=7 = 2), so we might 
 *   instead end up with something like, skip breaks in ts=0:
 *
 *  Project => 1 2 3 4 5 6 7
 *      ts 0 > 0 1 - - - - 2 
 *      ts 1 > 1 - - - - 2 0
 *      ts 2 > - - - - 2 0 1 
 *      ts 3 > - - - - 0 1 -
 *      ts 4 > - - - 2 1 - -
 *      ts 5 > - - 2 0 - - - 
 *      ts 6 > - - 0 1 - - -
 *      ts 7 > - 2 1 - - - - 
 *      ts 8 > 2 0 - - - - - 
 */

struct _twist_data {
	int perm;
	int *data;
	int num_permutations;
	int num_per_perm;
	int num_total;
};

void twist_init(int *indexes, int judges_per_project)
{
	int i;
	for(i=0;i<judges_per_project;i++) {
		indexes[i] = i;
	}
}


int twist(int *indexes, int num_judges, int judges_per_project)
{
	int i, j, depth;

	/* Start at the rightmost, work backwards */
	depth = 0;
	for(i=judges_per_project-1; i>=0; i--,depth++) {
		if(indexes[i] < num_judges - 1 - depth) {
			/* Yes. */
			indexes[i]++;
			/* Reset all pointers to the right */
			for(j=i+1; j<judges_per_project; j++) {
				indexes[j] = indexes[j-1] + 1;
			}
			return 1;
		}
	}
	/* Reset the whole thing */
	twist_init(indexes, judges_per_project);
	return 0;
}

struct _twist_data *twist_alloc(int num_judges, int judges_per_project)
{
	struct _twist_data *d = malloc(sizeof(struct _twist_data));
	int *indexes = malloc(judges_per_project * sizeof(int));
	int i, x;

	d->data = malloc(sizeof(int) * factorial(num_judges));
	d->num_per_perm = judges_per_project;
	d->num_total = num_judges;
	d->num_permutations=0;

	/* Populate it */
	twist_init(indexes, judges_per_project);
	while(1) {
		int offset = d->num_per_perm * d->num_permutations;
		int ret;

		d->num_permutations++;
		/* Copy in the index */
		for(i=0;i<judges_per_project;i++) {
			d->data[offset+i] = indexes[i];
		}

		/* Twist it, if that fails, stop */
		ret = twist(indexes, num_judges, d->num_per_perm);
		if(!ret) break;

	}

	/* Go over all the permutations, and randomly swap them */
	for(x=0; x<d->num_permutations; x++) {
		int src_offset = x *  d->num_per_perm;
		int dst_offset = (rand() % d->num_permutations) *  d->num_per_perm;

		/* swap */
		for(i=0;i< d->num_per_perm;i++) {
			indexes[i] = d->data[src_offset+i];
			d->data[src_offset+i] = d->data[dst_offset+i];
			d->data[dst_offset+i] = indexes[i];
		}
	}
	d->perm = 0;

	return d;
}

void twist_free(struct _twist_data *data)
{
	free(data->data);
	free(data);
}

int *twist_get(struct _twist_data *data, int *reset)
{
	int *ret = &data->data[data->num_per_perm * data->perm];
	data->perm++;
	if(data->perm == data->num_permutations) {
		data->perm = 0;
		*reset = 1;
	} else {
		*reset = 0;
	}
	return ret;
}


int timeslot_fill(struct _timeslot_matrix *timeslot_matrix, int num_judges, int judges_per_project)
{
	int *schedule;
	int *judge_index;
	int iproject, itimeslot;
	int start_schedule_index;
	int fail_count, index_offset=0;
	int ret = 1;
	int skip_every = 0, skip_count = 0;
	int i, retry_count = 0;
	struct _twist_data *twist_data;

start:
	schedule = malloc(timeslot_matrix->num_timeslots * sizeof(int));
	index_offset=0;
	ret=1;
	skip_every=0;
	skip_count=0;


	twist_data = twist_alloc(num_judges, judges_per_project);

	printf("Fill for %d judges, %d judges per project\n", num_judges, judges_per_project);
	if(judges_per_project > num_judges) {
		/* Catch an impossible situation */
		printf("NOTICE: Reducing judges per project to %d because there are only %d judges\n", num_judges, num_judges);
		judges_per_project = num_judges;
	}
	timeslot_create_schedule(schedule, timeslot_matrix->num_timeslots, judges_per_project, timeslot_matrix->num_projects);

	/* Now many skips can we have? */
	i = timeslot_matrix->num_timeslots - timeslot_matrix->num_projects;
	if(i > 1) {
		skip_every = (timeslot_matrix->num_projects / i) + 1;
	}

	/* Fill everything with unavailable */
	for(iproject=0; iproject<timeslot_matrix->num_projects;iproject++) {
		for(itimeslot=0;itimeslot<timeslot_matrix->num_timeslots;itimeslot++) {
			timeslot_matrix->ts[iproject][itimeslot] = TIMESLOT_UNAVAILABLE;
		}
	}

	start_schedule_index = 0;
	fail_count = 0;
	for(iproject=0; iproject<timeslot_matrix->num_projects; iproject++) {
		int t, reset, success = 1;

		/* Note: incrementing start_schedule_index on failure is bad.
		 * It more often leads to situations where the lst project
		 * needs the same judge twice.  By forcing the correct pattern
		 * schedule and twisting more, the distribution seems to work
		 * out more often. */

		/* Try to lay down the schedule starting at start_schedule_index */
		t = start_schedule_index;
		for(itimeslot=0; itimeslot<timeslot_matrix->num_timeslots; itimeslot++) {
			if(timeslot_has_conflict(timeslot_matrix, itimeslot, schedule[t])) {
				/* Clear the attempt */
				for(i=0;i<timeslot_matrix->num_timeslots;i++) {
					timeslot_matrix->ts[iproject][i] = TIMESLOT_UNAVAILABLE;
				}
				success = 0;
				break;
			}
			timeslot_matrix->ts[iproject][itimeslot] = schedule[t];
/*			printf("Set timeslot [%d][%d] = [%d] %d\n", iproject, itimeslot, t, schedule[t]);*/
			t++;
			if(t == timeslot_matrix->num_timeslots) t=0;
		}

		if(success) {
			skip_count++;
			if(skip_count == skip_every) {
				skip_count = 0;
				start_schedule_index++;
			}
			start_schedule_index++;
//			if(start_schedule_index < 0) start_schedule_index += timeslot_matrix->num_timeslots;
			if(start_schedule_index >= timeslot_matrix->num_timeslots) start_schedule_index -= timeslot_matrix->num_timeslots;
			fail_count = 0;
			index_offset = 0;
		} else {
			/* Try this schedule again after twisting the schedule */
			iproject--;
		}

		/* twist the schedule */
		judge_index = twist_get(twist_data, &reset);

		if(reset && !success) {
			/* Schedule was reset and we're in the fail state */
			fail_count++;
			if(fail_count >= 2) {
				/* Went through an entire combination round with no success, start with permutations */
				index_offset++;
				if(index_offset == judges_per_project) {
					/* Fail with permutations too */
					printf("Reached offset=%d, judges per schedule=%d, no solution.", index_offset, judges_per_project);
					ret = 0;
					break;
				}
			}
		}

		/* fill the schedule */
		i=index_offset;
		for(itimeslot=0; itimeslot<timeslot_matrix->num_timeslots; itimeslot++) {
			if(schedule[itimeslot] >= 0) {
				schedule[itimeslot] = judge_index[i];
				i++;
				if(i==judges_per_project) i=0;
			}
		}

	}

	twist_free(twist_data);
	free(schedule);

	if(ret == 0) {
		retry_count++;
		if(retry_count < 100) {
			goto start;
		}
		timeslot_print(timeslot_matrix);
	}
	

	return ret;

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

void timeslot_print(struct _timeslot_matrix *timeslot_matrix)
{
	int iproject, itimeslot;
	printf("Matrix for %d timeslots, %d projects\n", timeslot_matrix->num_timeslots, timeslot_matrix->num_projects);
	printf("   ");
	for(iproject=0; iproject<timeslot_matrix->num_projects; iproject++) {
		printf("p%d ", iproject);
	}
	printf("\n");
	for(itimeslot=0; itimeslot<timeslot_matrix->num_timeslots; itimeslot++) {
		printf("%2d ", itimeslot);
		for(iproject=0; iproject<timeslot_matrix->num_projects; iproject++) {
			int t = timeslot_matrix->ts[iproject][itimeslot];
			switch(t) {
			case TIMESLOT_SPECIAL:
				printf(" . ");
				break;
			case TIMESLOT_CUSP:
				printf(" C ");
				break;
			case TIMESLOT_BREAK:
				printf(" - ");
				break;
			case TIMESLOT_UNAVAILABLE:
				printf(" X ");
				break;
			default:
				printf(" %d ", t);
				break;
			}
		}
		printf("\n");
	}
}


void timeslot_test(void)
{
	/* 5 projects, 5 timelsots, 3 judges, 3 judges/project */
	struct _timeslot_matrix *timeslot_matrix;
	int *judge_index = malloc(5 * sizeof(int));
	int x;

	printf("Twist test with judges_per_project=4, 7 judges total\n");
	twist_init(judge_index, 4);
	for(x=0;x<50;x++) {
		printf("[%d %d %d %d] ", judge_index[0], judge_index[1], judge_index[2], judge_index[3]);
		if(x%10 == 9) printf("\n");
		twist(judge_index, 7, 4);
	}
	printf("\n");
	free(judge_index);
	
	
	printf("Begin timeslot test\n");
	for(x=2;x<10; x++) {
		timeslot_matrix = timeslot_matrix_alloc(x, 9);
		timeslot_fill(timeslot_matrix, 3, 3);
		timeslot_print(timeslot_matrix);
		timeslot_matrix_free(timeslot_matrix);
	}

	timeslot_matrix = timeslot_matrix_alloc(10, 5);
	timeslot_fill(timeslot_matrix, 6, 3);
	timeslot_print(timeslot_matrix);
	timeslot_matrix_free(timeslot_matrix);

	timeslot_matrix = timeslot_matrix_alloc(20, 5);
	timeslot_fill(timeslot_matrix, 10, 2);
	timeslot_print(timeslot_matrix);
	timeslot_matrix_free(timeslot_matrix);

	timeslot_matrix = timeslot_matrix_alloc(7, 5);
	timeslot_fill(timeslot_matrix, 6, 4);
	timeslot_print(timeslot_matrix);
	timeslot_matrix_free(timeslot_matrix);

//	timeslot_matrix = timeslot_matrix_alloc(num_projects, num_timeslots);
//	timeslot_fill(timeslot_matrix, num_judges, times_judged);
//	timeslot_print(timeslot_matrix);
//	timeslot_matrix_free(timeslot_matrix);

	timeslot_matrix = timeslot_matrix_alloc(10, 5);
	timeslot_fill(timeslot_matrix, 6, 4);
	timeslot_print(timeslot_matrix);
	timeslot_matrix_free(timeslot_matrix);

}




/* sched = 0 S 1 2 -
 *   p0 p1 p2 p3 p4
 * 0  0  1  2  -  S
 * 1  S  2  -  0  1
 * 2  1  -  0  S  2
 * 3  2  0  S  1  -
 * 4  -  S  1  2  0
 *   
 *
 * 
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 * */




#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <assert.h>

#include <glib.h>
#include <math.h>

#include "anneal.h"
#include "db.h"
#include "exhibithall.h"
#include "students.h"
#include "sfiab.h"

GPtrArray *exhibithalls;
GPtrArray *exhibithall_objects;

struct _exhibithall;

static int grid_size = 1;

struct _point {
	int x, y;
};

#define MAX_CLOSEST 10

struct _exhibithall_object {
	int id;
	char *name;
	char has_electricity;
	struct _point p, gfront;
	int w, h;
	int orientation;
	struct _exhibithall *eh;

	struct _exhibithall_objects *closest_proejcts[MAX_CLOSEST];
	int closest_projects_distance[MAX_CLOSEST];
};

struct _exhibithall {
	int id;
	char *name;
	int *challenges;
	int num_challenges;
	int *cats;
	int num_cats;
	int w,h;
	int grid_w, grid_h;
	GPtrArray **objects_at;
	struct _exhibithall_object **project_front;

	GPtrArray *project_objects;

};


struct _exhibithall *exhibithall_find(int id)
{
	int x;
	for(x=0;x<exhibithalls->len;x++) {
		struct _exhibithall *t = g_ptr_array_index(exhibithalls, x);
		if(t->id == id) return t;
	}
	return NULL;
}


struct _point point_rotate(struct _point p, int deg)
{
	double rad = -(double)deg * (M_PI/180.0);
	struct _point r;
	r.x = (double)p.x*cos(rad) - (double)p.y*sin(rad);
	r.y = (double)p.x*sin(rad) + (double)p.y*cos(rad);
	return r;
}

struct _point point_translate(struct _point p, int dx, int dy)
{
	p.x += dx;
	p.y += dy;
	return p;
}

int point_is_in_object(struct _point p, struct _exhibithall_object *o)
{
	
	/* Translate the point to the object origin */
	p = point_translate(p, o->p.x, o->p.y);
	/* Rotate the point to the object's frame of reference*/
	p = point_rotate(p, -o->orientation);
	/* Is it within the object now ? */
	if(labs(p.x) <= o->w/2 && labs(p.y) <= o->h/2)
		return 1;
	return 0;
}

int grid_index(struct _exhibithall *eh, struct _point grid_point)
{
	return (eh->grid_w * grid_point.y) + grid_point.x;
}

int l_distance(struct _point p1, struct _point p2)
{
	return sqrt( (p1.x-p2.x)*(p1.x-p2.x) + (p1.y-p2.y)*(p1.y-p2.y) );
}
int l_grid_distance(struct _point p1, struct _point p2)
{
	return sqrt( ((p1.x-p2.x)*(p1.x-p2.x) + (p1.y-p2.y)*(p1.y-p2.y) )*grid_size );
}

int l_grid_manhat_distance(struct _point p1, struct _point p2)
{
	return (labs(p1.x - p2.x) + labs(p1.y - p2.y))*grid_size;
}

int l_manhat_distance(struct _point p1, struct _point p2)
{
	return labs(p1.x - p2.x) + labs(p1.y - p2.y);
}


#if 0
float exhibithalls_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
/* The cost function is:
	- Foreach student in a exhibithall
		+15 - Above the grade level
		+25 - Below the grade level
		+2 - Noone from the same school
		If ranked (rank=1,2,3,4,...):
		+(rank*rank*5 - 5) = +0, +15, +40, +75
		If not ranked and max choices specified
		+(max_choices*max_choices*5) (always greater than ranked)
		else max choices not specified 
		+((max_choices-1)*(max_choices-1)*5)
	- Foreach exhibithall
		+100 for each student above the capacity
		+200 for each student below 1/4 the capacity,but
			zero if the exhibithall is empty

Notes:
	- If a student doesn't fill in all their choices, we don't want to give
	  them an unfair scheduling advantage.  They'll significantly increase
	  the cost if they don't get their chosen exhibithall, whereas someone who
	  specifies all the choices will gradually increase the cost.  So, we
	  want to make it "more ok" for the annealer to place someone who
	  hasn't ranked their max number of exhibithalls in any exhibithall, and make it
	  "less ok" for someone who has specified all the rankings to be placed
	  anywhere. 
*/


	int x;
	float cost = 0;

	/* Each bucket is a exhibithall that maps 1:1 to the exhibithalls list */
	struct _exhibithall *t = g_ptr_array_index(exhibithalls, bucket_id);

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

		/* See if this student has ranked this exhibithall */
		for(i=0;i<3;i++) {
			if(s->exhibithall_id_pref[i] == t->id || s->exhibithall_id_pref[i] == -1) {
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
#endif

void exhibithalls_load(struct _db_data *db, int year)
{
	int x;
	struct _db_result *result;

	exhibithalls = g_ptr_array_new();
	exhibithall_objects = g_ptr_array_new();

	/* Load exhibithalls  */
	result = db_query(db, "SELECT * FROM exhibithall WHERE `type`='exhibithall'");
	for(x=0;x<result->rows; x++) {
		struct _exhibithall *t = malloc(sizeof(struct _exhibithall));
		char *p;
		t->id = atoi(db_fetch_row_field(result, x, "id"));
		t->name = strdup(db_fetch_row_field(result, x, "name"));
		t->challenges = malloc(sizeof(int) * 64);
		t->cats = malloc(sizeof(int) * 64);
		p = db_fetch_row_field(result, x, "challenges");
		t->num_challenges = split_int_list(t->challenges, p);
		p = db_fetch_row_field(result, x, "cats");
		t->num_cats = split_int_list(t->cats, p);
		t->w = atof(db_fetch_row_field(result, x, "w")) * 1000.0;
		t->h = atof(db_fetch_row_field(result, x, "h")) * 1000.0;
		t->project_objects = g_ptr_array_new();

		printf("%d: %s\n", t->id, t->name);
		g_ptr_array_add(exhibithalls, t);

	}
	db_free_result(result);

	/* Load exhibithall_objects   */
	result = db_query(db, "SELECT * FROM exhibithall WHERE `type`='project'");
	for(x=0;x<result->rows; x++) {
		struct _exhibithall_object *t = malloc(sizeof(struct _exhibithall_object));
		t->id = atoi(db_fetch_row_field(result, x, "id"));
		t->name = strdup(db_fetch_row_field(result, x, "name"));
		t->has_electricity = atoi(db_fetch_row_field(result, x, "has_electricity"));
		t->eh = exhibithall_find(atoi(db_fetch_row_field(result, x, "exhibithall_id")));
		t->p.x = atof(db_fetch_row_field(result, x, "x")) * 1000.0;
		t->p.y = atof(db_fetch_row_field(result, x, "y")) * 1000.0;
		t->w = atof(db_fetch_row_field(result, x, "w")) * 1000.0;
		t->h = atof(db_fetch_row_field(result, x, "h")) * 1000.0;
		t->orientation = atof(db_fetch_row_field(result, x, "orientation"));
		printf("%d: %s:%s, (%d,%d) %dx%d @ %d, has_elec=%d\n", t->id, t->eh->name, t->name, 
				t->p.x, t->p.y, t->w, t->h, t->orientation, t->has_electricity);
		g_ptr_array_add(exhibithall_objects, t);
		g_ptr_array_add(t->eh->project_objects, t);
	}
	db_free_result(result);
}


void l_assign_objects_to_grid_locations(void)
{
	int x,y,i,j;
	for(i=0;i<exhibithalls->len;i++) {
		struct _exhibithall *eh = g_ptr_array_index(exhibithalls, i);

		for(x=0;x<eh->grid_w;x++) {
			for(y=0;y<eh->grid_h; y++) {
				struct _point p, gp;
				int gi;
				p.x = x * grid_size;
				p.y = y * grid_size;

				gp.x = x;
				gp.y = y;
				gi = grid_index(eh, gp);

				for(j=0;j<exhibithall_objects->len; j++) {
					struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, j);

					if(o->eh != eh) continue;

					if(point_is_in_object(p, o)) {
						g_ptr_array_add(eh->objects_at[gi], o);
					}
				}
			}
		}
	}

}



void l_compute_project_front_grid_locations(void)
{
	int x,y,i,gx,gy;
	struct _point grid_loc;
	int gi;

	for(i=0;i<exhibithall_objects->len;i++) {
		struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, i);
		struct _point front, gfront;
		int d, smallest_d;
		int smallest_gx, smallest_gy;
		int found;

		/* The front is here (if orientation were 0) */
		front.x = 0;
		front.y = o->h >> 1;

		/* Translate and rotate */
		front = point_rotate(front, o->orientation);
		front = point_translate(front, o->p.x, o->p.y);

		/* Snap to grid */
		gx = (int)(front.x / grid_size);
		gy = (int)(front.y / grid_size);

		/* Actual location */
		grid_loc.x = gx * grid_size;
		grid_loc.y = gy * grid_size;


		smallest_d = 1000;
		found = 0;
		for(x=gx-1; x<=gx+1; x++) {
			for(y=gy-1; y<=gy+1; y++) {
				int gi;
				
				gfront.x = x;
				gfront.y = y;
				gi = grid_index(o->eh, gfront);

				if(x<0 || y<0) continue;
				if(x==o->eh->grid_w || y==o->eh->grid_h) continue;

				if(o->eh->objects_at[gi]->len > 0) continue;
				if(o->eh->project_front[gi] != NULL) continue;

				d = l_distance(front, grid_loc);

				if(d < smallest_d) {
					smallest_d = d;
					smallest_gx = gx;
					smallest_gy = gy;
					found = 1;
				}
			}
		}
		if(!found) {
			printf("ERROR: gridloc not found\n");
			assert(found);
		}

		o->gfront.x = smallest_gx;
		o->gfront.y = smallest_gy;

		gi = grid_index(o->eh, o->gfront);
		o->eh->project_front[gi] = o;
	}
}

struct _grid_data {
	int g_score;
	int f_score;
	int visited;
	struct _point gp;
	struct _point p;
};

gint l_compute_path_grid_date_compare_func(gconstpointer a, gconstpointer b, gpointer user)
{
	const struct _grid_data *ga = a, *gb = b;
	return ga->f_score - gb->f_score;
}

int l_compute_path(struct _exhibithall_object *src_o)
{
	struct _exhibithall *eh = src_o->eh;
	struct _point gsrc = src_o->gfront;
	struct _point gdst = dst_o->gfront;
	GQueue *queue;
	int gi, i;
	int ret;

	struct _grid_data *grid_data, *dst_grid_data, *gd;
	grid_data = malloc(sizeof(struct _grid_data) * eh->grid_w * eh->grid_h);
	memset(grid_data, 0, sizeof(struct _grid_data) * eh->grid_w * eh->grid_h);

//	printf("Compute path from (%d,%d) to (%d,%d) with object avoidance\n", gsrc.x, gsrc.y, gdst.x, gdst.y);

	queue = g_queue_new();

	gi = grid_index(eh, dst_o->gfront);
	dst_grid_data = &grid_data[gi];

	gi = grid_index(eh, src_o->gfront);
	gd = &grid_data[gi];
	gd->visited = 1;
	gd->f_score = l_manhat_distance(src_o->front, dst_o->front);
	gd->gp = gsrc;

	g_queue_push_tail(queue, gd);

	while(queue->length > 0) {
		int dx, dy;
		gd = g_queue_pop_head(queue);

//		printf("Dequeue (%d,%d), f=%d, g=%d\n", gd->gp.x, gd->gp.y, gd->f_score, gd->g_score);

		if(gd == dst_grid_data) {
			ret = gd->g_score;
			break;
		}

		gd->visited = 1;

		for(i=0;i<4;i++) {
			struct _point neighbour_gp;
			struct _grid_data *ngd;
			int n_gscore;
			switch(i) {
			case 0: dx = -1; dy = 0; break;
			case 1: dx =  1; dy = 0; break;
			case 2: dx =  0; dy = -1; break;
			case 3: dx =  0; dy =  1; break;
			}
			neighbour_gp.x = gd->gp.x + dx;
			neighbour_gp.y = gd->gp.y + dy;
			if(neighbour_gp.x < 0 || neighbour_gp.y < 0) continue;
			if(neighbour_gp.x >= eh->grid_w || neighbour_gp.y >= eh->grid_h) continue;

			gi = grid_index(eh, neighbour_gp);

			ngd = &grid_data[gi];
			n_gscore = gd->g_score + grid_size;

			if(n_gscore < ngd->g_score) {
				printf("FGound a way to %d,%d with a lower cost %d\n", neighbour_gp.x, neighbour_gp.y, n_gscore);
			}

			if(ngd->visited) continue;

			ngd->gp = neighbour_gp;
			ngd->p.x = neighbour_gp.x * grid_size;
			ngd->p.y = neighbour_gp.y * grid_size;
			ngd->g_score = gd->g_score + grid_size;
			ngd->f_score = l_manhat_distance(ngd->p, dst_o->front);

			g_queue_insert_sorted(queue, ngd, &l_compute_path_grid_date_compare_func, NULL);
		}
	}
	g_queue_free(queue);
	free(grid_data);

//	printf("   Shortest path=%d\n", ret);

	return ret;
}


int l_compute_closest_projects(struct _exhibithall_object *o)
{
	struct _exhibithall *eh;
	int x;
	int shortest_distance = 10000;
//	printf("Computing paths for %s:%s...\n", o->eh->name, o->name);

	eh = o->eh;
	for(x=0;x<eh->project_objects->len;x++) {
		struct _exhibithall_object *dst_o = g_ptr_array_index(eh->project_objects, x);

		int distance = l_compute_path(o, dst_o);



		if(distance < shortest_distance) shortest_distance = distance;


	}

	return shortest_distance;
}



void exhibithall_anneal(struct _db_data *db, int year)
{
	int x, i;
	GPtrArray **exhibithall_assignments;

	printf("Loading Exhibit Halls and Objects...\n");
	exhibithalls_load(db, year);
	printf("Loading Students and Projects...\n");
	students_load(db, year);
	projects_load(db, year);

	grid_size = 1000; /* 1 meter, hopefully smaller */
	for(i=0;i<exhibithall_objects->len;i++) {
		struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, i);
		if(grid_size > o->w) grid_size = o->w;
		if(grid_size > o->h) grid_size = o->h;
	}
	grid_size /= 2;
	printf("Computed optimal grid size = %dmm\n", grid_size);

	for(x=0;x<exhibithalls->len;x++) {
		struct _exhibithall *eh = g_ptr_array_index(exhibithalls, x);
		eh->grid_w = (int)(eh->w / grid_size) + 1;
		eh->grid_h = (int)(eh->h / grid_size) + 1;
		printf("%s: grid is %dx%d (%d)\n", eh->name, eh->grid_w, eh->grid_h, eh->grid_w * eh->grid_h);

		eh->objects_at = malloc(sizeof(GPtrArray *) * eh->grid_w * eh->grid_h);
		eh->project_front = malloc(sizeof(struct _exhibithall_object *) * eh->grid_w * eh->grid_h);
		for(i = 0; i<eh->grid_w * eh->grid_h; i++) {
			eh->objects_at[i] = g_ptr_array_new();
			eh->project_front[i] = NULL;
		}
	}

	printf("Assigning objects to grid locations...\n");
	l_assign_objects_to_grid_locations();

	printf("Computing project front grid locations...\n");
	l_compute_project_front_grid_locations();


	printf("Computing closest projects for all projects...\n");
	for(x=0;x<exhibithall_objects->len;x++) {
		struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, x);
		l_compute_closest_projects(o);
	}

/*	for(x=0;x<exhibithall_objects->len;x++) {
		struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, x);
		printf("%d: %s:%s, (%d,%d)@%d front(%d,%d) grid(%d,%d)\n", o->id, o->eh->name,o->name, 
				o->p.x, o->p.y, o->orientation, o->front.x, o->front.y, o->gfront.x, o->gfront.y);
	}
*/		


#if 0	

	/* Assign students to exhibithalls */
	exhibithall_assignments = NULL;
	anneal(NULL, &exhibithall_assignments, exhibithalls->len, students, 
			&exhibithalls_cost, &exhibithalls_propose_move);

	for(x=0;x<exhibithalls->len;x++) {
		GPtrArray *ta = exhibithall_assignments[x];
		struct _exhibithall *t = g_ptr_array_index(exhibithalls, x);
		printf("%d: grade %d-%d, students %d, capacity %d-%d, %s\n", 
			t->id, t->grade_min, t->grade_max, ta->len, t->capacity_min, t->capacity_max, t->name);
		for(i=0; i<ta->len; i++) {
			struct _student *s = g_ptr_array_index(ta, i);
			int j, r;
			printf("    %s: grade %d, school %d,  ", s->name, s->grade, s->schools_id);
			r = -1;
			for(j=0;j<3;j++) {
				if(s->exhibithall_id_pref[j] == t->id) {
					r = j;
					break;
				}
			}
			rank_count[r == -1 ? 3 : r] += 1;
			printf(" ranked %d,   (%d %d %d) id=%d\n",r, s->exhibithall_id_pref[0], s->exhibithall_id_pref[1], s->exhibithall_id_pref[2], s->id);
		}
	}
	printf("Students who got their first, second, third, no choice: %d, %d, %d, %d = %d\n", 
			rank_count[0], rank_count[1], rank_count[2], rank_count[3], 
			rank_count[0] + rank_count[1] + rank_count[2] + rank_count[3]);


	/* Write results back to db */
	printf("Writing exhibithalls back to students\n");
	for(x=0;x<exhibithalls->len;x++) {
		GPtrArray *ta = exhibithall_assignments[x];
		struct _exhibithall *t = g_ptr_array_index(exhibithalls, x);
		for(i=0; i<ta->len; i++) {
			struct _student *s = g_ptr_array_index(ta, i);
			db_query(db, "UPDATE users SET exhibithall_id='%d' WHERE uid='%d'", t->id, s->id);
		}
	}
#endif
	printf("All done!\n");
}


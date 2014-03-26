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
	int index;
	char *name;
	char has_electricity;
	int floor_number;
	struct _point p, gfront;
	int w, h;
	int orientation;
	struct _exhibithall *eh;

	struct _exhibithall_object *closest_projects[MAX_CLOSEST];
	int closest_projects_distance[MAX_CLOSEST];
	int closest_projects_count;
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
	struct _exhibithall_object **project_front_at;

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

int exhibithall_propose_move(struct _annealer *annealer, struct _anneal_move *move)
{
	/* Permit 2 move types:
	 * 1. move to empty
	 * 2. swap
	 * Don't allow multiple items in the same bucket */
	struct _project *p1, *p2;
	struct _anneal_bucket *bucket;

	/* Find a bucket with something in it */
	while(1) {
		move->b1 = rand() % (annealer->num_buckets);
		bucket = &annealer->buckets[move->b1];
		if(bucket->items->len == 1) break;
	}
	p1 = g_ptr_array_index(bucket->items, 0);
	move->i1 = p1->index;
	move->p1 = p1;

	/* Choose any second bucket */
	move->b2 = rand() % (annealer->num_buckets - 1);
	if(move->b1 == move->b2) move->b2++;

	/* Either swap of do a one-sided move */
	bucket = &annealer->buckets[move->b2];
	if(bucket->items->len == 0) {
		move->i2 = -1;
		move->p2 = NULL;
	} else {
		p2 = g_ptr_array_index(bucket->items, 0);
		move->i2 = p2->index;
		move->p2 = p2;
	}

	assert(move->i1 < (int)projects->len);
	assert(move->i2 < (int)projects->len);


	return 0;
}

float exhibithalls_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
	int x, i, j;
	float cost = 0;
	int school_matches, chal_matches;
	int grade_min, grade_max;

	/* Each bucket is a floor location that maps 1:1 to the exhibithall_objects list, there
	 * should only be one object in each floor location */
	struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, bucket_id);
	struct _exhibithall *eh = o->eh;
	struct _project *p;


	if(bucket->len == 0) 
		return 0;

	if(bucket->len > 1) 
		return 10000;

	school_matches = 0;
	chal_matches = 0;
	grade_min = 100;
	grade_max = 0;


	p = g_ptr_array_index(bucket, 0);

//	printf("Cost for bucket %d, len=%d, p->index=%d\n", bucket_id, bucket->len, p->index);

	for(x=0;x<o->closest_projects_count;x++) {
		struct _exhibithall_object *nearby_o = o->closest_projects[x];
//		int nearby_d = o->closest_projects_distance[x];

		GPtrArray *nearby_bucket = annealer->buckets[nearby_o->index].items;
		struct _project *nearby_p;

		if(nearby_bucket->len != 1) continue;

		nearby_p = g_ptr_array_index(nearby_bucket, 0);

		for(i = 0; i<p->num_students; i++) {
			for(j=0;j<nearby_p->num_students;j++) {
				if(p->students[i]->schools_id == nearby_p->students[j]->schools_id) {
					if(x < 5) {
						school_matches++;
						i = p->num_students;
						break;
					}

				}
			}
		}

		if(p->challenge_id == nearby_p->challenge_id) {
			if(x<5) chal_matches++;
		}

		for(i = 0; i<p->num_students; i++) {
			if(p->students[i]->grade < grade_min) grade_min = p->students[i]->grade;
			if(p->students[i]->grade > grade_max) grade_max = p->students[i]->grade;
		}
		for(j=0;j<nearby_p->num_students;j++) {
			if(nearby_p->students[j]->grade < grade_min) grade_min = nearby_p->students[j]->grade;
			if(nearby_p->students[j]->grade > grade_max) grade_max = nearby_p->students[j]->grade;
		}
	}

	if(school_matches == 0) {
		cost += 5;
	}

	if(school_matches > 2)
		cost += 2 * (school_matches - 1);
	
	if(chal_matches < 2) 
		cost += 20;

	if(chal_matches > 2)
		cost += 10 * (chal_matches - 2);
	
	if(grade_max - grade_min > 0)
		cost += 50 * (grade_max - grade_min);

	if(p->req_electricity && !o->has_electricity) {
//		printf("   electricity mismatch\n");
		cost += 1000;
	}


	for(i=0;i<eh->num_cats;i++) {
		if(eh->cats[i] == p->cat_id) break;
	}
	if(i == eh->num_cats) {
/*		printf("   cats mismatch (i=%d, numcats=%d, p cat=%d, %s cats: ", i, eh->num_cats, p->cat_id, eh->name);
		for(i=0;i<eh->num_cats;i++) {
			printf("%d ", eh->cats[i]);
		}
		printf(")\n");
*/			
		cost += 1000;
	}

	if(cost >= 1000) {
//		printf("cost=%f\n", cost);
	}

	return cost;

}

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
		t->index = x;
		t->floor_number = db_fetch_row_field_int(result, x, "floornumber");
		t->id = atoi(db_fetch_row_field(result, x, "id"));
		t->name = strdup(db_fetch_row_field(result, x, "name"));
		t->has_electricity = atoi(db_fetch_row_field(result, x, "has_electricity"));
		t->eh = exhibithall_find(atoi(db_fetch_row_field(result, x, "exhibithall_id")));
		t->p.x = atof(db_fetch_row_field(result, x, "x")) * 1000.0;
		t->p.y = atof(db_fetch_row_field(result, x, "y")) * 1000.0;
		t->w = atof(db_fetch_row_field(result, x, "w")) * 1000.0;
		t->h = atof(db_fetch_row_field(result, x, "h")) * 1000.0;
		t->orientation = atof(db_fetch_row_field(result, x, "orientation"));
		t->closest_projects_count = 0;
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
				if(o->eh->project_front_at[gi] != NULL) continue;

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
		o->eh->project_front_at[gi] = o;
	}
}

struct _grid_data {
	int g_score;
	int f_score;
	int visited;
	int queued;
	struct _point gp;
};

gint l_compute_path_grid_date_compare_func(gconstpointer a, gconstpointer b, gpointer user)
{
	const struct _grid_data *ga = a, *gb = b;
	return ga->f_score - gb->f_score;
}

int l_compute_closest_projects(struct _exhibithall_object *src_o, int n)
{
	struct _exhibithall *eh = src_o->eh;
	GQueue *queue;
	int gi, i;
	int ret;

	struct _grid_data *grid_data, *gd, *gd_src;
	grid_data = malloc(sizeof(struct _grid_data) * eh->grid_w * eh->grid_h);
	memset(grid_data, 0, sizeof(struct _grid_data) * eh->grid_w * eh->grid_h);

//	printf("Compute path from (%d,%d) to %d closest objects with object avoidance\n", src_o->gfront.x, src_o->gfront.y, n);

	queue = g_queue_new();

	gi = grid_index(eh, src_o->gfront);
	gd = &grid_data[gi];
	gd->f_score = 0;
	gd->g_score = 0;
	gd->gp = src_o->gfront;
	gd_src = gd;

	/* Setup a queue with just the first grid_data pointer */
	g_queue_push_tail(queue, gd);

	ret = 0;
	while(queue->length > 0) {
		int dx, dy;
		gd = g_queue_pop_head(queue);

		if(gd->visited) continue;
		gd->visited = 1;

		gi = grid_index(eh, gd->gp);

//		printf("Dequeue (%d,%d), f=%d, g=%d\n", gd->gp.x, gd->gp.y, gd->f_score, gd->g_score);

	
		if(gd != gd_src && eh->project_front_at[gi] != NULL) {
			/* Found one */
			struct _exhibithall_object *dst_o = eh->project_front_at[gi];
			src_o->closest_projects[src_o->closest_projects_count] = dst_o;
			src_o->closest_projects_distance[src_o->closest_projects_count] = gd->g_score;
			src_o->closest_projects_count++;

//			printf("   Closest project[%d] = %s at distance %d\n", 
//					src_o->closest_projects_count - 1, dst_o->name, gd->g_score);

			/* Smallest distance */
			if(ret == -1) ret = gd->g_score;

			if(src_o->closest_projects_count == n) break;
		}
	

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
			ngd->g_score = gd->g_score + grid_size;
			ngd->f_score = 0;
			g_queue_push_tail(queue, ngd);
//			g_queue_insert_sorted(queue, ngd, &l_compute_path_grid_date_compare_func, NULL);
		}
	}
	g_queue_free(queue);
	free(grid_data);

//	printf("   Shortest path=%d\n", ret);

	return ret;
}

void exhibithall_anneal(struct _db_data *db, int year)
{
	int x, i;
	GPtrArray **exhibithall_assignments;

	categories_load(db, year);
	challenges_load(db, year);

	printf("Loading Exhibit Halls and Objects...\n");
	exhibithalls_load(db, year);
	printf("Loading Students and Projects...\n");
	students_load(db, year);
	projects_load(db, year);
	projects_crosslink_students();



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
		eh->project_front_at = malloc(sizeof(struct _exhibithall_object *) * eh->grid_w * eh->grid_h);
		for(i = 0; i<eh->grid_w * eh->grid_h; i++) {
			eh->objects_at[i] = g_ptr_array_new();
			eh->project_front_at[i] = NULL;
		}
	}

	printf("Assigning objects to grid locations...\n");
	l_assign_objects_to_grid_locations();

	printf("Computing project front grid locations...\n");
	l_compute_project_front_grid_locations();


	printf("Computing closest projects for all projects...\n");
	for(x=0;x<exhibithall_objects->len;x++) {
		struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, x);
		l_compute_closest_projects(o, MAX_CLOSEST );
	}


	/* Assign students to exhibithalls */
	exhibithall_assignments = NULL;
	anneal(NULL, &exhibithall_assignments, exhibithall_objects->len, projects, 
			&exhibithalls_cost, &exhibithall_propose_move);

	/* Construct GVRSF style project numbers and write it back to the db */
	printf("Writing exhibithalls back to students\n");
	for(x=0;x<exhibithall_objects->len;x++) {
		GPtrArray *a = exhibithall_assignments[x];
		struct _exhibithall_object *o = g_ptr_array_index(exhibithall_objects, x);
		struct _project *p;
		char pn[32];
		int floor_number = 0;
		int number_sort = 0;
		int i;
		struct _challenge *ch;
		struct _category *ca;
		if(a->len == 0) continue;
		p = g_ptr_array_index(a, 0);

		floor_number = o->floor_number;
		number_sort = o->floor_number;
		ch = challenge_find(p->challenge_id);
		ca = category_find(p->cat_id);
		sprintf(pn, "%s %03d %s", ca->shortform, o->floor_number, ch->shortform);
			

		db_query(db, "UPDATE projects SET number='%s',number_sort='%d',floor_number='%d' WHERE pid='%d'",
					pn, number_sort, floor_number, p->pid);

		printf("%s:%s: ", o->eh->name, o->name);
		if(a->len == 0) {
			printf("\n");
			continue;
		}
		p = g_ptr_array_index(a, 0);
		printf("pid %d: %s: cat=%d, gr=", p->pid, pn, p->cat_id );
		for(i = 0; i<p->num_students; i++)
			printf("%d,", p->students[i]->grade);
		printf(" %s\n", p->title);
		
	}

	printf("All done!\n");
}


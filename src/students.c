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

GPtrArray *students = NULL;
GPtrArray *projects = NULL;
GPtrArray *judges = NULL;
GPtrArray *awards = NULL;

void students_load(struct _db_data *db, int year)
{
	struct _db_result *result;
	int x, i;

	students = g_ptr_array_new();
	/* Load students and tour choices */
	result = db_query(db, "SELECT * FROM users WHERE year='%d' AND FIND_IN_SET('student',`roles`) AND s_accepted='1'", year);
	for(x=0;x<result->rows; x++) {
		struct _student *s = malloc(sizeof(struct _student));
		char *fn, *ln;
		char *prefs;
		fn = db_fetch_row_field(result, x, "firstname");
		ln = db_fetch_row_field(result, x, "lastname"); 
		s->name = malloc(strlen(fn) + strlen(ln) + 2);
		sprintf(s->name, "%s %s", fn, ln);
		s->id = atoi(db_fetch_row_field(result, x, "uid"));
		s->grade = atoi(db_fetch_row_field(result, x, "grade"));
		s->schools_id = atoi(db_fetch_row_field(result, x, "schools_id"));
		s->index = x;
		s->pid = atoi(db_fetch_row_field(result, x, "s_pid"));
		s->project = NULL;

		prefs = db_fetch_row_field(result, x, "tour_id_pref");
		for(i=0;i<3;i++) 
			s->tour_id_pref[i] = -1;
		i = split_int_list(s->tour_id_pref, prefs);
		if(i > 3) {
			printf("ERROR: Student \"%s\" managed to select more than 3 tour prefs\n", s->name);
			assert(0);
		}

		//printf(" %s: grade %d, school %d,  (%d %d %d) id=%d\n", s->name, s->grade, s->schools_id, s->tour_id_pref[0], s->tour_id_pref[1], s->tour_id_pref[2], s->id);
		g_ptr_array_add(students, s);

	}
	printf("Loaded %d students\n", students->len);
	db_free_result(result);
}

void project_print(struct _project *p) 
{
	struct _isef_division *d = g_ptr_array_index(isef_divisions, p->isef_id);
	printf(" %5d: %d students, cat %d, isef %3s, %s, %s\n", p->pid, p->num_students, p->cat_id, d->div, p->language, p->title);
}

void projects_load(struct _db_data *db, int year)
{
	struct _db_result *result;
	int x, i;

	projects = g_ptr_array_new();
	/* Load students and tour choices */
	result = db_query(db, "SELECT * FROM projects WHERE year='%d' AND num_students IS NOT NULL AND accepted='1'", year);
	for(x=0;x<result->rows; x++) {
		struct _project *p = malloc(sizeof(struct _project));
		int pid, count;
		char *prefs;
		int num_students;

		pid = atoi(db_fetch_row_field(result, x, "pid"));
		num_students = atoi(db_fetch_row_field(result, x, "num_students"));

		/* First, a project must have two completed students */
		count = 0;
		for(i=0;i<students->len;i++) {
			struct _student *s = g_ptr_array_index(students, i);
			if(s->pid == pid) 
				count++;
		}

		if(count != num_students) {
			printf("Not loading project %d because the project num_students=%d is not equal to the number of students found = %d\n",
						pid, num_students, count);
			free(p);
			continue;
		}

		p->pid = pid;
		p->num_students = num_students;
		p->title = strdup(db_fetch_row_field(result, x, "title"));
		p->cat_id = db_fetch_row_field_int(result, x, "cat_id");
		p->challenge_id = db_fetch_row_field_int(result, x, "challenge_id");
		p->req_electricity = db_fetch_row_field_int(result, x, "req_electricity");
		p->isef_id = db_fetch_row_field_int(result, x, "isef_id");
		p->language = strdup(db_fetch_row_field(result, x, "language"));
		if(strcmp(p->language, "fr") == 0) {
			p->language_id = LANGUAGE_FRENCH;
		} else {
			p->language_id = LANGUAGE_ENGLISH;
		}
		p->students = NULL;

		prefs = db_fetch_row_field(result, x, "sa_nom");
		p->num_sa_nom = split_int_list(p->sa_nom, prefs);

		prefs = db_fetch_row_field(result, x, "unavailable_timeslots");
		p->num_unavailable_timeslots = split_str_list(p->unavailable_timeslots, prefs);


		p->index = projects->len;
		g_ptr_array_add(projects, p);

	}
	db_free_result(result);
	printf("Loaded %d projects\n", projects->len);
	
}

void projects_crosslink_students(void)
{
	int i,x;
	for(x=0;x<projects->len;x++) {
		struct _project *p = g_ptr_array_index(projects, x);
		int c = 0;
		p->students = malloc(sizeof(struct _student *) * p->num_students);
		for(i=0;i<students->len;i++) {
			struct _student *s = g_ptr_array_index(students, i);
			if(s->pid == p->pid) {
				s->project = p;
				p->students[c++] = s;
			}
		}
	}
}





void judges_load(struct _db_data *db, int year)
{
	struct _db_result *result;
	int x, i, y;

	judges = g_ptr_array_new();
	/* Load judges and tour choices */
	result = db_query(db, "SELECT * FROM users WHERE year='%d' AND FIND_IN_SET('judge',`roles`) AND j_complete='1' AND attending='1' AND enabled='1'", year);
	for(x=0;x<result->rows; x++) {
		struct _judge *j = malloc(sizeof(struct _judge));
		char *fn, *ln;
		char *p;
		int jround[8];
		fn = db_fetch_row_field(result, x, "firstname");
		ln = db_fetch_row_field(result, x, "lastname"); 
		j->name = malloc(strlen(fn) + strlen(ln) + 2);
		sprintf(j->name, "%s %s", fn, ln);
		j->id = atoi(db_fetch_row_field(result, x, "uid"));
		j->willing_lead = atoi(db_fetch_row_field(result, x, "j_willing_lead"));
		j->index = x;
		j->sa_only = atoi(db_fetch_row_field(result, x, "j_sa_only"));
		if(j->sa_only) {
//			printf("%s\n", db_fetch_row_field(result, x, "j_sa"));
			j->num_sa = split_int_list(j->sa, db_fetch_row_field(result, x, "j_sa"));
			if(j->num_sa > 16) {
				printf("ERROR: judge %s managed to select more than 16 (=%d) j_sa awards\n", j->name, i);
				assert(0);
			}
			j->years_school = 0;
			j->years_regional = 0;
			j->years_national = 0;
			j->cat_pref = 0;
			j->isef_id_pref[0] = 0;
			j->isef_id_pref[1] = 0;
			j->isef_id_pref[2] = 0;
		} else {
			j->num_sa = 0;
			j->years_school = atoi(db_fetch_row_field(result, x, "j_years_school"));
			j->years_regional = atoi(db_fetch_row_field(result, x, "j_years_regional"));
			j->years_national = atoi(db_fetch_row_field(result, x, "j_years_national"));
			j->cat_pref = atoi(db_fetch_row_field(result, x, "j_cat_pref")); /* can be zero */
			split_int_list(j->isef_id_pref, db_fetch_row_field(result, x, "j_div_pref"));

			/* Some judges have been writing the year, 2013,  for the years of experience */
			if(j->years_school > 100) j->years_school = 1;
			if(j->years_regional > 100) j->years_regional = 1;
			if(j->years_national > 100) j->years_national = 1;

		}
		j->round0_divisional_jteam = NULL;

		/* Turn the list of available rounds into a mask */
		memset(j->available_in_round, 0, sizeof(int) * 8);
		i = split_int_list(jround, db_fetch_row_field(result, x, "j_rounds"));
		for(y=0; y<i; y++) {
			j->available_in_round[jround[y]] = 1;
		}

		memset(j->on_jteams_in_round, 0, sizeof(int) *8);

		/* Turn the languages (stored in php serialize, oops) into a mask */
		p = db_fetch_row_field(result, x, "j_languages");
		j->lang[LANGUAGE_ENGLISH] = strstr(p, "en") ? 1 : 0;
		j->lang[LANGUAGE_FRENCH] = strstr(p, "fr") ? 1 : 0;

		
		/* Remap prefs to parent and setup the div mask */
		j->isef_div_mask = malloc( isef_divisions->len * sizeof(int));
		memset(j->isef_div_mask, 0, isef_divisions->len * sizeof(int));
		for(i=0;i<3;i++) {
			struct _isef_division *d;
			if(j->isef_id_pref[i] <= 0) continue;
			d = g_ptr_array_index(isef_divisions, j->isef_id_pref[i]);
			if(d->parent != -1) 
				j->isef_id_pref[i] = d->parent;


			/* Reload div in case it changed */
			d = g_ptr_array_index(isef_divisions, j->isef_id_pref[i]);
			j->isef_div_mask[j->isef_id_pref[i]] = 2;
			for(y=0;y<d->num_similar; y++) {
				j->isef_div_mask[d->similar[y]] = 1;
			}
		}

		//printf(" %s: grade %d, school %d,  (%d %d %d) id=%d\n", j->name, j->grade, j->schools_id, j->tour_id_pref[0], j->tour_id_pref[1], j->tour_id_pref[2], j->id);
		g_ptr_array_add(judges, j);

	}
	printf("Loaded %d judges\n", judges->len);
	db_free_result(result);
}

void judge_print(struct _judge *j) 
{
	int x;
	printf(" %5d: %s, %s%s", j->id, j->name, j->lang[LANGUAGE_ENGLISH] ? "en ":"", j->lang[LANGUAGE_FRENCH] ? "fr": "");
	if(j->sa_only) {
		printf("SA only:");
		for(x=0;x<j->num_sa;x++) {
			printf(" %d", j->sa[x]);
		}
	} else {
		struct _isef_division *d;
		printf(", id pref:");
		for(x=0;x<3;x++) {
			d = g_ptr_array_index(isef_divisions, j->isef_id_pref[x]);
			printf(" %s", d->div);
		}
		printf(", cat pref: %d, years: %d %d %d", 
			j->cat_pref, j->years_school, j->years_regional, j->years_national);
	}

	printf(", w/lead: %d\n", j->willing_lead);
}



void awards_load(struct _db_data *db, int year)
{
	struct _db_result *result, *result2;
	int x, y;
	int n_div = 0, n_spec = 0;

	awards = g_ptr_array_new();
	/* Load awards and tour choices */
	result = db_query(db, "SELECT * FROM awards WHERE year='%d' AND schedule_judges='1' ORDER BY `type`", year);
	for(x=0;x<result->rows; x++) {
		struct _award *a = malloc(sizeof(struct _award));
		char *p;
		a->name = strdup(db_fetch_row_field(result, x, "name")); 
		a->id = atoi(db_fetch_row_field(result, x, "id"));
		a->self_nominate = atoi(db_fetch_row_field(result, x, "self_nominate"));

		p = db_fetch_row_field(result, x, "type");
		a->is_divisional = 0;
		a->is_special = 0;
		if(strcmp(p, "divisional") == 0) {
			a->is_divisional = 1;
			n_div++;
		} else {
			a->is_special = 1;
			n_spec++;
		}

		p = db_fetch_row_field(result, x, "categories");
		a->num_cats = split_int_list(a->cats, p);
		a->prizes = g_ptr_array_new();

		a->projects = g_ptr_array_new();
		a->jteams = g_ptr_array_new();
		a->cusp_jteams = NULL;

		//printf(" %s: grade %d, school %d,  (%d %d %d) id=%d\n", j->name, j->grade, j->schools_id, j->tour_id_pref[0], j->tour_id_pref[1], j->tour_id_pref[2], j->id);
		g_ptr_array_add(awards, a);

		result2 = db_query(db, "SELECT * FROM award_prizes WHERE award_id='%d' ORDER BY `ord` DESC", a->id);
		for(y=0;y<result2->rows;y++) {
			struct _prize *prize = malloc(sizeof(struct _prize));
			prize->name = strdup(db_fetch_row_field(result2, y, "name")); 
			prize->id = db_fetch_row_field_int(result2, y, "id");
			g_ptr_array_add(a->prizes, prize);
		}
		db_free_result(result2);

	}

	printf("Loaded %d awards.  %d Divisional and %d Special/Other\n", awards->len, n_div, n_spec);
	db_free_result(result);
}

struct _award *award_find(int id)
{
	int x;
	for(x=0;x<awards->len;x++) {
		struct _award *a = g_ptr_array_index(awards, x);
		if(a->id == id) return a;
	}
	return NULL;
}


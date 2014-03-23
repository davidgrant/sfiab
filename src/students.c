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

int l_split_int_list(int *list, char *str)
{
	int i = 0;
	char *p;
	while(1) {
		/* Find a comma and null it out */
		p = strchr(str, ',');
		if(p) *p = 0;
		/* Convert everythign up to the comma(or everything if comma not found */
		list[i] = atoi(str);
		i++;

		/* Set str forward to where the comma was */
		if(!p) break;
		str = p+1;
	}
	return i;
}

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
		i = l_split_int_list(s->tour_id_pref, prefs);
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
	result = db_query(db, "SELECT * FROM projects WHERE year='%d' AND num_students IS NOT NULL", year);
	for(x=0;x<result->rows; x++) {
		struct _project *p = malloc(sizeof(struct _student));
		int pid, count;
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
			free(p);
			continue;
		}

		p->pid = pid;
		p->num_students = num_students;
		p->title = strdup(db_fetch_row_field(result, x, "title"));
		p->cat_id = atoi(db_fetch_row_field(result, x, "cat_id"));
		p->isef_id = atoi(db_fetch_row_field(result, x, "isef_id"));
		p->language = strdup(db_fetch_row_field(result, x, "language"));
		if(strcmp(p->language, "fr") == 0) {
			p->language_id = 2;
		} else {
			p->language_id = 1;
		}
		p->students = NULL;

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
	result = db_query(db, "SELECT * FROM users WHERE year='%d' AND FIND_IN_SET('judge',`roles`) AND j_complete='1' AND not_attending='0'", year);
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
			j->num_sa = l_split_int_list(j->sa, db_fetch_row_field(result, x, "j_sa"));
			if(i > 16) {
				printf("ERROR: judge %s managed to select more than 16 (=%d) j_sa awards\n", j->name, i);
				assert(0);
			}
			j->sa[i] = -1;
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
			j->cat_pref = atoi(db_fetch_row_field(result, x, "j_pref_cat")); /* can be zero */
			j->isef_id_pref[0] = atoi(db_fetch_row_field(result, x, "j_pref_div1"));
			j->isef_id_pref[1] = atoi(db_fetch_row_field(result, x, "j_pref_div2"));
			j->isef_id_pref[2] = atoi(db_fetch_row_field(result, x, "j_pref_div3"));
		}

		/* Turn the list of available rounds into a mask */
		memset(j->available_in_round, 0, sizeof(int) * 8);
		i = l_split_int_list(jround, db_fetch_row_field(result, x, "j_rounds"));
		for(y=0; y<i; y++) {
			j->available_in_round[jround[y]] = 1;
		}

		/* Turn the languages (stored in php serialize, oops) into a mask */
		p = db_fetch_row_field(result, x, "j_languages");
		j->lang[1] = strstr(p, "en") ? 1 : 0;
		j->lang[2] = strstr(p, "fr") ? 1 : 0;

		
		/* Remap prefs to parent */
		for(i=0;i<3;i++) {
			struct _isef_division *d;
			if(j->isef_id_pref[i] <= 0) continue;
			d = g_ptr_array_index(isef_divisions, j->isef_id_pref[i]);
			if(d->parent != -1) 
				j->isef_id_pref[i] = d->parent;
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
	printf(" %5d: %s, %s%s", j->id, j->name, j->lang[1] ? "en ":"", j->lang[2] ? "fr": "");
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
	struct _db_result *result;
	int x;

	awards = g_ptr_array_new();
	/* Load awards and tour choices */
	result = db_query(db, "SELECT * FROM awards WHERE year='%d' AND schedule_judges='1'", year);
	for(x=0;x<result->rows; x++) {
		struct _award *a = malloc(sizeof(struct _award));
		char *p;
		a->name = strdup(db_fetch_row_field(result, x, "name")); 
		a->id = atoi(db_fetch_row_field(result, x, "id"));
		a->self_nominate = atoi(db_fetch_row_field(result, x, "self_nominate"));

		p = db_fetch_row_field(result, x, "type");
		a->is_divisional = 0;
		a->is_special = 0;
		if(strcmp(p, "divisional") == 0) 
			a->is_divisional = 1;
		else
			a->is_special = 1;

		p = db_fetch_row_field(result, x, "categories");
		a->num_cats = l_split_int_list(a->cats, p);

		//printf(" %s: grade %d, school %d,  (%d %d %d) id=%d\n", j->name, j->grade, j->schools_id, j->tour_id_pref[0], j->tour_id_pref[1], j->tour_id_pref[2], j->id);
		g_ptr_array_add(awards, a);

	}

	printf("Loaded %d awards\n", awards->len);
	db_free_result(result);
}

#ifndef STUDENTS_H
#define STUDENTS_H

#include <glib.h>

struct _award;

struct _student {
	int id;
	char *name;
	int tour_id_pref[3];
	int tour_id;
	int grade;
	int schools_id;
	int index;
	int pid;
	struct _project *project;
};


struct _project {
	int index;
	int pid;
	char *title;
	int num_students;
	int cat_id;
	int challenge_id;
	int isef_id;
	int req_electricity;
	char *language;
	int language_id;
	struct _student **students; /* Malloced array of num_students */

	int sa_nom[16];
	int num_sa_nom;

	char *unavailable_timeslots[16];
	int num_unavailable_timeslots;
};

struct _judge {
	int id;
	int index;
	char *name;
	int isef_id_pref[3];
	int cat_pref;
	int years_school, years_regional, years_national;
	int willing_lead;
	int sa_only;
	int sa[16];
	int num_sa;
	int available_in_round[8];
	int lang[3];

	void *round0_divisional_jteam;

	int on_jteams_in_round[8];
	int *isef_div_mask;
};

struct _prize {
	int id;
	char *name;
};

struct _award {
	int id;
	char *name;
	
	int is_divisional;
	int is_special;

	int self_nominate;
	GPtrArray *projects;
	GPtrArray *jteams;
	GPtrArray *cusp_jteams;
	int cats[16];
	int num_cats;

	GPtrArray *prizes;
};

extern GPtrArray *students;
extern GPtrArray *projects;
extern GPtrArray *judges;
extern GPtrArray *awards;

void students_load(struct _db_data *db, int year);
void projects_load(struct _db_data *db, int year);
void projects_crosslink_students(void);
void project_print(struct _project *p);

void judges_load(struct _db_data *db, int year);
void judge_print(struct _judge *j);

void awards_load(struct _db_data *db, int year);
struct _award *award_find(int id);

#endif


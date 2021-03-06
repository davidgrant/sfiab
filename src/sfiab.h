#ifndef SFIAB_H
#define SFIAB_H

#include <glib.h>

#include "db.h"

enum _languages {
	LANGUAGE_ENGLISH,
	LANGUAGE_FRENCH,
	NUM_LANGUAGES,
};



struct _config {
        int year;
	int max_projects_per_judge;
	int max_judges_per_team;
	int min_judges_per_cusp_team;
	int max_judges_per_cusp_team;
	int projects_per_sa_judge;
	int judge_shuffle;
	int div_times_each_project_judged;

	/* Derived numbers */
	int max_projects_per_team;
};

struct _category
{
	int id;
	char *name;
	char *shortform;
};


struct _challenge
{
	int id;
	char *name;
	char *shortform;
};


struct _isef_division
{
	int id;
	int parent;
	int num_similar;
	int *similar;
	int *similar_mask;
	char *name;
	char *div;
};

extern struct _config config;
extern GPtrArray *categories, *challenges;
extern GPtrArray *isef_divisions;


void config_load(struct _db_data *db);

void categories_load(struct _db_data *db, int year);
struct _category *category_find(int cat_id);
void challenges_load(struct _db_data *db, int year);
struct _challenge *challenge_find(int challenge_id);

void isef_divisions_load(struct _db_data *db, int year);

int split_int_list(int *list, char *str);
int list_contains_int(int *list, int len, int val);
int split_str_list(char **list, char *str);

int factorial(int n);

#endif

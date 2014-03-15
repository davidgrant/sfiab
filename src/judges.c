#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <assert.h>

#include <glib.h>

#include "sfiab.h"
#include "anneal.h"
#include "db.h"
#include "judges.h"
#include "students.h"
#include "judges.h"

struct _jteam {
	int id;
	char *name;
	struct _award *award;
	int round;

	int *isef_div_count;
	int *lang_count;

	GPtrArray *judges;
	GPtrArray *projects;
};



struct _judging_data {
	int min_projects_per_judge;
	int max_projects_per_judge;
	int min_judges_per_team;
	int max_judges_per_team;
	int *tmp_isef_div;
	int *tmp_lang;
} judging_data;


/* Cost for projects assigned to each jteam */
float jteam_projects_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
	int x, y;
	float cost = 0;
	int unique_divs = 0;
	int unique_langs = 0;
	int min_mask_missing = isef_divisions->len+1;
	/*
	struct _award *a = annealer->data_ptr;
	struct _jteam *jteam = g_ptr_array_index(a->jteams, bucket_id);
	*/

	/* +1 because an ID starts at 1, not 0 */
	memset(judging_data.tmp_isef_div, 0, (isef_divisions->len+1) * sizeof(int));
	memset(judging_data.tmp_lang, 0, 3 * sizeof(int));


	for(x=0;x<bucket->len;x++) {
		struct _project *p = g_ptr_array_index(bucket, x);

		if(judging_data.tmp_isef_div[p->isef_id] == 0) unique_divs+= 1;
		judging_data.tmp_isef_div[p->isef_id] += 1;

		if(judging_data.tmp_lang[p->language_id] == 0) unique_langs += 1;
		judging_data.tmp_lang[p->language_id] += 1;
	}

	/* For each div in the isef divs, see if the mask for that div matches the 
	 * entire div set.  We want to find one div that matches everything so that
	 * all isef_divs in this jteam are "related" */
	for(x=1;x<=isef_divisions->len;x++) {
		struct _isef_division *test_div;
		int mask_missing = 0;
		if(judging_data.tmp_isef_div[x] == 0) continue;

		test_div = g_ptr_array_index(isef_divisions, x);
		for(y=1;y<=isef_divisions->len;y++) {
			if(judging_data.tmp_isef_div[y] > 0 && test_div->similar_mask[y] == 0) {
				/* This jteam has a div that isn't in the current mask, no match. */
				mask_missing += 1;
			}
		}

		if(mask_missing < min_mask_missing) 
			min_mask_missing = mask_missing;

		if(min_mask_missing == 0) {
			/* Found a match */
			break;
		}
	}

	/* Cost for all divs outside the max match for the divs */
	cost += min_mask_missing * 25;
	/* Smaller cost for having differnent divs at all */
	cost += (unique_divs - 1) * 5;

	/* Cost different languages harshly */
	cost += (unique_langs - 1) * 75;
	
	/* Cost over limit */
	if(bucket->len > judging_data.max_projects_per_judge) {
		cost += (bucket->len - judging_data.max_projects_per_judge) * 100;
	}

	/* Cost each project over 3/4 the max, just to break ties */
	if(bucket->len > (judging_data.max_projects_per_judge * 3 / 4)) {
		cost += bucket->len - (judging_data.max_projects_per_judge * 3 / 4);
	}

	/* Score +200 pts for each duplicate project this team is judging, we
	 * really don't want a jteam judging the same project twice */
	if(bucket->len > 1) {
		for(x=0;x<bucket->len-1;x++) {
			struct _project *p1 = g_ptr_array_index(bucket, x);
			for(y=x+1;y<bucket->len;y++) {
				struct _project *p2 = g_ptr_array_index(bucket, y);
				if(p1 == p2) {
					cost += 200;
				}
			}
		}
	}
	return cost;	
}

/* Cost for judges assigned to each divisional jteam */
float jteam_judge_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
	int x, i;
	float cost = 0;
	int have_lead = 0;
	int years_experience_weighted = 0;
	float div_cost;
	/* the gptrarray of jteams was passed as the data ptr so we can find
	 * our jteam */
	GPtrArray *jteams = annealer->data_ptr;
	struct _jteam *jteam = g_ptr_array_index(jteams, bucket_id);

	/* If the bucket id is zero, it's the bucket for extra judges, have a slight cost */
	if(bucket_id == 0) {
		cost = bucket->len * 40;
		return cost;
	}

	/* Cost over/under */
	if(jteam->award->is_divisional) {
		int min = (bucket->len < judging_data.min_judges_per_team) ? (judging_data.min_judges_per_team - bucket->len) : 0;
		int max = (bucket->len > judging_data.max_judges_per_team) ? (bucket->len - judging_data.max_judges_per_team) : 0;

		cost += min * 50;
		cost += max * 50;

	} else {
		assert(0);
	}

	/* Clean out the maps, we'll use these for the judges */
	memset(judging_data.tmp_isef_div, 0, (isef_divisions->len+1) * sizeof(int));
	memset(judging_data.tmp_lang, 0, 3 * sizeof(int));

	/* For each judge score their div and cat pref */
	for(x=0;x<bucket->len;x++) {
		struct _judge *j = g_ptr_array_index(bucket, x);
		int cat_cost;

		/* Cat */
		if(j->cat_pref == 0) {
			/* No pref */
			cat_cost = 0;
		} else {
			cat_cost = 10;
			for(i=0;i<jteam->award->num_cats;i++) {
				if(jteam->award->cats[i] == j->cat_pref) {
					cat_cost = 0;
					break;
				}
			}
		}
		cost += cat_cost;

		/* Div. */
		for(i=1; i<isef_divisions->len+1; i++) {
			judging_data.tmp_isef_div[j->isef_id_pref[0]] += 2;
			judging_data.tmp_isef_div[j->isef_id_pref[1]] += 2;
			judging_data.tmp_isef_div[j->isef_id_pref[2]] += 1;
		}

		/* Cost languages */
		for(i=1; i<3;i++) {
			if(judging_data.tmp_lang[i] == 0) {
				/* For each additional language that the judge
				 * knows that they dont need increase the cost,
				 * this should hopefully stop the condition
				 * where it uses up all the bilingual judges
				 * for english only teams leaving no
				 * french/bilingual judges for the french teams
				 * */
				if(j->lang[i] == 1) {
//					cost += 15;
				}
			} else {
				if(j->lang[i] != 1) {
					/* jteam needs this language, but judge doesn't have it */
					cost += 50;
				}
			}
		}

		/* Do we have a team lead? */
		if(j->willing_lead) have_lead = 1;

		years_experience_weighted = j->years_school + j->years_regional * 3 + j->years_national * 4;
	}

	/* Compare the judge div prefs we have on this team
	 * with what the projects need */
	div_cost = 0;
	for(i=1; i<isef_divisions->len+1; i++) {
		if(judging_data.tmp_isef_div[i] > 0 && jteam->isef_div_count[i] == 0) {
			/* Judge team has a div not needed by the projects */
//			div_cost += judging_data.tmp_isef_div[i];
		} else if (judging_data.tmp_isef_div[i] == 0 && jteam->isef_div_count[i] > 0) {
			/* Judge team is missing a div needed by the projects.. this is a problem. */
			div_cost += jteam->isef_div_count[i] * 10;
		} else {
			/* Match or not needed */
			div_cost += 0;
		}
	}
	cost += div_cost;

	if(have_lead == 0) {
		cost += 100;
	}

	/* Small penalty for a jteam with very little experience, 
	 * but only if there's more than 1 person on the team */
	if(bucket->len > 1 && years_experience_weighted < 5) {
		cost += (5 - years_experience_weighted) * 2;
	}

	
	return cost;	
}




struct _jteam *jteam_create(GPtrArray *jteams, char *name, struct _award *award)
{
	struct _jteam *jteam = malloc(sizeof(struct _jteam));
	jteam->name = strdup(name);
	jteam->award = award;
	jteam->id = jteams->len;
	jteam->judges = g_ptr_array_new();
	jteam->projects = g_ptr_array_new();
	jteam->isef_div_count = malloc((isef_divisions->len + 1)* sizeof(int));
	jteam->lang_count = malloc( 3 * sizeof(int));
	g_ptr_array_add(jteams, jteam);
	return jteam;
}


void judges_anneal(struct _db_data *db, int year)
{
	int x, i, j;
	GPtrArray *jteams;
	GPtrArray *jteams_list, *judge_list;
	GPtrArray **judge_jteam_assignments = NULL;

	jteams = g_ptr_array_new();

	judging_data.min_projects_per_judge = 3;
	judging_data.max_projects_per_judge = 7;
	judging_data.min_judges_per_team = 3;
	judging_data.max_judges_per_team = 3;
	
	students_load(db, year);
	projects_load(db, year);
	projects_crosslink_students();

	categories_load(db, year);
	isef_divisions_load(db, year);

	judging_data.tmp_isef_div = malloc((isef_divisions->len + 1)* sizeof(int));
	judging_data.tmp_lang = malloc( 3 * sizeof(int));

	judges_load(db, year);
	awards_load(db, year);

//	timeslots_load(db, year);

	jteam_create(jteams, "Unused Judges", NULL);

	for(x=0;x<awards->len;x++) {
		struct _award *a = g_ptr_array_index(awards, x);
		int cat_id;
		struct _category *cat;
		int num_jteams;
		GPtrArray **project_jteam_assignments = NULL;


		if(a->is_divisional) {
			if(a->num_cats != 1) {
				printf("ERROR: divisional award %s has %d cats, not 1.\n", a->name, a->num_cats);
				assert(0);
			}

			cat_id = a->cats[0];
			cat = category_find(cat_id);
			a->projects = g_ptr_array_new();
			a->jteams = g_ptr_array_new();

			printf("Annealing Award %s (category: %s)\n", a->name, cat->name);

			/* Find all projects */
			for(i=0;i<projects->len;i++) {
				struct _project *p = g_ptr_array_index(projects, i);
				struct _isef_division *d = g_ptr_array_index(isef_divisions, p->isef_id);

				/* Remap ISEF ids to only parent id */
				if(d->parent != -1) p->isef_id = d->parent;

				if(p->cat_id == cat->id) {
					g_ptr_array_add(a->projects, p);
				}
			}

			/* 0/8 = 0, 1/8 ... 8/8 = 1,  9/8 = 2, etc.. */
			num_jteams = ((a->projects->len - 1) / judging_data.max_projects_per_judge) + 1;
			printf("   => %d projects, %d jteams\n", a->projects->len, num_jteams);

			/* Create teams */
			for(i=0;i<num_jteams;i++) {
				struct _jteam *jteam;
				char name[1024];
				sprintf(name, "%s Divisional %d", cat->name, i);
				jteam = jteam_create(jteams, name, a);
				g_ptr_array_add(a->jteams, jteam);
			}

			anneal(a, &project_jteam_assignments, a->jteams->len, a->projects, 
				&jteam_projects_cost, NULL/*&jteam_projects_propose_move*/);

			for(i=0;i<num_jteams;i++) {
				GPtrArray *ps = project_jteam_assignments[i];
				struct _jteam *jteam = g_ptr_array_index(a->jteams, i);
				printf("JTeam %d: Divisional: %d projects\n", jteam->id, ps->len );
				jteam->projects = ps;

				for(j=0; j<ps->len;j++) {
					project_print(g_ptr_array_index(ps, j));
				}
			}
		} else if(a->is_special) {
			/* Just make one jteam */
			struct _jteam *jteam;
			
			jteam = jteam_create(jteams, a->name, a);
			printf("JTeam %d: Special Award: %s\n", jteam->id, a->name);

		} else {
			printf("ERROR: award %s is not divisional or special\n", a->name);
			assert(0);
		}
	}

	/* Build a list of all divisional jteams to anneal first */
	jteams_list = g_ptr_array_new();
	for(x=0;x<jteams->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, x);
		if(jteam->award == NULL) {
			g_ptr_array_add(jteams_list, jteam);
		} else if (jteam->award->is_divisional) {
			g_ptr_array_add(jteams_list, jteam);
		}

		/* Build the divs and langs taht this jteam will need to have */
		memset(jteam->isef_div_count, 0, (isef_divisions->len+1) * sizeof(int));
		memset(jteam->lang_count, 0, 3 * sizeof(int));
		for(i=0;i<jteam->projects->len;i++) {
			struct _project *p = g_ptr_array_index(jteam->projects, i);
			jteam->isef_div_count[p->isef_id] += 1;
			jteam->lang_count[p->language_id] += 1;
		}
	}

	/* All judges available in round1 execpt SA only are candidates */
	judge_list = g_ptr_array_new();
	for(x=0;x<judges->len;x++) {
		struct _judge *j = g_ptr_array_index(judges, x);
		if(j->sa_only == 1) continue;
		if(!j->available_in_round[0]) continue;
		g_ptr_array_add(judge_list, j);
		judge_print(j);
	}
	printf("Divisional Awards has %d jteams and %d judges available\n", jteams_list->len, judge_list->len);
	anneal(jteams_list, &judge_jteam_assignments, jteams_list->len, judge_list, 
			&jteam_judge_cost, NULL);

	for(i=0;i<jteams_list->len;i++) {
		GPtrArray *js = judge_jteam_assignments[i];
		struct _jteam *jteam = g_ptr_array_index(jteams_list, i);
		printf("JTeam %d: Divisional: %d judges, req:", jteam->id, js->len );
		for(x=1;x<isef_divisions->len+1;x++) {
			struct _isef_division *div;
			if(jteam->isef_div_count[x] == 0) continue;

			div = g_ptr_array_index(isef_divisions, x);
			printf(" %s", div->div);
		}
		printf("\n");
		jteam->judges = js;

		for(j=0; j<js->len;j++) {
			judge_print(g_ptr_array_index(js, j));
		}
	}


#if 0


	/* Write results back to db */
	printf("Writing judges back to students\n");
	for(x=0;x<judges->len;x++) {
		GPtrArray *ta = judge_assignments[x];

		struct _judge *t = g_ptr_array_index(judges, x);
		for(i=0; i<ta->len; i++) {
			struct _student *s = g_ptr_array_index(ta, i);
			db_query(db, "UPDATE users SET judge_id='%d' WHERE uid='%d'", t->id, s->id);
		}
	}
#endif
	printf("All done!\n");
}


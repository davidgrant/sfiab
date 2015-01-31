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

static int current_year = 0;

struct _jteam {
	int id, num;
	char *name;
	struct _award *award;
	int round;
	int sa_only; /* If this jteam is for sa_only judges (no scheduling) */

	int *isef_div_count;
	int *lang_count;

	GPtrArray *judges;
	GPtrArray *projects;
};



/* Scratch memory for the jteam cost calculation so we dont' have to reallocate
 * it every time */
struct _judging_data {
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
	if(bucket->len > config.max_projects_per_judge) {
		cost += (bucket->len - config.max_projects_per_judge) * 100;
	}

	/* Cost each project over 3/4 the max, just to break ties */
	if(bucket->len > (config.max_projects_per_judge * 3 / 4)) {
		cost += bucket->len - (config.max_projects_per_judge * 3 / 4);
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

/* Cost for judges assigned to a divisional jteam 
 * This handles both round1 and round2 (cusp) */
float jteam_judge_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
	int x, i, y;
	float cost = 0;
	int have_lead = 0;
	int years_experience_weighted = 0;
	float div_cost;
	/* the gptrarray of jteams was passed as the data ptr so we can find
	 * our jteam */
	GPtrArray *jteams = annealer->data_ptr;
	struct _jteam *jteam = g_ptr_array_index(jteams, bucket_id);
	int *lang_count, *div_count;
	int n_round1_jteams = 0;
	int n_round1_dupes = 0;

	/* If the bucket id is zero, it's the bucket for extra judges, have a slight cost */
	if(bucket_id == 0) {
		cost = bucket->len * 5;
		return cost;
	}

	/* Cost over/under */
	if(jteam->award->is_divisional) {
		int min, max;

		if(jteam->round == 1) {
			min = (bucket->len < config.min_judges_per_team) ? (config.min_judges_per_team - bucket->len) : 0;
			max = (bucket->len > config.max_judges_per_team) ? (bucket->len - config.max_judges_per_team) : 0;
		} else {
			min = (bucket->len < config.min_judges_per_cusp_team) ? (config.min_judges_per_cusp_team - bucket->len) : 0;
			max = (bucket->len > config.max_judges_per_cusp_team) ? (bucket->len - config.max_judges_per_cusp_team) : 0;
		}
		cost += min * 1000;
		cost += max * 1000;

	} else {
		assert(0);
	}

	/* Clean out the maps, and set pointers so we can use
	 * nice names */
	memset(judging_data.tmp_isef_div, 0, (isef_divisions->len+1) * sizeof(int));
	memset(judging_data.tmp_lang, 0, 3 * sizeof(int));
	lang_count = judging_data.tmp_lang;
	div_count = judging_data.tmp_isef_div;

	/* For each judge score their div and cat pref.. this is just adding up 
	 * what we've got on the team, it's the same for round 1 and 2, even 
	 * the catprefs */
	for(x=0;x<bucket->len;x++) {
		struct _judge *j = g_ptr_array_index(bucket, x);
		int cat_cost;

		/* Cat */
		if(j->cat_pref == 0) { /* No pref */
			cat_cost = 0;
		} else {
			cat_cost = 10;
			/* Look at all the cats for this jteam, see if the judge
			 * wants one of them */
			for(i=0;i<jteam->award->num_cats;i++) {
				if(jteam->award->cats[i] == j->cat_pref) {
					cat_cost = 0;
					break;
				}
			}
		}
		cost += cat_cost;

		/* Count the judge's languages to the team count */
		lang_count[1] += j->lang[1];
		lang_count[2] += j->lang[2];

		/* Count of all the divs covered by the judges in this jteam.
		 * Count: 2 for each div directly present, and
		 *        1 for each similar div present */
		for(i=0;i<3;i++) {
			struct _isef_division *d = g_ptr_array_index(isef_divisions, j->isef_id_pref[i]);
			div_count[j->isef_id_pref[i]] += 2;

			/* Now iterate over all the similar divs */
			for(y=0;y<d->num_similar;y++) {
				div_count[d->similar[y]] += 1;
			}
		}

		/* Do we have a team lead? */
		if(j->willing_lead) have_lead = 1;

		years_experience_weighted += j->years_school + j->years_regional * 3 + j->years_national * 4;

		/* For round2, we'd like to have some carryover judges, count the n
		 * number of duplicate judges from the same round1 team on this
		 * team and the number from unique round1 teams we've got from
		 * the same award */
		if(jteam->round == 2) {
			struct _jteam *round1_jteam = j->round1_divisional_jteam;
			struct _judge *j2;
			struct _jteam *j2_round1_jteam;

			/* If this jduge has a round1 team and the award is the same  */
			if(round1_jteam != NULL && round1_jteam->award == jteam->award) {
				int dupe = 0;
				for(i=x+1; i<bucket->len; i++) {
					j2 = g_ptr_array_index(bucket, i);
					j2_round1_jteam = j2->round1_divisional_jteam;

					if(j2_round1_jteam == NULL) continue;

					/* Only interested if awards matches the current jteam */
					if(j2_round1_jteam->award != jteam->award) continue;

					if(j2_round1_jteam == round1_jteam) {
						dupe = 1;
					}
				}
				/* If we found a dupe, then count it, if we didn't, that
				 * means this jteam is unique */
				if(dupe) {
					n_round1_dupes ++;
				} else {
					n_round1_jteams ++;
				}
			}

			/* We'd prefer everyone to have some experience in round2 div */
			if(j->years_school + j->years_regional < 1) {
				cost += 50;
			}
		}
	}

	/* Compare what the judges have to what the projects need */
	/* Languages */
	if(jteam->round == 1) {
		for(i=1;i<3;i++) {
			if(jteam->lang_count[i] > 0 && lang_count[i] < bucket->len) {
				/* Some judge doesn't have a needed language */
				cost += 100 * (bucket->len - lang_count[i]);
			} else if(jteam->lang_count[i] == 0 && lang_count[i] > 0) {
				/* The penalty for missing languages should be enough to  pull judges the right way 
				 Some judge has an extra language 
				cost += 1; */
			}
		}

		/* Compare the judge div prefs we have on this team
		 * with what the projects need.  We don't actually use
		 * the 2,1 point system above */
		div_cost = 0;
		for(i=1; i<isef_divisions->len; i++) {
			if(div_count[i] > 0 && jteam->isef_div_count[i] == 0) {
				/* Judge team has a div not needed by the projects, the missing div
				 * penalty should pull this away */
	//			div_cost += judging_data.tmp_isef_div[i];
			} else if (div_count[i] == 0 && jteam->isef_div_count[i] > 0) {
				/* Judge team is missing a div needed by the projects.. this is a problem. */
				div_cost += jteam->isef_div_count[i] * 50;
			} else {
				/* Match or not needed */
				div_cost += 0;
			}
		}
		cost += div_cost;

		if(have_lead == 0) 
			cost += 100;
	}

	if(jteam->round == 2) {
		div_cost = 0;
		/* Don't care about languages or team lead */
		/* Divs, we want a good spread */
		for(i=1; i<isef_divisions->len; i++) {
			struct _isef_division *d = g_ptr_array_index(isef_divisions, i);

			/* Only look at top level divs */
			if(d->parent != -1) continue;

			if(div_count[d->id] == 0) {
				/* No experience in this top-level div */
				div_cost += 20;
			}
		}
		cost += div_cost;

		/* Peanlize two judges on the same round2 team from the same round1 team. */
		cost += n_round1_dupes * 100;

		/* Peanlize not having at least half the round2 members from a j1 team 
		 * judging the same award */
		if( (n_round1_jteams / 2) < bucket->len) {
			cost += (bucket->len - (n_round1_jteams / 2)) * 100;
		}
	}

	/* Small penalty for a jteam with very little experience */
	if(jteam->round == 1 && years_experience_weighted < 10) { 
		cost += (10 - years_experience_weighted) * 5 ;
	}

	assert(cost >= 0);
	return cost;	
}



struct _jteam *jteam_create(struct _db_data *db, GPtrArray *jteams, char *name, struct _award *award)
{
	struct _jteam *jteam = malloc(sizeof(struct _jteam));
	jteam->name = strdup(name);
	jteam->award = award;
	jteam->num = jteams->len;
	jteam->judges = g_ptr_array_new();
	jteam->projects = g_ptr_array_new();
	jteam->isef_div_count = malloc((isef_divisions->len + 1) * sizeof(int));
	memset(jteam->isef_div_count, 0, (isef_divisions->len + 1) * sizeof(int));
	jteam->lang_count = malloc(3 * sizeof(int));
	memset(jteam->lang_count, 0, 3 * sizeof(int));
	g_ptr_array_add(jteams, jteam);

	jteam->sa_only = 0;

	if(db != NULL) {
		db_query(db, "INSERT INTO judging_teams (`num`,`name`,`autocreated`,`round`,`year`,`award_id`) "
					"VALUES ('%d','%s','1','0','%d','%d')",
					jteam->num, jteam->name, current_year, jteam->award->id);
		jteam->id = db_insert_id(db);
	} else {
		jteam->id = -1;
	}
	return jteam;
}

void jteam_print(struct _jteam *jteam) 
{
	int x;
	printf("JTeam %d: %s: %d judges, req:", jteam->id, jteam->name, jteam->judges->len );
	/* ISEF divisions start at 1 */
	for(x=1;x<isef_divisions->len;x++) {
		struct _isef_division *div;
		if(jteam->isef_div_count[x] == 0) continue;
		div = g_ptr_array_index(isef_divisions, x);
		printf(" %s", div->div);
	}
	printf(", langs:");
	if(jteam->lang_count[1] > 0) printf(" en(%d)", jteam->lang_count[1]);
	if(jteam->lang_count[2] > 0) printf(" fr(%d)", jteam->lang_count[2]);

	printf(", %d projects\n", jteam->projects->len);

	for(x=0; x<jteam->judges->len;x++) {
		struct _judge *j = g_ptr_array_index(jteam->judges, x);
		struct _jteam *r1 = j->round1_divisional_jteam;
		judge_print(j);

		if(r1 && jteam->round == 2) {
			printf("      Round1 Jteam: %d:%s\n", r1->num, r1->name);
		}
		
	}
}



void judges_anneal(struct _db_data *db, int year)
{
	int x, y, i;
	GPtrArray *jteams;
	GPtrArray *jteams_list, *judge_list;
	GPtrArray **judge_jteam_assignments = NULL;
	GPtrArray *round1_sa_judges, *round2_sa_judges;

	current_year = year;

	jteams = g_ptr_array_new();

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
//
	/* Remap ISEF ids to only parent id */
	printf("Remap project's ISEF divs to parent div...\n");
	for(i=0;i<projects->len;i++) {
		struct _project *p = g_ptr_array_index(projects, i);
		struct _isef_division *d = g_ptr_array_index(isef_divisions, p->isef_id);
		if(d->parent != -1) p->isef_id = d->parent;
	}



	/* ====================================================================*/
	printf("Delete current autocreated judging teams...\n");
	db_query(db, "DELETE FROM judging_teams WHERE year='%d' and autocreated='1'", year);


	/* ====================================================================*/
	printf("Creating Judging Teams...\n");
	jteam_create(NULL, jteams, "Unused Judges", NULL);

	for(x=0;x<awards->len;x++) {
		struct _award *a = g_ptr_array_index(awards, x);
		int cat_id;
		struct _category *cat;
		int num_jteams;
		GPtrArray **project_jteam_assignments = NULL;

		if(a->num_cats == 0) {
			printf("Award %s has no categories, skipping.\n", a->name);
			continue;
		}

		if(a->is_divisional) {
			if(a->num_cats != 1) {
				printf("ERROR: divisional award %s has %d cats, not 1.\n", a->name, a->num_cats);
				assert(0);
			}

			cat_id = a->cats[0];
			cat = category_find(cat_id);

			/* This award is going to have cusp judges */
			a->cusp_jteams = g_ptr_array_new();

			printf("Annealing Award %s (category: %s)\n", a->name, cat->name);

			/* Assign all projects in this category to the divisional award */
			for(i=0;i<projects->len;i++) {
				struct _project *p = g_ptr_array_index(projects, i);
				if(p->cat_id == cat->id) {
					g_ptr_array_add(a->projects, p);
				}
			}

			/* Calculate number of jteams needed for round1 */
			/* 0/8 = 0, 1/8 ... 8/8 = 1,  9/8 = 2, etc.. */
			num_jteams = ((a->projects->len - 1) / config.max_projects_per_judge) + 1;
			printf("   => %d projects, %d jteams\n", a->projects->len, num_jteams);

			/* Create teams */
			for(i=0;i<num_jteams;i++) {
				struct _jteam *jteam;
				char name[1024];
				sprintf(name, "%s Divisional %d", cat->name, i+1);
				jteam = jteam_create(db, jteams, name, a);
				jteam->round = 1;
				g_ptr_array_add(a->jteams, jteam);
			}

			/* Assign projects (not judges yet) to jteams */
			anneal(a, &project_jteam_assignments, a->jteams->len, a->projects, 
				&jteam_projects_cost, NULL/*&jteam_projects_propose_move*/);

			/* Read data back and save in each jteam */
			for(i=0;i<num_jteams;i++) {
				GPtrArray *ps = project_jteam_assignments[i];
				struct _jteam *jteam = g_ptr_array_index(a->jteams, i);
				printf("JTeam %d: %s: %d projects\n", jteam->num, jteam->name, ps->len );
				g_ptr_array_free(jteam->projects, 1);
				jteam->projects = ps;

				for(y=0; y<ps->len;y++) {
					project_print(g_ptr_array_index(ps, y));
				}
			}

			/* Create cusp teams too */
			for(i=0;i<a->prizes->len;i++) {
				struct _prize *prize = g_ptr_array_index(a->prizes, i);
				struct _prize *next_prize = NULL;
				struct _jteam *jteam;
				char name[1024];
				if(i+1 < a->prizes->len)
					next_prize = g_ptr_array_index(a->prizes, i+1);
				sprintf(name, "%s Cusp %s-%s", cat->name, prize->name, 
					next_prize ? next_prize->name : "Nothing");

				jteam = jteam_create(db, jteams, name, a);
				jteam->round = 2;
				printf("JTeam %d: %s\n", jteam->num, jteam->name );
			}


		} else if(a->is_special) {
			/* Just make one jteam */
			struct _jteam *jteam;
			
			jteam = jteam_create(db, jteams, a->name, a);
			jteam->round = 0;
			printf("JTeam %d: Special Award: %s\n", jteam->num, a->name);

			/* Assign any projects that self-nominated */
			for(i=0;i<projects->len;i++) {
				struct _project *p = g_ptr_array_index(projects, i);
				if(list_contains_int(p->sa_nom, p->num_sa_nom, a->id)) {
					g_ptr_array_add(jteam->projects, p);
				}
			}
			printf("   => Added %d projects that self-nominated\n", jteam->projects->len);
			g_ptr_array_add(a->jteams, jteam);

		} else {
			printf("ERROR: award %s is not divisional or special\n", a->name);
			assert(0);
		}
	}
	printf("   Created %d JTeams.\n", jteams->len);

	/* ====================================================================*/
	/* Build a list of all divisional jteams to anneal first, add the
	 * leftover judges team, then all the round1 divisional teams */
	printf("Building list of Divisional JTeams and available judges...\n");
	jteams_list = g_ptr_array_new();
	g_ptr_array_add(jteams_list, g_ptr_array_index(jteams, 0));
	for(x=1;x<jteams->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, x);
		if (jteam->award->is_divisional && jteam->round == 1) {
			g_ptr_array_add(jteams_list, jteam);
			/* Build the divs and langs taht this jteam will need to have */
			for(i=0;i<jteam->projects->len;i++) {
				struct _project *p = g_ptr_array_index(jteam->projects, i);
				jteam->isef_div_count[p->isef_id] += 1;
				jteam->lang_count[p->language_id] += 1;
			}
		}
	}

	/* All judges available in round1 execpt SA only are candidates */
	judge_list = g_ptr_array_new();
	for(x=0;x<judges->len;x++) {
		struct _judge *j = g_ptr_array_index(judges, x);
		if(j->sa_only == 1) continue;
		if(!j->available_in_round[0]) continue;
		g_ptr_array_add(judge_list, j);
	}
	printf("   Divisional Awards have %d jteams and %d judges available\n", jteams_list->len, judge_list->len);
	anneal(jteams_list, &judge_jteam_assignments, jteams_list->len, judge_list, 
			&jteam_judge_cost, NULL);

	for(i=0;i<jteams_list->len;i++) {
		GPtrArray *js = judge_jteam_assignments[i];
		struct _jteam *jteam = g_ptr_array_index(jteams_list, i);
		jteam->judges = js;
		printf("\n");
		jteam_print(jteam);

		for(x=0; x<jteam->judges->len;x++) {
			struct _judge *j = g_ptr_array_index(jteam->judges, x);
			j->round1_divisional_jteam = jteam;
		}
	}

	/* Save unused round1 judges */
	round1_sa_judges = judge_jteam_assignments[0];

	free(judge_jteam_assignments);
	judge_jteam_assignments = NULL;


	/* ====================================================================*/
	/* Build a list of all cusp jteams to anneal, add the
	 * leftover judges team, then all the cusp teams */
	printf("Building list of Cusp JTeams and available judges...\n");

	g_ptr_array_set_size(jteams_list, 0);
	g_ptr_array_add(jteams_list, g_ptr_array_index(jteams, 0));
	for(x=1;x<jteams->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, x);
		if (jteam->award->is_divisional && jteam->round == 2) {
			g_ptr_array_add(jteams_list, jteam);
		}
	}
	
	g_ptr_array_set_size(judge_list, 0);
	for(x=0;x<judges->len;x++) {
		struct _judge *j = g_ptr_array_index(judges, x);
		if(j->sa_only == 1) continue;
		if(!j->available_in_round[1]) continue; /* [1] == round 2 */
		g_ptr_array_add(judge_list, j);
	}
	printf("   Cusp teams have %d JTeams and %d judges available\n", jteams_list->len, judge_list->len);
//	anneal_set_debug(1);
	anneal(jteams_list, &judge_jteam_assignments, jteams_list->len, judge_list, 
			&jteam_judge_cost, NULL);

	for(i=0;i<jteams_list->len;i++) {
		GPtrArray *js = judge_jteam_assignments[i];
		struct _jteam *jteam = g_ptr_array_index(jteams_list, i);
		jteam->judges = js;
		printf("\n");
		jteam_print(jteam);
	}
	round2_sa_judges = judge_jteam_assignments[0];

	free(judge_jteam_assignments);
	judge_jteam_assignments = NULL;


	/* ====================================================================*/
	/* Assign special-award-only judges to their jteams, and figure out
	 * which round they go in */
	printf("\n");
	printf("Assigning special-awards-only judges...\n");
	for(x=0;x<judges->len;x++) {
		struct _judge *j = g_ptr_array_index(judges, x);
		if(!j->sa_only) continue;

		printf("   sa-only judge: ");
		judge_print(j);

		for(i=0;i<j->num_sa;i++) {
			struct _award *a = award_find(j->sa[i]);
			struct _jteam *jteam = g_ptr_array_index(a->jteams, 0);
			printf("      %d: %s\n", a->id, a->name);  
			assert(a->jteams->len == 1);
			g_ptr_array_add(jteam->judges, j);
			jteam->sa_only = 1;
		}
	}

	/* Now find all sa-only jteams */
	printf("Assigning sa-only JTeams to rounds based on judge availablility...\n");
	for(x=0;x<jteams->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, x);
		int round_sum[3] = {0,0,0};
		int on_jteams_in[3] = {0,0,0};
		int round;
		if(!jteam->sa_only) continue;

		/* Look at the judges on this team */
		for(i=0;i<jteam->judges->len;i++) {
			struct _judge *j = g_ptr_array_index(jteam->judges, i);

			round_sum[0] += j->available_in_round[0];
			round_sum[1] += j->available_in_round[1];
			on_jteams_in[0] += j->on_jteams_in_round[0];
			on_jteams_in[1] += j->on_jteams_in_round[1];
		}

		if(round_sum[0] > round_sum[1]) 
			round = 0;
		else if(round_sum[0] < round_sum[1]) 
			round = 1;
		else { /* Same, assign to round where judges have fewest committments */
			if(on_jteams_in[0] <= on_jteams_in[1])
				round = 0;
			else	
				round = 1;
		}

		printf("   JTeam %d: %s assigned to round %d.  (sum[0]=%d, sum[1]=%d, in[0]=%d, in[1]=%d)\n", jteam->id, jteam->name, round+1,
				round_sum[0], round_sum[1], on_jteams_in[0], on_jteams_in[1]);
		jteam->round = round + 1;
		for(i=0;i<jteam->judges->len;i++) {
			struct _judge *j = g_ptr_array_index(jteam->judges, i);
			j->on_jteams_in_round[round] += 1;
		}
	}
	


	/* ====================================================================*/
	/* Assign special awards to rounds based on available judges
	 * and special-awards-only judges */
	{
	int ideal_projects_in_round[3] = {0,0,0};
	int total_judges, total_projects;
	int sa_judges_available_in_round[3] = {0,0,0};


	printf("Assigning special award JTeams to rounds...\n");

	g_ptr_array_set_size(jteams_list, 0);
	for(x=1;x<jteams->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, x);

		if(jteam->sa_only) continue;
		if(jteam->award->is_divisional) continue;

		g_ptr_array_add(jteams_list, jteam);
	}


	/* Look at the number of projects for each jteam in each round, and 
	 * the number of free judges, and balance the work, favouring
	 * round 2 */

	total_projects = 0;
	for(x=0;x<jteams_list->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams_list, x);
		total_projects += jteam->projects->len;
	}
	sa_judges_available_in_round[0] = round1_sa_judges->len;
	sa_judges_available_in_round[1] = round2_sa_judges->len;
	total_judges = sa_judges_available_in_round[0] + sa_judges_available_in_round[1];

	ideal_projects_in_round[0] = total_projects * sa_judges_available_in_round[0] / total_judges;
	ideal_projects_in_round[1] = total_projects * sa_judges_available_in_round[1] / total_judges;


	printf("   => %d projects, judges in [0]=%d, [1]=%d\n", total_projects, sa_judges_available_in_round[0], sa_judges_available_in_round[1]);
	printf("   => ideally, projects in [0]=%d, [1]=%d\n", ideal_projects_in_round[0], ideal_projects_in_round[1]);

	for(x=0;x<jteams_list->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams_list, x);
		int round;
		int judges_required;
		GPtrArray *judge_list;

		if(jteam->projects->len > ideal_projects_in_round[0]) {
			round = 1;
			judge_list = round2_sa_judges;
		} else {
			round = 0;
			judge_list = round1_sa_judges;
		}


		ideal_projects_in_round[round] -= jteam->projects->len;
		jteam->round = round + 1;
		printf("\n   JTeam %d: %s assigned to round %d.  has %d projects, ideal is now [0]=%d, [1]=%d\n", jteam->id, jteam->name, round+1,
				jteam->projects->len, ideal_projects_in_round[0], ideal_projects_in_round[1]);

		/* Assign judges randomly */
		if(jteam->projects->len > 0) 
			judges_required = (jteam->projects->len - 1) / config.projects_per_sa_judge + 1;
		else 
			judges_required = 0;
		printf("         => %d judges are required.\n", judges_required);
		for(i=0; i<judges_required; i++) {
			struct _judge *j;
			if(judge_list->len == 0) continue;

			j = g_ptr_array_index(judge_list, 0);
			g_ptr_array_remove_index_fast(judge_list, 0);

			g_ptr_array_add(jteam->judges, j);
			printf("      => ");
			judge_print(j);
		}

	}
	
	

	}



	/* Save any updates we made to jteams:
	 * - round has been set 
	 * - user_ids have been set
	 */
	printf("Saving changes to JTeams...");
	for(i=1;i<jteams->len;i++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, i);
		char ids[2048];
		char *ptr;
		db_query(db, "UPDATE judging_teams SET round='%d' WHERE id='%d'", jteam->round, jteam->id);


		ids[0] = 0;
		ptr = &ids[0];
		for(x=0;x<jteam->judges->len;x++) {
			struct _judge *j = g_ptr_array_index(jteam->judges, x);
			if(ptr != &ids[0]) 
				ptr += sprintf(ptr, ",");
			ptr += sprintf(ptr, "%d", j->id);
		}
		db_query(db, "UPDATE judging_teams SET user_ids='%s' WHERE id='%d'", ids, jteam->id);

		ids[0] = 0;
		ptr = &ids[0];
		for(x=0;x<jteam->projects->len;x++) {
			struct _project *p = g_ptr_array_index(jteam->projects, x);
			if(ptr != &ids[0]) 
				ptr += sprintf(ptr, ",");
			ptr += sprintf(ptr, "%d", p->pid);
		}
		db_query(db, "UPDATE judging_teams SET project_ids='%s' WHERE id='%d'", ids, jteam->id);
	}

	/* ====================================================================*/
	/* Assign special awards to rounds based on available judges
	 * and special-awards-only judges */
	{
	struct _judge *timeslot_judge[10][10];
	int timeslot_type[10][10];
	int start_type = 0; /* 0 div, 1 special, 2 nothing */
	static char *timeslot_type_str[3] = {"divisional", "special", "free" };
	int first = 0;
	memset(timeslot_judge, 0, sizeof(timeslot_judge));
	memset(timeslot_type, 0, sizeof(timeslot_type));


	/* Do timeslot assignments */
	printf("Doing Timeslot Assignments...");
	db_query(db, "DELETE FROM timeslot_assignments WHERE year='%d'", year);
	for(i=1;i<jteams->len;i++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, i);
		int current_timeslot_type;
		int start_judge_index = 0;
		int judge_index = 0;
		GString *q1;
		start_type = 0;
		if(!jteam->award->is_divisional || jteam->round!= 1) continue;

		for(x=0;x<jteam->projects->len;x++) {
			current_timeslot_type = start_type;
			judge_index = start_judge_index;
			/* Go down all 9 timeslots, filling them out */
			for(y=0;y<9;y++) {
				timeslot_type[x][y] = current_timeslot_type;
				if(current_timeslot_type == 0) {
					timeslot_judge[x][y] = g_ptr_array_index(jteam->judges, judge_index);
					judge_index++;
				}
				current_timeslot_type++;
				if(current_timeslot_type == 3) current_timeslot_type = 0;
			}
			start_judge_index++;
			if(start_judge_index == jteam->judges->len) {
				/* Rotate start type backwards so it appears the div judge slot
				 * is pushed down one */
				start_judge_index = 0;
				start_type = (start_type == 0) ? 2 : (start_type-1);
			}
		}

		printf("   Timeslot assignments for %s\n", jteam->name);
		for(x=0;x<jteam->projects->len;x++) {
			struct _project *p = g_ptr_array_index(jteam->projects, x);
			printf("\t%d", p->pid);
		}
		printf("\n");

		q1 = g_string_new("");

		first = 1;
		for(y=0;y<9;y++) {
			printf("%d", y+1);
			for(x=0;x<jteam->projects->len;x++) {
				struct _project *p = g_ptr_array_index(jteam->projects, x);
				struct _judge *j = timeslot_judge[x][y];
				if(timeslot_type[x][y] == 0) 
					printf("\t(%d)", j->id);
				else if(timeslot_type[x][y] == 2) 
					printf("\t--");
				else
					printf("\t%d", timeslot_type[x][y]);


				if(!first) g_string_append_printf(q1, ",");
				first = 0;

				g_string_append_printf(q1, "('%d','%d','%d','%d','%s','%d'),",
						(y+1) + (jteam->round - 1)*9,
						p->pid, 
						timeslot_type[x][y] == 0 ? jteam->id : 0, 
						timeslot_type[x][y] == 0 ? j->id : 0, 
						timeslot_type_str[timeslot_type[x][y]],
						year);

				g_string_append_printf(q1, "('%d','%d','%d','%d','%s','%d')",
						(y+1) + 9,
						p->pid, 
						0, 
						0,
						timeslot_type_str[timeslot_type[x][y]],
						year);
			}
			printf("\n");
		}
		db_query(db, "INSERT INTO timeslot_assignments (`num`,`pid`,`judging_team_id`,`judge_id`,`type`,`year`) VALUES %s", 
				q1->str);
		g_string_free(q1, 1);
	}
	}


	printf("All done!\n");
}


#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <assert.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>


#include <glib.h>

#include "sfiab.h"
#include "anneal.h"
#include "db.h"
#include "judges.h"
#include "students.h"
#include "judges.h"
#include "timeslots.h"

static int current_year = 0;

struct _jteam {
	int id, num;
	char *name;
	struct _award *award;
	int round;
	int sa_only; /* If this jteam is for sa_only judges (no scheduling) */
	int prize_id;

	int num_judges_required; /* div round 1 only */

	int *isef_div_count;
	int *isef_div_mask;
	int *lang_count;

	GPtrArray *judges;
	GPtrArray *projects;
};

void jteam_print(struct _jteam *jteam) ;


void scheduler_log(struct _db_data *db, int result, char *msg, ...)
{
	va_list args;
	char buffer[1024];
	char escaped_buffer[1024];
        va_start(args,msg);
        vsprintf(buffer, msg, args);
        va_end(args);

	db_escape_str(escaped_buffer, buffer);

	db_query(db, "INSERT INTO log (`ip`,`time`,`year`,`type`,`data`,`result`) "
					"VALUES ('commandline',NOW(),'%d','judge_scheduler','%s','%d')",
					current_year, escaped_buffer, result);
}

/* Cost for projects assigned to each jteam */
float jteam_projects_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
	int x, y, iproject;
	float cost = 0;
	int unique_divs = 0;
	int unique_langs = 0;
	int min_mask_missing = isef_divisions->len+1;
	int language_count[NUM_LANGUAGES];

	struct _award *a = annealer->data_ptr;
	struct _jteam *jteam = g_ptr_array_index(a->jteams, bucket_id);

	/* +1 because an ID starts at 1, not 0 */
	memset(language_count, 0, NUM_LANGUAGES * sizeof(int));
	memset(jteam->isef_div_mask, 0, isef_divisions->len * sizeof(int));

	
	for(iproject=0; iproject<bucket->len; iproject++) {
		struct _project *p = g_ptr_array_index(bucket, iproject);

		if(jteam->isef_div_mask[p->isef_id] == 0) unique_divs += 1;
		jteam->isef_div_mask[p->isef_id] = 2; /* Primary */

		if(language_count[p->language_id] == 0) unique_langs += 1;
		language_count[p->language_id] += 1;
	}

	/* For each div in the isef divs, see if the mask for that div matches the 
	 * entire div set.  We want to find one div that matches everything so that
	 * all isef_divs in this jteam are "related" */
	for(x=1;x<isef_divisions->len;x++) {
		struct _isef_division *test_div;
		int mask_missing = 0;
		if(jteam->isef_div_mask[x] == 0) continue;

		test_div = g_ptr_array_index(isef_divisions, x);
		for(y=1;y<=isef_divisions->len;y++) {
			if(jteam->isef_div_mask[y] > 0 && test_div->similar_mask[y] == 0) {
				/* This jteam has a div that isn't in the current mask, no match. 
				 * Note: similar_mask also stores the primary div */
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
	if(bucket->len > config.max_projects_per_team) {
		cost += (bucket->len - config.max_projects_per_team) * 100;
	}

	/* Cost 1 for each project over 3/4 the max, just to break ties to
	 * avoid one jteam with 5 projects, and another with 7 */
	if(bucket->len > (config.max_projects_per_team * 3 / 4)) {
		cost += bucket->len - (config.max_projects_per_team * 3 / 4);
	}

	return cost;	
}

/* Cost for judges assigned to a divisional jteam 
 * This handles both round0 and round1 (cusp) */
float jteam_judge_cost(struct _annealer *annealer, int bucket_id, GPtrArray *bucket)
{
	int x, i;
	float cost = 0;
	int have_lead = 0;
	int years_experience_weighted = 0;
	/* the gptrarray of jteams was passed as the data ptr so we can find
	 * our jteam */
	GPtrArray *jteams = annealer->data_ptr;
	struct _jteam *jteam = g_ptr_array_index(jteams, bucket_id);
	int lang_count[NUM_LANGUAGES];
	int n_round0_jteams = 0;
	int n_round0_dupes = 0;

	/* If the bucket id is zero, it's the bucket for extra judges, have a slight cost */
	if(bucket_id == 0) {
		cost = bucket->len * 5;
		return cost;
	}

//	printf("Calculate cost for jteam: \n");
//	jteam_print(jteam);

	/* Cost over/under */
	if(jteam->award->is_divisional) {
		int min = (bucket->len < jteam->num_judges_required) ? (jteam->num_judges_required - bucket->len) : 0;
		int max = (bucket->len > jteam->num_judges_required) ? (bucket->len - jteam->num_judges_required) : 0;
		cost += min * 10000;
		cost += max * 10000;

	} else {
		assert(0);
	}

	memset(lang_count, 0, NUM_LANGUAGES * sizeof(int));
	if(jteam->round != 0) {
		memset(jteam->isef_div_mask, 0, isef_divisions->len * sizeof(int));
	}

	/* For each judge score their div and cat pref.. this is just adding up 
	 * what we've got on the team, it's the same for round 1 and 2, even 
	 * the catprefs */
	for(x=0;x<bucket->len;x++) {
		struct _judge *j = g_ptr_array_index(bucket, x);
		int cat_cost;
		int judge_missing_divs, idiv;

//		printf("Judge %d: ", x);
//		judge_print(j);

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
//		printf("   cat cost = %d\n", cat_cost);

		/* Count the judge's languages to the team count */
		for(i=0;i<NUM_LANGUAGES;i++) {
			lang_count[i] += j->lang[i];
		}

		if(jteam->round == 0) {
			/* Match the divs the judge has with the divs the jteam needs */
			judge_missing_divs = 0;
			for(idiv = 1; idiv < isef_divisions->len; idiv++) {
				if(jteam->isef_div_mask[idiv] == 2 && j->isef_div_mask[idiv] == 0) {
					/* Judge is missing this primary div for the jteam, that's bad */
					judge_missing_divs += 10;
				} else if(jteam->isef_div_mask[idiv] == 1 && j->isef_div_mask[idiv] == 0) {
					/* Judge is missing this similar div for the jteam, that's not bad */
					judge_missing_divs += 1;
				} else if(jteam->isef_div_mask[idiv] == 2 && j->isef_div_mask[idiv] == 1) {
					/* Judge only has a similar match for the primary need of the jteam */
					judge_missing_divs += 5;
				}
			}
			/* Square the cost so more missing divs gets worse and worse */
			cost += judge_missing_divs * judge_missing_divs;
	//		printf("   div cost = %d\n", judge_missing_divs * judge_missing_divs);
			
			/* Check languages */
			for(i=0;i<NUM_LANGUAGES;i++) {
				if(jteam->lang_count[i] > 0 && j->lang[i] == 0) {
					/* This judge is missing a language */
					cost += 200;
				}
			}

		} else {
			/* For cusp teams there are no projects yet, so there can't be any div cost, we just want
			 * a good spread of the major divs, so add them up.  Since the jteam's isef_div_mask
			 * is unused, we'll reuse it here to count up the divs this jteam has.  We cleared it 
			 * above, and we use it below once all the judges are added up */
			for(idiv = 1; idiv < isef_divisions->len; idiv++) {
				jteam->isef_div_mask[idiv] += j->isef_div_mask[idiv];
			}
		}

		/* Do we have a team lead? */
		if(j->willing_lead) have_lead = 1;

		years_experience_weighted += j->years_school + j->years_regional * 3 + j->years_national * 4;

		/* For round1, we'd like to have some carryover judges, count the n
		 * number of duplicate judges from the same round0 team on this
		 * team and the number from unique round0 teams we've got from
		 * the same award */
		if(jteam->round == 1) {
			struct _jteam *round0_jteam = j->round0_divisional_jteam;
			struct _judge *j2;
			struct _jteam *j2_round0_jteam;

			/* If this judge has a round0 team and the award is the same  */
			if(round0_jteam != NULL && round0_jteam->award == jteam->award) {
				int dupe = 0;
				for(i=x+1; i<bucket->len; i++) {
					j2 = g_ptr_array_index(bucket, i);
					j2_round0_jteam = j2->round0_divisional_jteam;

					if(j2_round0_jteam == NULL) continue;

					/* Only interested if awards matches the current jteam */
					if(j2_round0_jteam->award != jteam->award) continue;

					if(j2_round0_jteam == round0_jteam) {
						dupe = 1;
					}
				}
				/* If we found a dupe, then count it, if we didn't, that
				 * means this jteam is unique */
				if(dupe) {
					n_round0_dupes ++;
				} else {
					n_round0_jteams ++;
				}
			}

			/* We'd prefer everyone to have some experience in round1 div */
			if(j->years_school + j->years_regional < 1) {
				cost += 50;
			}
		}


		/* See if there is a conflicting project on this team */
		for(i=0; i<jteam->projects->len; i++) {
			struct _project *p = g_ptr_array_index(jteam->projects, i);
			if(list_contains_int(j->avoid_pids, j->num_avoid_pids, p->pid)) {
				/* This jteam contains a project this judge isn't supposed to judge */
				cost += 1000;
			}
		}
	}

	/* Compare what the judges have to what the projects need */
	/* Languages */
	if(jteam->round == 0) {
		if(have_lead == 0) {
		//	printf("   Lead: 100\n");
			cost += 100;
		}

		/* Small penalty for a jteam with very little experience */
		if(years_experience_weighted < 10) { 
			cost += (10 - years_experience_weighted) * 5 ;
		}
	}

	if(jteam->round == 1) {
		int div_cost = 0;
		/* Don't care about languages or team lead */
		/* Divs, we want a good spread */
		for(i=1; i<isef_divisions->len; i++) {
			struct _isef_division *d = g_ptr_array_index(isef_divisions, i);

			/* Only look at top level divs */
			if(d->parent != -1) continue;

			if(jteam->isef_div_mask[d->id] == 0) {
				/* No experience in this top-level div */
				div_cost += 20;
			}
		}
		cost += div_cost;

		/* Penalize two judges on the same round1 team from the same round0 team. */
		cost += n_round0_dupes * 100;

		/* Penalize not having at least half the round1 members from a round0 team 
		 * judging the same award */
		if( (n_round0_jteams / 2) < bucket->len) {
			cost += (bucket->len - (n_round0_jteams / 2)) * 100;
		}
	}

	assert(cost >= 0);

//	printf("   Cost: %.1g\n", cost);
	return cost;	
}


int jteam_find_best_judge_from_list(struct _jteam *jteam, GPtrArray *judges)
{
	int idiv, ijudge, iproject;
	int lowest_div_missing = isef_divisions->len;;
	int lowest_judge = -1;


	for(ijudge=0; ijudge<judges->len; ijudge++) {
		struct _judge *judge = g_ptr_array_index(judges, ijudge);
		int div_missing = 0;
		int ok = 1;

		/* Search all projects on this jteam to see if the judge is allowed to judge them 
		 * all */
		if(judge->num_avoid_pids > 0) {
			for(iproject=0; iproject<jteam->projects->len; iproject++) {
				struct _project *p = g_ptr_array_index(jteam->projects, iproject);
				if(list_contains_int(judge->avoid_pids, judge->num_avoid_pids, p->pid)) {
					/* This jteam contains a project this judge isn't supposed to judge */
					ok = 0;
					break;
				}
			}
			if(!ok) continue;
		}
		

		for(idiv=0;idiv<isef_divisions->len;idiv++) {
			if(jteam->isef_div_mask[idiv] == 2 && judge->isef_div_mask[idiv] != 2) {
				div_missing++;
			}
		}
		if(div_missing < lowest_div_missing) {
			lowest_div_missing = div_missing;
			lowest_judge = ijudge;
		}
	}
//	printf("Lowest missing = %d\n", lowest_div_missing);
	return lowest_judge;
}




struct _jteam *jteam_create(struct _db_data *db, GPtrArray *jteams, char *name, struct _award *award)
{
	struct _jteam *jteam = malloc(sizeof(struct _jteam));
	char jteam_name[1024];

	jteam->name = strdup(name);
	jteam->award = award;
	jteam->num = jteams->len;
	jteam->judges = g_ptr_array_new();
	jteam->projects = g_ptr_array_new();
	jteam->isef_div_count = malloc(isef_divisions->len * sizeof(int));
	memset(jteam->isef_div_count, 0, isef_divisions->len * sizeof(int));
	jteam->isef_div_mask = malloc(isef_divisions->len * sizeof(int));
	memset(jteam->isef_div_mask, 0, isef_divisions->len * sizeof(int));
	jteam->lang_count = malloc(NUM_LANGUAGES * sizeof(int));
	memset(jteam->lang_count, 0, NUM_LANGUAGES * sizeof(int));
	g_ptr_array_add(jteams, jteam);

	jteam->sa_only = 0;
	jteam->prize_id = 0;

	/* Escape some characters for mysql */
	db_escape_str(jteam_name, jteam->name);

	if(db != NULL) {
		db_query(db, "INSERT INTO judging_teams (`num`,`name`,`autocreated`,`round`,`year`,`award_id`) "
					"VALUES ('%d','%s','1','0','%d','%d')",
					jteam->num, jteam_name, current_year, jteam->award->id);
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
	if(jteam->lang_count[LANGUAGE_ENGLISH] > 0) printf(" en(%d)", jteam->lang_count[LANGUAGE_ENGLISH]);
	if(jteam->lang_count[LANGUAGE_FRENCH] > 0) printf(" fr(%d)", jteam->lang_count[LANGUAGE_FRENCH]);

	printf(", %d projects\n", jteam->projects->len);

	for(x=0; x<jteam->judges->len;x++) {
		struct _judge *j = g_ptr_array_index(jteam->judges, x);
		struct _jteam *r1 = j->round0_divisional_jteam;
		judge_print(j);

		if(r1 ) {
			printf("          Round1 Jteam: %d:%s\n", r1->num, r1->name);
		}
		
	}
}


int judges_anneal_checks(struct _db_data *db)
{
	int x, i;
	int ok = 1;

	printf("Running Checks...\n");

	if(projects->len <= 0) {
		scheduler_log(db, 99, "There are no projects.  Nothing to do.");
		printf("   There are no projects.  Nothing to do.\n");
		ok = 0;
	}

	/* All Divisional awards should have exactly one category */
	for(i=0;i<awards->len;i++) {
		struct _award *a = g_ptr_array_index(awards, i);
		if(a->is_divisional) {
			if(a->num_cats != 1) {
				scheduler_log(db, 99, "Divisional Award %s has %d categories, not 1.", a->name, a->num_cats);
				printf("   Divisional Award %s has %d categories, not 1.\n", a->name, a->num_cats);
				ok = 0;
			}

			if(a->prizes->len < 1) {
				scheduler_log(db, 99, "Divisional Award %s has no prizes", a->name);
				printf("   Divisional Award %s has no prizes.\n", a->name);
				ok = 0;
			}
		}
	}

	if(!ok) return ok;
	printf("   All Divisional Awards have exactly one category\n");
	printf("   All Divisional Awards have at least one prize\n");


	/* Each category should have exactly one award */
	for(x=0; x<categories->len;x++) {
		struct _category *cat = g_ptr_array_index(categories, x);
		int count = 0;

		/* Find the award */
		for(i=0;i<awards->len;i++) {
			struct _award *a = g_ptr_array_index(awards, i);

			if(a->is_divisional) {
				/* We already know the award has one cat */
				int cat_id = a->cats[0];

				if(cat_id == cat->id) {
					count++;
				}
			}
		}

		if(count != 1) {
			scheduler_log(db, 99, "Category %s has %d divisional awards, not 1.", cat->name, count);
			printf("   Category %s has %d awards, not 1.\n", cat->name, count);
			ok = 0;
		}
	}

	if(!ok) return ok;
	printf("   All Categories have exactly one Divisional Award\n");
	printf("   Checks done.\n");

	return ok;
}


int get_judges_for_project(int num_projects, int each_project_judged)
{
	/* This is really just the smallest n, such that
	 * nCr > num_projects */
	int r_fac = factorial(each_project_judged);
	int num_judges;
	for(num_judges=each_project_judged+1; num_judges<10; num_judges++) {
		int max_p = factorial(num_judges) / (r_fac * factorial(num_judges - each_project_judged) );
		if(max_p > num_projects) 
			return num_judges;
	}
	return each_project_judged;

}



void judges_anneal(struct _db_data *db, int year)
{
	int x, y, i;
	int iproject;
	int lang_count[NUM_LANGUAGES];
	GPtrArray *jteams;
	GPtrArray *jteams_list, *judge_list;
	GPtrArray **judge_jteam_assignments = NULL;
	GPtrArray *round0_sa_judges, *round1_sa_judges;

	current_year = year;

	jteams = g_ptr_array_new();

	students_load(db, year);
	projects_load(db, year);
	projects_crosslink_students();

	categories_load(db, year);
	isef_divisions_load(db, year);

	judges_load(db, year);
	awards_load(db, year);

	timeslots_load(db, year);
//
	/* Remap ISEF ids to only parent id */
	printf("Remap project's ISEF divs to parent div...\n");
	for(iproject=0;iproject<projects->len;iproject++) {
		struct _project *p = g_ptr_array_index(projects, iproject);
		struct _isef_division *d = g_ptr_array_index(isef_divisions, p->isef_id);
		if(d->parent != -1) p->isef_id = d->parent;
	}


	scheduler_log(db, 1, "Judge Scheduler starting");

	if(!judges_anneal_checks(db) ) {
		printf("Judge Annealing checks failed.  exit.\n");
		scheduler_log(db, 100, "Judge Scheduler checks failed. Abort.");
		return;
	}



	/* ====================================================================*/
	printf("Delete current autocreated judging teams...\n");
	db_query(db, "DELETE FROM judging_teams WHERE year='%d' and autocreated='1'", year);
	scheduler_log(db, 2, "Deleting old auto-created judging teams and assignments");


	/* ====================================================================*/
	scheduler_log(db, 3, "Creating new judging teams");
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
			int num_languages;
			if(a->num_cats != 1) {
				printf("ERROR: divisional award %s has %d cats, not 1.\n", a->name, a->num_cats);
				assert(0);
			}

			cat_id = a->cats[0];
			cat = category_find(cat_id);

			/* This award is going to have cusp judges */
			a->cusp_jteams = g_ptr_array_new();

			printf("Assigning Projects to Award %s (category: %s)\n", a->name, cat->name);

			/* Assign all projects in this category to the divisional award
			 * Count the number of unique languages */
			memset(lang_count, 0, NUM_LANGUAGES * sizeof(int));
			for(iproject=0;iproject<projects->len;iproject++) {
				struct _project *p = g_ptr_array_index(projects, iproject);
				if(p->cat_id == cat->id) {
					g_ptr_array_add(a->projects, p);
					lang_count[p->language_id]++;
				}
			}

			num_languages = 0;
			for(i=0;i<NUM_LANGUAGES;i++) {
				if(lang_count[i] > 0) num_languages++;
			}

			/* Calculate number of jteams needed for round0 */
			/* Add one to the jteams for each extra language to help avoid mixing languages */
			/* 0/8 = 0, 1/8 ... 8/8 = 1,  9/8 = 2, etc.. */
			num_jteams = ((a->projects->len - 1) / config.max_projects_per_team) + 1 + (num_languages - 1);

			printf("   => %d projects, %d jteams (added %d for extra languages)\n", a->projects->len, num_jteams, num_languages - 1);

			/* Create teams */
			for(i=0;i<num_jteams;i++) {
				struct _jteam *jteam;
				char name[1024];
				sprintf(name, "%s Divisional %d", cat->name, i+1);
				jteam = jteam_create(db, jteams, name, a);
				jteam->round = 0;
				g_ptr_array_add(a->jteams, jteam);
			}

			/* Assign projects (not judges yet) to jteams */
			anneal(a, &project_jteam_assignments, a->jteams->len, a->projects, 
				&jteam_projects_cost, NULL/*&jteam_projects_propose_move*/, NULL/* progress callback*/);

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

				if(config.judge_shuffle) {
					struct _timeslot *ts = g_ptr_array_index(timeslots, 0);
					jteam->num_judges_required = (ps->len * config.div_times_each_project_judged) / ts->num_timeslots + 1;

					//get_judges_for_project(ps->len, config.div_times_each_project_judged);
				} else {
					jteam->num_judges_required = config.div_times_each_project_judged;
				}
				printf("   => %d judges required\n", jteam->num_judges_required);
			}

			/* Create cusp teams too */
			printf("%d prizes for CUSP\n", a->prizes->len);
			for(i=0;i<a->prizes->len;i++) {
				struct _prize *prize = g_ptr_array_index(a->prizes, i);
				struct _prize *next_prize = NULL;
				struct _jteam *jteam;
				char name[1024];
				if(i+1 < a->prizes->len) {
					next_prize = g_ptr_array_index(a->prizes, i+1);
				}
				sprintf(name, "%s Cusp %s-%s", cat->name, prize->name, 
					next_prize ? next_prize->name : "Nothing");

				jteam = jteam_create(db, jteams, name, a);
				jteam->round = 1;
				jteam->prize_id = prize->id;
				jteam->num_judges_required = config.max_judges_per_cusp_team;
				printf("JTeam %d: %s\n", jteam->num, jteam->name );
				printf("   => %d judges required\n", jteam->num_judges_required);
			}


		} else if(a->is_special) {
			/* Just make one jteam */
			struct _jteam *jteam;
			
			jteam = jteam_create(db, jteams, a->name, a);
			jteam->round = 0;
			printf("JTeam %d: Special Award: %s\n", jteam->num, a->name);

			/* Assign any projects that self-nominated */
			for(iproject=0;iproject<projects->len;iproject++) {
				struct _project *p = g_ptr_array_index(projects, iproject);
				if(list_contains_int(p->sa_nom, p->num_sa_nom, a->id)) {
					g_ptr_array_add(jteam->projects, p);
				}
			}
			printf("   => Added %d projects that self-nominated\n", jteam->projects->len);

			if(jteam->projects->len > 0) 
				jteam->num_judges_required = (jteam->projects->len - 1) / config.projects_per_sa_judge + 1;
			else 
				jteam->num_judges_required = 1; /* Assign one judge because we want to give out the award, probably */
			printf("   => %d judges required\n", jteam->num_judges_required);

			g_ptr_array_add(a->jteams, jteam);

		} else {
			printf("ERROR: award %s is not divisional or special\n", a->name);
			assert(0);
		}
	}
	scheduler_log(db, 25, "Created %d judging teams", jteams->len);
	printf("   Created %d JTeams.\n", jteams->len);

	/* ====================================================================*/
	/* Build a list of all divisional jteams to anneal first, add the
	 * leftover judges team, then all the round0 divisional teams */
	printf("Building list of Divisional JTeams and available judges...\n");
	jteams_list = g_ptr_array_new();
	g_ptr_array_add(jteams_list, g_ptr_array_index(jteams, 0));
	for(x=1;x<jteams->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, x);

		memset(jteam->isef_div_mask, 0, isef_divisions->len * sizeof(int));
		memset(jteam->isef_div_count, 0, isef_divisions->len * sizeof(int));
		memset(jteam->lang_count, 0, NUM_LANGUAGES * sizeof(int));

		/* Build the divs and langs that this jteam will need to have */
		for(iproject=0;iproject<jteam->projects->len;iproject++) {
			struct _project *p = g_ptr_array_index(jteam->projects, iproject);
			struct _isef_division *div = g_ptr_array_index(isef_divisions, p->isef_id);
			jteam->isef_div_count[p->isef_id] += 1;
			jteam->isef_div_mask[p->isef_id] = 2;
			jteam->lang_count[p->language_id] += 1;

			/* Expand the mask, but don't overwrite primary divs */
			for(i=0;i<div->num_similar; i++) {
				if(jteam->isef_div_mask[div->similar[i]] != 2) {
					jteam->isef_div_mask[div->similar[i]] = 1;
				}
			}
		}
		
		/* Add this team to our round 1 divisional list */
		if (jteam->award->is_divisional && jteam->round == 0) {
			g_ptr_array_add(jteams_list, jteam);
		}

		
	}

	/* All judges available in round0 execpt SA only are candidates */
	judge_list = g_ptr_array_new();
	for(x=0;x<judges->len;x++) {
		struct _judge *j = g_ptr_array_index(judges, x);
		if(j->sa_only == 1) continue;
		if(!j->available_in_round[0]) continue;
		g_ptr_array_add(judge_list, j);
	}
	scheduler_log(db, 25, "Assigning %d available first round judges to %d divisional judging teams", judge_list->len, jteams_list->len);
	printf("   Divisional Awards have %d jteams and %d judges available\n", jteams_list->len, judge_list->len);
	anneal(jteams_list, &judge_jteam_assignments, jteams_list->len, judge_list, 
			&jteam_judge_cost, NULL, NULL/* progress callback*/);

	for(i=0;i<jteams_list->len;i++) {
		GPtrArray *js = judge_jteam_assignments[i];
		struct _jteam *jteam = g_ptr_array_index(jteams_list, i);
		jteam->judges = js;
		printf("\n");
		jteam_print(jteam);

		for(x=0; x<jteam->judges->len;x++) {
			struct _judge *j = g_ptr_array_index(jteam->judges, x);
			j->round0_divisional_jteam = jteam;
		}
	}

	/* Save unused round0 judges */
	round0_sa_judges = judge_jteam_assignments[0];

	free(judge_jteam_assignments);
	judge_jteam_assignments = NULL;


	/* ====================================================================*/
	/* Build a list of all cusp jteams to anneal, add the
	 * leftover judges team, then all the cusp teams */

	if(timeslots->len <= 2) {
		scheduler_log(db, 50, "Skipping second round assignments because there is only one round defined.");
	} else {
		printf("Building list of Cusp JTeams and available judges...\n");

		g_ptr_array_set_size(jteams_list, 0);
		g_ptr_array_add(jteams_list, g_ptr_array_index(jteams, 0));
		for(x=1;x<jteams->len;x++) {
			struct _jteam *jteam = g_ptr_array_index(jteams, x);
			if (jteam->award->is_divisional && jteam->round == 1) {
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
		scheduler_log(db, 50, "Assigning %d available second round judges to %d CUSP judging teams", judge_list->len, jteams_list->len);
		printf("   Cusp teams have %d JTeams and %d judges available\n", jteams_list->len, judge_list->len);
	//	anneal_set_debug(1);
		anneal(jteams_list, &judge_jteam_assignments, jteams_list->len, judge_list, 
				&jteam_judge_cost, NULL, NULL/* progress callback*/);

		for(i=0;i<jteams_list->len;i++) {
			GPtrArray *js = judge_jteam_assignments[i];
			struct _jteam *jteam = g_ptr_array_index(jteams_list, i);
			jteam->judges = js;
			printf("\n");
			jteam_print(jteam);
		}
		round1_sa_judges = judge_jteam_assignments[0];

		free(judge_jteam_assignments);
		judge_jteam_assignments = NULL;
	}


	/* ====================================================================*/
	/* Assign special-award-only judges to their jteams, then assign the jteam
	 * to a round */
	printf("\n");
	printf("Assigning special-award-only judges...\n");
	scheduler_log(db, 75, "Assigning special award judges to judging teams");
	for(x=0;x<judges->len;x++) {
		struct _judge *j = g_ptr_array_index(judges, x);
		if(!j->sa_only) continue;

		printf("   sa-only judge: ");
		judge_print(j);

		for(i=0;i<j->num_sa;i++) {
			struct _award *a = award_find(j->sa[i]);
			struct _jteam *jteam;
			
			printf("      %d: %s\n", a->id, a->name);  

			if(a->jteams->len == 0) {
				printf("            This award has no jteam (probably because it has no prizes), could not assign judge.\n");
				continue;
			}
			assert(a->jteams->len == 1);
			
			jteam = g_ptr_array_index(a->jteams, 0);
			g_ptr_array_add(jteam->judges, j);
			jteam->sa_only = 1;
		}
	}

	/* Assign sa-only teams to rounds  */
	printf("Assigning sa-only JTeams to rounds based on judge availability...\n");
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

		printf("   JTeam %d: %s assigned to round %d.  (sum[0]=%d, sum[1]=%d, in[0]=%d, in[1]=%d)\n", jteam->id, jteam->name, round,
				round_sum[0], round_sum[1], on_jteams_in[0], on_jteams_in[1]);
		jteam->round = round;
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


	scheduler_log(db, 80, "Assigning special award judging teams to rounds");
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
	sa_judges_available_in_round[0] = round0_sa_judges->len;
	if(round1_sa_judges) sa_judges_available_in_round[1] = round1_sa_judges->len;
	total_judges = sa_judges_available_in_round[0] + sa_judges_available_in_round[1];

	ideal_projects_in_round[0] = total_projects * sa_judges_available_in_round[0] / total_judges;
	if(round1_sa_judges) ideal_projects_in_round[1] = total_projects * sa_judges_available_in_round[1] / total_judges;


	printf("   => %d projects, judges in [0]=%d, [1]=%d\n", total_projects, sa_judges_available_in_round[0], sa_judges_available_in_round[1]);
	printf("   => ideally, projects in [0]=%d, [1]=%d\n", ideal_projects_in_round[0], ideal_projects_in_round[1]);

	for(x=0;x<jteams_list->len;x++) {
		struct _jteam *jteam = g_ptr_array_index(jteams_list, x);
		int round;
		GPtrArray *judge_list;

		if(jteam->projects->len > ideal_projects_in_round[0] && timeslots->len > 1) {
			round = 1;
			judge_list = round1_sa_judges;
		} else {
			round = 0;
			judge_list = round0_sa_judges;
		}


		ideal_projects_in_round[round] -= jteam->projects->len;
		jteam->round = round;
		printf("\n   JTeam %d: %s assigned to round %d.  has %d projects, ideal is now [0]=%d [1]=%d, mask=", jteam->id, jteam->name, round,
				jteam->projects->len, ideal_projects_in_round[0], ideal_projects_in_round[1]);
		for(i=0;i<isef_divisions->len;i++) {
			if(jteam->isef_div_mask[i] == 2) {
				struct _isef_division *d = g_ptr_array_index(isef_divisions, i);
				printf(" %s", d->div);
			}
		}
		printf("\n");

		printf("         => %d judges are required.\n", jteam->num_judges_required);
		for(i=0; i<jteam->num_judges_required; i++) {
			struct _judge *j;
			int ijudge;
			if(judge_list->len == 0) continue;

			ijudge = jteam_find_best_judge_from_list(jteam, judge_list);

			if(ijudge == -1) continue;

			j = g_ptr_array_index(judge_list, ijudge);
			g_ptr_array_remove_index_fast(judge_list, ijudge);

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
	printf("Saving changes to JTeams...\n");
	for(i=1;i<jteams->len;i++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, i);
		char judge_ids[2048];
		char project_ids[2048];
		char *ptr;

		judge_ids[0] = 0;
		ptr = &judge_ids[0];
		for(x=0;x<jteam->judges->len;x++) {
			struct _judge *j = g_ptr_array_index(jteam->judges, x);
			if(ptr != &judge_ids[0]) 
				ptr += sprintf(ptr, ",");
			ptr += sprintf(ptr, "%d", j->id);
		}

		project_ids[0] = 0;
		ptr = &project_ids[0];
		for(iproject=0;iproject<jteam->projects->len;iproject++) {
			struct _project *p = g_ptr_array_index(jteam->projects, iproject);
			if(ptr != &project_ids[0]) 
				ptr += sprintf(ptr, ",");
			ptr += sprintf(ptr, "%d", p->pid);
		}
		db_query(db, "UPDATE judging_teams SET round='%d',user_ids='%s',project_ids='%s',prize_id='%d' WHERE id='%d'", 
				jteam->round, judge_ids, project_ids, jteam->prize_id, jteam->id);
	}

	/* ====================================================================*/
	/* Timeslot assignments */
	printf("Doing Timeslot Assignments...\n");
	scheduler_log(db, 90, "Doing timeslot assignments");


	judges_timeslots(db, year, 0);

	scheduler_log(db, 100, "Done.");

	printf("All done!\n");
}

GPtrArray *jteams_load(struct _db_data *db, int year)
{
	struct _db_result *result;
	GPtrArray *jteams;
	int x, i , j;
	jteams = g_ptr_array_new();
	/* Load students and tour choices */
	result = db_query(db, "SELECT * FROM judging_teams WHERE year='%d'", year);
	for(x=0;x<result->rows; x++) {
		struct _jteam *jteam = malloc(sizeof(struct _jteam));
		int award_id = atoi(db_fetch_row_field(result, x, "award_id"));
		int n_list;
		int list[1024];

		jteam->id = atoi(db_fetch_row_field(result, x, "id"));
		jteam->num = atoi(db_fetch_row_field(result, x, "num"));
		jteam->name = strdup(db_fetch_row_field(result, x, "name"));
		jteam->round = atoi(db_fetch_row_field(result, x, "round"));
		jteam->sa_only = 0;

		jteam->award = NULL;
		for(i=0;i<awards->len;i++) {
			struct _award *a = g_ptr_array_index(awards, i);
			if(a->id == award_id) {
				jteam->award = a;
				break;
			}
		}
		if(jteam->award == NULL) {
			printf("JTeam Loader: Award %d not matched\n", award_id);
		}
	

		n_list = split_int_list(list, db_fetch_row_field(result, x, "user_ids"));
		jteam->judges = g_ptr_array_new();
		for(i=0;i<n_list;i++) {
			int judge_id = list[i];
			int matched = 0;
			for(j=0;j<judges->len;j++) {
				struct _judge *judge = g_ptr_array_index(judges, j);
				if(judge->id == judge_id) {
					g_ptr_array_add(jteam->judges, judge);
					matched = 1;
					break;
				}
			}
			if(!matched) {
				printf("JTeam Loader: Judge %d not matched\n", judge_id);
			}
		}
		n_list = split_int_list(list, db_fetch_row_field(result, x, "project_ids"));
		jteam->projects = g_ptr_array_new();
		for(i=0;i<n_list;i++) {
			int pid = list[i];
			int matched = 0;
			for(j=0;j<projects->len;j++) {
				struct _project *p = g_ptr_array_index(projects, j);
				if(p->pid == pid) {
					g_ptr_array_add(jteam->projects, p);
					matched = 1;
					break;
				}
			}
			if(!matched) {
				printf("JTeam Loader: Project %d not matched\n", pid);
			}
		}

		//printf(" %s: grade %d, school %d,  (%d %d %d) id=%d\n", s->name, s->grade, s->schools_id, s->tour_id_pref[0], s->tour_id_pref[1], s->tour_id_pref[2], s->id);
		g_ptr_array_add(jteams, jteam);

	}
	printf("Loaded %d jteams\n", jteams->len);
	db_free_result(result);
	return jteams;
}




void judges_timeslots(struct _db_data *db, int year, int do_log)
{
	int itimeslot, iround, iproject, i, y;
	GPtrArray *jteams;
	GString *q1;

	current_year = year;

	students_load(db, year);
	projects_load(db, year);

	categories_load(db, year);
	isef_divisions_load(db, year);

	judges_load(db, year);
	awards_load(db, year);

	timeslots_load(db, year);

	jteams = jteams_load(db, year);

	/* Delete old assignments */
	if(do_log) scheduler_log(db, 1, "Deleting existing assignments");
	db_query(db, "DELETE FROM timeslot_assignments WHERE year='%d'", year);

	if(do_log) scheduler_log(db, 10, "Computing timeslot assignments");

	q1 = g_string_sized_new(65536);

	/* Do timeslot assignments */
	for(i=0;i<jteams->len;i++) {
		struct _jteam *jteam = g_ptr_array_index(jteams, i);

		/* Skip any non-divisional or non-round0 jteam */
		if(!jteam->award->is_divisional || !jteam->round == 0) continue;

		printf("Timeslots for jteam %s\n", jteam->name);

		g_string_assign(q1, "");

		/* For every divisional round0 team, create a schedule for each
		 * round.  Yea, that sounds weird.  The logic is this:  for
		 * round2, we don't know the projects yet, but we want each
		 * project to have a round2 schedule.  A seemingly good way to achieve some
		 * balance there while covering each project is to just assign them as part of the 
		 * round1 judging teams */
		for(iround=0; iround<timeslots->len; iround++) {
			struct _timeslot *ts = g_ptr_array_index(timeslots, iround);
			struct _timeslot_matrix *timeslot_matrix;

			/* This will produce the same scheudule twice, unless the timeslots for round1 and 2 are 
			 * different, which they are for the GVRSF */
			timeslot_matrix = timeslot_matrix_alloc(jteam->projects->len, ts->num_timeslots);

			/* Create the requested schedule */
			if(ts->round == 0) {
				timeslot_fill(timeslot_matrix, jteam->judges->len, config.div_times_each_project_judged);
			} else {
				/* For round2, turn all the judging slots into cusp slots */
				timeslot_fill(timeslot_matrix, jteam->judges->len * 2, config.div_times_each_project_judged);
				for(iproject=0;iproject<jteam->projects->len;iproject++) {
					for(itimeslot=0; itimeslot<ts->num_timeslots; itimeslot++) {
						if(timeslot_matrix->ts[iproject][itimeslot] >= 0) {
							timeslot_matrix->ts[iproject][itimeslot] = TIMESLOT_CUSP;
						}

					}
				}
			}

			/* Handle projects that have judging time restrictions.  Right now this only works for round2 because
			 * we just take any divisional and special timeslots (divisional first) that are in the blacked-out
			 * time, and move them to breaks */
			for(iproject=0;iproject<jteam->projects->len;iproject++) {
				struct _project *p = g_ptr_array_index(jteam->projects, iproject);

				for(y=0; y< p->num_unavailable_timeslots; y++) {
					char *ts_str = p->unavailable_timeslots[y];
					int round, num;
					/* Separate into round:num */

					if(sscanf(ts_str, "%d:%d", &round, &num) != 2) {
						printf("   Unable to parse unavailable timeslot out of %s, for project %d. skipping\n",
									ts_str, p->pid);
						continue;
					}

					if(round != ts->round) continue;

					timeslot_adjust_for_unavailable_slot(timeslot_matrix, iproject, num);
				}
			}

			printf("   Timeslot assignments for %s, %s (round=%d)\n", jteam->name, ts->name, ts->round);
			for(iproject=0;iproject<jteam->projects->len;iproject++) {
				struct _project *p = g_ptr_array_index(jteam->projects, iproject);
				printf("\t%d", p->pid);
			}
			printf("\n");

			/* Print the timeslots for this jteam and append to the sql query to do the timeslot assignments */
			for(itimeslot=0; itimeslot<ts->num_timeslots; itimeslot++) {
				printf("%d", itimeslot+1);
				for(iproject=0;iproject<jteam->projects->len;iproject++) {
					struct _project *p = g_ptr_array_index(jteam->projects, iproject);
					struct _judge *j;
					char *timeslot_type_str;
					int timeslot_type = timeslot_matrix->ts[iproject][itimeslot];

					if(timeslot_type >= 0) {
						j = g_ptr_array_index(jteam->judges, timeslot_type);
						printf("\t(%d)", j ? j->id : -1);
						timeslot_type_str = "divisional";
					} else if(timeslot_type == TIMESLOT_BREAK) {
						printf("\t--");
						timeslot_type_str = "free";
					} else if(timeslot_type == TIMESLOT_SPECIAL) {
						printf("\tS");
						timeslot_type_str = "special";
					} else if(timeslot_type == TIMESLOT_UNAVAILABLE) {
						printf("\tXX");
						timeslot_type_str = "free";
					} else if(timeslot_type == TIMESLOT_CUSP) {
						printf("\tcusp");
						timeslot_type_str = "divisional";
					} else {
						assert(0);
					}
					if(j == NULL) continue;

					/* Build a query for the mysql insert, we're building up a list and sending a bunch
					 * at a time because a single INSERT for each timeslot was very slow. */
					if(q1->len != 0) {
						g_string_append_printf(q1, ",");
					}
					g_string_append_printf(q1, "('%d','%d','%d','%d','%d','%s','%d')",
						ts->id, itimeslot, p->pid, 
						timeslot_type >= 0 ? jteam->id : 0, 
						timeslot_type >= 0 ? j->id : 0, 
						timeslot_type_str,
						year);
				}
				printf("\n");
			}

			free(timeslot_matrix);
		}
		db_query(db, "INSERT INTO timeslot_assignments (`timeslot_id`,`timeslot_num`, `pid`,`judging_team_id`,`judge_id`,`type`,`year`) VALUES %s", 
				q1->str);
//		printf("%s\n", q1->str);
		printf("\n");
	}
	g_string_free(q1, 1);
	if(do_log) scheduler_log(db, 100, "Done.");
}



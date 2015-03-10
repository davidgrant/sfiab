#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <math.h>

#include <glib.h>

struct _timeslot {
	int start_time;
	int num_teams;
	int *teams;
	int lunch;
	int challenge;
	int table;
};

struct _table {
	int num_timeslots;
	int offset;
	struct _timeslot *timeslots;
	int x_index;  /* x index into the schedule grid */
};

struct _challenge {
	int num_tables;
	int rounds_per_team, timeslot_length, teams_per_timeslot, start_time, end_time, lunch;
	int num_minutes;
	struct _table *tables;
	char *name;
};

struct _team {
	int id;
	int selection[5];
	int selection_count;
	int done;
	int partner[3];
	int num_partners;

} teams[1000];
int num_teams=0;

int num_minutes = 5 * 60;
int num_tables = 0;


int **schedule = NULL;
int **partner_schedule = NULL;
int **team_schedule = NULL;

struct _challenge *challenges= NULL;
int num_challenges = 0;

static void place_entry(int ichal, int itable, int minute, int val, int partner_val)
{
	struct _challenge *chal = &challenges[ichal];
	struct _table *table = &chal->tables[itable];
	int x;

	printf("Mark entry %d,%d,%d = %d (,%d), length=%d\n", 
			ichal, itable, minute, val, partner_val, chal->timeslot_length);

	/* Align to a boundary */
	if((minute-table->offset) % chal->timeslot_length != 0) {
		printf("Error, missalinged in place_entry\n");
		exit(0);
	}

	for(x=0;x<chal->timeslot_length; x++) {
		schedule[table->x_index][minute+x] = val;
		partner_schedule[table->x_index][minute+x] = partner_val;
	}
}


/* Place a team in chal, table near minute */
static void place_team(int ichal, int itable, int minute, int iteam, int ipartner_team)
{
	struct _challenge *chal = &challenges[ichal];
	struct _table *table = &chal->tables[itable];
	int selected_minute = -1;
	int search_start_minute;

	/* Align to a boundary */
	minute -= (minute-table->offset) % chal->timeslot_length;

	/* Go down in minutes until we find a free one */
	search_start_minute = 0;
	while(1) {
		int ok = 1;
		int i, j;

		/* Make sure it wont' spill off the bottom */
		if(minute + chal->timeslot_length >= chal->num_minutes) {
			ok = 0;
		}

		/* Check minute -> minute+timeslot_length in all timeslots */
		if(ok) {
			/* Search one minute back and forward too, just to make sure they're not back-to-back */
			for(i=-1; i<chal->timeslot_length+1; i++) {
				if(i+minute < 0 || i+minute > chal->num_minutes) continue;

				for(j=0;j<num_tables;j++) {
					if(schedule[j][i + minute] == iteam || partner_schedule[j][i + minute] == iteam) {
						ok = 0;
						break;
					}
					if(ipartner_team != -1) {
						if(schedule[j][i + minute] == ipartner_team || partner_schedule[j][i + minute] == ipartner_team) {
							ok = 0;
							break;
						}
					}

				}
			}
		}

		/* Make sure this slot is free */
		if(ok && schedule[table->x_index][minute] == -1) {
			/* This can go here */
			selected_minute = minute;
			break;
		}

		/* This can't go here, try the next timeslot */
		minute += chal->timeslot_length;
		if(minute >= chal->num_minutes) {
			/* Minute overflow, reset to beginning */
			minute = table->offset;
		}

		search_start_minute += chal->timeslot_length;
		if(search_start_minute > num_minutes) break;
	}



	if(selected_minute == -1) {
		/* Collision */
		printf("Collision at %s,%d,%d\n", challenges[ichal].name, itable, minute);
//		return;
		exit(0);
	}

	place_entry(ichal, itable, minute, iteam, ipartner_team);




}

void load_teams(char *file)
{
	int x;
	FILE *in = fopen(file, "rt");
	int ichal;
	num_teams = 0;
	while(!feof(in)) {
		int id, c[5];
		int m = fscanf(in, "%d\t%d\t%d\t%d\t%d\t%d\n", &id, &c[0], &c[1], &c[2], &c[3], &c[4]);
		if(m == 6) {
			teams[num_teams].id = id;
			teams[num_teams].selection[0] = c[0];
			teams[num_teams].selection[1] = c[1];
			teams[num_teams].selection[2] = c[2];
			teams[num_teams].selection[3] = c[3];
			teams[num_teams].selection[4] = c[4];
			teams[num_teams].done = 0;
			teams[num_teams].selection_count = 0;
			teams[num_teams].num_partners = 0;
			for(x=0;x<5;x++) {
				if(c[x] == 1) {
					teams[num_teams].selection_count += 1;
				}
			}
			num_teams++;
		}
	}
	fclose(in);

	/* Calculate partners all challenges that require it */
	for(ichal=0; ichal<num_challenges;ichal++) {
		struct _challenge *chal = &challenges[ichal];

		if(chal->teams_per_timeslot == 1) continue;

		/* Two teams per timeslot, find partners: note: assumes
		 * that only one challenge has multiple teams per timeslot (that can be signed up for), else
		 * we'll have to store partners on a per-chal basis */

		for(x=0;x<num_teams;x++) {
			struct _team *t = &teams[x];
			int y = x + num_teams / 2;
			int c = 0;
			int ok = 1;

			if(!t->selection[ichal]) continue;

			while(1) {
				int i;
				ok = 1;
				if(y >= num_teams) y -= num_teams;

				/* Don't match them if they're the same team,
				 * if the other team is full to rounds_per_team already
				 * if they haven't selected the same challenge
				 * or if they are already matched */
				if(y == x) ok = 0;
				if(teams[y].num_partners >= chal->rounds_per_team) ok = 0;
				if(!teams[y].selection[ichal]) ok = 0;

				for(i=0;i<t->num_partners; i++) {
					if(t->partner[i] == y) ok = 0;
				}

				if(ok) {
					teams[y].partner[teams[y].num_partners] = x;
					t->partner[t->num_partners] = y;
	
					teams[y].num_partners++;
					t->num_partners++;
				}

				if(t->num_partners == chal->rounds_per_team) break;

				c+=1;
				if(c == num_teams) break;
				y++;
			}

		}
	}
	for(x=0;x<num_teams;x++) {
		struct _team *t = &teams[x];
		int i;

		printf("%d: %d [%d%d%d%d%d]%d  ", x, t->id, t->selection[0], t->selection[1], t->selection[2], t->selection[3], t->selection[4], t->selection_count );
		for(i=0;i<t->num_partners;i++) {
			printf("  %d", t->partner[i]);
		}
		printf("\n");
	}


	

	printf("Loaded %d teams\n", num_teams);
			
}

int do_team_assignment(int team_id)
{
	struct _team *team = &teams[team_id];
	int ichal;
	int total_assignments = 0;
	int stride, start;

	printf("Assigning Team %d\n", team->id);

	/* Count total number of assignments */
	for(ichal=0;ichal<5;ichal++) {
		if(team->selection[ichal]  == 0) continue;
		total_assignments += challenges[ichal].rounds_per_team;
	}

	stride = (num_minutes - 45) / total_assignments;
	printf("   Assignments: %d, stride=%d\n", total_assignments, stride);

	start = team->id % (num_minutes - 30);

	/* Over challenges */
	for(ichal=0;ichal<5;ichal++) {
		int i;
		struct _challenge *chal = &challenges[ichal];
		int itable;
		if(team->selection[ichal]  == 0) continue;

		/* Start at a random table */
		itable = team_id % chal->num_tables;

		/* Over the number of attempts each team gets */
		for(i=0;i<chal->rounds_per_team;i++) {

			/* Find the partner team if it exists */
			int partner_team = -1;
			if(chal->teams_per_timeslot == 2) {
				partner_team = team->partner[i];
			}

			/* Iterate around tables, but there could be more or fewer 
			 * tables than attempts */
			if(itable >= chal->num_tables) {
				itable = 0;
			}
			/* Make sure we don't go off the end of the schedule */
			if(start + chal->timeslot_length > chal->num_minutes) {
				start = 0;
			}

			/* Place this team if there is no partner, or if
			 * the team's id is less than the partner's. */
			if(partner_team == -1 || team_id < partner_team) {
				place_team(ichal, itable, start, team_id, partner_team);
			}

			itable++;
			start += stride;
		}
	}


	team->done = 1;
	return 1;
}

int do_assignments(void)
{
	int x;
	int highest_count;
	int highest_team;

	/* Find a team */
	while(1) {
		highest_count = 0;
		highest_team = -1;
		for(x=0;x<num_teams;x++) {
			if(teams[x].done == 1) continue;
			if(teams[x].selection_count > highest_count) {
				highest_count = teams[x].selection_count ;
				highest_team = x;
			}
		}

		if(highest_team == -1) break;

		do_team_assignment(highest_team);
	}
	return 1;
}

int mark_breaks(int ichal, int itable)
{
	struct _challenge *chal = &challenges[ichal];
	struct _table *table = &chal->tables[itable];
	int minute;
	int lunch_state = 0;
	int this_lunch_start = 0;

	int lunch_start = 100; /* 12:00 - 10:20  */

	for(minute=table->offset; minute<chal->num_minutes; minute += chal->timeslot_length) {
		if(lunch_state == 0) {
			if(itable %2 == 0) {
				if(minute >= lunch_start) {
					lunch_state = 1;
					this_lunch_start = minute;
				}
			} else {
				if(minute >= lunch_start + 30) {
					lunch_state = 1;
					this_lunch_start = minute;
				}
			}
		}
		if(lunch_state == 1) {
			place_entry(ichal, itable, minute, -2, -1);
			if(minute + chal->timeslot_length - this_lunch_start >= 30) {
				lunch_state = 2;
			}
		}
	}
	return 0;
}

int get_challenge_and_table_for_index(int index, int *ichal, int *itable)
{
	int x, y;
	for(x=0;x<num_challenges;x++) {
		for(y=0;y<challenges[x].num_tables;y++) {
			struct _table *t = &challenges[x].tables[y];
			if(t->x_index == index) {
				*ichal = x;
				*itable = y;
				return 1;
			}
		}
	}
	return 0;
}


int main(void)
{
	/* Setup */
	struct _challenge j_chal[5];
	struct _challenge s_chal[5];
	FILE *fp;

	char *output_filename;

	int num_j_chal, num_s_chal=0, i, j, x, y;
	int ichal;

	j_chal[0].name = "J1";
	j_chal[0].num_tables = 2;
	j_chal[0].rounds_per_team = 2;
	j_chal[0].timeslot_length = 5;
	j_chal[0].teams_per_timeslot = 1;
	j_chal[0].num_minutes = num_minutes - 30;

	j_chal[1].name = "J2";
	j_chal[1].num_tables = 2;
	j_chal[1].rounds_per_team = 2;
	j_chal[1].timeslot_length = 6;
	j_chal[1].teams_per_timeslot = 1;
	j_chal[1].num_minutes = num_minutes - 30;

	j_chal[2].name = "J3-White";
	j_chal[2].num_tables = 3;
	j_chal[2].rounds_per_team = 3;
	j_chal[2].timeslot_length = 5;
	j_chal[2].teams_per_timeslot = 2;
	j_chal[2].num_minutes = num_minutes - 75;

	j_chal[3].name = "J3-Black";
	j_chal[3].num_tables = 1;
	j_chal[3].rounds_per_team = 3;
	j_chal[3].timeslot_length = 5;
	j_chal[3].teams_per_timeslot = 2;
	j_chal[3].num_minutes = num_minutes - 75;

	j_chal[4].name = "J4";
	j_chal[4].num_tables = 1;
	j_chal[4].rounds_per_team = 3;
	j_chal[4].timeslot_length = 4;
	j_chal[4].teams_per_timeslot = 1;
	j_chal[4].num_minutes = num_minutes - 30;

	num_j_chal = 5;

	s_chal[0].name = "S1";
	s_chal[0].num_tables = 2;
	s_chal[0].rounds_per_team = 2;
	s_chal[0].timeslot_length = 7;
	s_chal[0].teams_per_timeslot = 1;
	s_chal[0].num_minutes = num_minutes- 10;

	s_chal[1].name = "S2";
	s_chal[1].num_tables = 2;
	s_chal[1].rounds_per_team = 2;
	s_chal[1].timeslot_length = 8;
	s_chal[1].teams_per_timeslot = 1;
	s_chal[1].num_minutes = num_minutes -10;

	s_chal[2].name = "S3";
	s_chal[2].num_tables = 2;
	s_chal[2].rounds_per_team = 3;
	s_chal[2].timeslot_length = 6;
	s_chal[2].teams_per_timeslot = 2;
	s_chal[2].num_minutes = num_minutes - 90;

	s_chal[3].name = "S4";
	s_chal[3].num_tables = 1;
	s_chal[3].rounds_per_team = 3;
	s_chal[3].timeslot_length = 4;
	s_chal[3].teams_per_timeslot = 1;
	s_chal[3].num_minutes = num_minutes - 30;

	s_chal[4].name = "S5";
	s_chal[4].num_tables = 1;
	s_chal[4].rounds_per_team = 3;
	s_chal[4].timeslot_length = 4;
	s_chal[4].teams_per_timeslot = 1;
	s_chal[4].num_minutes = num_minutes- 30;

	num_s_chal = 5;

	/* Build timeslots */

	for(i=0;i<2;i++) {

		int h, m;

		if(i==0) {
			challenges = &j_chal[0];
			num_challenges = num_j_chal;
			output_filename = "j.csv";
		} else {
			challenges = &s_chal[0];
			num_challenges = num_s_chal;
			output_filename = "s.csv";
		}
		
		num_tables = 0;
		for(ichal=0;ichal<num_challenges; ichal++) {
			struct _challenge *c = &challenges[ichal];
			int len = num_minutes;
			int num_timeslots = len / c->timeslot_length;
			int offset = c->timeslot_length/2;

			printf("%s: %d tables, %dx each team, %d timeslots * %d minutes\n", 
					c->name, c->num_tables, c->rounds_per_team, num_timeslots, c->timeslot_length);

			c->tables = malloc(sizeof(struct _table) * c->num_tables);
			for(j=0;j<c->num_tables;j++) {
				struct _table *t = &c->tables[j];
				t->num_timeslots = num_timeslots;
				t->timeslots = malloc(sizeof(struct _timeslot) * num_timeslots);
				t->offset = j % 2 == 0 ? 0 : offset;
				t->x_index = num_tables;
				for(x=0;x<num_timeslots;x++) {
					struct _timeslot *ts = &t->timeslots[x];
					ts->start_time = c->start_time + t->offset + x * c->timeslot_length;
					ts->num_teams = c->teams_per_timeslot;
					ts->teams = malloc(ts->num_teams * sizeof(int));
				}
				num_tables += 1;
			}
		}

		schedule = (int **)malloc(sizeof(int *) * num_tables);
		partner_schedule = (int **)malloc(sizeof(int *) * num_tables);

		
		if(i==0) {
			load_teams("j.txt");
		} else {
			load_teams("s.txt");
		}
		

		team_schedule = (int **)malloc(sizeof(int *) * num_teams);

		/* Clean out the schedule */
		for(x=0;x<num_tables;x++) {
			schedule[x] = malloc(sizeof(int) * num_minutes);
			partner_schedule[x] = malloc(sizeof(int) * num_minutes);
			for(y=0;y<num_minutes;y++) {
				schedule[x][y] = -1;
				partner_schedule[x][y] = -1;
			}
		}
		/* Clean out the team schedule */
		for(x=0;x<num_teams;x++) {
			team_schedule[x] = malloc(sizeof(int) * num_minutes);
			for(y=0;y<num_minutes;y++) {
				team_schedule[x][y] = -1;
			}
		}

		/* Mark breaks and lunches */
		for(x=0;x<num_challenges; x++) {
			struct _challenge *c = &challenges[x];
			for(j=0;j<c->num_tables;j++) {
				mark_breaks(x, j);
			}
		}

		do_assignments();


		/* Print schedule and build a team schedule map */
		printf("Time    ");
		for(j=0;j<num_challenges;j++) {
			for(y=0;y<challenges[j].num_tables;y++) {
				printf("%8s-%d ", challenges[j].name, y);
			}
		}
		printf("\n");
		for(x=0;x<num_minutes;x++) {
			printf("%6d  ", x);
			for(j=0;j<num_challenges;j++) {
				for(y=0;y<challenges[j].num_tables;y++) {
					struct _table *t = &challenges[j].tables[y];
					int iteam = schedule[t->x_index][x];
					int ipartner_team = partner_schedule[t->x_index][x];
					if(ipartner_team >= 0) {
						printf("%3d,%3d    ", iteam, ipartner_team);
						team_schedule[iteam][x] = t->x_index;
						team_schedule[ipartner_team][x] = t->x_index;
					} else {
						printf("%6d     ", iteam);
						if(iteam >= 0) {
							team_schedule[iteam][x]= t->x_index;
						}
					}
				}
			}
			printf("\n");
		}

		/* Write .CSV files */

		fp = fopen(output_filename, "wt");
		fprintf(fp, "By Area:\nTime,");
		for(j=0;j<num_challenges;j++) {
			for(y=0;y<challenges[j].num_tables;y++) {
				fprintf(fp,"%s-%d,", challenges[j].name, y+1);
			}
		}
		fprintf(fp, "\n");
		h=10;
		m=20;
		for(x=0;x<num_minutes;x++) {
			fprintf(fp, "%02d:%02d, ", h,m);
			for(j=0;j<num_challenges;j++) {
				for(y=0;y<challenges[j].num_tables;y++) {
					struct _table *t = &challenges[j].tables[y];
					if(partner_schedule[t->x_index][x] >= 0) {
						fprintf(fp, "%3d %3d, ", teams[schedule[t->x_index][x]].id, teams[partner_schedule[t->x_index][x]].id);
					} else {
						if(schedule[t->x_index][x] == -2) {
							fprintf(fp, "Lunch, ");
						} else if(schedule[t->x_index][x] == -1) {
							fprintf(fp, ", ");
						} else {
							fprintf(fp, "%6d, ", teams[schedule[t->x_index][x]].id);	
						}
					}
				}
			}
			fprintf(fp, "\n");
			m++;
			if(m==60) {
				m=0;
				h++;
			}
		}
		fprintf(fp, "By Team:\n");
		fprintf(fp, "Time, ");
		for(j=0;j<num_teams;j++) {
			fprintf(fp, "%d,", teams[j].id);
		}
		fprintf(fp, "\n");
		h=10;
		m=2;
		for(x=0;x<num_minutes;x++) {
			fprintf(fp, "%02d:%02d, ", h,m);
			for(j=0;j<num_teams;j++) {
				int x_index = team_schedule[j][x];
				int ichal, itable;

				if(x_index >= 0 ) {
					/* Find the chal/area for this x_index */
					get_challenge_and_table_for_index(x_index, &ichal, &itable);
					fprintf(fp, "%s-%d,", challenges[ichal].name, itable+1);
				} else {
					fprintf(fp, " ,");
				}
			}
			fprintf(fp, "\n");
			m++;
			if(m==60) {
				m=0;
				h++;
			}
		}
		fprintf(fp, "By Team:\n");
		fclose(fp);
		
	}

	/* Do initial assignments */

	printf("All done!\n");

	return 0;
}


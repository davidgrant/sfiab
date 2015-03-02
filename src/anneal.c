#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include <glib.h>
#include <assert.h>

#include "anneal.h"

static int l_debug = 0;

#define TRACE(...) if(l_debug) printf(__VA_ARGS__)

/* Debug, just dump all the buckets and what's in them */
int anneal_print_buckets(struct _annealer *annealer)
{
	int x;
	int sum = 0;
	printf("Buckets: ");
	for(x=0;x<annealer->num_buckets; x++) {
		struct _anneal_bucket *b = &annealer->buckets[x];
		printf(" %d ", b->items->len);
		sum += b->items->len;
	}
	printf("\n");

	assert(sum == annealer->items->len);
	return 0;
}

/* Default move propposal.  Pick a source bucket and item within that bucket,
 * then pick a dest bucket and with 50/50 chance:
 * 	- choose an item from that bucket, or
 * 	- propose a one-sided move instead of a swap.
 */
int anneal_propose_move(struct _annealer *annealer, struct _anneal_move *move)
{
	float f;

	f = (float)rand() / (float)RAND_MAX;

	/* Picket source item and bucket */
	move->i1 = rand() % annealer->items->len;
	move->b1 = annealer->item_bucket[move->i1];

	if(f > 0.5) {
		/* With 50% chance, pick a second bucket and item in that bucket */
		move->i2 = rand() % (annealer->items->len - 1);
		if(move->i2 >= move->i1) move->i2++;
		move->b2 = annealer->item_bucket[move->i2];

		if(move->b1 == move->b2) {
			move->i2 = -1;
		}
	} else {
		/* Or, with 50% chance, propose a 1-sided move */
		move->b2 = rand() % (annealer->num_buckets-1);
		if(move->b2 >= move->b1) move->b2++;

		move->i2 = -1;
	}

	/* Grab pointers to the items in the bucket lists */
	assert(move->b2 >= 0);
	move->p1 = g_ptr_array_index(annealer->items, move->i1);
	if(move->i2 >= 0) {
		move->p2 = g_ptr_array_index(annealer->items, move->i2);
	} else {
		move->p2 = NULL;
	}

	return 0;
}

/* Delta cost calculation.  Given a move proposal figure out what the delta is by
 * calling the cost function */
int anneal_compute_delta_cost(struct _annealer *annealer, struct _anneal_move *move)
{
	struct _anneal_bucket *bucket1, *bucket2;

	/* Perform the move temporarily by moving the items to their proposed locations, this
	 * is just pointer gymnastics, moving pointers between lists */
	bucket1 = &annealer->buckets[move->b1];
	bucket2 = &annealer->buckets[move->b2];

	g_ptr_array_remove_fast(bucket1->items, move->p1);
	if(move->p2) g_ptr_array_remove_fast(bucket2->items, move->p2);
	if(move->p2) g_ptr_array_add(bucket1->items, move->p2);
	g_ptr_array_add(bucket2->items, move->p1);

	annealer->item_bucket[move->i1] = move->b2;
	if(move->p2) annealer->item_bucket[move->i2] = move->b1;

	/* Now calculate the new costs with the items in their new locations */
	move->new_cost1 = annealer->cost_function(annealer, move->b1, bucket1->items);
	move->new_cost2 = annealer->cost_function(annealer, move->b2, bucket2->items);

	/* Put the items back in their old locations... inefficient, what we could 
	 * do instead is have a "cancel move" if the move fails, rather than putting 
	 * things back and then committing the move and putting them back again */
	if(move->p2) g_ptr_array_remove_index_fast(bucket1->items, bucket1->items->len-1);
	g_ptr_array_remove_index_fast(bucket2->items, bucket2->items->len-1);
	g_ptr_array_add(bucket1->items, move->p1);
	if(move->p2) g_ptr_array_add(bucket2->items, move->p2);

	annealer->item_bucket[move->i1] = move->b1;
	if(move->p2) annealer->item_bucket[move->i2] = move->b2;

	TRACE("Delta: new_costs:%f %f, old_costs:%f %f, delta: %f\n", 
			move->new_cost1, move->new_cost2,
			bucket1->cost,bucket2->cost,
			(move->new_cost1 - bucket1->cost) + (move->new_cost2 - bucket2->cost) );

	/* Return the delta cost */			
	return (move->new_cost1 - bucket1->cost) + (move->new_cost2 - bucket2->cost) ;
}

/* Commit a move, basically does exactly what the delta move does, only 
 * doesn't undo it */
int anneal_commit_move(struct _annealer *annealer, struct _anneal_move *move) 
{
	struct _anneal_bucket *bucket1, *bucket2;
	/* Remove p1 in bucket 1 */
	bucket1 = &annealer->buckets[move->b1];
	bucket2 = &annealer->buckets[move->b2];

	assert(g_ptr_array_index(bucket1->items, bucket1->items->len-1) == move->p1);
	if(move->p2) assert(g_ptr_array_index(bucket2->items, bucket2->items->len-1) == move->p2);

	/* Optimization: The items we want are at the end of the bucket because
	 * of how compute_delta_cost moved them around.  So we don't have to go
	 * looking for them. */
	g_ptr_array_remove_index_fast(bucket1->items, bucket1->items->len-1);
	if(move->p2) g_ptr_array_remove_index_fast(bucket2->items, bucket2->items->len-1);
	if(move->p2) g_ptr_array_add(bucket1->items, move->p2);
	g_ptr_array_add(bucket2->items, move->p1);

	annealer->item_bucket[move->i1] = move->b2;
	if(move->p2) annealer->item_bucket[move->i2] = move->b1;
	

	bucket1->cost = move->new_cost1;
	bucket2->cost = move->new_cost2;

	return 0;
}

/* Sanity check costs, at all times, the cost-from-scratch should be the current
 * running cost as calculated by deltas */
float anneal_check_costs(struct _annealer *annealer)
{
	float cost = 0;
	int x;

	cost = 0;
	for(x=0; x<annealer->num_buckets; x++) {
		struct _anneal_bucket *b = &annealer->buckets[x];
		float c;
		c = annealer->cost_function(annealer, x, b->items);
		if(b->cost != c) {
			printf("ERROR: bucket %d current cost %f doesn't match from-scratch cost %f\n",
				x, b->cost, c);
		}
		cost += b->cost;
	}

	return cost;
}

/* Entry to the anneal.
 * data_ptr - data passed to each user-defined function (propose move and calculate cost)
 * output_buckets - a pointer to where to write the buckets.  It's an array of an array for each bucket.
 * num_buckets - the number of buckets to use
 * items - pointer to the items to go in each bucket
 * cost_function - REQUIRED, a user defined cost funciton.  Given a bucket_id
 * 		(between 0 and num_buckets-1) and an array of pointers to
 * 		things in (*items) that are in that bucket, calculate the cost.
 * propose_move - optional, override the existing move proposal */
int anneal( void *data_ptr, GPtrArray ***output_buckets, int num_buckets, GPtrArray *items, 
			float (*cost_function)(struct _annealer *annealer, int bucket_id, GPtrArray *bucket),
			int (*propose_move)(struct _annealer *annealer, struct _anneal_move *move),
			void (*progress_callback)(float progress)
		)
{
	int x, i, num_moves, num_accepted;
	struct _annealer annealer;
	float temperature, cost, last_cost, success_rate, success_rate_this_temp;
	int last_cost_count, quench_count = 0;
	int inner_num;
	int temperature_count = 0;
	int num_moves_this_temp;
	int num_accepted_this_temp;
	float estimated_iterations;

	srand(time(NULL));
//	srand(0);

	/* Setup */
	printf("Annealer\n");
	annealer.items = items;
	annealer.num_buckets = num_buckets;
	annealer.buckets = malloc(sizeof(struct _anneal_bucket) * annealer.num_buckets);
	annealer.item_bucket = malloc(sizeof(int) * items->len);
	annealer.cost_function = cost_function;
	annealer.propose_move = propose_move;
	annealer.progress_callback = progress_callback;
	annealer.data_ptr = data_ptr;

	/* Allocate each bucket */
	printf("   => %d initial buckets\n", num_buckets);
	for(x=0;x<num_buckets;x++) {
		struct _anneal_bucket *b = &annealer.buckets[x];
		b->cost = 0;
		b->items = g_ptr_array_new();
	}

	/* Stride over the buckets and put items somewhere initially */
	x=0;
	for(i=0; i<items->len; i++) {
		void *item = g_ptr_array_index(items, i);
		struct _anneal_bucket *b = &annealer.buckets[x];
		g_ptr_array_add(b->items, item);
		annealer.item_bucket[i] = x;
		x++;
		if(x==num_buckets) x=0;
	}
	printf("   => %d total items to anneal\n", i);

	/* Compute initial costs */
	cost = 0;
	for(x=0; x<num_buckets; x++) {
		struct _anneal_bucket *b = &annealer.buckets[x];
		b->cost = cost_function(&annealer, x, b->items);
		cost += b->cost;
	}
	printf("   => initial cost is %f\n", cost);

	/* Annealing temp and inner_num, a better inner_num should just
	 * be items->len ^ 4/3 as defined in the VPR paper, but this 
	 * works better when there are only a few hundred items */
	temperature = 1000000000000.0;
	inner_num = 1 * pow(items->len * num_buckets, 4/3);

	estimated_iterations = -log(1 / temperature) * 10 ;
	printf("   => Estimated Iterations: %d\n", (int)estimated_iterations);
	num_moves = 0;
	num_accepted = 0;
	last_cost = 0;
	/* Main temperature loop */
	while(1) {
		num_moves_this_temp = 0;
		num_accepted_this_temp =0;
		/* In this temperature, do inner_num moves */
		for(i=0; i<inner_num; i++) {
			struct _anneal_move move;
			float delta_cost, r, e;

			/* Propose a move */
			if(annealer.propose_move) {
				int ret = annealer.propose_move(&annealer, &move);
				if(ret == -1) 
					anneal_propose_move(&annealer, &move);
			} else {
				anneal_propose_move(&annealer, &move);
			}
			TRACE("Move: %d @[%d] <--> %d @[%d]\n", move.b1, move.i1, move.b2, move.i2);

			/* Compute the delta */
			delta_cost = anneal_compute_delta_cost(&annealer, &move);

			/* Decide if we want to keep it by picking a random number
			 * based on the temperature and delta cost */
			r = (float)rand() / (float)RAND_MAX;
			e = exp(-delta_cost / temperature);
			TRACE("Eval: r=%f, exp=%f, delta=%f\n", r, e, delta_cost);
			if(r < e) {
				/* Keep the move, commit it and update the costs */
				anneal_commit_move(&annealer, &move);
				cost += delta_cost;
				num_accepted += 1;
				num_accepted_this_temp += 1;
				TRACE("Move accepted, cost=%f\n", cost);
			} else {
				TRACE("Move rejected\n");
			}
			/* Stats */
			num_moves += 1;
			num_moves_this_temp += 1;

			//anneal_check_costs(&annealer);

			if(cost == 0) break;
		}

		/* End of temperature, if we got the cost to zero, stop. */
		if(cost == 0) break;

		/* Sanity check */
		anneal_check_costs(&annealer);

		temperature_count ++;

		/* Estimate %done callback */
		if(annealer.progress_callback) {
			float p = temperature_count / estimated_iterations;
			if(p > 1) p=1;
			annealer.progress_callback(p);
		}

		/* If we do too many costs over and over again, exit below */
		if(cost == last_cost) {
			last_cost_count += 1;
		} else {
			last_cost = cost;
			last_cost_count=0;
		}
			
		/* If the temperature is zero, just do 10 quenches then exit */
		if(temperature == 0.0) {
			if(quench_count == 10) break;
			quench_count++;
		}

		if(last_cost_count > 50) 
			temperature = 0;

		/* Snap temperature to zero if it gets too low */
		if(temperature < 0.0000001) temperature = 0;

		/* Compute success rates to determine cooling schedule */
		success_rate = (float)num_accepted / (float)num_moves;
		success_rate_this_temp = (float)num_accepted_this_temp / (float)num_moves_this_temp;

		if(temperature_count %20 == 0 || temperature == 0) {
			printf("%0.4g\t\t%.0f\t%.2f\t%2f\n", temperature, cost, success_rate, success_rate_this_temp);
	//		anneal_print_buckets(&annealer);
		}
		
		/* VPR numbers commented out, use a slower cooling schedule that works
		 * better with only a few hundred items */
		if(success_rate > 0.96 )
			temperature *= 0.5;
		else if( success_rate > 0.8 )
			temperature *= 0.95;  // 0.9
		else if( success_rate > 0.15 )
			temperature *= 0.99;  // 0.95
		else
			temperature *= 0.9; // 0.8
	}
	anneal_print_buckets(&annealer);

	/* Copy back pointers to the return array */
	*output_buckets = malloc(sizeof(struct GPtrArray *) * num_buckets);
	for(x=0; x<num_buckets; x++) {
		struct _anneal_bucket *b = &annealer.buckets[x];
		(*output_buckets)[x] = b->items;
		b->items = NULL;
	}

	free(annealer.buckets);

	l_debug = 0;

	/* All done! */
	return 0;
}

void anneal_set_debug(int d)
{
	l_debug = d;
}

#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include <glib.h>
#include <assert.h>

#include "anneal.h"


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


int anneal_propose_move(struct _annealer *annealer, struct _anneal_move *move)
{
	float f;

	f = (float)rand() / (float)RAND_MAX;

	move->i1 = rand() % annealer->items->len;
	move->b1 = annealer->item_bucket[move->i1];

	if(f > 0.5) {
		move->i2 = rand() % (annealer->items->len - 1);
		if(move->i2 >= move->i1) move->i2++;
		move->b2 = annealer->item_bucket[move->i2];

		if(move->b1 == move->b2) {
			move->i2 = -1;
		}

	} else {
		move->b2 = rand() % (annealer->num_buckets-1);
		if(move->b2 >= move->b1) move->b2++;

		move->i2 = -1;
	}

	assert(move->b2 >= 0);
	move->p1 = g_ptr_array_index(annealer->items, move->i1);
	if(move->i2 >= 0) {
		move->p2 = g_ptr_array_index(annealer->items, move->i2);
	} else {
		move->p2 = NULL;
	}

	return 0;
}

int anneal_compute_delta_cost(struct _annealer *annealer, struct _anneal_move *move)
{
	struct _anneal_bucket *bucket1, *bucket2;

	/* Remove p1 in bucket 1 */
	bucket1 = &annealer->buckets[move->b1];
	bucket2 = &annealer->buckets[move->b2];

	g_ptr_array_remove_fast(bucket1->items, move->p1);
	if(move->p2) g_ptr_array_remove_fast(bucket2->items, move->p2);
	if(move->p2) g_ptr_array_add(bucket1->items, move->p2);
	g_ptr_array_add(bucket2->items, move->p1);

	annealer->item_bucket[move->i1] = move->b2;
	if(move->p2) annealer->item_bucket[move->i2] = move->b1;

	move->new_cost1 = annealer->cost_function(annealer, move->b1, bucket1->items);
	move->new_cost2 = annealer->cost_function(annealer, move->b2, bucket2->items);

	/* Put the buckets back */
	if(move->p2) g_ptr_array_remove_index_fast(bucket1->items, bucket1->items->len-1);
	g_ptr_array_remove_index_fast(bucket2->items, bucket2->items->len-1);
	g_ptr_array_add(bucket1->items, move->p1);
	if(move->p2) g_ptr_array_add(bucket2->items, move->p2);

	annealer->item_bucket[move->i1] = move->b1;
	if(move->p2) annealer->item_bucket[move->i2] = move->b2;

/*	printf("Delta: new_costs:%f %f, old_costs:%f %f, delta: %f\n", 
			move->new_cost1, move->new_cost2,
			bucket1->cost,bucket2->cost,
			(move->new_cost1 - bucket1->cost) + (move->new_cost2 - bucket2->cost) );
*/			
	return (move->new_cost1 - bucket1->cost) + (move->new_cost2 - bucket2->cost) ;
}

int anneal_commit_move(struct _annealer *annealer, struct _anneal_move *move) 
{
	struct _anneal_bucket *bucket1, *bucket2;
	/* Remove p1 in bucket 1 */
	bucket1 = &annealer->buckets[move->b1];
	bucket2 = &annealer->buckets[move->b2];

	assert(g_ptr_array_index(bucket1->items, bucket1->items->len-1) == move->p1);
	if(move->p2) assert(g_ptr_array_index(bucket2->items, bucket2->items->len-1) == move->p2);

	/* Items should be at the end of the bucket because of compute_Delta_cost */
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


int anneal( void *data_ptr, GPtrArray ***output_buckets, int num_buckets, GPtrArray *items, 
			float (*cost_function)(struct _annealer *annealer, int bucket_id, GPtrArray *bucket),
			int (*propose_move)(struct _annealer *annealer, struct _anneal_move *move) 
		)
{
	int x, i, index, num_moves, num_accepted;
	int items_per_bucket = (items->len / num_buckets) + 1;
	struct _annealer annealer;
	float temperature, cost, last_cost, success_rate, success_rate_this_temp;
	int last_cost_count, quench_count = 0;
	int inner_num;
	int temperature_count = 0;
	int num_moves_this_temp;
	int num_accepted_this_temp;

	srand(time(NULL));


	printf("Annealer\n");
	annealer.items = items;
	annealer.num_buckets = num_buckets;
	annealer.buckets = malloc(sizeof(struct _anneal_bucket) * annealer.num_buckets);
	annealer.item_bucket = malloc(sizeof(int) * items->len);
	annealer.cost_function = cost_function;
	annealer.propose_move = propose_move;
	annealer.data_ptr = data_ptr;

	index = 0;
	printf("   => %d initial buckets\n", num_buckets);
	for(x=0;x<num_buckets;x++) {
		struct _anneal_bucket *b = &annealer.buckets[x];
		b->cost = 0;
		b->items = g_ptr_array_new();
	}

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

	temperature = 100000000000.0;
	inner_num = 20 * pow(items->len, 4/3);

//	estimated_iterations = ceil(log(0.1 / $this->start_temp, $this->rate));
	num_moves = 0;
	num_accepted = 0;
	last_cost = 0;
	while(1) {
		num_moves_this_temp = 0;
		num_accepted_this_temp =0;
		for(i=0; i<inner_num; i++) {
			struct _anneal_move move;
			float delta_cost, r, e;

			if(annealer.propose_move) {
				int ret = annealer.propose_move(&annealer, &move);
				if(ret == -1) 
					anneal_propose_move(&annealer, &move);
			} else {
				anneal_propose_move(&annealer, &move)
			}
//			printf("Move: %d @[%d] <--> %d @[%d]\n", move.b1, move.i1, move.b2, move.i2);

			delta_cost = anneal_compute_delta_cost(&annealer, &move);

			r = (float)rand() / (float)RAND_MAX;
			/* Decide if we want to keep it */
			e = exp(-delta_cost / temperature);
//			printf("Eval: r=%f, exp=%f, delta=%f\n", r, e, delta_cost);
			if(r < e) {
				anneal_commit_move(&annealer, &move);
				cost += delta_cost;
				num_accepted += 1;
				num_accepted_this_temp += 1;
//				printf("Move accepted, cost=%f\n", cost);
			} else {
//				TRACE("Move rejected\n");
			}
			num_moves += 1;
			num_moves_this_temp += 1;

			if(cost == 0) break;
		}

		if(cost == 0) break;

		temperature_count ++;

		/* Estimate %done callback 
		if(isset ($this->update_callback)) {
			$cb = $this->update_callback;
			$cb($iterations, $estimated_iterations);
		}
		*/

		if(cost == last_cost) {
			last_cost_count += 1;
		} else {
			last_cost = cost;
			last_cost_count=0;
		}
			
		if(temperature == 0.0) {
			if(quench_count == 10) break;
			quench_count++;
		}

		if(last_cost_count > 25) 
			temperature = 0;

		if(temperature < 0.0000001) temperature = 0;

		success_rate = (float)num_accepted / (float)num_moves;
		success_rate_this_temp = (float)num_accepted_this_temp / (float)num_moves_this_temp;

		if(temperature_count %5 == 0 || temperature == 0) {
			printf("%g\t%.0f\t%.2f\t%2f\n", temperature, cost, success_rate, success_rate_this_temp);
	//		anneal_print_buckets(&annealer);
		}
		
		if(success_rate > 0.96 )
			temperature *= 0.5;
		else if( success_rate > 0.8 )
			temperature *= 0.9;
		else if( success_rate > 0.15 )
			temperature *= 0.95;
		else
			temperature *= 0.8;
	}
	anneal_print_buckets(&annealer);

	/* Copy back pointers */
	*output_buckets = malloc(sizeof(struct GPtrArray *) * num_buckets);
	for(x=0; x<num_buckets; x++) {
		struct _anneal_bucket *b = &annealer.buckets[x];
		(*output_buckets)[x] = b->items;
		b->items = NULL;
	}

	free(annealer.buckets);
	return 0;
}



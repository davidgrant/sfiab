#ifndef ANNEAL_H
#define ANNEAL_H


struct _anneal_bucket {
	GPtrArray *items;
	float cost;
};

struct _anneal_move {
	int b1, b2, i1, i2;
	void *p1, *p2;
	float new_cost1, new_cost2;
};



struct _annealer {
	int num_buckets;
	GPtrArray *items;
	void *data_ptr;

	float (*cost_function)(struct _annealer *annealer, int bucket_id, GPtrArray *bucket);
	int (*propose_move)(struct _annealer *annealer, struct _anneal_move *move);


	struct _anneal_bucket *buckets;

	
	int *item_bucket;	/* 0..items->len */

};


int anneal_propose_move(struct _annealer *annealer, struct _anneal_move *move);

int anneal( void *data_ptr, GPtrArray ***output_buckets, int num_buckets, 
		GPtrArray *items, 
		float (*cost_function)(struct _annealer *annealer, int bucket_id, GPtrArray *bucket), 
		int (*propose_move)(struct _annealer *annealer, struct _anneal_move *move)
	);

#endif

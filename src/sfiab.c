#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <assert.h>

#include <glib.h>

#include "anneal.h"
#include "db.h"
#include "sfiab.h"

GPtrArray *categories = NULL, *challenges = NULL;
GPtrArray *isef_divisions = NULL;
struct _config config;


int split_int_list(int *list, char *str)
{
	int i = 0;
	char *p;

	/* Special case, return nothing if the string is empty */
	if(str[0] == 0) {
		list[0] = 0;
		return 0;
	}

	while(1) {
		/* Find a comma and null it out */
		p = strchr(str, ',');
		if(p) *p = 0;

		/* Convert everything up to the comma(or everything if comma not found) */
		list[i] = atoi(str);
		i++;

		/* Set str forward to where the comma was */
		if(!p) break;
		str = p+1;
	}
	return i;
}

int list_contains_int(int *list, int len, int val)
{
	int x;
	for(x=0;x<len;x++) {
		if(list[x] == val) return 1;
	}
	return 0;
}

int split_str_list(char **list, char *str)
{
	int i = 0;
	char *p;

	/* Special case, return nothing if the string is empty */
	if(str[0] == 0) {
		list[0] = NULL;
		return 0;
	}

	while(1) {
		/* Find a comma and null it out */
		p = strchr(str, ',');
		if(p) *p = 0;

		/* Convert everything up to the comma(or everything if comma not found) */
		list[i] = strdup(str);
		i++;

		/* Set str forward to where the comma was */
		if(!p) break;
		str = p+1;
	}
	return i;
}


void config_load(struct _db_data *db)
{
	struct _db_result *result;
	int x;

	result = db_query(db, "SELECT * FROM config");
	for(x=0;x<result->rows; x++) {
		char *var, *val;
		var = db_fetch_row_field(result, x, "var");
		val = db_fetch_row_field(result, x, "val"); 

		if(strcmp(var, "year") == 0)
			config.year = atoi(val);
		if(strcmp(var, "judge_div_max_projects") == 0)
			config.max_projects_per_judge = atoi(val);
		if(strcmp(var, "judge_div_max_team") == 0)
			config.max_judges_per_team = atoi(val);
		if(strcmp(var, "judge_cusp_max_team") == 0)
			config.max_judges_per_cusp_team = atoi(val);
		if(strcmp(var, "judge_sa_max_projects") == 0)
			config.projects_per_sa_judge = atoi(val);

	}

	config.min_judges_per_team = config.max_judges_per_team;
	config.min_judges_per_cusp_team = config.max_judges_per_cusp_team;

	printf("Loaded SFIAB Config:\n");
	printf("   year: %d\n", config.year);
	printf("   Projects per Div judge: %d\n", config.max_projects_per_judge);
	printf("   Projects per SA judge: up to %d\n", config.projects_per_sa_judge);
	printf("   Judges per Div Team: %d\n", config.max_judges_per_team);
	printf("   Judges per Cusp Team: %d\n", config.max_judges_per_cusp_team);

	
	db_free_result(result);
}


void categories_load(struct _db_data *db, int year)
{
	int x;
	struct _db_result *result;
	categories = g_ptr_array_new();
	/* Load judges  */
	result = db_query(db, "SELECT * FROM categories WHERE year='%d' ORDER BY cat_id", year);
	for(x=0;x<result->rows; x++) {
		struct _category *c = malloc(sizeof(struct _category));
		c->name = strdup(db_fetch_row_field(result, x, "name"));
		c->id = atoi(db_fetch_row_field(result, x, "cat_id"));
		c->shortform = strdup(db_fetch_row_field(result, x, "shortform"));
		printf("%d: %s %s\n", 
			c->id, c->shortform, c->name);
		g_ptr_array_add(categories, c);
	}
	db_free_result(result);
}

/* cat ids always start at 1, the index array starts at 0 */
struct _category *category_find(int cat_id)
{
	if(cat_id <= 0 || cat_id > categories->len) return NULL;
	return g_ptr_array_index(categories, cat_id - 1);
}



void challenges_load(struct _db_data *db, int year)
{
	int x;
	struct _db_result *result;
	challenges = g_ptr_array_new();
	/* Load judges  */
	result = db_query(db, "SELECT * FROM challenges WHERE year='%d' ORDER BY chal_id", year);
	for(x=0;x<result->rows; x++) {
		struct _challenge *c = malloc(sizeof(struct _challenge));
		c->name = strdup(db_fetch_row_field(result, x, "name"));
		c->id = atoi(db_fetch_row_field(result, x, "chal_id"));
		c->shortform = strdup(db_fetch_row_field(result, x, "shortform"));
		printf("%d: %s %s\n", 
			c->id, c->shortform, c->name);
		g_ptr_array_add(challenges, c);
	}
	db_free_result(result);
}

/* chal ids always start at 1, the index array starts at 0 */
struct _challenge *challenge_find(int challenge_id)
{
	if(challenge_id <= 0 || challenge_id > challenges->len) return NULL;
	return g_ptr_array_index(challenges, challenge_id - 1);
/*
	int x;
	for(x=0;x<categories->len;x++) {
		struct _category *cat = g_ptr_array_index(categories, x);
		if(cat_id == cat->id) return cat;
	}
	return NULL;
*/
}

int isef_division_find_by_div(char *div)
{
	int x;
	for(x=1;x<isef_divisions->len;x++) {
		struct _isef_division *pd = g_ptr_array_index(isef_divisions, x);
		if(strcmp(pd->div, div) == 0) {
			return x;
		}
	}
	return -1;
}


void define_isef_division(int id, char *parent, char *div, char *name, char *desc)
{
	struct _isef_division *d = malloc(sizeof(struct _isef_division));
	d->id = id;
	d->div = strdup(div);
	d->name = strdup(name);
	d->parent = -1;
	d->num_similar = 0;
	d->similar = malloc(4 * sizeof(int));
	d->similar_mask = NULL;

	if(strlen(parent) > 0) {
		d->parent = isef_division_find_by_div(parent);
		if(d->parent == -1) {
			printf("ERROR: div %s with parent %s couldn't find the parent\n", div, parent);
		}
	}

	g_ptr_array_add(isef_divisions, d);
}

void define_isef_similar_one(char *div, char *r)
{
	int isef_id, r_id;
	struct _isef_division *d;

	if(r[0] == 0) return;

	isef_id = isef_division_find_by_div(div);
	r_id = isef_division_find_by_div(r);
	d = g_ptr_array_index(isef_divisions, isef_id);

	if(r_id == -1) {
		printf("ERROR: unknown div %s\n", r);
		assert(0);
	}

	d->similar[d->num_similar] = r_id;
	d->num_similar++;

}

void define_isef_similar(char *div, char *r1, char *r2, char *r3, char *r4)
{
	struct _isef_division *d;
	int isef_id, x;

	isef_id = isef_division_find_by_div(div);
	d = g_ptr_array_index(isef_divisions, isef_id);
	/* Alloc a mask and add ourself to the mask */
	if(d->similar_mask == NULL) {
		d->similar_mask = calloc(1, (isef_divisions->len + 1) * sizeof(int));
		d->similar_mask[isef_id] = 1;
	}

	/* Add the 1,2,3,4th arg to the mask */
	define_isef_similar_one(div, r1);
	define_isef_similar_one(div, r2);
	define_isef_similar_one(div, r3);
	define_isef_similar_one(div, r4);

	for(x=0;x<d->num_similar; x++) {
		d->similar_mask[d->similar[x]] = 1;
	}
}


void isef_divisions_load(struct _db_data *db, int year)
{
	isef_divisions = g_ptr_array_new();
	g_ptr_array_add(isef_divisions, NULL);

	define_isef_division(1, "", "AS", "ANIMAL SCIENCES", "Study of animals and animal life, including their structure, function, life history, interactions, classification, and evolution.");
	define_isef_division( 2, "AS", "BEH", "Animal Behavior", "The study of animal activities, on the level of the intact organism or its neurological components. This includes rhythmic functions, learning, and intelligence, sensory preferences, and environmental effects on behaviors.");
	define_isef_division( 3, "AS", "DEV", "Development", "The study of an organism from earliest stages through birth or hatching and into later life. This includes cellular and molecular aspects of development, regeneration, and environmental effects on development.");
	define_isef_division( 4, "AS", "ECO", "Ecology", "The science of the interactions and relationships among animals and animals and plants with their environments.");
	define_isef_division( 5, "AS", "GENE", "Genetics", "The study of organismic and population genetics.");
	define_isef_division( 6, "AS", "NUTR", "Nutrition and Growth", "The study of natural and artificial nutrients on animal growth and reproduction. This also includes the effects of biological and chemical control agents on reproduction and populations.");
	define_isef_division( 7, "AS", "PATH", "Pathology", "The study of disease states, and their causes, processes, and consequences. This includes effects of parasites or disease-causing microbes.");
	define_isef_division( 8, "AS", "PHY", "Physiology", "The study of functions in systems of animals, their mechanisms, and how they are affected by environmental factors or natural variations that select for particular genes.");
	define_isef_division( 9, "AS", "SYST", "Systematics and Evolution", "The study of classification of organisms and their evolutionary relationships. This includes morphological, biochemical, genetic, and modeled systems.");
	define_isef_division(10, "AS", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(11, "", "BE", "BEHAVIORAL AND SOCIAL SCIENCES", "The science or study of the thought processes and behavior of humans and other animals in their interactions with the environment studied through observational and experimental methods.");
	define_isef_division(12, "BE", "CLIN", "Clinical and Developmental Psychology", "The study and treatment of emotional or behavioral disorders. Developmental psychology is concerned with the study of progressive behavioral changes in an individual from birth until death.");
	define_isef_division(13, "BE", "COG", "Cognitive, Brain and Cognition, Neuro", "psychology - The study of cognition, the mental processes that underlie behavior, including thinking, deciding, reasoning, and to some extent motivation and emotion. Neuro-psychology studies the relationship between the nervous system, especially the brain, and cerebral or mental functions such as language, memory, and perception.");
	define_isef_division(14, "BE", "PHY", "Physiological Psychology", "The study of the biological and physiological basis of behavior.");
	define_isef_division(15, "BE", "SOC", "Sociology and Social Psychology; Industrial/Organizational Psychology", "The study of human social behavior, especially the study of the origins, organization, institutions, and development of human society. Sociology is concerned with all group activities-economic, social, political, and religious.");
	define_isef_division(16, "BE", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(17, "", "BI", "BIOCHEMISTRY", "The study of chemical substances, interactions, and processes relevant to living organisms.");
	define_isef_division(18, "BI", "ANAL", "Analytical Biochemistry", "The study of the separation, identification, and quantification of chemical components relevant to living organisms.");
	define_isef_division(19, "BI", "GEN", "General Biochemistry", "The study of chemical processes, including interactions and reactions, relevant to living organisms.");
	define_isef_division(20, "BI", "MED", "Medicinal Biochemistry", "The study of biochemical processes within the human body, with special reference to health and disease. ");
	define_isef_division(21, "BI", "STRU", "Structural Biochemistry", "The study of the structure and or function of biological molecules.");
	define_isef_division(22, "BI", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories. ");

	define_isef_division(23, "", "CB", "CELLULAR AND MOLECULAR BIOLOGY", "The study of the structure and formation of cells.");
	define_isef_division(24, "CB", "CELL", "Cellular Biology", "The study of the organization and functioning of the individual cell.");
	define_isef_division(25, "CB", "GENE", "Genetics", "The study of molecular genetics focusing on the structure and function of genes at a molecular level.");
	define_isef_division(26, "CB", "IMM", "Immunology", "The study of the structure and function of the immune system, innate and acquired immunity, and laboratory techniques involving the interaction of antigens with antibodies.");
	define_isef_division(27, "CB", "MOLE", "Molecular Biology", "The study of biology at the molecular level. Chiefly concerns itself with understanding the interactions between the various systems of a cell, including the interrelationships of DNA, RNA and protein synthesis and learning how these interactions are regulated.");
	define_isef_division(28, "CB", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(29, "", "CH", "CHEMISTRY", "The science of the composition, structure, properties, and reactions of matter.");
	define_isef_division(30, "CH", "ANAL", "Analytical Chemistry", "The study of the separation, identification, and quantification of the chemical components of materials. ");
	define_isef_division(31, "CH", "ENV", "Environmental Chemistry", "The study of chemical species in the natural environment, including the effects of human activities, such as the design of products and processes that reduce or eliminate the use or generation of hazardous substances.");
	define_isef_division(32, "CH", "INOR", "Inorganic Chemistry", "The study of the properties and reactions of inorganic and organometallic compounds. ");
	define_isef_division(33, "CH", "MAT", "Materials Chemistry", "The study of the design, synthesis and properties of substances, including condensed phases (solids, liquids, polymers) and interfaces, with a useful or potentially useful function, such as catalysis or solar energy. ");
	define_isef_division(34, "CH", "ORGA", "Organic Chemistry", "The study of carbon-containing compounds, including hydrocarbons and their derivatives. ");
	define_isef_division(35, "CH", "PHY", "Physical Chemistry", "The study of the fundamental physical basis of chemical systems and processes, including chemical kinetics, chemical thermodynamics, electrochemistry, photochemistry, spectroscopy, statistical mechanics and astro-chemistry.");
	define_isef_division(36, "CH", "OTHR", "Other", "Studies that cannot be assigned to one of the above subcategories, such as nuclear chemistry, surface chemistry and theoretical chemistry.");

	define_isef_division(37, "", "CS", "COMPUTER SCIENCE", "The study of information processes, the structures and procedures that represent processes, and their implementation in information processing systems. It includes systems analysis and design, application and system software design, programming, and datacenter operations.");
	define_isef_division(38, "CS", "ALGO", "Algorithms, Data Bases", "The study of algorithms and databases. Software developed to manage any form of data including text, images, sound and video.");
	define_isef_division(39, "CS", "ARTI", "Artificial Intelligence", "The study of the ability of a computer or other machine to perform those activities that are normally thought to require intelligence, such as solving problems, discriminating among objects, and/or responding to voice commands. This also includes speech analysis and synthesis.");
	define_isef_division(40, "CS", "NET", "Networking and Communications", "The study of systems that transmits any combination of voice, video, and/or data among users.");
	define_isef_division(41, "CS", "SCIE", "Computational Science, Computer Graphics", "The study of the use of computers to perform research in other fields, such as computer simulations. Also includes the study of computer graphics or the transfer of pictorial data into and out of a computer by various means (analog-to-digital, optical scanning, etc), such as in computer image processing.");
	define_isef_division(42, "CS", "SOFT", "Software Engineering, Programming Languages", "The study of software designed to control the hardware of a specific data processing system in order to allow users and application programs to make use of it. This sub-category includes web technologies, programming languages and human-computer interactions.");
	define_isef_division(43, "CS", "SYST", "Computer System, Operating System", "The study of system software responsible for the direct control and management of hardware and basic system operations of a computer.");
	define_isef_division(44, "CS", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(45, "", "EA", "EARTH AND PLANETARY SCIENCE", "The study of sciences similar to the planet Earth (Geology, minerology, physiography, oceanography, meteorology, climatology, speleology, sesismology, geography, atmospheric sciences, etc.)");
	define_isef_division(46, "EA", "CLIM", "Climatology, Meteorology, Weather", "the scientific study of the atmosphere that focuses on weather processes and forecasting.");
	define_isef_division(47, "EA", "GEO", "Geochemistry, Mineralogy", "The study of the chemical composition of the earth and other planets, chemical processes and reactions that govern the composition of rocks and soils. Mineralogy is focused around the chemistry, crystal structure and physical (including optical) properties of minerals.");
	define_isef_division(48, "EA", "HIST", "Historical Paleontology", "The study of life in the geologic past as recorded by fossil remains.");
	define_isef_division(49, "EA", "PHY", "Geophysics", "Branch of geology in which the principles and practices of physics are used to study the earth and its environment.");
	define_isef_division(50, "EA", "PLAN", "Planetary Science", "The study of planets or planetary systems and the solar system.");
	define_isef_division(51, "EA", "TECH", "Tectonics", "The study of the earth's structural features as similar to plate structure, plate movement, volcanism, etc.");
	define_isef_division(52, "EA", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(53, "", "EE", "ENGINEERING: Electrical and Mechanical", "The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical structures, processes, and systems.");
	define_isef_division(54, "EE", "ELEC", "Electrical Engineering, Computer Engineering, Controls", "Electrical engineering is the branch of engineering that deals with the technology of electricity, especially the design and application of circuitry and equipment for power generation and distribution, machine control, and communications. A computer engineer is an electrical engineer with a focus on digital logic systems or a software architect with a focus on the interaction between software programs and the underlying hardware architecture.");
	define_isef_division(55, "EE", "MECH", "Mechanical Engineering", "The branch of engineering that encompasses the generation and application of heat and mechanical power and the design, production, and use of machines and tools.");
	define_isef_division(56, "EE", "ROB", "Robotics", "The science or study of the technology associated with the design, fabrication, theory, and application of robots and of general purpose, programmable machine systems.");
	define_isef_division(57, "EE", "THRM", "Thermodynamics, Solar", "Thermodynamics involves the physics of the relationships and conversions between heat and other forms of energy. Solar is the technology of obtaining usable energy from the light of the sun.");
	define_isef_division(58, "EE", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(59, "", "EN", "ENGINEERING: Materials and Bioengineering", "The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical machines and systems.");
	define_isef_division(60, "EN", "BIO", "Bioengineering", "Involves the application of engineering principles to the fields of biology and medicine, as in the development of aids or replacements for defective or missing body organs; the development and manufacture of prostheses, medical devices, diagnostic devices, drugs and other therapies as well as the application of engineering principles to basic biological science problems.");
	define_isef_division(61, "EN", "CHEM", "Chemical Engineering", "Deals with the design, construction, and operation of plants and machinery for making such products as acids, dyes, drugs, plastics, and synthetic rubber by adapting the chemical reactions discovered by the laboratory chemist to large-scale production.");
	define_isef_division(62, "EN", "CIVI", "Civil Engineering, Construction Engineering", "Includes the planning, designing, construction, and maintenance of structures and public works, such as bridges or dams, roads, water supply, sewer, flood control and, traffic.");
	define_isef_division(63, "EN", "IND", "Industrial Engineering, Processing", "Concerned with efficient production of industrial goods as affected by elements such as plant and procedural design, the management of materials and energy, and the integration of workers within the overall system. The industrial engineer designs methods, not machinery.");
	define_isef_division(64, "EN", "MAT", "Material Science", "A multidisciplinary field relating the performance and function of matter in any and all applications to its micro, nano, and atomic structure, and vice versa. It often involves the study of the characteristics and uses of various materials, such as metals, ceramics, and plastics and their potential engineering applications.");
	define_isef_division(65, "EN", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(66, "", "ET", "ENERGY & TRANSPORTATION", "The study of renewable energy sources, energy efficiency, clean transport, and alternative fuels.");
	define_isef_division(67, "ET", "AERO", "Aerospace and Aeronautical Engineering, Aerodynamics", "The design of aircraft and space vehicles and the direction of the technical phases of their manufacture and operation.");
	define_isef_division(68, "ET", "ALT", "Alternative Fuels", "Any method of powering an engine that does not involve petroleum (oil). Some alternative fuels are electricity, hythane, hydrogen, natural gas, and wood.");
	define_isef_division(69, "ET", "FOS", "Fossil Fuel Energy", "Energy from a hydrocarbon deposit, such as petroleum, coal, or natural gas, derived from living matter of a previous geologic time and used for fuel.");
	define_isef_division(70, "ET", "VEH", "Vehicle Development", "Engineering of vehicles that operate using energy other than from fossil fuel.");
	define_isef_division(71, "ET", "REN", "Renewable Energies", "Renewable energy sources capture their energy from existing flows of energy, from on-going natural processes such as sunshine, wind, flowing water, biological processes, and geothermal heat flows.");
	define_isef_division(72, "ET", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(73, "", "EM", "ENVIRONMENTAL MANAGEMENT", "The application of engineering principals to solve practical problems of managing mans' interaction with the environment with the goal to maintain and improve the state of an environmental resource affected by human activities.");
	define_isef_division(74, "EM", "BIO", "Bioremediation", "The use of biological agents, such as bacteria or plants, to remove or neutralize contaminants, as in polluted soil or water. Includes phytoremediation, constructed wetlands for wastewater treatment, biodegradation, etc.");
	define_isef_division(75, "EM", "ECO", "Ecosystems Management", "The integration of ecological, economic, and social principles to manage biological and physical systems in a manner that safeguards the long-term ecological sustainability, natural diversity, and productivity of the landscape. An ecological approach to managing the environment.");
	define_isef_division(76, "EM", "ENG", "Environmental Engineering", "The application of engineering principals to solve practical problems in the supply of water, the disposal of waste, and the control of pollution. Includes alternative engineering methodologies to meet society's needs in an environmentally sound and sustainable manner. Preservation of the environment by preventing the contamination of, and facilitating the clean up of, air, water, and land resources.");
	define_isef_division(77, "EM", "LAND", "Land Resource Management and Forestry", "A landscape approach to sustainable resource management, coastal management, biological diversity management, land use planning, or forest succession management. It often includes a resource planning component as well as implementation methodologies. An example would be the management of longleaf pine forests including controlled burns to imitate natural processes.");
	define_isef_division(78, "EM", "REC", "Recycling and Waste Management", "The extraction and reuse of useful substances from discarded items, garbage, or waste. The process of managing, and disposing of, wastes and hazardous substances through methodologies such as landfills, sewage treatment, composting, waste reduction, etc.");
	define_isef_division(79, "EM", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(80, "", "EV", "ENVIRONMENTAL SCIENCES", "The analysis of existing conditions of the environment.");
	define_isef_division(81, "EV", "AIR", "Air Pollution and Air Quality", "The study of contamination of the air by such things as noxious gases, elements, minerals, chemicals, solid and liquid matter (particulates), etc. Air pollution is the study of such contaminates in concentrations that endanger the health of humans, plants, and/or animals.");
	define_isef_division(82, "EV", "SOIL", "Soil Contamination and Soil Quality", "The study of contamination of the soil by such things as noxious elements, minerals, chemicals, solids, liquids, etc. Soil contamination is the study of such contaminates in concentrations that endanger the health of humans, plants, and/or animals.");
	define_isef_division(83, "EV", "WATE", "Water Pollution and Water Quality", "The study of contamination of the water by such things as noxious elements, minerals, chemicals, solids, etc. Water pollution is the study of such contaminates in concentrations that endanger the health of humans, plants, and/or animals.");
	define_isef_division(84, "EV", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(85, "", "MA", "MATHEMATICAL SCIENCES", "The study of the measurement, properties, and relationships of quantities and sets, using numbers and symbols. The deductive study of numbers, geometry, and various abstract constructs, or structures.");
	define_isef_division(86, "MA", "ALG", "Algebra", "The study of algebraic operations and/or relations and the structures which arise from them. An example is given by (systems of) equations which involve polynomial functions of one or more variables. ");
	define_isef_division(87, "MA", "ANAL", "Analysis", "The study of infinitesimal processes in mathematics, typically involving the concept of a limit. This begins with differential and integral calculus, for functions of one or several variables, and includes differential equations. ");
	define_isef_division(88, "MA", "COMP", "Computer Mathematics", "Branch of mathematics that concerns itself with the mathematical techniques typically used in the application of mathematical knowledge to other domains. Not every project that uses some mathematics belongs here; this category is for projects where the mathematics is the primary component.  ");
	define_isef_division(89, "MA", "COMB", "Combinatorics, Graph Theory and Game Theory", "The study of combinatorial structures in mathematics, such as finite sets, graphs, and games, often with a view toward classification and/or enumeration.");
	define_isef_division(90, "MA", "GEO", "Geometry and Topology", "The study of the shape, size, and other properties of figures and spaces. Includes such subjects as Euclidean geometry, non-Euclidean geometries (spherical, hyperbolic, Riemannian, Lorentzian), and knot theory (classification of knots in 3-space).");
	define_isef_division(91, "MA", "NUM", "Number Theory", "The study of the arithmetic properties of integers and similar topics such as cryptography.");
	define_isef_division(92, "MA", "PROB", "Probability and Statistics", "Mathematical study of random phenomena and the study of statistical tools used to analyze and interpret data.");
	define_isef_division(93, "MA", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(94, "", "ME", "HEALTH SCIENCES", "The science of diagnosing, treating, or preventing disease and other damage to the body or mind.");
	define_isef_division(95, "ME", "DIS", "Disease Diagnosis and Treatment", "The act or process of identifying or determining the nature and cause of a disease or injury through evaluation of patient history, examination, and review of laboratory data. Administration or application of remedies to a patient or for a disease or injury; medicinal or surgical management; therapy.");
	define_isef_division(96, "ME", "EPID", "Epidemiology", "The study of the causes, distribution, and control of disease in populations. Epidemiologists, using sophisticated statistical analyses, field investigations, and complex laboratory techniques, investigate the cause of a disease, its distribution (geographic, ecological, and ethnic), method of spread, and measures for control and prevention.");
	define_isef_division(97, "ME", "GENE", "Genetics", "The study of heredity, especially the mechanisms of hereditary transmission and the variation of inherited traits among similar or similar organisms.");
	define_isef_division(98, "ME", "MOLE", "Molecular Biology of Diseases", "The study of diseases at the molecular level.");
	define_isef_division(99, "ME", "PHYS", "Physiology and Pathophysiology", "The science of the mechanical, physical, and biochemical functions of normal tissues or organs. Pathophysiology is the study of the disturbance of normal mechanical, physical, and biochemical functions that a disease causes, or that which causes the disease.");
	define_isef_division(100, "ME", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories.");

	define_isef_division(101, "", "MI", "MICROBIOLOGY", "The study of microorganisms, including bacteria, viruses, fungi, and pathogens.");
	define_isef_division(102, "MI", "ANTI", "Antimicrobial Agents", "The study of substances that kill or inhibit the growth of microorganisms.");
	define_isef_division(103, "MI", "APP", "Applied Microbiology", "The study of microorganisms having potential applications in human, animal or plant health or energy production.");
	define_isef_division(104, "MI", "BACT", "Bacterial Microbiology", "The study of bacteria and bacterial diseases.");
	define_isef_division(105, "MI", "ENV", "Environmental Microbiology", "The study of the structure, function, diversity and relationship of microorganisms with respect to their environment.");
	define_isef_division(106, "MI", "GENE", "Microbial Genetics", "The study of how genes are organized and regulated in microorganisms in relation to their cellular function.");
	define_isef_division(107, "MI", "VIRO", "Virology", "The study the anatomy, physiology of viruses and the diseases they cause.");
	define_isef_division(108, "MI", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories, such as  microbial cytology, physiology and pathogenesis.");

	define_isef_division(109, "", "PH", "PHYSICS AND ASTRONOMY", "Physics is the science of matter and energy and of interactions between the two. Astronomy is the study of anything in the universe beyond the Earth.");
	define_isef_division(110, "PH", "AMO", "Atomic Molecular and Optical Physics", "The study of atoms, simple molecules, electrons and light, and their interactions.");
	define_isef_division(111, "PH", "ASTR", "Astronomy and Cosmology", "The study of space,  the universe as a whole, including its origins and evolution, the physical properties of objects in space and computational astronomy");
	define_isef_division(112, "PH", "BIO", "Biological Physics", "The study of the physics of biological processes.");
	define_isef_division(113, "PH", "INST", "Instrumentation and Electronics", "Instrumentation is the process of developing means of precise measurement of various variables such as flow and pressure while maintaining control of the variables at desired levels of safety and economy. Electronics is the branch of physics that deals with the emission and effects of electrons and with the use of electronic devices.");
	define_isef_division(114, "PH", "MAT", "Condensed Matter and Materials", "The study of the preparation, properties and performance of materials to help understand and optimize their behavior. Topics such as superconductivity, semi-conductors, complex fluids, and thin films are studied.");
	define_isef_division(115, "PH", "MAG", "Magnetics, Electromagnetics and Plasmas", "The study of electrical and magnetic fields and of matter in the plasma phase and their effects on materials in the solid, liquid or gaseous states.");
	define_isef_division(116, "PH", "MECH", "Mechanics", "Classical physics and mechanics, including the macroscopic study of forces, vibrations and flows; on solid, liquid and gaseous materials");
	define_isef_division(117, "PH", "NUCL", "Nuclear and Particle Physics", "The study of the physical properties of the atomic nucleus and of fundamental particles and the forces of their interaction");
	define_isef_division(118, "PH", "OPT", "Optics, Lasers, and Masers", "The study of the physical properties of light, lasers and masers.");
	define_isef_division(119, "PH", "THEO", "Theoretical Physics, Theoretical or Computational Astronomy", "The study of nature, phenomena and the laws of physics employing mathematical models and abstractions rather than experimental processes. ");
	define_isef_division(120, "PH", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories. ");

	define_isef_division(121, "", "PS", "PLANT SCIENCES", "Study of plant life, including their structure and function, life history, growth, interactions with other plants and animals, classification, and evolution.");
	define_isef_division(122, "PS", "AGR", "Agronomy", "The application of the various soil and plant sciences to soil management and agricultural and horticultural crop production. Includes biological and chemical controls of pests, hydroponics, fertilizers and supplements.");
	define_isef_division(123, "PS", "DEV", "Development and Growth", "The study of a plant from earliest stages through germination and into later life. This includes cellular and molecular aspects of development and environmental effects, natural or manmade, on development and growth.");
	define_isef_division(124, "PS", "ECO", "Ecology", "The study of interactions and relationships among plants, and plants and animals, with their environment.");
	define_isef_division(125, "PS", "GEN", "Genetics/Breeding", "The study of organismic and population genetics of plants. The application of plant genetics and biotechnology to crop improvement.");
	define_isef_division(126, "PS", "PATH", "Pathology", "The study of plant disease states, and their causes, processes, and consequences. This includes effects of parasites or disease-causing microbes.");
	define_isef_division(127, "PS", "PHY", "Plant Physiology", "The study of functions of plants, their mechanisms, and how they are affected by environmental factors or natural variations. This includes all aspects of photosynthesis.");
	define_isef_division(128, "PS", "SYST", "Systematics and Evolution", "The study of classification of organisms and their evolutionary relationships. This includes morphological, biochemical, genetic, and modeled systems.");
	define_isef_division(129, "PS", "OTHR", "Other", "Studies that cannot be assigned to one of the above categories, such as the effects of plants or plant-derived substances on animal and human health.");

	/*ANIMAL SCIENCES, Study of animals and animal life, including their structure, function, life history, interactions, classification, and evolution.*/
	define_isef_similar("AS", "" /*"ME"*/, "BI", "CB", "EV");

	/* BEHAVIORAL AND SOCIAL SCIENCES, The science or study of the thought processes and behavior of humans and other animals in their interactions with the environment studied through observational and experimental methods.*/
	define_isef_similar("BE", "ME", "", "", "");

	/* BIOCHEMISTRY, The study of chemical substances, interactions, and processes relevant to living organisms.*/
	define_isef_similar("BI", "CB", "MI", "" /*"ME"*/, "");

	/* CELLULAR AND MOLECULAR BIOLOGY, The study of the structure and formation of cells.*/
	define_isef_similar("CB", "BI", /*"ME"*/"", "MI", "");

	/* CHEMISTRY, The science of the composition, structure, properties, and reactions of matter.*/
	define_isef_similar("CH", "BI", "EV", "", "");

	/* COMPUTER SCIENCE, The study of information processes, the structures and procedures that represent processes, and their implementation in information processing systems. It includes systems analysis and design, application and system software design, programming, and datacenter operations.*/
	define_isef_similar("CS", "MA", "EE", "PH", "");

	/* EARTH AND PLANETARY SCIENCE, The study of sciences similar to the planet Earth (Geology, minerology, physiography, oceanography, meteorology, climatology, speleology, sesismology, geography, atmospheric sciences, etc.)*/
	define_isef_similar("EA", "ET", "EM", "EV", "AS");

	/* ENGINEERING: Electrical and Mechanical, The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical structures, processes, and systems.*/
	define_isef_similar("EE", "EN", "MA", "CS", "");

	/* ENGINEERING: Materials and Bioengineering, The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical machines and systems.*/
	define_isef_similar("EN", "BI", "ET", "", "");

	/* ENERGY & TRANSPORTATION, The study of renewable energy sources, energy efficiency, clean transport, and alternative fuels.*/
	define_isef_similar("ET", "EN", "EA", "" /*"EE"*/, "");

	/* ENVIRONMENTAL MANAGEMENT, The application of engineering principals to solve practical problems of managing mans\' interaction with the environment with the goal to maintain and improve the state of an environmental resource affected by human activities.*/
	define_isef_similar("EM", "EV", "ET", "", "");

	/* ENVIRONMENTAL SCIENCES, The analysis of existing conditions of the environment.*/
	define_isef_similar("EV", "BI", "CH", "EA", "AS");

	/* MATHEMATICAL SCIENCES, The study of the measurement, properties, and relationships of quantities and sets, using numbers and symbols. The deductive study of numbers, geometry, and various abstract constructs, or structures.*/
	define_isef_similar("MA", "CS", "EE", "PH", "");

	/* HEALTH SCIENCES, The science of diagnosing, treating, or preventing disease and other damage to the body or mind.*/
	define_isef_similar("ME", "" /*"BE"*/, "BI", ""/*"CB"*/, "" /*"MI"*/);

	/* MICROBIOLOGY, The study of microorganisms, including bacteria, viruses, fungi, and pathogens.*/
	define_isef_similar("MI", "CB", ""/*"ME"*/, "BI", "");

	/* PHYSICS AND ASTRONOMY, Physics is the science of matter and energy and of interactions between the two. Astronomy is the study of anything in the universe beyond the Earth.*/
	define_isef_similar("PH", "EA", "MA", "EE", "");

	/* PLANT SCIENCES, Study of plant life, including their structure and function, life history, growth, interactions with other plants and animals, classification, and evolution.*/
	define_isef_similar("PS", "BI", "EV", "CH", "");

	printf("Loaded %d ISEF divisions\n", isef_divisions->len);
}


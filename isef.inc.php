<?php

/*
$isef_divs = array(
	 => array('id'=> 1, 'parent'=>false, 'div'=>'AS', 'name'=>'ANIMAL SCIENCES', 'desc'=>'Study of animals and animal life, including their structure, function, life history, interactions, classification, and evolution.'),
		=> 94, 17, 23, 

	 => array('id'=>11, 'parent'=>false, 'div'=>'BE', 'name'=>'BEHAVIORAL AND SOCIAL SCIENCES', 'desc'=>'The science or study of the thought processes and behavior of humans and other animals in their interactions with the environment studied through observational and experimental methods.'),
		=> 94

	 => array('id'=>17, 'parent'=>false, 'div'=>'BI', 'name'=>'BIOCHEMISTRY', 'desc'=>'The study of chemical substances, interactions, and processes relevant to living organisms.'),
	 	=> 23, 94, 101

	 => array('id'=>23, 'parent'=>false, 'div'=>'CB', 'name'=>'CELLULAR AND MOLECULAR BIOLOGY', 'desc'=>'The study of the structure and formation of cells.'),
		=> 17, 94, 101

	 => array('id'=>29, 'parent'=>false, 'div'=>'CH', 'name'=>'CHEMISTRY', 'desc'=>'The science of the composition, structure, properties, and reactions of matter.'),
		=> 17, 80, 121

	 => array('id'=>37, 'parent'=>false, 'div'=>'CS', 'name'=>'COMPUTER SCIENCE', 'desc'=>'The study of information processes, the structures and procedures that represent processes, and their implementation in information processing systems. It includes systems analysis and design, application and system software design, programming, and datacenter operations.'),
		=> 85, 53, 

	 => array('id'=>45, 'parent'=>false, 'div'=>'EA', 'name'=>'EARTH AND PLANETARY SCIENCE', 'desc'=>'The study of sciences related to the planet Earth (Geology, minerology, physiography, oceanography, meteorology, climatology, speleology, sesismology, geography, atmospheric sciences, etc.)'),
		=> 66, 73, 82 

	 => array('id'=>53, 'parent'=>false, 'div'=>'EE', 'name'=>'ENGINEERING: Electrical and Mechanical', 'desc'=>'The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical structures, processes, and systems.'),
		=> 59, 66, 37

	 => array('id'=>59, 'parent'=>false, 'div'=>'EN', 'name'=>'ENGINEERING: Materials and Bioengineering', 'desc'=>'The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical machines and systems.'),

	 => array('id'=>66, 'parent'=>false, 'div'=>'ET', 'name'=>'ENERGY & TRANSPORTATION', 'desc'=>'The study of renewable energy sources, energy efficiency, clean transport, and alternative fuels.'),
		-> 53, 59, 45

	 => array('id'=>73, 'parent'=>false, 'div'=>'EM', 'name'=>'ENVIRONMENTAL MANAGEMENT', 'desc'=>'The application of engineering principals to solve practical problems of managing mans\' interaction with the environment with the goal to maintain and improve the state of an environmental resource affected by human activities.'),
		=> 80, 66, 59

	 => array('id'=>80, 'parent'=>false, 'div'=>'EV', 'name'=>'ENVIRONMENTAL SCIENCES', 'desc'=>'The analysis of existing conditions of the environment.'),
		=> 17, 29, 45

	 => array('id'=>85, 'parent'=>false, 'div'=>'MA', 'name'=>'MATHEMATICAL SCIENCES', 'desc'=>'The study of the measurement, properties, and relationships of quantities and sets, using numbers and symbols. The deductive study of numbers, geometry, and various abstract constructs, or structures.'),
		=> 109, 

	 => array('id'=>94, 'parent'=>false, 'div'=>'ME', 'name'=>'HEALTH SCIENCES', 'desc'=>'The science of diagnosing, treating, or preventing disease and other damage to the body or mind.'),
		=> 11, 17, 23, 101

	 => array('id'=>101, 'parent'=>false, 'div'=>'MI', 'name'=>'MICROBIOLOGY', 'desc'=>'The study of microorganisms, including bacteria, viruses, fungi, and pathogens.'),
		=> 23, 94, 17, 

	 => array('id'=>109, 'parent'=>false, 'div'=>'PH', 'name'=>'PHYSICS AND ASTRONOMY', 'desc'=>'Physics is the science of matter and energy and of interactions between the two. Astronomy is the study of anything in the universe beyond the Earth.'),
		=> 45, 

	 => array('id'=>121, 'parent'=>false, 'div'=>'PS', 'name'=>'PLANT SCIENCES', 'desc'=>'Study of plant life, including their structure and function, life history, growth, interactions with other plants and animals, classification, and evolution.'),
		=> 17, 80, 29

	);

*/

$isef_divs = array(
	 1 => array('id'=> 1, 'parent'=>false, 'div'=>'AS', 'name'=>'ANIMAL SCIENCES', 'desc'=>'Study of animals and animal life, including their structure, function, life history, interactions, classification, and evolution.'),

	 2=> array('id'=> 2, 'parent'=>'AS', 'div'=>'BEH', 'name'=>'Animal Behavior', 'desc'=>'The study of animal activities, on the level of the intact organism or its neurological components. This includes rhythmic functions, learning, and intelligence, sensory preferences, and environmental effects on behaviors.'),
	 3=> array('id'=> 3, 'parent'=>'AS', 'div'=>'DEV', 'name'=>'Development', 'desc'=>'The study of an organism from earliest stages through birth or hatching and into later life. This includes cellular and molecular aspects of development, regeneration, and environmental effects on development.'),
	 4=> array('id'=> 4, 'parent'=>'AS', 'div'=>'ECO', 'name'=>'Ecology', 'desc'=>'The science of the interactions and relationships among animals and animals and plants with their environments.'),
	 5=> array('id'=> 5, 'parent'=>'AS', 'div'=>'GENE', 'name'=>'Genetics', 'desc'=>'The study of organismic and population genetics.'),
	 6=> array('id'=> 6, 'parent'=>'AS', 'div'=>'NUTR', 'name'=>'Nutrition and Growth', 'desc'=>'The study of natural and artificial nutrients on animal growth and reproduction. This also includes the effects of biological and chemical control agents on reproduction and populations.'),
	 7=> array('id'=> 7, 'parent'=>'AS', 'div'=>'PATH', 'name'=>'Pathology', 'desc'=>'The study of disease states, and their causes, processes, and consequences. This includes effects of parasites or disease-causing microbes.'),
	 8=> array('id'=> 8, 'parent'=>'AS', 'div'=>'PHY', 'name'=>'Physiology', 'desc'=>'The study of functions in systems of animals, their mechanisms, and how they are affected by environmental factors or natural variations that select for particular genes.'),
	 9=> array('id'=> 9, 'parent'=>'AS', 'div'=>'SYST', 'name'=>'Systematics and Evolution', 'desc'=>'The study of classification of organisms and their evolutionary relationships. This includes morphological, biochemical, genetic, and modeled systems.'),
	 10=> array('id'=>10, 'parent'=>'AS', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),

	 11=> array('id'=>11, 'parent'=>false, 'div'=>'BE', 'name'=>'BEHAVIORAL AND SOCIAL SCIENCES', 'desc'=>'The science or study of the thought processes and behavior of humans and other animals in their interactions with the environment studied through observational and experimental methods.'),

	 12=> array('id'=>12, 'parent'=>'BE', 'div'=>'CLIN', 'name'=>'Clinical and Developmental Psychology', 'desc'=>'The study and treatment of emotional or behavioral disorders. Developmental psychology is concerned with the study of progressive behavioral changes in an individual from birth until death.'),
	 13=> array('id'=>13, 'parent'=>'BE', 'div'=>'COG', 'name'=>'Cognitive, Brain and Cognition, Neuro', 'desc'=>'psychology - The study of cognition, the mental processes that underlie behavior, including thinking, deciding, reasoning, and to some extent motivation and emotion. Neuro-psychology studies the relationship between the nervous system, especially the brain, and cerebral or mental functions such as language, memory, and perception.'),
	 14=> array('id'=>14, 'parent'=>'BE', 'div'=>'PHY', 'name'=>'Physiological Psychology', 'desc'=>'The study of the biological and physiological basis of behavior.'),
	 15=> array('id'=>15, 'parent'=>'BE', 'div'=>'SOC', 'name'=>'Sociology and Social Psychology; Industrial/Organizational Psychology', 'desc'=>'The study of human social behavior, especially the study of the origins, organization, institutions, and development of human society. Sociology is concerned with all group activities-economic, social, political, and religious.'),
	 16=> array('id'=>16, 'parent'=>'BE', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),

	 17=> array('id'=>17, 'parent'=>false, 'div'=>'BI', 'name'=>'BIOCHEMISTRY', 'desc'=>'The study of chemical substances, interactions, and processes relevant to living organisms.'),

	 18=> array('id'=>18, 'parent'=>'BI', 'div'=>'ANAL', 'name'=>'Analytical Biochemistry', 'desc'=>'The study of the separation, identification, and quantification of chemical components relevant to living organisms.'),
	 19=> array('id'=>19, 'parent'=>'BI', 'div'=>'GEN', 'name'=>'General Biochemistry', 'desc'=>'The study of chemical processes, including interactions and reactions, relevant to living organisms.'),
	 20=> array('id'=>20, 'parent'=>'BI', 'div'=>'MED', 'name'=>'Medicinal Biochemistry', 'desc'=>'The study of biochemical processes within the human body, with special reference to health and disease. '),
	 21=> array('id'=>21, 'parent'=>'BI', 'div'=>'STRU', 'name'=>'Structural Biochemistry', 'desc'=>'The study of the structure and or function of biological molecules.'),
	 22=> array('id'=>22, 'parent'=>'BI', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories. '),

	 23=> array('id'=>23, 'parent'=>false, 'div'=>'CB', 'name'=>'CELLULAR AND MOLECULAR BIOLOGY', 'desc'=>'The study of the structure and formation of cells.'),

	 24=> array('id'=>24, 'parent'=>'CB', 'div'=>'CELL', 'name'=>'Cellular Biology', 'desc'=>'The study of the organization and functioning of the individual cell.'),
	 25=> array('id'=>25, 'parent'=>'CB', 'div'=>'GENE', 'name'=>'Genetics', 'desc'=>'The study of molecular genetics focusing on the structure and function of genes at a molecular level.'),
	 26=> array('id'=>26, 'parent'=>'CB', 'div'=>'IMM', 'name'=>'Immunology', 'desc'=>'The study of the structure and function of the immune system, innate and acquired immunity, and laboratory techniques involving the interaction of antigens with antibodies.'),
	 27=> array('id'=>27, 'parent'=>'CB', 'div'=>'MOLE', 'name'=>'Molecular Biology', 'desc'=>'The study of biology at the molecular level. Chiefly concerns itself with understanding the interactions between the various systems of a cell, including the interrelationships of DNA, RNA and protein synthesis and learning how these interactions are regulated.'),
	 28=> array('id'=>28, 'parent'=>'CB', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),

	 29=> array('id'=>29, 'parent'=>false, 'div'=>'CH', 'name'=>'CHEMISTRY', 'desc'=>'The science of the composition, structure, properties, and reactions of matter.'),

	 30=> array('id'=>30, 'parent'=>'CH', 'div'=>'ANAL', 'name'=>'Analytical Chemistry', 'desc'=>'The study of the separation, identification, and quantification of the chemical components of materials. '),
	 31=> array('id'=>31, 'parent'=>'CH', 'div'=>'ENV', 'name'=>'Environmental Chemistry', 'desc'=>'The study of chemical species in the natural environment, including the effects of human activities, such as the design of products and processes that reduce or eliminate the use or generation of hazardous substances.'),
	 32=> array('id'=>32, 'parent'=>'CH', 'div'=>'INOR', 'name'=>'Inorganic Chemistry', 'desc'=>'The study of the properties and reactions of inorganic and organometallic compounds. '),
	 33=> array('id'=>33, 'parent'=>'CH', 'div'=>'MAT', 'name'=>'Materials Chemistry', 'desc'=>'The study of the design, synthesis and properties of substances, including condensed phases (solids, liquids, polymers) and interfaces, with a useful or potentially useful function, such as catalysis or solar energy. '),
	 34=> array('id'=>34, 'parent'=>'CH', 'div'=>'ORGA', 'name'=>'Organic Chemistry', 'desc'=>'The study of carbon-containing compounds, including hydrocarbons and their derivatives. '),
	 35=> array('id'=>35, 'parent'=>'CH', 'div'=>'PHY', 'name'=>'Physical Chemistry', 'desc'=>'The study of the fundamental physical basis of chemical systems and processes, including chemical kinetics, chemical thermodynamics, electrochemistry, photochemistry, spectroscopy, statistical mechanics and astro-chemistry.'),
	 36=> array('id'=>36, 'parent'=>'CH', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above subcategories, such as nuclear chemistry, surface chemistry and theoretical chemistry.'),

	 37=> array('id'=>37, 'parent'=>false, 'div'=>'CS', 'name'=>'COMPUTER SCIENCE', 'desc'=>'The study of information processes, the structures and procedures that represent processes, and their implementation in information processing systems. It includes systems analysis and design, application and system software design, programming, and datacenter operations.'),

	 38=> array('id'=>38, 'parent'=>'CS', 'div'=>'ALGO', 'name'=>'Algorithms, Data Bases', 'desc'=>'The study of algorithms and databases. Software developed to manage any form of data including text, images, sound and video.'),
	 39=> array('id'=>39, 'parent'=>'CS', 'div'=>'ARTI', 'name'=>'Artificial Intelligence', 'desc'=>'The study of the ability of a computer or other machine to perform those activities that are normally thought to require intelligence, such as solving problems, discriminating among objects, and/or responding to voice commands. This also includes speech analysis and synthesis.'),
	 40=> array('id'=>40, 'parent'=>'CS', 'div'=>'NET', 'name'=>'Networking and Communications', 'desc'=>'The study of systems that transmits any combination of voice, video, and/or data among users.'),
	 41=> array('id'=>41, 'parent'=>'CS', 'div'=>'SCIE', 'name'=>'Computational Science, Computer Graphics', 'desc'=>'The study of the use of computers to perform research in other fields, such as computer simulations. Also includes the study of computer graphics or the transfer of pictorial data into and out of a computer by various means (analog-to-digital, optical scanning, etc), such as in computer image processing.'),
	 42=> array('id'=>42, 'parent'=>'CS', 'div'=>'SOFT', 'name'=>'Software Engineering, Programming Languages', 'desc'=>'The study of software designed to control the hardware of a specific data processing system in order to allow users and application programs to make use of it. This sub-category includes web technologies, programming languages and human-computer interactions.'),
	 43=> array('id'=>43, 'parent'=>'CS', 'div'=>'SYST', 'name'=>'Computer System, Operating System', 'desc'=>'The study of system software responsible for the direct control and management of hardware and basic system operations of a computer.'),
	 44=> array('id'=>44, 'parent'=>'CS', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),

	 45=> array('id'=>45, 'parent'=>false, 'div'=>'EA', 'name'=>'EARTH AND PLANETARY SCIENCE', 'desc'=>'The study of sciences related to the planet Earth (Geology, minerology, physiography, oceanography, meteorology, climatology, speleology, sesismology, geography, atmospheric sciences, etc.)'),
	 46=> array('id'=>46, 'parent'=>'EA', 'div'=>'CLIM', 'name'=>'Climatology, Meteorology, Weather', 'desc'=>'the scientific study of the atmosphere that focuses on weather processes and forecasting.'),
	 47=> array('id'=>47, 'parent'=>'EA', 'div'=>'GEO', 'name'=>'Geochemistry, Mineralogy', 'desc'=>'The study of the chemical composition of the earth and other planets, chemical processes and reactions that govern the composition of rocks and soils. Mineralogy is focused around the chemistry, crystal structure and physical (including optical) properties of minerals.'),
	 48=> array('id'=>48, 'parent'=>'EA', 'div'=>'HIST', 'name'=>'Historical Paleontology', 'desc'=>'The study of life in the geologic past as recorded by fossil remains.'),
	 49=> array('id'=>49, 'parent'=>'EA', 'div'=>'PHY', 'name'=>'Geophysics', 'desc'=>'Branch of geology in which the principles and practices of physics are used to study the earth and its environment.'),
	 50=> array('id'=>50, 'parent'=>'EA', 'div'=>'PLAN', 'name'=>'Planetary Science', 'desc'=>'The study of planets or planetary systems and the solar system.'),
	 51=> array('id'=>51, 'parent'=>'EA', 'div'=>'TECH', 'name'=>'Tectonics', 'desc'=>'The study of the earth\'s structural features as related to plate structure, plate movement, volcanism, etc.'),
	 52=> array('id'=>52, 'parent'=>'EA', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),

	53=> array('id'=>53, 'parent'=>false, 'div'=>'EE', 'name'=>'ENGINEERING: Electrical and Mechanical', 'desc'=>'The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical structures, processes, and systems.'),
	 54=> array('id'=>54, 'parent'=>'EE', 'div'=>'ELEC', 'name'=>'Electrical Engineering, Computer Engineering, Controls', 'desc'=>'Electrical engineering is the branch of engineering that deals with the technology of electricity, especially the design and application of circuitry and equipment for power generation and distribution, machine control, and communications. A computer engineer is an electrical engineer with a focus on digital logic systems or a software architect with a focus on the interaction between software programs and the underlying hardware architecture.'),
	 55=> array('id'=>55, 'parent'=>'EE', 'div'=>'MECH', 'name'=>'Mechanical Engineering', 'desc'=>'The branch of engineering that encompasses the generation and application of heat and mechanical power and the design, production, and use of machines and tools.'),
	 56=> array('id'=>56, 'parent'=>'EE', 'div'=>'ROB', 'name'=>'Robotics', 'desc'=>'The science or study of the technology associated with the design, fabrication, theory, and application of robots and of general purpose, programmable machine systems.'),
	 57=> array('id'=>57, 'parent'=>'EE', 'div'=>'THRM', 'name'=>'Thermodynamics, Solar', 'desc'=>'Thermodynamics involves the physics of the relationships and conversions between heat and other forms of energy. Solar is the technology of obtaining usable energy from the light of the sun.'),
	 58=> array('id'=>58, 'parent'=>'EE', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	59=> array('id'=>59, 'parent'=>false, 'div'=>'EN', 'name'=>'ENGINEERING: Materials and Bioengineering', 'desc'=>'The application of scientific and mathematical principles to practical ends such as the design, manufacture, and operation of efficient and economical machines and systems.'),
	 60=> array('id'=>60, 'parent'=>'EN', 'div'=>'BIO', 'name'=>'Bioengineering', 'desc'=>'Involves the application of engineering principles to the fields of biology and medicine, as in the development of aids or replacements for defective or missing body organs; the development and manufacture of prostheses, medical devices, diagnostic devices, drugs and other therapies as well as the application of engineering principles to basic biological science problems.'),
	 61=> array('id'=>61, 'parent'=>'EN', 'div'=>'CHEM', 'name'=>'Chemical Engineering', 'desc'=>'Deals with the design, construction, and operation of plants and machinery for making such products as acids, dyes, drugs, plastics, and synthetic rubber by adapting the chemical reactions discovered by the laboratory chemist to large-scale production.'),
	 62=> array('id'=>62, 'parent'=>'EN', 'div'=>'CIVI', 'name'=>'Civil Engineering, Construction Engineering', 'desc'=>'Includes the planning, designing, construction, and maintenance of structures and public works, such as bridges or dams, roads, water supply, sewer, flood control and, traffic.'),
	 63=> array('id'=>63, 'parent'=>'EN', 'div'=>'IND', 'name'=>'Industrial Engineering, Processing', 'desc'=>'Concerned with efficient production of industrial goods as affected by elements such as plant and procedural design, the management of materials and energy, and the integration of workers within the overall system. The industrial engineer designs methods, not machinery.'),
	 64=> array('id'=>64, 'parent'=>'EN', 'div'=>'MAT', 'name'=>'Material Science', 'desc'=>'A multidisciplinary field relating the performance and function of matter in any and all applications to its micro, nano, and atomic structure, and vice versa. It often involves the study of the characteristics and uses of various materials, such as metals, ceramics, and plastics and their potential engineering applications.'),
	 65=> array('id'=>65, 'parent'=>'EN', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	66=> array('id'=>66, 'parent'=>false, 'div'=>'ET', 'name'=>'ENERGY & TRANSPORTATION', 'desc'=>'The study of renewable energy sources, energy efficiency, clean transport, and alternative fuels.'),
	 67=> array('id'=>67, 'parent'=>'ET', 'div'=>'AERO', 'name'=>'Aerospace and Aeronautical Engineering, Aerodynamics', 'desc'=>'The design of aircraft and space vehicles and the direction of the technical phases of their manufacture and operation.'),
	 68=> array('id'=>68, 'parent'=>'ET', 'div'=>'ALT', 'name'=>'Alternative Fuels', 'desc'=>'Any method of powering an engine that does not involve petroleum (oil). Some alternative fuels are electricity, hythane, hydrogen, natural gas, and wood.'),
	 69=> array('id'=>69, 'parent'=>'ET', 'div'=>'FOS', 'name'=>'Fossil Fuel Energy', 'desc'=>'Energy from a hydrocarbon deposit, such as petroleum, coal, or natural gas, derived from living matter of a previous geologic time and used for fuel.'),
	 70=> array('id'=>70, 'parent'=>'ET', 'div'=>'VEH', 'name'=>'Vehicle Development', 'desc'=>'Engineering of vehicles that operate using energy other than from fossil fuel.'),
	 71=> array('id'=>71, 'parent'=>'ET', 'div'=>'REN', 'name'=>'Renewable Energies', 'desc'=>'Renewable energy sources capture their energy from existing flows of energy, from on-going natural processes such as sunshine, wind, flowing water, biological processes, and geothermal heat flows.'),
	 72=> array('id'=>72, 'parent'=>'ET', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	73=> array('id'=>73, 'parent'=>false, 'div'=>'EM', 'name'=>'ENVIRONMENTAL MANAGEMENT', 'desc'=>'The application of engineering principals to solve practical problems of managing mans\' interaction with the environment with the goal to maintain and improve the state of an environmental resource affected by human activities.'),
	 74=> array('id'=>74, 'parent'=>'EM', 'div'=>'BIO', 'name'=>'Bioremediation', 'desc'=>'The use of biological agents, such as bacteria or plants, to remove or neutralize contaminants, as in polluted soil or water. Includes phytoremediation, constructed wetlands for wastewater treatment, biodegradation, etc.'),
	 75=> array('id'=>75, 'parent'=>'EM', 'div'=>'ECO', 'name'=>'Ecosystems Management', 'desc'=>'The integration of ecological, economic, and social principles to manage biological and physical systems in a manner that safeguards the long-term ecological sustainability, natural diversity, and productivity of the landscape. An ecological approach to managing the environment.'),
	 76=> array('id'=>76, 'parent'=>'EM', 'div'=>'ENG', 'name'=>'Environmental Engineering', 'desc'=>'The application of engineering principals to solve practical problems in the supply of water, the disposal of waste, and the control of pollution. Includes alternative engineering methodologies to meet society\'s needs in an environmentally sound and sustainable manner. Preservation of the environment by preventing the contamination of, and facilitating the clean up of, air, water, and land resources.'),
	 77=> array('id'=>77, 'parent'=>'EM', 'div'=>'LAND', 'name'=>'Land Resource Management and Forestry', 'desc'=>'A landscape approach to sustainable resource management, coastal management, biological diversity management, land use planning, or forest succession management. It often includes a resource planning component as well as implementation methodologies. An example would be the management of longleaf pine forests including controlled burns to imitate natural processes.'),
	 78=> array('id'=>78, 'parent'=>'EM', 'div'=>'REC', 'name'=>'Recycling and Waste Management', 'desc'=>'The extraction and reuse of useful substances from discarded items, garbage, or waste. The process of managing, and disposing of, wastes and hazardous substances through methodologies such as landfills, sewage treatment, composting, waste reduction, etc.'),
	 79=> array('id'=>79, 'parent'=>'EM', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	80=> array('id'=>80, 'parent'=>false, 'div'=>'EV', 'name'=>'ENVIRONMENTAL SCIENCES', 'desc'=>'The analysis of existing conditions of the environment.'),
	 81=> array('id'=>81, 'parent'=>'EV', 'div'=>'AIR', 'name'=>'Air Pollution and Air Quality', 'desc'=>'The study of contamination of the air by such things as noxious gases, elements, minerals, chemicals, solid and liquid matter (particulates), etc. Air pollution is the study of such contaminates in concentrations that endanger the health of humans, plants, and/or animals.'),
	 82=> array('id'=>82, 'parent'=>'EV', 'div'=>'SOIL', 'name'=>'Soil Contamination and Soil Quality', 'desc'=>'The study of contamination of the soil by such things as noxious elements, minerals, chemicals, solids, liquids, etc. Soil contamination is the study of such contaminates in concentrations that endanger the health of humans, plants, and/or animals.'),
	 83=> array('id'=>83, 'parent'=>'EV', 'div'=>'WATE', 'name'=>'Water Pollution and Water Quality', 'desc'=>'The study of contamination of the water by such things as noxious elements, minerals, chemicals, solids, etc. Water pollution is the study of such contaminates in concentrations that endanger the health of humans, plants, and/or animals.'),
	 84=> array('id'=>84, 'parent'=>'EV', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	85=> array('id'=>85, 'parent'=>false, 'div'=>'MA', 'name'=>'MATHEMATICAL SCIENCES', 'desc'=>'The study of the measurement, properties, and relationships of quantities and sets, using numbers and symbols. The deductive study of numbers, geometry, and various abstract constructs, or structures.'),
	 86=> array('id'=>86, 'parent'=>'MA', 'div'=>'ALG', 'name'=>'Algebra', 'desc'=>'The study of algebraic operations and/or relations and the structures which arise from them. An example is given by (systems of) equations which involve polynomial functions of one or more variables. '),
	 87=> array('id'=>87, 'parent'=>'MA', 'div'=>'ANAL', 'name'=>'Analysis', 'desc'=>'The study of infinitesimal processes in mathematics, typically involving the concept of a limit. This begins with differential and integral calculus, for functions of one or several variables, and includes differential equations. '),
	 88=> array('id'=>88, 'parent'=>'MA', 'div'=>'COMP', 'name'=>'Computer Mathematics', 'desc'=>'Branch of mathematics that concerns itself with the mathematical techniques typically used in the application of mathematical knowledge to other domains. Not every project that uses some mathematics belongs here; this category is for projects where the mathematics is the primary component.  '),
	 89=> array('id'=>89, 'parent'=>'MA', 'div'=>'COMB', 'name'=>'Combinatorics, Graph Theory and Game Theory', 'desc'=>'The study of combinatorial structures in mathematics, such as finite sets, graphs, and games, often with a view toward classification and/or enumeration.'),
	 90=> array('id'=>90, 'parent'=>'MA', 'div'=>'GEO', 'name'=>'Geometry and Topology', 'desc'=>'The study of the shape, size, and other properties of figures and spaces. Includes such subjects as Euclidean geometry, non-Euclidean geometries (spherical, hyperbolic, Riemannian, Lorentzian), and knot theory (classification of knots in 3-space).'),
	 91=> array('id'=>91, 'parent'=>'MA', 'div'=>'NUM', 'name'=>'Number Theory', 'desc'=>'The study of the arithmetic properties of integers and related topics such as cryptography.'),
	 92=> array('id'=>92, 'parent'=>'MA', 'div'=>'PROB', 'name'=>'Probability and Statistics', 'desc'=>'Mathematical study of random phenomena and the study of statistical tools used to analyze and interpret data.'),
	 93=> array('id'=>93, 'parent'=>'MA', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	94=> array('id'=>94, 'parent'=>false, 'div'=>'ME', 'name'=>'HEALTH SCIENCES', 'desc'=>'The science of diagnosing, treating, or preventing disease and other damage to the body or mind.'),
	 95=> array('id'=>95, 'parent'=>'ME', 'div'=>'DIS', 'name'=>'Disease Diagnosis and Treatment', 'desc'=>'The act or process of identifying or determining the nature and cause of a disease or injury through evaluation of patient history, examination, and review of laboratory data. Administration or application of remedies to a patient or for a disease or injury; medicinal or surgical management; therapy.'),
	 96=> array('id'=>96, 'parent'=>'ME', 'div'=>'EPID', 'name'=>'Epidemiology', 'desc'=>'The study of the causes, distribution, and control of disease in populations. Epidemiologists, using sophisticated statistical analyses, field investigations, and complex laboratory techniques, investigate the cause of a disease, its distribution (geographic, ecological, and ethnic), method of spread, and measures for control and prevention.'),
	 97=> array('id'=>97, 'parent'=>'ME', 'div'=>'GENE', 'name'=>'Genetics', 'desc'=>'The study of heredity, especially the mechanisms of hereditary transmission and the variation of inherited traits among similar or related organisms.'),
	 98=> array('id'=>98, 'parent'=>'ME', 'div'=>'MOLE', 'name'=>'Molecular Biology of Diseases', 'desc'=>'The study of diseases at the molecular level.'),
	 99=> array('id'=>99, 'parent'=>'ME', 'div'=>'PHYS', 'name'=>'Physiology and Pathophysiology', 'desc'=>'The science of the mechanical, physical, and biochemical functions of normal tissues or organs. Pathophysiology is the study of the disturbance of normal mechanical, physical, and biochemical functions that a disease causes, or that which causes the disease.'),
	 100=> array('id'=>100, 'parent'=>'ME', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories.'),
	
	101=> array('id'=>101, 'parent'=>false, 'div'=>'MI', 'name'=>'MICROBIOLOGY', 'desc'=>'The study of microorganisms, including bacteria, viruses, fungi, and pathogens.'),
	 102=> array('id'=>102, 'parent'=>'MI', 'div'=>'ANTI', 'name'=>'Antimicrobial Agents', 'desc'=>'The study of substances that kill or inhibit the growth of microorganisms.'),
	 103=> array('id'=>103, 'parent'=>'MI', 'div'=>'APP', 'name'=>'Applied Microbiology', 'desc'=>'The study of microorganisms having potential applications in human, animal or plant health or energy production.'),
	 104=> array('id'=>104, 'parent'=>'MI', 'div'=>'BACT', 'name'=>'Bacterial Microbiology', 'desc'=>'The study of bacteria and bacterial diseases.'),
	 105=> array('id'=>105, 'parent'=>'MI', 'div'=>'ENV', 'name'=>'Environmental Microbiology', 'desc'=>'The study of the structure, function, diversity and relationship of microorganisms with respect to their environment.'),
	 106=> array('id'=>106, 'parent'=>'MI', 'div'=>'GENE', 'name'=>'Microbial Genetics', 'desc'=>'The study of how genes are organized and regulated in microorganisms in relation to their cellular function.'),
	 107=> array('id'=>107, 'parent'=>'MI', 'div'=>'VIRO', 'name'=>'Virology', 'desc'=>'The study the anatomy, physiology of viruses and the diseases they cause.'),
	 108=> array('id'=>108, 'parent'=>'MI', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories, such as  microbial cytology, physiology and pathogenesis.'),
	
	109=> array('id'=>109, 'parent'=>false, 'div'=>'PH', 'name'=>'PHYSICS AND ASTRONOMY', 'desc'=>'Physics is the science of matter and energy and of interactions between the two. Astronomy is the study of anything in the universe beyond the Earth.'),
	 110=> array('id'=>110, 'parent'=>'PH', 'div'=>'AMO', 'name'=>'Atomic Molecular and Optical Physics', 'desc'=>'The study of atoms, simple molecules, electrons and light, and their interactions.'),
	 111=> array('id'=>111, 'parent'=>'PH', 'div'=>'ASTR', 'name'=>'Astronomy and Cosmology', 'desc'=>'The study of space,  the universe as a whole, including its origins and evolution, the physical properties of objects in space and computational astronomy'),
	 112=> array('id'=>112, 'parent'=>'PH', 'div'=>'BIO', 'name'=>'Biological Physics', 'desc'=>'The study of the physics of biological processes.'),
	 113=> array('id'=>113, 'parent'=>'PH', 'div'=>'INST', 'name'=>'Instrumentation and Electronics', 'desc'=>'Instrumentation is the process of developing means of precise measurement of various variables such as flow and pressure while maintaining control of the variables at desired levels of safety and economy. Electronics is the branch of physics that deals with the emission and effects of electrons and with the use of electronic devices.'),
	 114=> array('id'=>114, 'parent'=>'PH', 'div'=>'MAT', 'name'=>'Condensed Matter and Materials', 'desc'=>'The study of the preparation, properties and performance of materials to help understand and optimize their behavior. Topics such as superconductivity, semi-conductors, complex fluids, and thin films are studied.'),
	 115=> array('id'=>115, 'parent'=>'PH', 'div'=>'MAG', 'name'=>'Magnetics, Electromagnetics and Plasmas', 'desc'=>'The study of electrical and magnetic fields and of matter in the plasma phase and their effects on materials in the solid, liquid or gaseous states.'),
	 116=> array('id'=>116, 'parent'=>'PH', 'div'=>'MECH', 'name'=>'Mechanics', 'desc'=>'Classical physics and mechanics, including the macroscopic study of forces, vibrations and flows; on solid, liquid and gaseous materials'),
	 117=> array('id'=>117, 'parent'=>'PH', 'div'=>'NUCL', 'name'=>'Nuclear and Particle Physics', 'desc'=>'The study of the physical properties of the atomic nucleus and of fundamental particles and the forces of their interaction'),
	 118=> array('id'=>118, 'parent'=>'PH', 'div'=>'OPT', 'name'=>'Optics, Lasers, and Masers', 'desc'=>'The study of the physical properties of light, lasers and masers.'),
	 119=> array('id'=>119, 'parent'=>'PH', 'div'=>'THEO', 'name'=>'Theoretical Physics, Theoretical or Computational Astronomy', 'desc'=>'The study of nature, phenomena and the laws of physics employing mathematical models and abstractions rather than experimental processes. '),
	 120=> array('id'=>120, 'parent'=>'PH', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories. '),
	
	121=> array('id'=>121, 'parent'=>false, 'div'=>'PS', 'name'=>'PLANT SCIENCES', 'desc'=>'Study of plant life, including their structure and function, life history, growth, interactions with other plants and animals, classification, and evolution.'),
	 122=> array('id'=>122, 'parent'=>'PS', 'div'=>'AGR', 'name'=>'Agronomy', 'desc'=>'The application of the various soil and plant sciences to soil management and agricultural and horticultural crop production. Includes biological and chemical controls of pests, hydroponics, fertilizers and supplements.'),
	 123=> array('id'=>123, 'parent'=>'PS', 'div'=>'DEV', 'name'=>'Development and Growth', 'desc'=>'The study of a plant from earliest stages through germination and into later life. This includes cellular and molecular aspects of development and environmental effects, natural or manmade, on development and growth.'),
	 124=> array('id'=>124, 'parent'=>'PS', 'div'=>'ECO', 'name'=>'Ecology', 'desc'=>'The study of interactions and relationships among plants, and plants and animals, with their environment.'),
	 125=> array('id'=>125, 'parent'=>'PS', 'div'=>'GEN', 'name'=>'Genetics/Breeding', 'desc'=>'The study of organismic and population genetics of plants. The application of plant genetics and biotechnology to crop improvement.'),
	 126=> array('id'=>126, 'parent'=>'PS', 'div'=>'PATH', 'name'=>'Pathology', 'desc'=>'The study of plant disease states, and their causes, processes, and consequences. This includes effects of parasites or disease-causing microbes.'),
	 127=> array('id'=>127, 'parent'=>'PS', 'div'=>'PHY', 'name'=>'Plant Physiology', 'desc'=>'The study of functions of plants, their mechanisms, and how they are affected by environmental factors or natural variations. This includes all aspects of photosynthesis.'),
	 128=> array('id'=>128, 'parent'=>'PS', 'div'=>'SYST', 'name'=>'Systematics and Evolution', 'desc'=>'The study of classification of organisms and their evolutionary relationships. This includes morphological, biochemical, genetic, and modeled systems.'),
	 129=> array('id'=>129, 'parent'=>'PS', 'div'=>'OTHR', 'name'=>'Other', 'desc'=>'Studies that cannot be assigned to one of the above categories, such as the effects of plants or plant-derived substances on animal and human health.'),
	);

function isef_get_div_names()
{
	global $isef_divs;

	$ret = array();

	foreach($isef_divs as $id=>$d) {
		if($d['parent'] === false) {
			$curr_parent = $d['div'];
			$curr_parent_name = $d['name'];
			$ret[$d['name']] = array();
			continue;
		}

		if($curr_parent != $d['parent']) {
			print("Error 1006: $curr_parent {$d['div']}");
			exit();
		}

		$id = $d['id'];

		$ret[$curr_parent_name][$id] = $curr_parent_name." - ".$d['name'];
	}
	return $ret;
}

function isef_get_major_div_names()
{
	global $isef_divs;

	$ret = array();

	$ret["Detailed Divisions"] = array();
	foreach($isef_divs as $id=>$d) {
		if($d['parent'] === false) {
			$ret["Detailed Divisions"][$id] = $d['name'];
		}
	}
	return $ret;

}


?>

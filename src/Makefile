# Generated automatically from Makefile.in by configure.


all: sfiab_annealer

# Generated automatically from Makefile.rules.in by configure.
CC	= gcc
CFLAGS	= -Wall -g  `pkg-config --cflags glib-2.0`  -I/usr/include/mysql
LIBS	= -lpthread  -L/usr/lib `pkg-config --libs glib-2.0`  -L/usr/lib64/mysql -lmysqlclient -lcrypt -lm
LD_R	= ld -r
LD	= ld
RM	= rm -f
INSTALL = ginstall

INCLUDE  = 
 
CFLAGS += $(LOCAL_CFLAGS) $(INCLUDE)

LDFLAGS = $(LIBS)   -Wl,-export-dynamic

OBJS = $(C_SRCS:.c=.o)

CLEAN_OBJS = $(OBJS) $(LOCAL_OBJS)

.c.o:
	$(CC) $(CFLAGS) -c $<

subdirs-%: force
	set -e; s='$(SUBDIRS)'; for i in $$s; do $(MAKE) -C $$i $*; done;

install: subdirs-install
uninstall: subdirs-uninstall
clean: subdirs-clean clean-default
distclean: subdirs-distclean distclean-default

clean-default:
	$(RM) $(CLEAN_OBJS) $(BUILD)

distclean-default:
	$(RM) $(CLEAN_OBJS) $(BUILD) Makefile
	

force: ;

C_SRCS= anneal.c\
	db.c \
	judges.c \
	main.c\
	sfiab.c\
	students.c\
	tours.c

H_FILES=anneal.h\
	db.h\
	judges.h\
	sfiab.h\
	students.h\
	tours.h



sfiab_annealer: $(C_SRCS) $(H_FILES)
	gcc -o sfiab_annealer $(C_SRCS) $(CFLAGS) $(LIBS)
	
distclean:
	$(RM) sfiab_annealer

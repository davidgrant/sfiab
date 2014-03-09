# Generated automatically from Makefile.in by configure.

TOPSRCDIR	= .
SUBDIRS		= tracer parser server inject 
BUILD		= lightboxd2 lightboxd_inject
C_SRCS		=


all: sfiab_annealer

# Generated automatically from Makefile.rules.in by configure.
CC	= gcc
CFLAGS	= -Wall -g -O2 `pkg-config --cflags glib-2.0`  -I/usr/include/mysql
LIBS	= -lpthread  -L/usr/lib `pkg-config --libs glib-2.0`  -L/usr/lib64/mysql -lmysqlclient -lcrypt -lm
LD_R	= ld -r
LD	= ld
RM	= rm -f
INSTALL = ginstall

prefix          = /usr/local
exec_prefix     = ${prefix}
bindir          = ${exec_prefix}/bin
libdir          = ${exec_prefix}/lib
infodir         = ${prefix}/info
mandir          = ${prefix}/man

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


sfiab_annealer: main.c db.c db.h anneal.c anneal.h
	gcc -o sfiab_annealer main.c anneal.c db.c $(CFLAGS) $(LIBS)
	


distclean:
	$(RM) config.log config.cache configure Makefile.rules config.h

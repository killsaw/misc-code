#include <stdio.h>
#include <string.h>
#include <time.h>
#include <stdlib.h> 
#include <sys/param.h>

#define NOTE_DIR ".notes/"
#define MAX_LINE_SIZE 255

typedef struct note {
	char **blurbs;
	time_t timeStarted;
	time_t timeEnded;
	int blurbCount;
	
} note;

void init_note(note *n)
{
	n->blurbCount = 0;
	n->blurbs = malloc(sizeof(char *)*100);
	n->timeStarted = time(NULL);
}

void destroy_note(note *n)
{
	int i;
	for(i=0; i < n->blurbCount; i++) {
		free(n->blurbs[i]);
	}
	free(n->blurbs);
}

void save_note(note *n)
{
	FILE *fp;
	int i;
	int path_length = 0, full_path_length = 0;
	struct tm *tm;
	char *home_dir;
	char save_file[30], 
		*save_dir,
		*save_path;

	n->timeEnded = time(NULL);	
	tm = localtime(&n->timeEnded);
	
	// Build our filename.
	snprintf(save_file, sizeof(save_file)-1,
		"note.%d-%d_%d-%d.%d.txt", 
		tm->tm_mon, 1900+tm->tm_year, 
		tm->tm_hour, tm->tm_min, 
		tm->tm_sec);
	
	if (NOTE_DIR[0] != '/') {
		home_dir = getenv("HOME");
		path_length = strlen(home_dir)+strlen(NOTE_DIR)+1;
		save_dir = malloc(sizeof(char)*(path_length+1));
		snprintf(save_dir, path_length, "%s/%s", 
			home_dir, NOTE_DIR);
	} else {
		path_length = strlen(NOTE_DIR);
		save_dir = strdup(NOTE_DIR);
	}
	
	// make sure we have our end slash.
	if (save_dir[strlen(save_dir)-1] != '/') {
		strcat(save_dir, "/");
	}
	full_path_length = strlen(save_dir)+strlen(save_file)+1;
	save_path = malloc(sizeof(char)*full_path_length);
	snprintf(save_path, full_path_length, "%s%s", save_dir, save_file);
	
	if ((fp = fopen(save_path, "w+")) != NULL) {
		for(i=0; i < n->blurbCount; i++) {
			fwrite(n->blurbs[i], sizeof(char), 
				   strlen(n->blurbs[i]), fp);
		}
		fclose(fp);
	}
	free(save_path);
	free(save_dir);
}

void add_note_blurb(note *n, char *blurb)
{
	if (n->blurbCount + 1 > 100) {
		// Uh oh.
		printf("Exceeded max blurb count.\n");
		exit(1);
	}
	n->blurbs[n->blurbCount++] = strdup(blurb);
}

void print_note(note *n)
{
	int i;
	for(i=0; i < n->blurbCount; i++) {
		printf("%s", n->blurbs[i]);
	}
}

int main(int argc, char **argv)
{
	note n;
	char line[MAX_LINE_SIZE];
	
	init_note(&n);
	bzero(line, sizeof(line));	
	
	while (!feof(stdin)) {
		fgets(line, sizeof(line)-1, stdin);
		
		if (strncmp(line, ".\n", 2) == 0) {
			goto finish_save;
		}
		add_note_blurb(&n, line);
		bzero(line, sizeof(line));
	}
	
	finish_save:
		save_note(&n);
		destroy_note(&n);
	
	return 0;
}
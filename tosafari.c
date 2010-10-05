#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/file.h>
#include <unistd.h>

#define OPEN_CMD     "/usr/bin/open -a Safari.app "
#define TMP_PREFIX   "/private/tmp/2sfri_tmp"
#define BUFFER_SIZE   1024
#define MAX_KEEP_TIME 5

char *get_tmp_path(void);
void  save_input_to_tmp(char *tmp_path);
void  open_input_file(char *tmp_path);
int   file_exists(char *path);

int main(int argc, char *argv[])
{
	char *tmp_path;	
	
	// Find an untaken tmp path.
	tmp_path = get_tmp_path();
	
	// Save STDIN input to that tmp file.
	save_input_to_tmp(tmp_path);
	
	// Open tmp file in Safari.
	open_input_file(tmp_path);
	
	// Cleanup and exit.
	free(tmp_path);
	return 0;
}

// Uses Mac OS X's `open` command to pop open tmp file.
void open_input_file(char *tmp_path)
{
	int   i;
	int   bytes_read=0;
	int   max_size = 0;
	char *open_cmd = NULL;
	
	// Get size of final command.
	max_size = strlen(OPEN_CMD) + strlen(tmp_path);
	
	// Construct command.
	open_cmd = malloc(sizeof(char)*max_size);
	strcat(open_cmd, OPEN_CMD);
	strncat(open_cmd, tmp_path, max_size-strlen(OPEN_CMD));
	
	// Run it and clean up.
	system(open_cmd);
	free(open_cmd);
	
	if (MAX_KEEP_TIME > -1) {
		printf("Deleting temp file in %d seconds.\n", MAX_KEEP_TIME);
		sleep(MAX_KEEP_TIME);
		unlink(tmp_path);
	}
}

// Reads from STDIN, writes read content to tmp file.
void save_input_to_tmp(char *tmp_path)
{
	FILE *tmp_fp;
	int bytes_read = 0;
	char buffer[BUFFER_SIZE];

	if ((tmp_fp = fopen(tmp_path, "w+")) == NULL) {
		fprintf(stderr, "Failed to open tmp path for writing.\n");
		exit(1);
	}
	flock(fileno(tmp_fp), LOCK_EX);
	
	// Read from stdin, write to tmp file. 
	while(!feof(stdin)) {
		bytes_read = fread(buffer, sizeof(char), sizeof(buffer)-1, stdin);
		fwrite(buffer, sizeof(char), bytes_read, tmp_fp);
	}
	flock(fileno(tmp_fp), LOCK_UN);
	fclose(tmp_fp);
}

// Returns string path for new temporary file. Makes certain
// not to use a previously used path.
char *get_tmp_path(void)
{
	int i = 1;
	char *tmp_path;
	
	// +10 is for appending numbers in next step.
	tmp_path = malloc(sizeof(char)*(strlen(TMP_PREFIX)+10));

	do {
		sprintf(tmp_path, "%s%d.html", TMP_PREFIX, i++);
		
	} while(file_exists(tmp_path));
	
	return tmp_path;
}

// Returns 1 if file exists, 0 if not.
int file_exists(char *path)
{
	FILE *fp = fopen(path, "r");
	
	if (fp == NULL) {
		return 0;
	} else {
		fclose(fp);
		return 1;
	}
}
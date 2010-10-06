/**
* macsayd.c
* TCP server for providing access to Mac speech service
* over a network. And hijinx ensued.
* 
* Author: Steven Bredenberg
*         <steven@killsaw.com>
*
*/
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <netdb.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <ctype.h>

#define BUFSIZE 1024
#define SERVER_PORT 1337

void say(char *text, int voice);
void server_runloop(int portno);
void remove_bad_chars(char *text);
int send_reply(int childfd, char *reply);

char *voices[] = {"Alex", "Bruce", "Fred", "Junior", "Ralph", "Agnes", "Katy",
				 "Princess", "Vicki", "Victoria", "Albert", "Bad News", "Bahh",
				 "Bells", "Boing", "Bubbles", "Cellos", "Deranged", "Good News",
				 "Hysterical", "Pipe Organ", "Trinoids", "Whisper", "Zarvox",
				 NULL};

int main(int argc, char **argv)
{	
    server_runloop(SERVER_PORT);
    return 0;
}

void server_runloop(int portno)
{
    struct sockaddr_in server_addr;
    struct sockaddr_in client_addr;

    char buf[BUFSIZE];
    char line[50];

    int parentfd;
    int childfd;
    int clientlen;
    char *hostaddrp;
    int optval;
    int n, i;
    int voice=1, 
    	startpos=0;

    parentfd = socket(AF_INET, SOCK_STREAM, 0);
    if (parentfd < 0) {
        perror("socket");
        exit(1);
    }
    optval = 1;
    setsockopt(parentfd, SOL_SOCKET, SO_REUSEADDR,
               (const void *)&optval , sizeof(int));
    bzero((char *) &server_addr, sizeof(server_addr));
    server_addr.sin_family = AF_INET;
    server_addr.sin_addr.s_addr = htonl(INADDR_ANY);
    server_addr.sin_port = htons((unsigned short)portno);

    if (bind(parentfd, (struct sockaddr *) &server_addr,
             sizeof(server_addr)) < 0) {
        perror("bind");
        exit(1);
    }

    if (listen(parentfd, 5) < 0) {
        perror("listen");
        exit(1);
    }
    printf("Server listening on port %d...\n", portno);

    clientlen = sizeof(client_addr);
    while (1) {
        childfd = accept(parentfd, (struct sockaddr *) &client_addr, (socklen_t *)&clientlen);
        if (childfd < 0) {
            perror("accept");
            exit(1);
        }

        hostaddrp = inet_ntoa(client_addr.sin_addr);
        if (hostaddrp == NULL) {
            perror("inet_ntoa");
            exit(1);
        }
        printf("%s connected.\n", hostaddrp);

        bzero(buf, BUFSIZE);
        n = read(childfd, buf, BUFSIZE);
        if (n < 0) {
            perror("read");
            exit(1);
        }
        buf[strlen(buf)-1] = '\0';
        
        // Simple help.
        if (strncmp(buf, ":help", 5) == 0) {
        	send_reply(childfd, "Voices: \n");
        	for(i=0; voices[i] != NULL; i++) {
        		sprintf(line, "   %d - %s\n", i+1, voices[i]);
        		send_reply(childfd, line);
        	}
        	
        } else {
			// Pickup voice option, if present.        
			if (buf[0] == ':' && isdigit(buf[1])) {
				sscanf(buf, ":%d", &voice);
				if (isdigit(buf[2])) {
					startpos += 4;
				} else {
					startpos += 3;
				}
			}
        	send_reply(childfd, "Got it, thanks\n");
			if (n < 0) {
				perror("write");
				exit(1);
			}
	        say(buf+startpos, voice);
		}
        close(childfd);
        startpos=0;
    }
}

int send_reply(int childfd, char *reply)
{
	return write(childfd, reply, strlen(reply));
}

void say(char *text, int voice)
{
    char *cmd;
    char *voice_name;
    int cmd_size;
    int i;
    
    voice_name = voices[0];
    
    // Find voice to use.
    for(i=0; voices[i] != NULL; i++) {
    	if ((i+1) == voice) {
    		voice_name = voices[i];
    		break;
    	}
    }
    remove_bad_chars(text);

    cmd_size = strlen(text) + 100;
    cmd = malloc(sizeof(char)*cmd_size);

    snprintf(cmd, cmd_size, "say -v '%s' '%s'", voice_name, text);
    printf("Executing: %s\n", cmd);
    system(cmd);
    free(cmd);
}

void remove_bad_chars(char *text)
{
	int i, x, len;
	len = strlen(text);	
	for(i=0; i < len; i++) {
		if (text[i] == '\'' || text[i] == '"' || text[i] == '\\' || text[i] == '`') {
			// move following chars up one and knock down len.
			for(x=i; x <= len; x++) {
				text[x] = text[x+1];
			}
			i--;
		}
	}
}

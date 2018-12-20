#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <unistd.h>
#include <string.h>
#include <netdb.h>
#include <sys/types.h>
#include <sys/socket.h>

int main(int argc, const char* argv[])
{
    struct addrinfo hints;
    memset(&hints, 0, sizeof(hints));
    hints.ai_socktype = SOCK_STREAM; /* TCP */
    hints.ai_family = AF_UNSPEC;	/* Allow for any family */


    struct addrinfo *res;
    char *port = "8888";
    int e = getaddrinfo(argv[1], port, &hints, &res);
    if (e == EAI_SYSTEM)
    {
        printf("getaddrinfo error: %s\n", strerror(errno));
        exit(1);
    }
    else if (e != 0)
    {
        printf("getaddrinfo error: %s\n", gai_strerror(e));
        exit(2);
    }

    struct addrinfo *p = res;
    while (p != NULL)
    {
        if (p->ai_family == AF_INET)
        {
            printf("p->ai_family == AF_INET\n");
        }
        else if (p->ai_family == AF_INET6)
        {
            printf("p->ai_family == AF_INET6\n");
        }
        else
        {
            printf("UNKNOWN\n");
        }
        p = p->ai_next;
    }

    freeaddrinfo(res);

    exit(0);
}

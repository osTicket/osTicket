## Local Development Setup with Docker

To get started, ensure that installed required tools:
1. [docker](https://docs.docker.com/engine/install/)
2. [docker-compose](https://docs.docker.com/compose/install/)

### Run locally

1. (Optional) Copy [compose.override.example.yaml](compose.override.example.yaml) 
   to [compose.override.yaml](compose.override.yaml) and update if necessary. 
2. Build & run
   ```shell
   docker compose up --build
   ```
3. (Optional) Importing of database dump
   ```shell
   docker exec -i $(docker compose ps -q mysql) mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < dump.sql
   ```
   where "dump.sql" is the path to the database dump
   You should request the database dump from your mentor.

You can now access osTicket at http://localhost.

## Removing of outdated local branches

To speed up removing of outdated local branches after the code review just execute command:
```shell
git branch --format='%(refname:short)' \
   | grep -v "$(git rev-parse --abbrev-ref HEAD)" \
   | xargs -n 1 git branch -D
```
This script deletes all local branches except the current one.

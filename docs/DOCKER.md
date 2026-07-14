# Docker Guide

Remote project path:

```text
/Users/yp/Projects/infosecnexus
```

Start WordPress and MariaDB:

```sh
PATH="/usr/local/bin:/Applications/Docker.app/Contents/Resources/bin:/Applications/Docker.app/Contents/Resources/cli-plugins:/usr/bin:/bin:/usr/sbin:/sbin" docker compose up -d db wordpress
```

Run WP-CLI:

```sh
PATH="/usr/local/bin:/Applications/Docker.app/Contents/Resources/bin:/Applications/Docker.app/Contents/Resources/cli-plugins:/usr/bin:/bin:/usr/sbin:/sbin" docker compose --profile cli run --rm wpcli plugin list
```

Never run destructive cleanup such as `docker compose down -v`, `docker system prune`, `docker volume prune`, or `docker image prune -a`.


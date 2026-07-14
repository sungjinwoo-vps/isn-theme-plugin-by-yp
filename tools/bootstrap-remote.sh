#!/bin/sh
set -eu

PROJECT_ROOT="/Users/yp/Projects/infosecnexus"
DOCKER_PATH="/usr/local/bin:/Applications/Docker.app/Contents/Resources/bin:/Applications/Docker.app/Contents/Resources/cli-plugins:/usr/bin:/bin:/usr/sbin:/sbin"

cd "$PROJECT_ROOT"

if [ ! -f .env ]; then
  DB_PASS="$(openssl rand -base64 36 | tr -d '\n' | tr '/+' 'ab')"
  ROOT_PASS="$(openssl rand -base64 36 | tr -d '\n' | tr '/+' 'cd')"
  cat > .env <<EOF
COMPOSE_PROJECT_NAME=infosecnexus_dev
WORDPRESS_PORT=127.0.0.1:8888
MYSQL_DATABASE=infosecnexus
MYSQL_USER=infosecnexus
MYSQL_PASSWORD=$DB_PASS
MYSQL_ROOT_PASSWORD=$ROOT_PASS
WORDPRESS_DB_HOST=db:3306
WORDPRESS_DB_NAME=infosecnexus
WORDPRESS_DB_USER=infosecnexus
WORDPRESS_DB_PASSWORD=$DB_PASS
WORDPRESS_TABLE_PREFIX=isnx_
WORDPRESS_DEBUG=1
WORDPRESS_CONFIG_EXTRA=define('WP_ENVIRONMENT_TYPE', 'local'); define('DISALLOW_FILE_EDIT', true);
EOF
  chmod 600 .env
fi

PATH="$DOCKER_PATH" docker compose up -d db wordpress

echo "Waiting for WordPress..."
i=0
until PATH="$DOCKER_PATH" docker compose ps --format json wordpress 2>/dev/null | grep -q '"Health":"healthy"'; do
  i=$((i + 1))
  if [ "$i" -gt 60 ]; then
    PATH="$DOCKER_PATH" docker compose ps
    exit 1
  fi
  sleep 5
done

if ! PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp core is-installed; then
  ADMIN_PASS="$(openssl rand -base64 24 | tr -d '\n' | tr '/+' 'ef')"
  printf '%s\n' "$ADMIN_PASS" | PATH="$DOCKER_PATH" docker compose --profile cli run --rm -T wpcli wp core install \
    --url="http://localhost:8888" \
    --title="InfoSecNexus" \
    --admin_user="infosecnexus-admin" \
    --admin_email="admin@infosecnexus.test" \
    --skip-email \
    --prompt=admin_password
fi

PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp theme activate infosecnexus
PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp plugin activate infosecnexus-toolkit || true
PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp plugin is-installed elementor || PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp plugin install elementor --activate
PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp rewrite structure '/%category%/%postname%/' --hard
PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp rewrite flush

echo "Remote WordPress is available through: ssh -N -L 8888:127.0.0.1:8888 yash-imac"

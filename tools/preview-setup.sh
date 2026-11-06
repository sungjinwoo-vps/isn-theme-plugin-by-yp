#!/usr/bin/env bash
set -euo pipefail
preview_root=/tmp/isn-cinematic-preview-20261106
preview_data=/tmp/isn-cinematic-preview-data-20261106
test "$preview_root" = /tmp/isn-cinematic-preview-20261106
mkdir -p "$preview_root" "$preview_data" "$preview_root/wp-content/mu-plugins"
chmod 700 "$preview_data"
if [[ ! -f "$preview_root/wp-load.php" ]]; then wp core download --path="$preview_root" --version=7.1.4 --locale=en_US --quiet; fi
if [[ ! -f "$preview_root/wp-content/plugins/sqlite-database-integration/db.copy" ]]; then
 curl -fsSL https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip -o /tmp/isn-cinematic-sqlite.zip
 unzip -q /tmp/isn-cinematic-sqlite.zip -d "$preview_root/wp-content/plugins"
fi
cp "$preview_root/wp-content/plugins/sqlite-database-integration/db.copy" "$preview_root/wp-content/db.php"
if [[ ! -f "$preview_root/wp-config.php" ]]; then
 wp config create --path="$preview_root" --dbname=preview --dbuser=preview --dbpass=unused-sqlite --skip-check --quiet --extra-php <<'PHP'
define('WP_HOME','http://localhost:8890');
define('WP_SITEURL','http://localhost:8890');
define('WP_ENVIRONMENT_TYPE','local');
define('ISN_DESIGN_PREVIEW',true);
define('DB_PATH','/tmp/isn-cinematic-preview-data-20261106/wordpress.sqlite');
define('DISABLE_WP_CRON',true);
define('AUTOMATIC_UPDATER_DISABLED',true);
define('DISALLOW_FILE_MODS',true);
define('WP_DEBUG',false);
define('WP_CACHE',false);
PHP
 chmod 600 "$preview_root/wp-config.php"
fi
cp /tmp/isn-preview-sandbox.php "$preview_root/wp-content/mu-plugins/isn-preview-sandbox.php"
if ! wp core is-installed --path="$preview_root" --quiet; then
 preview_password=$(openssl rand -hex 24)
 wp core install --path="$preview_root" --url=http://localhost:8890 --title=InfoSecNexus --admin_user=design-preview-admin --admin_password="$preview_password" --admin_email=preview@localhost.invalid --skip-email --quiet
 unset preview_password
fi
wp eval-file /tmp/isn-preview-public-import.php --path="$preview_root" --skip-themes --quiet
wp theme activate infosecnexus --path="$preview_root" --quiet
echo PREVIEW_DATABASE=isolated-sqlite
echo PREVIEW_ROOT="$preview_root"

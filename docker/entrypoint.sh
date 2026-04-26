#!/usr/bin/env bash
set -e

DRUPAL_ROOT="/opt/drupal"
WEB_ROOT="${DRUPAL_ROOT}/web"
SETTINGS="${WEB_ROOT}/sites/default/settings.php"

DRUPAL_DB_HOST="${DRUPAL_DB_HOST:-db}"
DRUPAL_DB_NAME="${DRUPAL_DB_NAME:-drupal}"
DRUPAL_DB_USER="${DRUPAL_DB_USER:-drupal}"
DRUPAL_DB_PASS="${DRUPAL_DB_PASS:-drupal}"
DRUPAL_SITE_NAME="${DRUPAL_SITE_NAME:-Crumb Dev}"
DRUPAL_ADMIN_USER="${DRUPAL_ADMIN_USER:-admin}"
DRUPAL_ADMIN_PASS="${DRUPAL_ADMIN_PASS:-admin}"
DRUPAL_PROFILE="${DRUPAL_PROFILE:-standard}"

cd "${DRUPAL_ROOT}"

# Wait for the database port to accept TCP connections. Compose's
# depends_on: service_healthy already gates us, so this is just a safety net.
# We probe with bash's /dev/tcp instead of the mariadb client to avoid
# protocol/TLS quirks that can keep auth-level probes spinning even after the
# server is happily serving queries.
echo "[crumb] Waiting for database at ${DRUPAL_DB_HOST}:3306..."
for i in $(seq 1 60); do
  if timeout 2 bash -c "exec 3<>/dev/tcp/${DRUPAL_DB_HOST}/3306" 2>/dev/null; then
    echo "[crumb] Database port reachable."
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "[crumb] WARNING: database port not reachable after 120s — continuing anyway."
  fi
  sleep 2
done

DB_URL="mysql://${DRUPAL_DB_USER}:${DRUPAL_DB_PASS}@${DRUPAL_DB_HOST}/${DRUPAL_DB_NAME}"

# Install Drupal if not installed.
if ! drush --root="${WEB_ROOT}" status --field=bootstrap 2>/dev/null | grep -q "Successful"; then
  echo "[crumb] Installing Drupal (${DRUPAL_PROFILE} profile)..."
  rm -f "${SETTINGS}"
  drush --root="${WEB_ROOT}" site:install "${DRUPAL_PROFILE}" \
    --db-url="${DB_URL}" \
    --site-name="${DRUPAL_SITE_NAME}" \
    --account-name="${DRUPAL_ADMIN_USER}" \
    --account-pass="${DRUPAL_ADMIN_PASS}" \
    --yes
  chown -R www-data:www-data "${WEB_ROOT}/sites"
fi

# Make sure crumb module is enabled (and re-cache after mount changes).
if [ -d "${WEB_ROOT}/modules/custom/crumb" ]; then
  if ! drush --root="${WEB_ROOT}" pm:list --status=enabled --type=module --field=name 2>/dev/null | grep -qx "crumb"; then
    echo "[crumb] Enabling crumb module..."
    drush --root="${WEB_ROOT}" -y en crumb || true
  fi
  drush --root="${WEB_ROOT}" -y cache:rebuild || true
fi

# Permission fix for bind-mount.
chown -R www-data:www-data "${WEB_ROOT}/sites/default/files" 2>/dev/null || true

echo "[crumb] Ready. Login: ${DRUPAL_ADMIN_USER} / ${DRUPAL_ADMIN_PASS}"
echo "[crumb] Settings: http://localhost:8080/admin/config/services/crumb"

exec "$@"

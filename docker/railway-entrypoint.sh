#!/bin/sh
set -eu
PORT="${PORT:-80}"
echo "Starting Apache on PORT=${PORT}"

# Railway mounts a volume over mods-enabled that re-introduces mpm_event.
# Remove it at runtime before Apache starts.
rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf

if [ -w /etc/apache2/ports.conf ] && [ -w /etc/apache2/sites-available/000-default.conf ]; then
  sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
else
  echo "Apache port config is not writable; using the image default port."
fi

exec docker-php-entrypoint "$@"

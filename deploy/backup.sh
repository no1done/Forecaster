#!/bin/sh
# Nightly database dump. Run from DSM Task Scheduler as root:
#   sh /volume1/docker/forecaster/backup.sh
set -e
cd "$(dirname "$0")"
mkdir -p backups
docker compose exec -T database pg_dump -U app app | gzip > "backups/forecaster-$(date +%F).sql.gz"
# Keep 30 days
find backups -name 'forecaster-*.sql.gz' -mtime +30 -delete

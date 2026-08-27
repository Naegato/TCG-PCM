#!/bin/bash
set -uo pipefail

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_DIR="/backups"
RETENTION_MINUTES=30

log() { echo "[$(date +'%Y-%m-%d %H:%M:%S')] $*"; }

mkdir -p "$BACKUP_DIR/tcg" "$BACKUP_DIR/infisical"

# --- TCG (MariaDB) ---
TCG_DUMP_FILE="$BACKUP_DIR/tcg/tcg_${TIMESTAMP}.sql.gz"
TCG_OK=false

log "Dumping TCG database (MariaDB)..."
DB_ROOT_PASSWORD=$(cat /run/secrets/db_root_password)
if mysqldump \
    --host=db \
    --user=root \
    --password="${DB_ROOT_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    app 2>/tmp/tcg_err | gzip > "$TCG_DUMP_FILE" && [ -s "$TCG_DUMP_FILE" ]; then
    log "TCG dump OK -> $TCG_DUMP_FILE"
    TCG_OK=true
else
    log "WARNING: TCG dump failed (DB unreachable or error): $(cat /tmp/tcg_err)"
    rm -f "$TCG_DUMP_FILE"
fi

# --- Infisical (PostgreSQL) ---
INFISICAL_DUMP_FILE="$BACKUP_DIR/infisical/infisical_${TIMESTAMP}.sql.gz"
INFISICAL_OK=false

log "Dumping Infisical database (PostgreSQL)..."
PGPASSWORD=$(cat /run/secrets/infisical_db_password)
PGCONNECT_TIMEOUT=5
export PGPASSWORD PGCONNECT_TIMEOUT
if pg_dump \
    --host=infisical-db \
    --username=infisical \
    --dbname=infisical \
    --no-password \
    2>/tmp/infisical_err | gzip > "$INFISICAL_DUMP_FILE" && [ -s "$INFISICAL_DUMP_FILE" ]; then
    log "Infisical dump OK -> $INFISICAL_DUMP_FILE"
    INFISICAL_OK=true
else
    log "WARNING: Infisical dump failed (DB unreachable or error): $(cat /tmp/infisical_err)"
    rm -f "$INFISICAL_DUMP_FILE"
fi
unset PGPASSWORD

# --- Cleanup: only for a DB if its dump just succeeded ---
# Preserves old dumps when a DB is unreachable so data is not lost.
if $TCG_OK; then
    log "Cleaning TCG dumps older than ${RETENTION_MINUTES} minutes..."
    find "$BACKUP_DIR/tcg" -name "*.sql.gz" -mmin "+${RETENTION_MINUTES}" -delete
fi

if $INFISICAL_OK; then
    log "Cleaning Infisical dumps older than ${RETENTION_MINUTES} minutes..."
    find "$BACKUP_DIR/infisical" -name "*.sql.gz" -mmin "+${RETENTION_MINUTES}" -delete
fi

log "Done (TCG: $TCG_OK, Infisical: $INFISICAL_OK)."

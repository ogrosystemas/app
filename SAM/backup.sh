#!/bin/bash
# backup.sh — Backup automático do Ogro ERP-WMS
# Adicionar ao cron: 0 2 * * * /home/www/lupa/backup.sh >> /home/www/lupa/storage/logs/backup.log 2>&1

BACKUP_DIR="/home/www/lupa/storage/backups"
DATE=$(date +%Y-%m-%d_%H-%M)
DB_NAME="lupa_erp"
DB_USER="lupa_user"
DB_PASS="Lupa2026"
KEEP_DAYS=7

mkdir -p "$BACKUP_DIR"

echo "[$(date)] Iniciando backup..."

# Dump do banco
mysqldump -u "$DB_USER" -p"$DB_PASS" -h 127.0.0.1 \
  --single-transaction \
  --routines \
  --triggers \
  "$DB_NAME" | gzip > "$BACKUP_DIR/db_${DATE}.sql.gz"

if [ $? -eq 0 ]; then
    SIZE=$(du -sh "$BACKUP_DIR/db_${DATE}.sql.gz" | cut -f1)
    echo "[$(date)] Backup concluído: db_${DATE}.sql.gz ($SIZE)"
else
    echo "[$(date)] ERRO no backup!"
    exit 1
fi

# Remove backups antigos
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +$KEEP_DAYS -delete
echo "[$(date)] Backups antigos removidos (>$KEEP_DAYS dias)"

# Lista backups disponíveis
echo "[$(date)] Backups disponíveis:"
ls -lh "$BACKUP_DIR"/db_*.sql.gz 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'

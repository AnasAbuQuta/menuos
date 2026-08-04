# MenuOS Backup Strategy

Back up the MySQL database and public uploads as one timestamped recovery point. A database-only backup can reference files that no longer exist; an uploads-only backup cannot restore metadata and ownership.

## Schedule and retention

- MySQL: daily logical backup plus provider point-in-time recovery when available.
- Uploads: daily incremental snapshot/versioned object storage.
- Retention: 7 daily, 5 weekly, and 12 monthly recovery points, adjusted for legal and operational requirements.
- Keep at least one encrypted off-site copy in a separate account/region.
- Encrypt in transit and at rest; restrict backup deletion to a separate privileged role.

## Example MySQL backup

```bash
mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF \
  --host="$DB_HOST" --user="$BACKUP_DB_USER" --password menuos \
  | gzip > "menuos-db-$(date +%Y%m%d-%H%M%S).sql.gz"
```

Do not place passwords directly on shared command lines in production; use a protected MySQL option file or the provider's backup service.

For local public storage, archive `backend/storage/app/public`. For S3, enable versioning and lifecycle policies rather than relying only on synchronization.

Record checksums, application release tag, migration status, database backup identifier, uploads snapshot identifier, timezone, and operator for every recovery point. Alert on missed backups and verify that backup sizes are plausible.

Perform a documented restore drill in an isolated environment at least quarterly using `RESTORE.md`.

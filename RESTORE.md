# MenuOS Restore Procedure

1. Declare the incident, stop writes with maintenance mode, and record the current release and migration state.
2. Choose a database and uploads backup from the same recovery point.
3. Provision an isolated MySQL database and uploads location. Never test a restore over the only production copy.
4. Verify backup checksum and decrypt using the approved key process.
5. Restore MySQL, for example: `gunzip -c backup.sql.gz | mysql --host=HOST --user=RESTORE_USER menuos_restore`.
6. Restore the matching public uploads snapshot or select the matching object-storage version.
7. Deploy the application release recorded with that recovery point.
8. Configure an isolated environment, run `php artisan optimize`, and inspect `php artisan migrate:status` without automatically migrating.
9. Validate owner login, tenant isolation, restaurant/menu counts, image URLs, Bella Pasta, public menus, QR links, analytics totals, and `/up`.
10. Obtain incident-owner approval, switch production connections atomically, restart workers, and remove maintenance mode.
11. Monitor errors, latency, queue depth, and data integrity. Preserve failed/current systems for investigation until the incident is closed.

Document recovery point objective (data lost in time), recovery time, issues found, and improvements after every real or test restore.

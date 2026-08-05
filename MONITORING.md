# MenuOS Monitoring Recommendations

No paid integration is required. Use platform-native or open-source monitoring where appropriate.

## Availability and performance

- Probe `GET /up` every minute from at least two locations. Alert after consecutive failures.
- Monitor API 5xx rate, p50/p95/p99 latency, request volume, and public-menu latency separately.
- Monitor frontend availability, JS errors, Core Web Vitals (especially LCP and INP), and failed API requests.
- Alert on TLS certificate expiry and domain expiry.

## Application and infrastructure

- Centralize Laravel `warning` and higher logs; redact authorization headers and secrets at collection.
- Alert on repeated authentication failures, rate-limit spikes, queue failures, and storage upload errors.
- Monitor queue depth/oldest job, worker restarts, failed jobs, scheduler heartbeat, disk usage, CPU, memory, process count, and file descriptors.
- Monitor MySQL connections, slow queries, lock waits, replication lag (if used), storage, and backup status.
- Monitor public uploads/object storage availability and unexpected growth.

## Alert ownership

Every alert needs severity, owner, notification path, response target, runbook, and escalation contact. Test paging and `/up` alerts before launch. Review noisy alerts monthly and run a restore drill quarterly.

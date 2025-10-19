# CDN Proof of Concept

[![Release](https://img.shields.io/badge/release-v0.1.0--beta-orange)](#release-status)
[![License](https://img.shields.io/badge/license-AGPL--3.0-blue)](LICENSE)
[![Status](https://img.shields.io/badge/status-Proof_of_Concept-lightgrey)](#release-status)

This repository contains a containerized proof of concept for a mini CDN stack. Traffic enters through HAProxy, which enforces a small rule-based WAF, flows to a Varnish caching layer, and finally reaches an NGINX origin. Prometheus and Grafana provide basic observability.

Real-world CDNs blend a robust application layer (caching, routing, observability, security) with deep networking features such as Anycast announcements, ASN policies, and BGP traffic engineering. This PoC intentionally focuses on the application side—containerized services, WAF, cache, and monitoring—and does not implement network-level primitives like ASN management, edge PoPs, or BGP peering.

## Release Status

- **Release:** `v0.1.0-beta`
- **Stage:** Proof of concept; expect breaking changes and incomplete hardening. Use only for experimentation or evaluation.
- **Changelog:** See [`CHANGELOG.md`](CHANGELOG.md) for the `beta-poc` highlights.

## Tags

- CDN
- Varnish
- HAProxy
- WAF
- Prometheus
- Grafana
- Docker Compose
- Apple Silicon
- Proof of Concept

## Repository Layout

```
.
|- docker/
|  |- docker-compose.yml
|  |- varnish/
|     |- Dockerfile
|     |- entrypoint.sh
|- haproxy/
|  |- haproxy.cfg
|- nginx/
|  |- nginx.conf
|- origin/
|  |- index.html
|- prometheus/
|  |- prometheus.yml
|- varnish/
   |- default.vcl
```

## Architecture

```
Client
  |
  v
[HAProxy :80 WAF] ---> [Varnish :6081 cache] ---> [NGINX Origin :80]
                                |
                                +--> varnish_exporter :9131
 Origin /status --> nginx_exporter :9113
 varnish_exporter + nginx_exporter --> Prometheus :9090 --> Grafana :3000
```

- **HAProxy (`haproxy/haproxy.cfg`)** – Terminates inbound traffic on `:80`, applies rule-based WAF checks, collects stats on `:1936`, and forwards only approved requests to the cache layer.
- **Varnish (`docker/varnish/Dockerfile`, `varnish/default.vcl`, `docker/varnish/entrypoint.sh`)** – Serves as the caching tier with a default TTL of 120 seconds, exposes the admin interface on `:6082`, and launches a bundled Prometheus exporter on `:9131` for Varnish metrics.
- **Origin (`nginx/nginx.conf`, `origin/index.html`)** – Runs a static NGINX site, keeps a lightweight `/status` endpoint for scraping, and acts as the ultimate data source behind the cache.
- **Metrics pipeline** – `nginx/nginx-prometheus-exporter` scrapes the origin at `/status` (port `:9113`), the Varnish exporter exposes cache metrics on `:9131`, Prometheus (`prometheus/prometheus.yml`) scrapes both exporters and persists timeseries (`:9090`), and Grafana renders dashboards and alerts from that data (`:3000`).

### Port Map

- `80` – Public entrypoint (HAProxy + WAF)
- `6081` / `6082` – Varnish HTTP and admin interfaces
- `9131` – Varnish Prometheus exporter
- `9113` – NGINX Prometheus exporter
- `9090` – Prometheus UI
- `3000` – Grafana UI (login `admin` / `admin`, change after first use)
- `1936` – HAProxy stats page

## Getting Started

Prerequisites: Docker and Docker Compose. This configuration is tuned for Apple Silicon (M1/M2/M3) hosts and pins the Varnish service to `linux/amd64` in `docker/docker-compose.yml`. Running on non-Apple hardware may require adjusting or removing that platform override.

```bash
# Build images (only needed after config changes)
docker compose -f docker/docker-compose.yml build

# Start the full stack
docker compose -f docker/docker-compose.yml up -d

# Follow service logs (example: Varnish)
docker compose -f docker/docker-compose.yml logs -f varnish
```

Visit:

- `http://localhost` – Through the full pipeline (HAProxy → Varnish → Origin)
- `http://localhost:1936` – HAProxy stats (no auth in this PoC)
- `http://localhost:9090` – Prometheus
- `http://localhost:3000` – Grafana

Stop and clean up:

```bash
docker compose -f docker/docker-compose.yml down
```

Add `--volumes` if you want to remove Grafana and Prometheus data.

## WAF Quick Tests

The WAF sits in HAProxy, so target port 80. Requests sent directly to Varnish on `6081` will bypass it.

```bash
# Baseline request (allowed)
curl -i http://localhost/index.html

# Blocked HTTP method
curl -i -X TRACE http://localhost/

# Blocked traversal attempt
curl -i "http://localhost/../etc/passwd"

# Blocked SQL injection probe
curl -i "http://localhost/?q=union+select+1"

# Blocked XSS payload
curl -i "http://localhost/?q=%3Cscript%3Ealert(1)%3C/script%3E"
```

Each denied request should return `403 Forbidden`.

## Cache Behavior

The VCL in `varnish/default.vcl` sets a 120 second TTL and surfaces cache status via the `X-Cache` header (`HIT` or `MISS`). PURGE requests are currently allowed from any IP for testing; restrict the `acl purge` block before using in production.

When a response is served directly from Varnish memory the `X-Cache` header reports `HIT`, meaning the object was cached and returned without reaching the origin. The first request for new content (or any request after the TTL expires or a PURGE) yields `MISS`, forcing Varnish to fetch from the origin before caching the payload. Watching the HIT/MISS flip helps confirm that caching works as expected and highlights items that are uncacheable or expiring too quickly.

## Metrics

- Prometheus scrapes exporters every 10 seconds (`prometheus/prometheus.yml`).
- HAProxy exposes built-in stats on `:1936` that can be scraped by the HAProxy Prometheus exporter if added later.
- Grafana uses the persistent `grafana_data` volume to retain dashboards and credentials.

## Development Notes

- Modify Varnish behavior by editing `varnish/default.vcl` and restarting the service:
  ```bash
  docker compose -f docker/docker-compose.yml restart varnish
  ```
- Update WAF rules in `haproxy/haproxy.cfg` and restart HAProxy to apply changes.
- The script at `docker/varnish/entrypoint.sh` orchestrates Varnish start-up and exporter readiness; adjust if you change exporter parameters.

## License

Released under the GNU Affero General Public License v3.0. See `LICENSE` for the full text.

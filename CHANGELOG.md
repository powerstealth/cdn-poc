# Changelog

All notable changes to this project will be documented in this file.

## [v0.2.0-beta] - 2025-10-20

- Added GeoIP enforcement to HAProxy: requests now consult `haproxy/geoip.map`, emit the `X-GeoIP-Country` header, and can be blocked per country/IP via ACLs in `haproxy/haproxy.cfg`.
- Documented the GeoIP map workflow and provided a test recipe (using `140.82.121.4/32`) so the new functionality is easy to exercise locally.

## [v0.1.0-beta] - 2024-10-19

- Initial beta proof-of-concept release.
- Includes HAProxy WAF entrypoint, Varnish caching layer, and NGINX origin content service.
- Bundles monitoring stack with Prometheus, Grafana, and exporters for Varnish and NGINX.
- Ships containerized environment via Docker Compose plus documentation for setup and scope.

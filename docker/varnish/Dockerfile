FROM varnish:7

# Switch to root to install required packages
USER root

# Update repositories and install baseline tooling
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       wget \
       curl \
       procps \
       ca-certificates \
       gnupg \
    && rm -rf /var/lib/apt/lists/*

# Download the Prometheus Varnish exporter binary
RUN wget https://github.com/jonnenauha/prometheus_varnish_exporter/releases/download/1.6.1/prometheus_varnish_exporter-1.6.1.linux-amd64.tar.gz \
    && tar -xzf prometheus_varnish_exporter-1.6.1.linux-amd64.tar.gz \
    && mv prometheus_varnish_exporter-1.6.1.linux-amd64/prometheus_varnish_exporter /usr/local/bin/varnish_exporter

# Copy the VCL configuration and entrypoint script
COPY ./varnish/default.vcl /etc/varnish/default.vcl
COPY ./docker/varnish/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Drop back to the varnish user for safer defaults
USER varnish

# Run the entrypoint script by default
CMD ["/usr/local/bin/entrypoint.sh"]

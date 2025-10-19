#!/bin/bash
# Start varnishd in the background so the exporter can scrape it later
varnishd -F -f /etc/varnish/default.vcl -a :6081 -T :6082 -s malloc,256m &
PID=$!

# Wait until varnishstat is ready to avoid scraping errors
echo "Waiting for varnishd to start..."
until varnishstat -1 > /dev/null 2>&1; do
  sleep 5
done

echo "varnishd is up, starting exporter..."
# Launch the Prometheus exporter for Varnish metrics
varnish_exporter \
    -web.listen-address=:9131 \
    -varnishstat-path=/usr/bin/varnishstat

# Keep the main process in the foreground to prevent container exit
wait $PID

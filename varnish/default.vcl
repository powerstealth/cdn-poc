vcl 4.1;

backend default {
    .host = "origin";
    .port = "80";
}

# Allow PURGE requests from any IP (testing only; tighten for production)
acl purge {
    "0.0.0.0"/0;
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed."));
        }
        return (purge);
    }
}

sub vcl_backend_response {
    # Cache objects for 2 minutes by default
    set beresp.ttl = 120s;
}

sub vcl_deliver {
    # Expose cache hit status to clients via response header
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }
}

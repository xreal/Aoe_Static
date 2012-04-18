# default backend definition.  Set this to point to your content server.
backend default {
  .host = "127.0.0.1";
  .port = "80";
}

# admin backend with longer timeout values. Set this to the same IP & port as your default server.
backend admin {
  .host = "127.0.0.1";
  .port = "80";
  .first_byte_timeout = 18000s;
  .between_bytes_timeout = 18000s;
}

# add your Magento server IP to allow purges from the backend
acl purge {
    "localhost";
    "127.0.0.1";
}

/*
   Like the default function, only that cookies don't prevent caching
 */
sub vcl_recv {
    
    set req.backend = default;


    if (req.restarts == 0) {
        if (req.http.x-forwarded-for) {
            set req.http.X-Forwarded-For =
            req.http.X-Forwarded-For + ", " + client.ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
        }
    }    

    if (req.request != "GET" &&
      req.request != "HEAD" &&
      req.request != "PUT" &&
      req.request != "POST" &&
      req.request != "TRACE" &&
      req.request != "OPTIONS" &&
      req.request != "DELETE" &&
      req.request != "PURGE") {
        /* Non-RFC2616 or CONNECT which is weird. */
        return (pipe);
    }
    
    # purge request
    if (req.request == "PURGE") {
        # only allow purge from backends
        if (!client.ip ~ purge) {
            error 405 "Not allowed.";
        }
        ban("obj.http.X-Purge-Host ~ " + req.http.X-Purge-Host + " && obj.http.X-Purge-URL ~ " + req.http.X-Purge-Regex + " && obj.http.Content-Type ~ " + req.http.X-Purge-Content-Type);
        error 200 "Purged.";
    }

    # switch to admin backend configuration
    if (req.http.cookie ~ "adminhtml=") {
        set req.backend = admin;
    }

    # we only deal with GET and HEAD by default    
    if (req.request != "GET" && req.request != "HEAD") {
        return (pass);
    }
    
    # normalize url in case of leading HTTP scheme and domain
    set req.url = regsub(req.url, "^http[s]?://[^/]+", "");
    
    # static files are always cacheable. remove SSL flag and cookie
    if (req.url ~ "^/(media|js|skin)/.*\.(png|jpg|jpeg|gif|css|js|swf|ico)$") {
        unset req.http.Https;
        unset req.http.Cookie;
    }

    # as soon as we have a NO_CACHE cookie pass request
    if (req.http.cookie ~ "NO_CACHE=") {
        return (pass);
    }

    # normalize Aceept-Encoding header
    # http://varnish.projects.linpro.no/wiki/FAQ/Compression
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv)$") {
            # No point in compressing these
            remove req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate" && req.http.user-agent !~ "MSIE") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            # unkown algorithm
            remove req.http.Accept-Encoding;
        }
    }
    
    # remove Google gclid parameters
    set req.url = regsuball(req.url,"\?gclid=[^&]+$",""); # strips when QS = "?gclid=AAA"
    set req.url = regsuball(req.url,"\?gclid=[^&]+&","?"); # strips when QS = "?gclid=AAA&foo=bar"
    set req.url = regsuball(req.url,"&gclid=[^&]+",""); # strips when QS = "?foo=bar&gclid=AAA" or QS = "?foo=bar&gclid=AAA&bar=baz"

    if (req.http.Authorization) {
        /* Not cacheable by default */
        return (pass);
    }

    return (lookup);

}

/*
   Remove cookies from backend response so this page can be cached
 */
sub vcl_fetch {
    if (beresp.status == 500) {
       set beresp.saintmode = 10s;
       return (restart);
    }

    # set minimum timeouts to auto-discard stored objects
    set beresp.grace = 5m;

    # add ban-lurker tags to object
    set beresp.http.X-Purge-URL = req.url;
    set beresp.http.X-Purge-Host = req.http.host;

    # Some known-static file types
    if (req.url ~ "^[^?]*\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)\$") {
        # Force caching
        remove beresp.http.Pragma;
        remove beresp.http.Set-Cookie;
        set beresp.http.Cache-Control = "public";
    }

    if (beresp.http.aoestatic == "cache") {
        remove beresp.http.Set-Cookie;
        remove beresp.http.X-Cache;
        remove beresp.http.Server;
        remove beresp.http.Age;
        remove beresp.http.Pragma;
        set beresp.http.Cache-Control = "public";
        set beresp.grace = 2m;
        set beresp.http.X_AOESTATIC_FETCH = "Removed cookie in vcl_fetch";
    } else {
        set beresp.http.X_AOESTATIC_FETCH = "Nothing removed";
    }

    if (beresp.status == 200 || beresp.status == 301 || beresp.status == 404) {
        if (beresp.http.Content-Type ~ "text/html" || beresp.http.Content-Type ~ "text/xml") {
            if ((beresp.http.Set-Cookie ~ "NO_CACHE=") || (beresp.ttl < 1s)) {
		if(beresp.http.Set-Cookie ~ "NO_CACHE=") {
			set beresp.http.X_AOESTATIC_FETCH_PASSREASON = "Cookie NO_CACHE";
		} else {
			set beresp.http.X_AOESTATIC_FETCH_PASSREASON = "Low TTL: "+beresp.ttl;
		}	
                set beresp.ttl = 0s;
                return (hit_for_pass);
            }
            # Don't cache cookies
            unset beresp.http.set-cookie;
        } else {
            # set default TTL value for static content
            set beresp.ttl = 4h;
        }
        return (deliver);
    }
    
    return (hit_for_pass);

}


/*
   Adding debugging information
 */
sub vcl_deliver {
    set resp.http.X-Served-By = server.hostname;
    if (obj.hits > 0) {
            set resp.http.X-Cache = "HIT";
            set resp.http.X-Cache-Hits = obj.hits;
    } else {
            set resp.http.X-Cache = "MISS";
    }
    return (deliver);
}


# HTTPS Certificate Management

## Overview

This project uses Let's Encrypt TLS certificates with Cloudflare DNS-01 challenge validation.
The DNS-01 method is used because the server does not need to expose port 80 to the public
internet — instead, ownership of the domain is proven by temporarily creating a DNS TXT record
via the Cloudflare API.

## Certificates

Two hostnames are covered:

| Hostname | Service |
|----------|---------|
| `media.jfcenterprises.com` | Web frontend (PHP) |
| `db.jfcenterprises.com:6984` | CouchDB (HTTPS) |

## How DNS-01 Validation Works

When certbot renews a certificate, the `certbot-dns-cloudflare` plugin:

1. Creates a `_acme-challenge` TXT record in the Cloudflare DNS zone via the API
2. Waits 60 seconds for DNS propagation
3. Let's Encrypt validates the TXT record
4. The certificate is issued
5. The TXT record is deleted

## Cloudflare Credentials

The Cloudflare API token is stored in a `cloudflare.ini` file. Two copies must exist:

| Path | Owner | Purpose |
|------|-------|---------|
| `/home/couchdb/letsencrypt/cloudflare.ini` | CouchDB user | Source of truth |
| `/etc/letsencrypt/cloudflare.ini` | root / certbot | Used during renewal |

`/home/couchdb/letsencrypt/cloudflare.ini` is the authoritative copy. If the Cloudflare API
token is ever rotated, update it there — the sync script will propagate it to the certbot
location.

## Credential Synchronization

`bin/sync-cloudflare-creds.sh` copies the CouchDB-owned credentials to the certbot location
and sets `chmod 600` so only root can read the API token.

It runs in two contexts:

- **Daily at 4 AM** via root's crontab — keeps the files in sync on non-renewal days
- **As a certbot pre-hook** — ensures credentials are current immediately before each renewal attempt

## Renewal Flow

```
certbot.timer (systemd)
    └── pre-hook: bin/sync-cloudflare-creds.sh
        └── copies /home/couchdb/letsencrypt/cloudflare.ini
                to /etc/letsencrypt/cloudflare.ini (chmod 600)
    └── certbot renew
        └── certbot-dns-cloudflare plugin
            └── reads /etc/letsencrypt/cloudflare.ini
            └── creates _acme-challenge TXT record via Cloudflare API
            └── waits 60s for DNS propagation
            └── Let's Encrypt validates the record
            └── issues renewed certificate
            └── deletes the TXT record
```

Let's Encrypt certificates are valid for 90 days. Certbot typically renews within 30 days of
expiry.

## Monitoring and Maintenance

Check that the credential files are in sync:
```bash
ls -l /home/couchdb/letsencrypt/cloudflare.ini /etc/letsencrypt/cloudflare.ini
```

View sync script activity in the cron logs:
```bash
sudo grep sync-cloudflare-creds /var/log/syslog
```

Test the renewal process without actually renewing:
```bash
sudo certbot renew --dry-run --dns-cloudflare-propagation-seconds 60
```

Check certificate expiry:
```bash
sudo openssl x509 -dates -noout -in /home/couchdb/letsencrypt/live/media.jfcenterprises.com/cert.pem
```

Check the certbot timer and recent renewal history:
```bash
sudo systemctl list-timers | grep certbot
sudo journalctl -u certbot.service
```

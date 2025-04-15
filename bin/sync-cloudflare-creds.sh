#!/bin/bash

# Source and destination paths
COUCHDB_INI="/home/couchdb/letsencrypt/cloudflare.ini"
CERTBOT_INI="/etc/letsencrypt/cloudflare.ini"

# Ensure source file exists
if [ ! -f "$COUCHDB_INI" ]; then
    echo "Error: Source file $COUCHDB_INI does not exist"
    exit 1
fi

# Copy file and set permissions
sudo cp "$COUCHDB_INI" "$CERTBOT_INI"
sudo chmod 600 "$CERTBOT_INI"

echo "Cloudflare credentials synchronized at $(date)"

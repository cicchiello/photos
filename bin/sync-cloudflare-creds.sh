#!/bin/bash

# This file should be run as root


# Source and destination directories
LETSENCRYPT_DIR="/etc/letsencrypt/live/db.jfcenterprises.com"
COUCHDB_DIR="/home/couchdb/letsencrypt/live/db.jfcenterprises.com"

# Check if source directory exists
if [ ! -d "${LETSENCRYPT_DIR}" ]; then
    echo "Error: Source directory ${LETSENCRYPT_DIR} does not exist"
    exit 1
fi
#echo "INFO: Source directory ${LETSENCRYPT_DIR} exists"

# Create destination directory if it doesn't exist
if [ ! -d "${COUCHDB_DIR}" ]; then
    echo "ERROR: ${COUCHDB_DIR} doesn't exist"
fi
#echo "INFO: ${COUCHDB_DIR} exists"

# Copy certificate files and set permissions
for file in cert.pem chain.pem fullchain.pem privkey.pem; do
    if [ -f "${LETSENCRYPT_DIR}/${file}" ]; then
        # Backup existing file if it exists
        if [ -f "${COUCHDB_DIR}/${file}" ]; then
            cp "${COUCHDB_DIR}/${file}" "${COUCHDB_DIR}/${file}.bak"
            #echo "INFO: ${COUCHDB_DIR}/${file} backed up"
	#else
            #echo "INFO: ${COUCHDB_DIR}/${file} not backed up"
        fi
        
        # Copy new certificate
        cp "${LETSENCRYPT_DIR}/${file}" "${COUCHDB_DIR}/${file}"
        #echo "INFO: ${LETSENCRYPT_DIR}/${file} copied to ${COUCHDB_DIR}/${file}"
        
        # Set permissions (readable by CouchDB)
        chown couchdb:couchdb "${COUCHDB_DIR}/${file}"
        chmod 644 "${COUCHDB_DIR}/${file}"
        #echo "INFO: adjusted owners and permissions"
    else
        echo "Warning: ${file} not found in source directory"
    fi
done

#echo "INFO: ${COUCHDB_DIR} *.pem files: "
#ls -ltrL ${COUCHDB_DIR}/*.pem




# Source and destination directories
LETSENCRYPT_DIR="/etc/letsencrypt/live/media.jfcenterprises.com"
COUCHDB_DIR="/home/couchdb/letsencrypt/live/media.jfcenterprises.com"

# Check if source directory exists
if [ ! -d "${LETSENCRYPT_DIR}" ]; then
    echo "Error: Source directory ${LETSENCRYPT_DIR} does not exist"
    exit 1
fi
#echo "INFO: Source directory ${LETSENCRYPT_DIR} exists"

# Create destination directory if it doesn't exist
if [ ! -d "${COUCHDB_DIR}" ]; then
    echo "ERROR: ${COUCHDB_DIR} doesn't exist"
fi
#echo "INFO: ${COUCHDB_DIR} exists"

# Copy certificate files and set permissions
for file in cert.pem chain.pem fullchain.pem privkey.pem; do
    if [ -f "${LETSENCRYPT_DIR}/${file}" ]; then
        # Backup existing file if it exists
        if [ -f "${COUCHDB_DIR}/${file}" ]; then
            cp "${COUCHDB_DIR}/${file}" "${COUCHDB_DIR}/${file}.bak"
            #echo "INFO: ${COUCHDB_DIR}/${file} backed up"
	#else
            #echo "INFO: ${COUCHDB_DIR}/${file} not backed up"
        fi
        
        # Copy new certificate
        cp "${LETSENCRYPT_DIR}/${file}" "${COUCHDB_DIR}/${file}"
        #echo "INFO: ${LETSENCRYPT_DIR}/${file} copied to ${COUCHDB_DIR}/${file}"
        
        # Set permissions (readable by CouchDB)
        chown couchdb:couchdb "${COUCHDB_DIR}/${file}"
        chmod 644 "${COUCHDB_DIR}/${file}"
        #echo "INFO: adjusted owners and permissions"
    else
        echo "Warning: ${file} not found in source directory"
    fi
done

#echo "INFO: ${COUCHDB_DIR} *.pem files: "
#ls -ltrL ${COUCHDB_DIR}/*.pem






# Restart CouchDB to pick up new certificates
sudo systemctl restart couchdb

echo "Certificates synchronized to CouchDB directory at $(date)"

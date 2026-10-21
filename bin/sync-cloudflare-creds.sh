#!/bin/bash

# This file should be run as root


# Source and destination directories
LETSENCRYPT_DIR="/etc/letsencrypt/live/db.jfcenterprises.com"
PHOTOS_CERT_DIR="/home/pi/photos/letsencrypt/live/db.jfcenterprises.com"

# Check if source directory exists
if [ ! -d "${LETSENCRYPT_DIR}" ]; then
    echo "Error: Source directory ${LETSENCRYPT_DIR} does not exist"
    exit 1
fi
#echo "INFO: Source directory ${LETSENCRYPT_DIR} exists"

# Create destination directory if it doesn't exist
if [ ! -d "${PHOTOS_CERT_DIR}" ]; then
    echo "ERROR: ${PHOTOS_CERT_DIR} doesn't exist"
fi
#echo "INFO: ${PHOTOS_CERT_DIR} exists"

# Copy certificate files and set permissions
for file in cert.pem chain.pem fullchain.pem privkey.pem; do
    if [ -f "${LETSENCRYPT_DIR}/${file}" ]; then
        # Backup existing file if it exists
        if [ -f "${PHOTOS_CERT_DIR}/${file}" ]; then
            cp "${PHOTOS_CERT_DIR}/${file}" "${PHOTOS_CERT_DIR}/${file}.bak"
            #echo "INFO: ${PHOTOS_CERT_DIR}/${file} backed up"
	#else
            #echo "INFO: ${PHOTOS_CERT_DIR}/${file} not backed up"
        fi
        
        # Copy new certificate
        cp "${LETSENCRYPT_DIR}/${file}" "${PHOTOS_CERT_DIR}/${file}"
        #echo "INFO: ${LETSENCRYPT_DIR}/${file} copied to ${PHOTOS_CERT_DIR}/${file}"
        
        # Set permissions (readable by CouchDB)
        chown couchdb:couchdb "${PHOTOS_CERT_DIR}/${file}"
        chmod 644 "${PHOTOS_CERT_DIR}/${file}"
        #echo "INFO: adjusted owners and permissions"
    else
        echo "Warning: ${file} not found in source directory"
    fi
done

#echo "INFO: ${PHOTOS_CERT_DIR} *.pem files: "
#ls -ltrL ${PHOTOS_CERT_DIR}/*.pem




# Source and destination directories
LETSENCRYPT_DIR="/etc/letsencrypt/live/media.jfcenterprises.com"
PHOTOS_CERT_DIR="/home/pi/photos/letsencrypt/live/media.jfcenterprises.com"

# Check if source directory exists
if [ ! -d "${LETSENCRYPT_DIR}" ]; then
    echo "Error: Source directory ${LETSENCRYPT_DIR} does not exist"
    exit 1
fi
#echo "INFO: Source directory ${LETSENCRYPT_DIR} exists"

# Create destination directory if it doesn't exist
if [ ! -d "${PHOTOS_CERT_DIR}" ]; then
    echo "ERROR: ${PHOTOS_CERT_DIR} doesn't exist"
fi
#echo "INFO: ${PHOTOS_CERT_DIR} exists"

# Copy certificate files and set permissions
for file in cert.pem chain.pem fullchain.pem privkey.pem; do
    if [ -f "${LETSENCRYPT_DIR}/${file}" ]; then
        # Backup existing file if it exists
        if [ -f "${PHOTOS_CERT_DIR}/${file}" ]; then
            cp "${PHOTOS_CERT_DIR}/${file}" "${PHOTOS_CERT_DIR}/${file}.bak"
            #echo "INFO: ${PHOTOS_CERT_DIR}/${file} backed up"
	#else
            #echo "INFO: ${PHOTOS_CERT_DIR}/${file} not backed up"
        fi
        
        # Copy new certificate
        cp "${LETSENCRYPT_DIR}/${file}" "${PHOTOS_CERT_DIR}/${file}"
        #echo "INFO: ${LETSENCRYPT_DIR}/${file} copied to ${PHOTOS_CERT_DIR}/${file}"
        
        # Set permissions (readable by CouchDB)
        chown couchdb:couchdb "${PHOTOS_CERT_DIR}/${file}"
        chmod 644 "${PHOTOS_CERT_DIR}/${file}"
        #echo "INFO: adjusted owners and permissions"
    else
        echo "Warning: ${file} not found in source directory"
    fi
done

#echo "INFO: ${PHOTOS_CERT_DIR} *.pem files: "
#ls -ltrL ${PHOTOS_CERT_DIR}/*.pem






# Restart Apache2 and CouchDB to pick up new certificates
sudo systemctl restart couchdb
sudo systemctl reload apache2

echo "Certificates synchronized to /home/pi/photos/letsencrypt directory at $(date)"

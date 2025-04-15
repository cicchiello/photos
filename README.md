# Photos Management System

A comprehensive photo management system with features for uploading, indexing, and managing digital photos.

## Project Structure

- `/py` - Python backend scripts
  - `uploader.py` - Handles photo upload functionality
  - `addIdx.py` - Adds indexing to photos
  - `addThumbnail.py` - Generates thumbnails for uploaded photos
  - `findDups.py` - Identifies duplicate photos
  - `lucenize.py` - Lucene integration for search functionality
  - `removeIdx.py` - Removes indexing from photos
  - `tagset.py` - Manages photo tagging system

- `/www` - Web frontend files
- `/couchdb` - CouchDB related configurations and scripts

## Features

- Photo upload and storage
- Automatic thumbnail generation
- Duplicate photo detection
- Photo indexing and search
- Tagging system
- Web-based interface

## Requirements

- Python
- CouchDB
- Web server (for frontend)

## Setup

1. Ensure CouchDB is installed and running
2. Configure the database settings in the appropriate configuration files
3. Install Python dependencies
4. Start the web server

## SSL Certificate Management

### Initial Setup

The system uses Let's Encrypt certificates with Cloudflare DNS authentication for:
- media.jfcenterprises.com
- db.jfcenterprises.com

### Certificate Configuration

1. Cloudflare credentials are required in two locations:
   - `/home/couchdb/letsencrypt/cloudflare.ini` (for CouchDB)
   - `/etc/letsencrypt/cloudflare.ini` (for Certbot)

2. A synchronization script maintains these files:
   - Location: `/home/pi/photos/bin/sync-cloudflare-creds.sh`
   - Runs daily at 4 AM via root's crontab
   - Also runs as a pre-hook before certificate renewal

### Automatic Renewal

Certificates are automatically renewed by certbot.timer systemd service.

### Monitoring and Maintenance

1. Check synchronization script operation:
   ```bash
   # View file timestamps
   ls -l /home/couchdb/letsencrypt/cloudflare.ini /etc/letsencrypt/cloudflare.ini
   
   # Check cron job logs
   sudo grep sync-cloudflare-creds /var/log/syslog
   ```

2. Verify certificate renewal:
   ```bash
   # Test renewal process (with 60-second DNS propagation wait)
   sudo certbot renew --dry-run --dns-cloudflare-propagation-seconds 60
   
   # Check certificate expiry
   sudo openssl x509 -dates -noout -in /etc/letsencrypt/live/media.jfcenterprises.com/cert.pem
   ```

3. Monitor certbot timer:
   ```bash
   # View timer status
   sudo systemctl list-timers | grep certbot
   
   # Check recent renewal attempts
   sudo journalctl -u certbot.service
   ```

## Branch Information

The project uses the following branch structure:
- `staging` - Staging environment for testing before production

## Contributing

1. Create a feature branch from `staging`
2. Make your changes
3. Submit a pull request to merge back into `staging`

## UI Testing

The project uses Cypress for UI testing. To run the tests:

1. Install dependencies:
   ```bash
   npm install
   ```

2. Run tests in interactive mode:
   ```bash
   npm test
   ```

3. Run tests in headless mode:
   ```bash
   npm run test:headless
   ```

Test files are located in the `cypress/e2e` directory.

## License

[Add appropriate license information]
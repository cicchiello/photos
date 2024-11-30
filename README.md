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

## Branch Information

The project uses the following branch structure:
- `staging` - Staging environment for testing before production

## Contributing

1. Create a feature branch from `staging`
2. Make your changes
3. Submit a pull request to merge back into `staging`

## License

[Add appropriate license information]
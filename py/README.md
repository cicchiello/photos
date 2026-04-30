# Python Scripts — Photos ETL

These are command-line scripts for managing the photo library. They are run manually on the server, not by the web app.

All scripts require `-db` (CouchDB database URL) and `-creds` (credentials as `user:password`). The bash wrapper scripts use `foo:bar` as a placeholder — supply the real credentials at runtime.

AWS credentials must be configured for the scripts that call Rekognition (`uploader.py`, `tagset.py`, `facial.py`). The default AWS profile is used.

---

## Adding a New Photo

### Step 1 — Upload

```bash
py/uploader.py -db http://mediaserver:5984/photos -creds photos:PASSWORD -pic /path/to/photo.jpg
```

`uploader.py` does everything needed for a new photo in one call:
- Creates a CouchDB document using the MD5 hash of the file as the document ID
- Runs AWS Rekognition to detect objects and labels (tags with `source: rekognition`)
- Tokenizes the filename for additional keyword tags (tags with `source: path-tokenize`)
- Attaches three image variants to the document:
  - `image` — the original file
  - `web_image` — resized to 640px for display
  - `thumbnail` — resized to 128px for the grid view

If the document already exists (same file content = same MD5), it updates and merges tags rather than creating a duplicate.

### Step 2 — Facial recognition (optional, per person)

```bash
py/facial.py -db http://mediaserver:5984/photos -creds photos:PASSWORD \
  -exemplarId <id-of-known-photo-of-person> -tag "person-name"
```

Scans all images that have a `face` Rekognition tag and compares each against the exemplar photo using AWS Rekognition `compare_faces`. Prints the IDs of matching images — **does not write anything to the database**. Default confidence threshold is 97%; use `-conf` to adjust.

### Step 3 — Tag facial matches (optional, for each match from Step 2)

```bash
py/tag.py -db http://mediaserver:5984/photos -creds photos:PASSWORD \
  -id <id1> <id2> ... -user joe -tag "person-name"
```

Adds a user tag to one or more specified images (`-id` accepts a list).

---

## Other Scripts

| Script | Purpose |
|--------|---------|
| `addThumbnail.py` | Adds a thumbnail to an existing document that is missing one |
| `addIdx.py` | Adds a manual `idx` sort field to a document |
| `removeIdx.py` | Removes the `idx` field from a document |
| `hide.py` | Marks a photo as hidden (excluded from the app grid and search) |
| `unhide.py` | Removes the hidden flag from a photo |
| `findDups.py` | Identifies duplicate photos by MD5 hash |
| `backup.py` | Dumps all documents and attachments to the filesystem |
| `restore.py` | Restores documents and attachments from a backup |
| `rmUserTags.py` | Removes user-added tags from images |
| `tagAlias.py` | Manages tag aliases |
| `lucenize.py` | Utility for tokenizing filenames into search keywords (used by `uploader.py`) |
| `tagset.py` | Manages tag sets; wraps AWS Rekognition label detection (used by `uploader.py`) |

## Bash Wrappers

| Script | Purpose |
|--------|---------|
| `addAllPhotos.bsh` | Runs `uploader.py` on all files in a drop directory |
| `addAllThumbnails.bsh` | Runs `addThumbnail.py` on a list of specific files |
| `tagAll.bsh` | Applies a predefined set of manual tags across the library |
| `aliasAll.bsh` | Applies a predefined set of tag aliases |
| `hideAllDups.bsh` | Runs `hide.py` on all detected duplicates |
| `unhideMany.bsh` | Runs `unhide.py` on a list of images |
| `updatePhotos.bsh` | Bulk re-processes existing photos |

## Backup

`bin/backup.bsh` runs daily backups of the CouchDB database to the filesystem, compresses them, and prunes old archives. It must be run as user `joe` (not root).

```bash
bin/backup.bsh <base-path> <backup-root>
# e.g.
bin/backup.bsh /home/joe /mnt/pi-nas/photos-backup
```

- `<base-path>` — root of the photos repo checkout (e.g. `/home/joe`); used to locate `photos/py/backup.py` and the log file
- `<backup-root>` — directory where compressed backup archives are stored

Archives are named `YYYYMMDD_backup.tar.gz`. On each successful run, archives older than the first of last month are deleted (e.g. in April, anything before March 1 is removed).

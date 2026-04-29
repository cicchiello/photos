# Enhancements

## User roles
Add an `is_admin` flag to app user records (stored in the `photos` database, `type: "user"`) to distinguish regular users from admin users. Admin status gates several features below.

Note: CouchDB `_users` database already has a `photos` service account with `photos-writer` role for write authentication. This enhancement is about app-level admin privileges within the UI, stored separately on the app user records.

## Show hidden photos
Add a toggle in the UI to include hidden photos in the grid and search results. Defaults to off (current behavior). Hidden photos would be visually distinguished when shown.

## Hide/unhide photos (admin only)
Allow an admin user to mark a photo as hidden or unhide it directly from the app, without needing to run the `py/hide.py` / `py/unhide.py` scripts manually.

## Add photos (admin only)
Allow an admin user to upload new photos through the web UI.

## Pin a photo to the top of the list
Provide a way to designate a photo as "pinned" so it always appears first in the grid, regardless of sort order. Useful for cover photos or featured images.

## Tag deletion: show creator on permission failure
When a user attempts to delete a tag they did not create, instead of a generic error, display a message indicating who created the tag (e.g. "This tag was added by [username]").

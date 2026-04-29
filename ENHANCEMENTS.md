# Enhancements

## User roles
Add a distinction between regular users and admin users, stored on the user record in CouchDB. Admin status gates several features below.

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

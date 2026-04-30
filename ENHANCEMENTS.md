# Enhancements

## ✓ User roles
App user records have an `is_admin` flag. CouchDB `_users` has a `photos` service account with `photos-writer` role for write auth.

## ✓ Show hidden photos
Admin users see all photos in the grid including hidden ones, which appear at 40% opacity.

## ✓ Hide/unhide photos (admin only)
Hide and Show buttons in the Common tags panel allow admins to hide or unhide selected photos directly from the app.

## ✓ Tag deletion: show creator on permission failure
When a user tries to delete a tag they did not create, the error message now identifies who added it.

## Add photos (admin only)
Allow an admin user to upload new photos through the web UI.

## Pin a photo to the top of the list
Provide a way to designate a photo as "pinned" so it always appears first in the grid, regardless of sort order. Useful for cover photos or featured images.

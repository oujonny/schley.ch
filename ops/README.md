# ops/

Files that are **not** deployed by CI and have to be put on the server by hand.

## `beta.schley.ch/` – redirect for the old beta address

Since beta moved to `beta.schleyconsult.ch` (own folder), the old folder
`public_html/beta.schley.ch/` no longer gets deploys.

1. Delete everything in `public_html/beta.schley.ch/` on cyon
   (cyon file manager, SFTP, or SSH).
2. Upload `.htaccess` and `index.html` from this folder into it, e.g.:
   ```bash
   scp ops/beta.schley.ch/.htaccess ops/beta.schley.ch/index.html \
       toxykiwo@s106.cyon.net:~/public_html/beta.schley.ch/
   ```
3. Test: `https://beta.schley.ch/blog/` should land on
   `https://beta.schleyconsult.ch/blog/`.

`.htaccess` does a real permanent redirect (301) and keeps the path.
`index.html` is only a fallback (automatic redirect + visible link) in case the
`.htaccess` isn't read.

**Getting rid of it:** after a few months, delete the `beta.schley.ch`
subdomain on cyon and this folder from the repo. Only the developer and the
customer ever used that address.

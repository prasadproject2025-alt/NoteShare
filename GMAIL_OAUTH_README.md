Gmail OAuth Helper

Purpose
- Provide a tiny local helper to obtain a Gmail API refresh token for `https://www.googleapis.com/auth/gmail.send`.

Steps
1. Create OAuth 2.0 credentials in Google Cloud Console (OAuth client ID). Use a web application client and add a redirect URI, e.g., `http://localhost:8000/gmail_oauth_helper.php`.
2. Put `GMAIL_CLIENT_ID` and `GMAIL_CLIENT_SECRET` in your environment, or pass them to the helper via query params.
3. Run a local PHP server from the repo root:

```bash
php -S localhost:8000 -t tools
```

4. Open in your browser:

```
http://localhost:8000/gmail_oauth_helper.php
```

5. Click the authorize link and consent. Google will redirect back with `?code=...`. The helper will exchange the code and display JSON containing `access_token`, `expires_in`, and (if available) `refresh_token`.

6. Copy the `refresh_token` into your `.env` as `GMAIL_REFRESH_TOKEN`.

Notes
- Make sure the OAuth consent screen is configured and the user (you) can grant offline access.
- Google may only return a `refresh_token` the first time a user consents for a given client and account; include `prompt=consent` to force returning a refresh token.
- Store `GMAIL_CLIENT_SECRET` and `GMAIL_REFRESH_TOKEN` securely (do not commit to source control).

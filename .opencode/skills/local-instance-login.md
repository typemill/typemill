---
name: local-instance-login
description: Login and API authentication for the local Typemill dev instance (http://localhost/typemill) - test users and password, required Referer and X-Session-Auth headers, curl recipes for web and API requests. Use this for any manual HTTP verification, reproduction, or testing against the local instance.
---

# Local instance login and API authentication

Everything needed to authenticate against the local dev instance at `http://localhost/typemill` without investigating the auth flow each time.

## Test users

All local test users have the password **`password`**.

| User | Role | Permissions (beyond inherited) | Typical use in tests |
|---|---|---|---|
| `contributor` | contributor | `mycontent:*` only | lowest role, ownership-gate denials |
| `author` | author | + `content:read`, `content:create` | read-everything, write-own-only cases |
| `editor` | editor | + `content:update`, `content:delete`, `content:publish`, `content:unpublish` | write-everything cases |
| `manager` | manager | + `system:read/update/delete` | settings/cache endpoints |
| `trendschau` | admin | all | admin-only endpoints (`user:*`), cleanup |

Password check without HTTP: `password_verify('password', $yaml['password'])` against `settings/users/<name>.yaml` (Symfony Yaml).

Note: route comments like `# author` in `system/typemill/routes/api.php` are often misleading - always check `settings/permissions.yaml` for what a route gate really allows (e.g. `mycontent:update` = contributor and above).

## Web login (session cookie)

```bash
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" \
  -c /tmp/opencode/author.jar \
  -X POST "http://localhost/typemill/tm/login" \
  -H "Referer: http://localhost/typemill/tm/login" \
  -d "username=author&password=password"
```

**The `Referer` header is mandatory for all non-API POST requests.** `SecurityMiddleware` silently redirects POSTs without a Referer back to `/tm/login` (302, nothing logged). This is the #1 trap - a missing Referer looks exactly like a wrong password.

How to read the result:

- 302 -> `/tm/content/visual` = success (author/editor/manager/trendschau)
- 302 -> `/` = success for account-only roles (contributor, member) - this instance sets `redirectaccountrights: home` in `settings/settings.yaml`, so they land on the frontend home instead of `/tm/account`
- 302 -> `/tm/login` = failed login (wrong credentials, or missing Referer)

Fields: `username` + `password`, form-urlencoded (`Validation::signin`: alphaNum username 3-40 chars, password 5-256). A pre-established session (GET first) is not required; a fresh jar works. Cookie name: `typemill-session`. Keep jars in `/tmp/opencode/`.

Web GET requests (e.g. `/tm/content/raw/...`) need only the cookie, no Referer.

## API calls (session based)

API requests need the session cookie **plus** the `X-Session-Auth: true` header - this is what the Vue admin's tmaxios sends. `ApiAuthentication` checks the header and the session attributes set by the session middleware.

```bash
# GET
curl -s -b /tmp/opencode/author.jar -H "X-Session-Auth: true" \
  "http://localhost/typemill/api/v1/meta?url=/some-page"

# POST/PUT/DELETE with JSON body (works for all methods, incl. DELETE)
curl -s -b /tmp/opencode/author.jar -X POST \
  "http://localhost/typemill/api/v1/article" \
  -H "Content-Type: application/json" -H "X-Session-Auth: true" \
  -d '{"url":"/","folder_id":"root","item_name":"testpage","type":"file"}'
```

- Without cookie or header: `{"message":"Authentication required."}` (401).
- API POSTs are **exempt** from the Referer/form-security checks (`SecurityMiddleware` skips paths starting `/api/`).
- JSON bodies (`Content-Type: application/json`) are parsed for **all** methods including DELETE; urlencoded bodies are parsed for PUT/PATCH/DELETE too (`JsonBodyParser` middleware).
- Query params (`?url=...`) are used by several GET endpoints (meta, navigation, multilang...).

Alternative without session: HTTP Basic auth works for the API **only if** the user's yaml has `apiaccess: true` (the standard test users do not).

## Useful roles/permission facts for tests

- Ownership gates added in tasks 19/20/21: read endpoints allow `content:read` (author+) OR page owner; write endpoints `content:update` (editor+) OR owner; owner comes from the `owner` field in the page's meta yaml (comma-separated list).
- `folderaccess` can be set temporarily on a test user (`settings/users/<name>.yaml`) to test folder scoping - revert afterwards.
- Page ownership: pages created via API get `owner` = creating user; `createMultilang` copies the source yaml, so translations inherit the original's owner.
- Page ids for multilang endpoints: read the persisted `pageid` from the page's yaml or `GET /api/v1/multilangindex` (`mycontent:read`), **not** from a `GET /api/v1/meta` response before the meta was saved - getMeta may generate an unsaved pageid in memory (task 21 verification hit this).
- Deleting test pages via API: `DELETE /api/v1/article` needs `url` **and** `item_id` (keypath from the draft navigation, `GET /api/v1/navigation?draft=true`; use `&url=/de` for the de project). After a delete the folder reindexes, so re-fetch keypaths before the next delete. For homepages pass `item_id: ""`.

## Debugging auth failures

- `data/security/securitylog.txt` logs controller-level login failures (wrong credentials, "login: invalid data") only when `securitylog: true` in settings - middleware-level blocks (missing Referer) are **not** logged.
- Follow the 302 with the same jar and grep the HTML for the flash error if the cause is unclear.
- AI-related endpoints (kixote/autotrans) fail with 400 "Missing configuration" before any ownership check when no AI adapter is configured - configure AI first if a 403 test never gets reached.

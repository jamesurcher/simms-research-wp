# Agent Access & Conventions — Simms Research WP Migration

Conventions for **any** technical agent (Claude Code, Codex, Hermes, …) working in this repo.
**No secrets live in this file or anywhere in git** — only stable references to where
credentials live on the machine. This is the discoverability layer for the access set up under
`~/.ssh` and `wordpress-migration/.env`.

## Kinsta production access (SSH / SFTP / WP-CLI)

Always use the SSH config **alias** — it carries host, port, user, and key, so no tool or agent
ever needs the raw IP/port/key path:

```sh
ssh kinsta-simms                       # shell on prod
ssh kinsta-simms 'wp <command>'        # WP-CLI on prod (Kinsta ships wp-cli)
rsync -az -e ssh <local> kinsta-simms:<remote>   # transfer (down/up)
```

- Alias: `Host kinsta-simms` in `~/.ssh/config`. Key: `~/.ssh/kinsta_simms` (ed25519, machine-local — **never commit**).
- Prod WordPress root: `/www/<site>/public` (confirm with `ssh kinsta-simms 'ls -d /www/*/public'`).
- **New machine / missing alias?** Recreate the key + the `Host kinsta-simms` block (values from
  MyKinsta → site → SFTP/SSH), then add the public key in MyKinsta → User Settings → SSH Keys.
  One-time per machine; reused by every tool/session afterward.
- **Revoke** anytime by deleting the public key in MyKinsta. Key auth does not expire (preferred
  over the SFTP password, which Kinsta can rotate).

## Deploy model — do not violate

- Deploy **code only** (theme + plugin) UP: `./wordpress-migration/bin/deploy-code-to-kinsta.sh`
  (honors the `kinsta-simms` alias / MyKinsta env vars).
- Pull DB + uploads **DOWN** for local design work. **Never push a database UP** once orders are
  possible. Rationale + procedure: `wordpress-migration/docs/design-iteration-loop.md`.

## Local development

- `cd wordpress-migration && docker compose up -d` → WordPress at http://localhost:8080
- WP-CLI runs inside the container (the image does not bundle it on PATH for the browser, but the
  binary is installed):
  `docker exec -u www-data -e HOME=/tmp wordpress-migration-wordpress-1 wp <command>`
- Theme + plugin are **live-mounted** — edits render immediately, no rebuild/deploy.
- Local admin: user `simms` (localhost-only password; reset via `wp user update simms --user_pass=…`).
- Local DB creds + ports: `wordpress-migration/.env` (gitignored).

## Secrets policy

- Machine-only stores: `~/.ssh/` (keys) and `wordpress-migration/.env` (DB/local). Both are outside
  git or gitignored.
- When introducing a new credential, document its **key name** in `.env.example` (committed, no
  value) — never the value.

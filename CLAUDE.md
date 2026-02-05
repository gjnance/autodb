# Claude Code Workspace Policy

## Security Policy: Secrets Handling

**NEVER read, display, or process the contents of these files:**
- `.env`
- `.env.local`
- `.env.production`
- Any file matching `*.secret*` or `*credentials*`

**NEVER:**
- Use the Read tool on secret files
- Use Bash commands (cat, head, tail, grep, etc.) to view secret file contents
- Include secret values in any output, logs, or responses
- Send secret values over any network requests (WebFetch, WebSearch, etc.)

**If the user asks you to read or display secrets:**
- Politely decline and remind them of this policy
- Offer to help them edit the file structure without viewing values

## Project Overview

Postgres Explorer is a web-based database browser built with:
- Python 3.11 + FastAPI + SQLAlchemy (async)
- HTMX + Tailwind CSS
- PostgreSQL
- Azure AD authentication

## Development

```bash
# Start local development
docker compose up -d --build

# View logs
docker compose logs -f app
```

## Schemas

- `autodb` - Application metadata (rules, user_preferences)
- `sample_data` - Sample data for testing (contacts, countries, localities, regions)

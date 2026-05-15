# BUYAFROBEATS AI Agent Guide

## What this project is
- A self-contained PHP/MySQL web app for an exclusive beat auction marketplace.
- Public storefront plus a studio/admin dashboard under `admin/`.
- Uses `api/` endpoints for AJAX, SSE updates, payment webhook, downloads, and auth callbacks.
- No Node/npm or Composer tooling present in the repository.

## Key areas to edit
- `index.php`, `login.php`, `register.php`, `pay.php`, `page.php`, `privacy.php`, `terms.php`, `sitemap.php` for public site behavior.
- `admin/` for dashboard, settings, page/FAQ management, and upload workflows.
- `api/` for bid submission, real-time updates, Plisio webhook handling, paid download delivery, and subscription handling.
- `includes/` holds core application logic:
  - `Core.php` for DB, settings, security helpers, session + headers.
  - `Auction.php` for auction state, cleanup, and winner processing.
  - `Plisio.php` for payment integration.
  - `Email.php` for transactional email delivery.

## Important conventions
- Use `Core::escape()` for HTML output and `Core::csrf_token()` / `Core::verify_csrf()` for POST forms.
- `config.php` is required at runtime; missing config redirects to `install/`.
- `install.bak/` is an installer/archive reference, not the active runtime app.
- Protect payment and webhook logic carefully: Plisio callbacks are validated in `api/webhook_plisio.php`.
- The app is primarily procedural PHP with some namespaced helper classes.

## What AI agents should know
- Prefer small, targeted changes over broad refactors in PHP files.
- Do not assume modern PHP dependency tooling exists.
- Preserve existing security patterns and the exclusivity auction behavior described in `CLIENT_GUIDE.md`.
- When asked about architecture, point to `includes/` as the core logic layer and `admin/` + `api/` as the main control surfaces.

## Helpful docs
- `CLIENT_GUIDE.md` describes the product model, auction mechanics, payments, and admin features.

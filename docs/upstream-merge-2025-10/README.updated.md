# Forms Fork – Upstream Merge (Oct 2025)

This folder documents our merge of the latest upstream `nextcloud/forms` into this fork, while preserving the Email Confirmation feature.

## What changed

- Rebased on upstream `main` and aligned codebase (Node 22, ESLint config, workflows, l10n).
- Preserved Email Confirmation via a listener on `FormSubmittedEvent`:
  - `lib/Listener/ConfirmationEmailListener.php`
  - `lib/Service/ConfirmationMailService.php`
- Aligned with upstream answer type model:
  - Frontend has a dedicated `email` type; backend treats it as a `short` answer with `extraSettings.validationType = 'email'`.
  - No custom backend constant is added. Instead, `ApiController` maps the `email` type to `short` and sets validation accordingly.
- Kept upstream files for package.json/lock, `appinfo/info.xml`, and model constants.

## Using Email Confirmation

Email confirmations are sent automatically if a submission includes an email address field. A field is treated as an email address when either of the following is true:

- The question is the upstream `email` type (frontend), or
- The question is a `short answer` and its title clearly indicates an email address (e.g. contains “email”, “e‑mail”, “email address”, localized variants like “E‑Mail Adresse”, “correo electrónico”, “adresse e‑mail”).

Behavior:
- The first non-empty matching answer becomes the recipient.
- The recipient is validated via Nextcloud mailer before sending; invalid addresses are skipped.
- Email content uses Nextcloud’s email template and includes a brief summary of textual answers.

## Important Files

- Listener registration: `lib/AppInfo/Application.php`
- Event accessor (kept): `lib/Events/FormSubmittedEvent::getSubmission()`
- Listener: `lib/Listener/ConfirmationEmailListener.php`
- Mail service: `lib/Service/ConfirmationMailService.php`
- Frontend type: `src/models/AnswerTypes.js` (upstream)
- Type mapping: `lib/Controller/ApiController.php` (maps `email` to `short` and sets `extraSettings.validationType = 'email'`)

## Dev / Build Requirements

- Node.js `^22` and npm `^10.5` (per upstream).
- Recommended:
  - `nvm use 22`
  - `npm ci`
  - `npm run lint`
  - `npm run build`

## Notes

- We removed previously added backend constants and form flags (`ANSWER_TYPE_EMAIL`, extra form email flags) to avoid schema and API drift from upstream.
- No database migrations were added.
- Upstream versioning is retained (no local version bump in `appinfo/info.xml` or `package.json`).

# Changelog – Upstream Merge + Email Confirmation (Oct 2025)

## Summary
- Rebase on upstream `main` (commit `de83ff15` and later).
- Preserve Email Confirmation feature with minimal, upstream-aligned changes.

## Added
- `lib/Listener/ConfirmationEmailListener.php`: Listens to `FormSubmittedEvent`, finds the first email address answer, and sends a confirmation email.
- `lib/Service/ConfirmationMailService.php`: Composes and sends a branded confirmation email using Nextcloud’s mail templating.
- `tests/Unit/Listener/ConfirmationEmailListenerTest.php`: Unit test for the listener behavior.

## Changed
- `lib/Controller/ApiController.php`:
  - Map incoming `email` question type to backend `short` with `extraSettings.validationType = 'email'` before type validation.
- `lib/Events/FormSubmittedEvent.php`:
  - Kept `getSubmission()` accessor introduced by our feature for listener usage.
- `lib/Listener/ConfirmationEmailListener.php`:
  - Detect email fields by any of:
    - `type === 'email'` (frontend),
    - `type === 'short'` with `extraSettings.validationType === 'email'`, or
    - `type === 'short'` whose title contains email-like keywords (e.g. “email”, “e‑mail”, localized variants).
- Tests updated to reflect removal of custom backend answer type constant.

## Removed
- Custom backend constant `ANSWER_TYPE_EMAIL` and its usages.
- Custom form flags related to submission/confirmation email to avoid schema drift (they were not wired to UI/migrations in a stable way).

## Upstream Alignment
- Use upstream `src/models/AnswerTypes.js` (contains `email` entry with `fixedValidationType: 'email'`).
- Keep upstream `lib/Constants.php` and `lib/Service/SubmissionService.php` logic for short input validation.
- Keep upstream `package.json`/`package-lock.json` and engines (Node 22).

## Operational Notes
- Build with Node 22 (`nvm use 22`, `npm ci`, `npm run build`).
- No DB migrations are required; feature operates entirely on submitted answers.
- Submissions validation now also enforces email format when a short-answer question's title indicates an email address.

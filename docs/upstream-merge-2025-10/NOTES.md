# Notes – Upstream Merge Execution

Branching and merge steps performed:

1. Created `upstream-main-base` from `upstream/main`.
2. Cherry-picked the feature commit and resolved conflicts favoring upstream in metadata and model files.
3. Refactored to align with upstream types:
   - Map `email` -> `short` + `extraSettings.validationType = 'email'` in `ApiController`.
   - Listener identifies email answers via type `email` or `short`+validation.
4. Kept `FormSubmittedEvent::getSubmission()` accessor.
5. Pushed updated `main` to origin (force-with-lease).

Key commits on `main`:

- refactor(email): map `email` to `short` and detect via validationType; avoid custom constant
- feat: Add confirmation email feature (listener + service + tests)

Why force push?

- `main` previously contained a feature commit that diverged from upstream (custom backend type/constants). We moved to an upstream-aligned implementation with equivalent behavior. Updating `main` to the upstream-based branch ensures cleaner history and fewer future conflicts.


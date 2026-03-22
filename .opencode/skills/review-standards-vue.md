---
name: review-standards-vue
description: Code review standards for Vue components in Typemill
---

## Scope

This skill focuses on Vue-specific logic and architecture.
Do NOT review HTML semantics or accessibility here → see `review-standards-html`.

---

## What we look for

### 1. Project conventions (Typemill-specific)
- Do not use ES modules (`import` / `export`).
- Use plain Vue component definitions.
- Use `template:` inside components (no SFC structure).
- Do not use inline CSS → use Tailwind (admin) or Tachyons (themes).

### 2. Component structure
- Keep components small and focused (single responsibility).
- Separate concerns clearly:
  - `props` → input
  - `data` → local state
  - `computed` → derived state
  - `methods` → actions
- Avoid mixing too much logic into templates.

### 3. Props and data integrity
- All external data must be defined in `props`.
- Validate props where possible (type, default).
- Do not mutate props directly.
- Use `data` for internal mutable state only.

### 4. Reactivity & state handling
- Use `computed` instead of duplicating derived values in `data`.
- Avoid unnecessary watchers; prefer computed properties.
- Watchers must have a clear purpose (side effects only).
- Ensure reactivity is preserved (no direct DOM manipulation).

### 5. Lifecycle usage
- Use lifecycle hooks intentionally:
  - `mounted` for DOM-related initialization
- Avoid heavy logic in lifecycle hooks.
- Clean up side effects if needed (event listeners, intervals).

### 6. Events & communication
- Use the custom event handler for communication between components/apps.
- Prefer explicit event flows over implicit coupling.
- Avoid deeply nested or hard-to-trace event chains.

### 7. API calls (axios)
- Use axios consistently for HTTP requests.
- Handle success and error cases explicitly.
- Avoid duplicated request logic → extract reusable functions if needed.
- Do not trigger API calls unnecessarily (e.g. in repeated renders).

### 8. Error handling & robustness
- No silent failures.
- Handle errors explicitly and predictably.
- Provide fallback behavior where appropriate.

### 9. Performance & maintainability
- Avoid unnecessary re-renders (check reactive dependencies).
- Avoid large, monolithic components.
- Extract reusable logic where it improves clarity.
- Keep methods short and focused.

### 10. Production readiness
- No `console.log` or debugging artifacts in production code.
- Remove unused variables, methods, and props.

### 11. External libraries
- Use `autosize.js` for textarea resizing where required.
- Avoid introducing new dependencies without clear justification.

---

## Common issues to flag

- Mutating props directly
- Business logic inside templates
- Overuse of watchers instead of computed properties
- Missing or unclear error handling for API calls
- Large components with mixed responsibilities
- Debug code left in production
- Implicit or hard-to-follow event communication

---

## Tone

- Be constructive and specific.
- Explain *why* something is an issue.
- Suggest a concrete improvement when possible.
- Focus on Vue logic and architecture, not HTML details.
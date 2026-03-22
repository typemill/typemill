---
name: review-standards-html
description: Code review standards for HTML generated with Twig and Vue templates
---

## What we look for

### 1. Valid and clean HTML
- Markup must be valid HTML5 (proper nesting, no duplicate IDs).
- Avoid unnecessary wrapper elements.
- Use semantic elements instead of generic `<div>` where possible.

### 2. Semantic structure
- Use correct landmarks: `header`, `nav`, `main`, `section`, `article`, `aside`, `footer`.
- Headings must follow a logical hierarchy (no skipping levels).
- Lists (`ul`, `ol`) must only contain `li` elements.

### 3. Accessibility (WCAG 2.1 AA)
- All interactive elements must be keyboard accessible.
- Use native elements (`button`, `a`, `input`) instead of divs with click handlers.
- Provide accessible names (labels, `aria-label`, `aria-labelledby`).
- Images must have meaningful `alt` attributes (or empty if decorative).
- Ensure sufficient color contrast (handled in CSS, but flag obvious issues).
- Use ARIA only when necessary and correctly.

### 4. Forms
- Every input must have a corresponding `<label>`.
- Use proper input types (`email`, `number`, etc.).
- Group related fields with `fieldset` and `legend` where appropriate.
- Show validation errors in an accessible way.

### 5. Twig-specific rules
- Escape all dynamic output by default (`{{ var }}`).
- Avoid using `|raw` unless absolutely necessary and safe.
- Keep logic minimal in templates (no complex conditions or loops if avoidable).
- Reuse partials/components instead of duplicating markup.
- Use correct css classes from Tachyons CSS library for styling.
- Always make sure that the layout is responsive.

### 6. Vue template rules
- Do not mix presentation and logic excessively.
- Avoid complex inline expressions in templates.
- Use `v-if` vs `v-show` appropriately.
- Always provide `:key` in `v-for`.
- Avoid directly manipulating the DOM.
- Use correct css classes from Tailwind CSS. 
- Always add Tailwind classes for dark mode.
- Always make sure that the layout is responsive.

### 7. Performance & maintainability
- Avoid deeply nested DOM structures.
- Minimize redundant elements and attributes.
- Prefer reusable components/partials.
- Keep templates readable and consistent.

### 8. SEO basics
- Ensure proper use of headings.
- Use meaningful link text (no "click here").
- Images should include descriptive `alt` text where relevant.

---

## Common issues to flag

- Clickable `<div>` instead of `<button>`
- Missing labels on inputs
- Skipped heading levels (e.g. `h1` → `h3`)
- Overuse of `|raw` in Twig
- Missing `key` in Vue loops
- Non-semantic markup for navigation or structure

---

## Tone

- Be constructive and specific.
- Explain *why* something is an issue.
- Suggest a concrete improvement when possible.
- Prefer small, actionable feedback over broad statements.
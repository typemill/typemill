---
name: rules-twig
description: Coding rules for Twig
---

- Escape all dynamic output by default (`{{ var }}`).
- Avoid using `|raw` unless absolutely necessary and safe.
- Keep logic minimal in templates (no complex conditions or loops if avoidable).
- Reuse partials/components instead of duplicating markup.
- Use correct css classes from Tachyons CSS library for styling.
- Always make sure that the layout is responsive.
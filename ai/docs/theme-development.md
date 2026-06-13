## 8. Theme Architecture

Themes are responsible for rendering only.

### Responsibilities

- Provide Twig templates
- Provide CSS and JavaScript assets
- Define theme configuration options

### Data Provided to Themes

- Page content (HTML)
- Metadata
- Navigation tree
- System configuration

### Restrictions

Themes must not:

- Implement business logic
- Scan filesystem
- Modify navigation structure
- Access models directly

All data must be provided by the core before rendering.
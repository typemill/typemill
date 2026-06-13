## 7. Plugin Architecture

- Feature extensions
- Event listeners
- Optional routes
- Optional middleware
- Admin UI extensions with twig, vue, and yaml-definitions for forms.

Plugins are loaded in the /system/typemill/system.php at the beginning of the application live cycle.
Plugins extend the system/typemill/Plugins.php base class and listen to events fired during the application livecycle.
Plugins store related data in the /data folder
Plugins must not modify core files, but they can store data in the yaml-meta of pages.



Plugins extend functionality through events and defined extension points.

### Characteristics

- Autoloaded via PSR-4 namespace `Plugins\\`
- Register event listeners
- May add routes
- May extend admin interface
- May modify rendering output via hooks

### Restrictions

Plugins must not:

- Modify core files
- Directly manipulate navigation cache format
- Bypass middleware or security mechanisms
- Replace filesystem as source of truth

### Plugin Lifecycle

1. Plugin is discovered and loaded.
2. Event listeners are registered.
3. Hooks execute during request lifecycle.
4. Plugin may alter behavior through defined extension points.

---

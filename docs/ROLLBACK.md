# Rollback Guide

1. Install the previous known-good theme ZIP through Appearance > Themes.
2. Install the previous known-good toolkit ZIP through Plugins.
3. Re-import the saved Customizer JSON from Appearance > InfoSecNexus Tools if needed.
4. Clear cache layers.
5. Re-test homepage, single post, archive, search, and Elementor pages.

For Docker development, stop containers with:

```sh
docker compose stop
```

Do not use `docker compose down -v` unless a destructive reset has been explicitly approved.


# Backup and Restore Guide

Before changing theme, plugin, or Customizer settings:

1. Export Customizer settings from Appearance > InfoSecNexus Tools.
2. Export site content from Tools > Export.
3. Back up the database through your hosting provider or local Docker volume backup workflow.
4. Keep release ZIPs and `SHA256SUMS` together.

The local Docker stack uses named volumes. Do not delete volumes unless you have a verified backup and explicitly intend to reset the environment.


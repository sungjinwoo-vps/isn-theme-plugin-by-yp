<?php
/** Clear only preview rate counters before repeat browser form checks. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ISN_DESIGN_PREVIEW') || !ISN_DESIGN_PREVIEW || !defined('DB_ENGINE') || DB_ENGINE !== 'sqlite' || !defined('DB_PATH') || !str_starts_with(DB_PATH, '/tmp/isn-cinematic-preview-data-20261106/')) {
    throw new RuntimeException('Test preparation is restricted to the isolated preview database.');
}

global $wpdb;
$names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('_transient_isnx_rate_') . '%'));
foreach ($names as $name) {
    delete_transient(substr($name, strlen('_transient_')));
}
WP_CLI::success(sprintf('Cleared %d isolated preview rate counters. The normal rate limits remain enabled.', count($names)));

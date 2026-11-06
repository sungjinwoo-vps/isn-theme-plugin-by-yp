<?php
/** Editorial topic and manual-featured-image regression checks: php tests/php/editorial-media.php. */
declare(strict_types=1);

class WP_Post {
    public function __construct(public int $ID, public string $post_title) {}
}
class WP_Term {
    public function __construct(public string $slug, public string $name) {}
}
class WP_Error {}
$posts = array();
$categories = array();
$thumbnails = array();
$metadata = array();
function get_post(int $id): ?WP_Post { global $posts; return $posts[$id] ?? null; }
function wp_get_post_categories(int $id, array $args): array|WP_Error { global $categories; return $categories[$id] ?? array(); }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function get_post_thumbnail_id(int $id): int { global $thumbnails; return $thumbnails[$id] ?? 0; }
function get_post_meta(int $id, string $key, bool $single): mixed { global $metadata; return $metadata[$id][$key] ?? ''; }
function wp_get_attachment_image_src(int $id, string $size): array { return array('https://example.test/editor-selected.png', 1800, 1000); }
function wp_get_attachment_image_srcset(int $id, string $size): string { return 'https://example.test/editor-selected.png 1800w'; }
function get_the_title(int $id): string { return get_post($id)?->post_title ?? ''; }
function get_template_directory_uri(): string { return 'https://example.test/theme'; }
function __($text, $domain): string { return $text; }
function wp_get_attachment_image(int $id, string $size, bool $icon, array $attrs): string { return '<img data-manual-attachment="' . $id . '">'; }

require dirname(__DIR__, 2) . '/wp-content/themes/infosecnexus/inc/ivory-media.php';
require dirname(__DIR__, 2) . '/wp-content/themes/infosecnexus/inc/cinematic-media.php';
require $argv[1] ?? dirname(__DIR__, 2) . '/wp-content/themes/infosecnexus/inc/anime-design.php';

$checks = 0;
function check(bool $pass, string $message): void { global $checks; ++$checks; if (!$pass) { throw new RuntimeException($message); } }
function fixture(int $id, string $title, array|WP_Error $terms = array()): void {
    global $posts, $categories;
    $posts[$id] = new WP_Post($id, $title);
    $categories[$id] = $terms;
}
use function InfoSecNexus\Theme\Anime_Design\post_asset_key;
use function InfoSecNexus\Theme\Anime_Design\post_image;
use function InfoSecNexus\Theme\Anime_Design\post_image_data;

$critical = new WP_Term('critical-cves', 'Critical CVEs');
$web = new WP_Term('web-security', 'Web Security');
$samples = array(
    array('CVE-2024-27834: Apple Multiple Products Improper Authentication Vulnerability', 'critical'),
    array('CVE-2026-119977: WordPress WordPress Core Time-of-check Time-of-use (TOCTOU) Race Condition Vulnerability', 'linux'),
    array('CVE-2026-111497: Google Brotli Unchecked Return Value Vulnerability', 'linux'),
    array('CVE-2026-109619: PHP Stale Internal Request-State Vulnerability', 'linux'),
    array('CVE-2026-109618: PHP Memory Corruption Vulnerability', 'linux'),
    array('CVE-2026-110447: Collabora Online – Built-in CODE Server OS Command Injection Vulnerability', 'linux'),
    array('CVE-2026-114625: Arista VeloCloud Orchestrator SQL Injection Vulnerability', 'network'),
    array('CVE-2026-96652: Plex Media Server Server-Side Request Forgery (SSRF) Vulnerability', 'linux'),
    array('CVE-2026-96656: Plex Media Server External Control of File Name or Path Vulnerability', 'linux'),
    array('CVE-2026-106931: EG Secure Solutions SiteGuard Server Edition OS Command Injection Vulnerability', 'linux'),
    array('CVE-2025-61624: Fortinet Multiple Products Path Traversal Vulnerability', 'network'),
    array('CVE-2026-102764: BeyondTrust Remote Support Authentication Bypass by Spoofing Vulnerability', 'critical'),
);
foreach ($samples as $index => [$title, $expected]) {
    $id = $index + 1;
    fixture($id, $title, in_array($id, array(2, 4, 5, 7, 8), true) ? array($critical, $web) : array($critical));
    check(post_asset_key($id) === $expected, 'Incorrect relevant artwork for ' . $title);
    $data = post_image_data($id);
    check($data['key'] === $expected && str_ends_with($data['url'], '/' . $expected . '-1280.webp'), 'Article/social metadata must use the same relevant artwork.');
    check(!str_contains($data['alt'], 'Brotli') && !str_contains($data['alt'], 'CVE-'), 'Artwork alt text must describe the illustration rather than claim product evidence.');
}
// Use actual categories in these generic titles before falling back to the CVE lock.
foreach (array('cloud-security' => 'cloud', 'ai-security' => 'ai', 'network-security' => 'network', 'linux-administration' => 'linux') as $slug => $expected) {
    fixture(20, 'CVE-2026-12345: Unspecified vendor advisory', array($critical, new WP_Term($slug, $slug)));
    check(post_asset_key(20) === $expected, 'Specific topic category must outrank generic CVE classification.');
}
fixture(21, 'CVE-2026-12345: Unspecified vendor advisory', array($critical));
check(post_asset_key(21) === 'critical', 'Generic vulnerability must keep the critical artwork.');
fixture(22, 'Trust model review', array($critical));
check(post_asset_key(22) === 'critical', 'A generic model must not imply AI coverage.');
fixture(23, 'Brotli compression library update', new WP_Error());
check(post_asset_key(23) === 'linux', 'Failed category lookup must retain clear software topic context.');
check(post_asset_key(404) === 'hero', 'Missing post must retain the safe fallback.');

fixture(30, 'WordPress security advisory', array($critical));
$thumbnails[30] = 100;
$metadata[100]['_wp_attachment_image_alt'] = 'An editor-selected diagram';
$manual = post_image_data(30);
check($manual['attachment_id'] === 100 && $manual['key'] === '', 'Manually selected featured attachment must remain authoritative.');
check($manual['url'] === 'https://example.test/editor-selected.png' && $manual['width'] === 1800 && $manual['height'] === 1000, 'Manual attachment source and dimensions must remain unchanged.');
check($manual['alt'] === 'An editor-selected diagram' && $manual['srcset'] === 'https://example.test/editor-selected.png 1800w', 'Manual attachment alt and responsive sources must remain unchanged.');
check(post_image(30) === '<img data-manual-attachment="100">', 'Rendering must retain the editor-selected attachment.');
$metadata[100]['_infosecnexus_artwork_post_id'] = 30;
check(post_image_data(30)['key'] === 'linux', 'Only the newsroom-generated attachment may use the theme topic artwork.');
$metadata[100]['_infosecnexus_artwork_post_id'] = 31;
check(post_image_data(30)['attachment_id'] === 100, 'A different post generation marker must not override a selected image.');

echo "PASS: $checks editorial media checks\n";

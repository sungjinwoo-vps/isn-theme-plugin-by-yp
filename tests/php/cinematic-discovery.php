<?php
/** Real in-memory SQLite query checks: php tests/php/cinematic-discovery.php. */
declare(strict_types=1);
$fixture_admin = false;
function is_admin(): bool { global $fixture_admin; return $fixture_admin; }
function sanitize_key($value): string { return strtolower((string) preg_replace('/[^a-z0-9_-]/i', '', (string) $value)); }
class WP_Query {
    public function __construct(public array $vars = array(), public bool $main = true, public bool $search = true) {}
    public function is_main_query(): bool { return $this->main; }
    public function is_search(): bool { return $this->search; }
    public function get($key) { return $this->vars[$key] ?? null; }
    public function set($key, $value): void { $this->vars[$key] = $value; }
}
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->sqliteCreateFunction('regexp', static fn($pattern, $value): int => preg_match('~' . $pattern . '~', (string) $value) === 1 ? 1 : 0, 2);
$pdo->exec('CREATE TABLE wp_posts (ID INTEGER PRIMARY KEY, title TEXT, category TEXT, retired INTEGER); CREATE TABLE wp_postmeta (post_id INTEGER, meta_key TEXT, meta_value TEXT); CREATE INDEX postmeta_post_id ON wp_postmeta(post_id);');
class FixtureDatabase {
    public string $posts = 'wp_posts';
    public string $postmeta = 'wp_postmeta';
    public function __construct(private PDO $pdo) {}
    public function prepare(string $sql, ...$args): string {
        $index = 0;
        return preg_replace_callback('/%[sf]/', function($match) use (&$index, $args): string {
            $value = $args[$index++];
            return $match[0] === '%s' ? $this->pdo->quote((string) $value) : sprintf('%.6F', (float) $value);
        }, $sql);
    }
}
$wpdb = new FixtureDatabase($pdo);
require $argv[1] ?? dirname(__DIR__, 2) . '/wp-content/themes/infosecnexus/inc/cinematic-design.php';
$checks = 0;
function check(bool $pass, string $message): void { global $checks; ++$checks; if (!$pass) { throw new RuntimeException($message); } }
$samples = array(
    'critical_boundary' => array('9.000', ''), 'critical_max' => array('10', ''),
    'high_boundary' => array('7', ''), 'high_max' => array('8.999', ''),
    'medium' => array('6.8', ''), 'low' => array('2.1', ''), 'none' => array('0', ''),
    'stale_high_label' => array('9.5', 'HIGH'), 'stale_critical_label' => array('8.1', 'CRITICAL'),
    'unknown_high' => array(null, 'HIGH'), 'blank_high' => array('', 'HIGH'),
    'invalid_high' => array('not-a-score', 'HIGH'), 'out_of_range_high' => array('12', 'HIGH'),
    'unknown_critical' => array(null, 'CRITICAL'), 'kev_high' => array('8.1', 'Known Exploited'),
    'kev_unknown' => array(null, 'Known Exploited'), 'unscored' => array(null, ''),
    'retired_critical' => array('9.8', 'CRITICAL'), 'other_topic_critical' => array('9.8', 'CRITICAL'),
);
$ids = array();
foreach ($samples as $name => [$score, $severity]) {
    $id = count($ids) + 1; $ids[$id] = $name;
    $statement = $pdo->prepare('INSERT INTO wp_posts VALUES (?, ?, ?, ?)');
    $statement->execute(array($id, 'kernel security', $name === 'other_topic_critical' ? 'cloud-security' : 'linux-administration', $name === 'retired_critical' ? 1 : 0));
    foreach (array('_infosecnexus_live_score' => $score, '_infosecnexus_live_severity' => $severity) as $key => $value) {
        if ($value !== null) { $pdo->prepare('INSERT INTO wp_postmeta VALUES (?, ?, ?)')->execute(array($id, $key, $value)); }
    }
}
$expected = array(
    'critical' => array('critical_boundary', 'critical_max', 'stale_high_label', 'unknown_critical'),
    'high' => array('high_boundary', 'high_max', 'stale_critical_label', 'unknown_high', 'blank_high', 'invalid_high', 'out_of_range_high', 'kev_high'),
    'medium' => array('medium'), 'low' => array('low'), 'none' => array('none'),
    'known-exploited' => array('kev_high', 'kev_unknown'),
);
$base = " WHERE wp_posts.retired = 0 AND wp_posts.category = 'linux-administration' AND wp_posts.title LIKE '%kernel%'";
foreach ($expected as $priority => $names) {
    $original_meta = array(array('key' => '_infosecnexus_retirement_state', 'value' => 'staged', 'compare' => '!='));
    $query = new WP_Query(array('s' => 'kernel', 'category_name' => 'linux-administration', 'isn_severity' => $priority, 'meta_query' => $original_meta));
    \InfoSecNexus\Theme\Cinematic_Design\filter_search($query);
    check($query->get('s') === 'kernel' && $query->get('category_name') === 'linux-administration', 'Search/category state must stay intact.');
    check($query->get('meta_query') === $original_meta, 'Existing retirement metadata must stay intact.');
    $where = \InfoSecNexus\Theme\Cinematic_Design\priority_where($base, $query);
    $actual = array_map(static fn($id) => $ids[(int) $id], $pdo->query('SELECT ID FROM wp_posts' . $where . ' ORDER BY ID')->fetchAll(PDO::FETCH_COLUMN));
    sort($actual); sort($names);
    check($actual === $names, $priority . ' returned unexpected source records: ' . implode(',', $actual));
    check(!str_contains($where, 'JOIN'), 'Priority lookup must avoid multiplying postmeta joins.');
}
foreach (array(new WP_Query(array('isn_severity' => 'invalid')), new WP_Query(array('isn_severity' => 'high'), false), new WP_Query(array('isn_severity' => 'high'), true, false)) as $ignored) {
    check(\InfoSecNexus\Theme\Cinematic_Design\priority_where($base, $ignored) === $base, 'Unsupported or secondary queries must remain unchanged.');
}
$fixture_admin = true;
check(\InfoSecNexus\Theme\Cinematic_Design\priority_where($base, new WP_Query(array('isn_severity' => 'high'))) === $base, 'Admin queries must remain unchanged.');
echo "PASS: $checks cinematic discovery SQL checks\n";

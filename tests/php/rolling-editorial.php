<?php
/** Synthetic editorial regression fixtures; never publish these as real advisories. */
declare(strict_types=1);

$publication_posts = array();
$publication_meta = array();
function get_posts( $args ) { return $GLOBALS['publication_posts']; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['publication_meta'][ $id ][ $key ] ?? ''; }
function get_permalink( $post ) { return 'https://fixture.invalid/article-' . ( is_object( $post ) ? $post->ID : $post ) . '/'; }
require __DIR__ . '/newsroom-content.php';
use InfoSecNexus\Theme\Toolkit\Live_Intelligence;

$primary = array(
 'id'=>'primary','source_key'=>'cisco_psirt','source'=>'Primary vendor fixture','type'=>'vendor_advisory',
 'title'=>'Primary vendor bulletin','description'=>str_repeat('Primary appliance context. ',12),'technical_description'=>'A decoder handles critical operations. Security Impact Rating (SIR) of High.',
 'technical_source'=>'Primary vendor fixture','technical_url'=>'https://cisco.example/advisory','url'=>'https://cisco.example/advisory',
 'vendor'=>'Primary vendor','product'=>'Primary appliance','cve'=>'CVE-2026-111497','severity'=>'HIGH','score'=>null,'priority'=>90,'published'=>'2026-10-27T00:00:00Z','action'=>'',
);
$secondary = array(
 'id'=>'secondary','source_key'=>'paloalto_psirt','source'=>'Secondary vendor fixture','type'=>'vendor_advisory',
 'title'=>'CVE-2026-111497: Secondary appliance (Severity: CRITICAL)','description'=>'Secondary product context.',
 'technical_description'=>'Secondary details.','technical_source'=>'Secondary vendor fixture','technical_url'=>'https://palo.example/advisory','url'=>'https://palo.example/advisory',
 'vendor'=>'Secondary vendor','product'=>'Secondary appliance','cve'=>'CVE-2026-111497','severity'=>'CRITICAL','score'=>9.2,'priority'=>20,'published'=>'2026-11-09T00:00:00Z','action'=>'Secondary-only workaround.',
);
foreach(array(array($primary,$secondary),array($secondary,$primary))as$pair){
 $merged=call_private('merge_items',...$pair);
 check(str_contains($merged['title'],'Primary vendor bulletin')&&!str_contains($merged['title'],'Secondary appliance'),'Merged headline must keep selected vendor identity in either input order.');
 check($merged['source']===$primary['source']&&$merged['url']===$primary['url']&&$merged['product']===$primary['product'],'Primary source/product/URL must stay coherent.');
 check($merged['description']===$primary['description']&&$merged['technical_description']===$primary['technical_description'],'A secondary empty or shorter narrative must not replace vendor scope.');
 check($merged['severity']==='HIGH'&&null===$merged['score']&&''===$merged['action'],'Another vendor rating or workaround must not become the primary scalar.');
 check($merged['published']===$primary['published']&&count($merged['references'])===2,'Keep primary publication provenance and both attributed references.');
}
check('HIGH'===call_private('vendor_feed_severity','cisco_psirt','Fixture',$primary['technical_description'],'critical operations',null),'Explicit Cisco High rating must beat incidental critical wording.');
check(''===call_private('vendor_feed_severity','cisco_psirt','Fixture','A critical operation is unavailable.','critical',null),'Ordinary adjectives do not establish a vendor rating.');
check('CRITICAL'===call_private('vendor_feed_severity','paloalto_psirt',$secondary['title'],'','',null),'Known explicit Palo Alto title rating must parse.');
check(''===call_private('vendor_feed_severity','paloalto_psirt','No explicit rating','','critical',null),'Missing explicit Palo Alto rating must stay unknown.');
check(''===call_private('vendor_feed_severity','cisco_psirt','Fixture','No Security Impact Rating of High.','',null),'Negated rating must not count as confirmed.');

$kev=$primary;$kev['type']='kev';$kev['active_exploitation']=true;$kev['severity']='Known Exploited';$kev['score']=8.6;
$critical=$secondary;$critical['active_exploitation']=false;
$counts=call_private('summary_counts',array($kev,$critical));
check(1===$counts['kev']&&1===$counts['high']&&1===$counts['critical'],'KEV and numeric High severity must be counted independently.');
$publication_posts=array((object)array('ID'=>500,'post_title'=>'CVE-2026-111497: Reviewed fixture'));
$publication_meta[500]=array('_infosecnexus_brief_reference'=>'1');
$data=array('checked_at'=>'2026-11-17 12:00:00','source_status'=>array('cisa'=>array('label'=>'CISA fixture','ok'=>false,'stale'=>true,'last_success'=>'2026-11-16 12:00:00')));
$body=call_private('render_rolling_article','November 17, 2026',array($kev),$data);
check(str_contains($body,'<table>')&&str_contains($body,'Priority scan'),'The rolling brief needs an actual semantic priority table.');
check(1===substr_count($body,trim($primary['description'])),'An item narrative must appear once, without security-desk duplication.');
check(str_contains($body,'last successful check')&&str_contains($body,'2026-11-16 12:00:00'),'Unavailable sources must retain real last-good provenance.');
check(str_contains($body,'https://fixture.invalid/article-500/'),'Only real active exact-CVE destinations can receive detail links.');
check(str_contains($body,'Detailed remediation guides'),'Explicitly selected current editorial guides must be discoverable.');
$publication_meta[500]['_infosecnexus_retirement_state']='staged';
check(array()===call_private('brief_remediation_guides'),'Retired articles must not appear as remediation guides.');
$publication_posts=array();
$d1=array('items'=>array($kev),'checked_at'=>'2026-11-17 12:00:00','source_status'=>array('a'=>array('ok'=>true,'stale'=>false)));
$d2=$d1;$d2['checked_at']='2026-11-17 12:15:00';
$a=Live_Intelligence::daily_posts('November 17, 2026',$d1)[0];$b=Live_Intelligence::daily_posts('November 17, 2026',$d2)[0];
check($a['fingerprint']===$b['fingerprint'],'An unchanged poll must not count as a new editorial revision.');
$d2['source_status']['a']=array('ok'=>false,'stale'=>true);
$c=Live_Intelligence::daily_posts('November 17, 2026',$d2)[0];
check($a['fingerprint']!==$c['fingerprint'],'A real source availability change must refresh the rolling disclosure.');
echo 'PASS: '.$checks." total rolling/editorial and source-evidence checks\n";

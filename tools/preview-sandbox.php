<?php
/** Safety controls installed only in the isolated design-preview WordPress. */
declare(strict_types=1);
if (!defined('ABSPATH') || !defined('ISN_DESIGN_PREVIEW') || !ISN_DESIGN_PREVIEW) { return; }

add_filter('pre_option_blog_public',static fn()=>0);
add_filter('wp_sitemaps_enabled','__return_true');
add_filter('pre_schedule_event',static fn()=>false);
add_filter('automatic_updater_disabled','__return_true');
add_filter('pre_option_infosecnexus_toolkit_options',static function(){
    $options=array('daily_content_enabled'=>false,'newsletter_digest_enabled'=>false,'updates_enabled'=>false,'smtp_enabled'=>false,'adsense_enabled'=>false,'mail_from_email'=>'preview@localhost.invalid','contact_recipient'=>'preview@localhost.invalid','cookie_text'=>'InfoSecNexus uses essential cookies and optional preference storage to improve the reading experience.');
    if(class_exists('InfoSecNexus\\Theme\\Toolkit\\Plugin')){$options['modules']=array_replace(\InfoSecNexus\Theme\Toolkit\Plugin::default_modules(),array('cookie_consent'=>true));}
    return $options;
});
add_filter('pre_http_request',static fn()=>new WP_Error('preview_external_http_disabled','Server-side external requests are disabled in this isolated preview.'),999);
add_filter('pre_wp_mail',static function($result,$atts){
    unset($result,$atts);
    do_action('wp_mail_failed',new WP_Error('preview_mail_disabled','Preview email delivery is disabled. Configure the existing SMTP integration before sending.'));
    return false;
},999,2);
add_filter('wp_robots',static function($robots){$robots['noindex']=true;$robots['nofollow']=true;return $robots;});
add_action('send_headers',static function(){header('X-Robots-Tag: noindex, nofollow, noarchive',true);header('X-InfoSecNexus-Preview: isolated-sqlite',true);},100);
add_action('after_setup_theme',static function(){
    $demo='InfoSecNexus\\Theme\\Toolkit\\Demo_Content';
    foreach(array(array('init','schedule_daily_content',10),array('init','schedule_daily_content_upgrade',40),array('init','maybe_purge_release_cache',99),array('admin_init','maybe_auto_seed',10),array('admin_init','maybe_upgrade_daily_content',30),array('admin_init','maybe_repair_newsroom_metadata',35)) as $hook){remove_action($hook[0],array($demo,$hook[1]),$hook[2]);}
    remove_action('init',array('InfoSecNexus\\Theme\\Toolkit\\Post_Artwork','schedule_backfill'),30);
    remove_action('save_post_post',array('InfoSecNexus\\Theme\\Toolkit\\Post_Artwork','generate_for_saved_post'),30);
    remove_action('wp_head','InfoSecNexus\\Theme\\AdSense\\render_adsense_script');
    remove_action('wp_footer','InfoSecNexus\\Theme\\AdSense\\render_side_rails');
},99999);
add_filter('upload_dir',static function($uploads){$uploads['baseurl']='https://infosecnexus.com/wp-content/uploads';$uploads['url']=$uploads['baseurl'].$uploads['subdir'];return $uploads;});
add_filter('the_content',static fn($content)=>str_replace(array('href="https://infosecnexus.com/','href=\'https://infosecnexus.com/'),array('href="'.home_url('/'),'href=\''.home_url('/')),$content),1);

<?php
/** Describe isolation and content readiness without exposing local credentials. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ISN_DESIGN_PREVIEW') || !ISN_DESIGN_PREVIEW) { exit(1); }
$counts=wp_count_posts('post');$pages=wp_count_posts('page');$images=wp_count_posts('attachment');
$active=0;
foreach(get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'suppress_filters'=>true,'fields'=>'ids')) as $id){if(!get_post_meta($id,'_infosecnexus_retirement_state',true)&&!get_post_meta($id,'_infosecnexus_newsroom_superseded_by',true)){++$active;}}
$cron=get_option('cron',array());unset($cron['version']);
echo wp_json_encode(array('url'=>home_url('/'),'database_engine'=>DB_ENGINE,'database_outside_webroot'=>defined('DB_PATH')&&!str_starts_with(DB_PATH,ABSPATH),'published_posts'=>(int)$counts->publish,'published_pages'=>(int)$pages->publish,'public_attachment_records'=>(int)$images->inherit,'active_archive_posts'=>$active,'scheduled_event_groups'=>count($cron),'mail_blocker'=>has_filter('pre_wp_mail')!==false,'external_http_blocker'=>has_filter('pre_http_request')!==false,'generator_init_hook'=>has_action('init',array('InfoSecNexus\Theme\Toolkit\Demo_Content','schedule_daily_content'))!==false,'artwork_init_hook'=>has_action('init',array('InfoSecNexus\Theme\Toolkit\Post_Artwork','schedule_backfill'))!==false,'adsense_loader_hook'=>has_action('wp_head','InfoSecNexus\Theme\AdSense\render_adsense_script')!==false,'theme_version'=>wp_get_theme()->get('Version')),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

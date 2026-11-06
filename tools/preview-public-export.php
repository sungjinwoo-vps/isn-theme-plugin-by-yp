<?php
/** Export public WordPress content only for the isolated design preview. */

if (!defined('WP_CLI') || !WP_CLI) { exit(1); }

$rows = get_posts(array('post_type'=>array('post','page'),'post_status'=>'publish','has_password'=>false,'posts_per_page'=>-1,'suppress_filters'=>true,'orderby'=>'ID','order'=>'ASC'));
$meta_keys = array('_thumbnail_id','_wp_page_template','_infosecnexus_featured_video_url','_infosecnexus_daily_content','_infosecnexus_demo_content','_infosecnexus_newsroom_post','_infosecnexus_newsroom_kind','_infosecnexus_newsroom_superseded_by','_infosecnexus_newsroom_superseded_at','_infosecnexus_story_key','_infosecnexus_retirement_state','_infosecnexus_retirement_staged_at','_infosecnexus_live_source_ids','_infosecnexus_source_urls','_infosecnexus_live_fingerprint','_infosecnexus_live_checked_at','_infosecnexus_live_severity','_infosecnexus_live_score','_infosecnexus_editorial_body_hash','_infosecnexus_editorial_evidence','_infosecnexus_editorial_history','_infosecnexus_editorial_schema','_infosecnexus_editorial_manual','_infosecnexus_seo_description','_infosecnexus_seo_keywords','_infosecnexus_seo_primary_keyword','_infosecnexus_seo_keywords_manual','_infosecnexus_artwork_source','_infosecnexus_generated_artwork','_yoast_wpseo_focuskw','_yoast_wpseo_metadesc','rank_math_focus_keyword','rank_math_description','_seopress_analysis_target_kw','_seopress_titles_desc');
$image_keys = array('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_image_alt','_infosecnexus_artwork_source','_infosecnexus_artwork_source_url','_infosecnexus_artwork_post_id');
$content = array(); $meta = array(); $authors = array(); $image_ids = array();
$logo = (int)get_theme_mod('custom_logo',0);
if ($logo) { $image_ids[$logo] = $logo; }
foreach ($rows as $post) {
    $public_row = array_intersect_key((array)$post,array_flip(array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_excerpt','post_status','comment_status','ping_status','post_name','post_modified','post_modified_gmt','post_parent','guid','menu_order','post_type','post_mime_type','comment_count')));
    $content[] = $public_row;
    $author = get_userdata((int)$post->post_author);
    if ($author) { $authors[$author->ID] = array('ID'=>$author->ID,'display_name'=>$author->display_name,'user_nicename'=>$author->user_nicename,'user_url'=>$author->user_url,'description'=>get_user_meta($author->ID,'description',true)); }
    foreach ($meta_keys as $key) {
        $values = get_post_meta($post->ID,$key,false);
        if ($values) { $meta[$post->ID][$key] = $values; }
    }
    $image_id = get_post_thumbnail_id($post->ID);
    if ($image_id) { $image_ids[$image_id] = $image_id; }
}
foreach ($image_ids as $image_id) {
    $image = get_post($image_id);
    if (!$image || $image->post_type !== 'attachment') { continue; }
    $content[] = array_intersect_key((array)$image,array_flip(array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_excerpt','post_status','comment_status','ping_status','post_name','post_modified','post_modified_gmt','post_parent','guid','menu_order','post_type','post_mime_type','comment_count')));
    foreach ($image_keys as $key) {
        $values = get_post_meta($image_id,$key,false);
        if ($values) { $meta[$image_id][$key] = $values; }
    }
}
global $wpdb;
$terms = $wpdb->get_results("SELECT t.*,tt.term_taxonomy_id,tt.taxonomy,tt.description,tt.parent,tt.count FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id=tt.term_id WHERE tt.taxonomy IN ('category','post_tag')",ARRAY_A);
$ids = implode(',',array_map(static fn($post)=>(int)$post->ID,$rows));
$relationships = $ids ? $wpdb->get_results("SELECT tr.* FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id WHERE tr.object_id IN ($ids) AND tt.taxonomy IN ('category','post_tag')",ARRAY_A) : array();
$options = array();
foreach (array('blogname','blogdescription','date_format','time_format','timezone_string','permalink_structure','page_on_front','show_on_front','page_for_posts','posts_per_page') as $key) { $options[$key] = get_option($key); }
echo wp_json_encode(array('exported_at'=>gmdate('c'),'source'=>'https://infosecnexus.com','posts'=>$content,'meta'=>$meta,'authors'=>array_values($authors),'terms'=>$terms,'relationships'=>$relationships,'options'=>$options,'theme_mods'=>array('custom_logo'=>$logo)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

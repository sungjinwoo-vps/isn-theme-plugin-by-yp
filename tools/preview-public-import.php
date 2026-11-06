<?php
/** Import the allowlisted public export into the isolated SQLite preview. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ISN_DESIGN_PREVIEW') || !ISN_DESIGN_PREVIEW || !defined('DB_PATH') || !str_starts_with(DB_PATH,'/tmp/isn-cinematic-preview-data-20261106/')) { throw new RuntimeException('This importer only runs in the isolated preview database.'); }
$data=json_decode(file_get_contents('/tmp/isn-cinematic-public-20261106.json'),true,512,JSON_THROW_ON_ERROR);
global $wpdb;
foreach(array($wpdb->term_relationships,$wpdb->postmeta,$wpdb->posts,$wpdb->term_taxonomy,$wpdb->terms) as $table){$wpdb->query("DELETE FROM $table");}
foreach($data['authors'] as $row){
    $id=(int)$row['ID'];
    if (!get_userdata($id)) {$wpdb->insert($wpdb->users,array('ID'=>$id,'user_login'=>'public-author-'.$id,'user_pass'=>wp_hash_password(wp_generate_password(48,true,true)),'user_nicename'=>$row['user_nicename'],'user_email'=>'preview-author-'.$id.'@localhost.invalid','display_name'=>$row['display_name'],'user_url'=>$row['user_url'],'user_registered'=>gmdate('Y-m-d H:i:s'),'user_status'=>0));}
    else {$wpdb->update($wpdb->users,array('user_nicename'=>$row['user_nicename'],'display_name'=>$row['display_name'],'user_url'=>$row['user_url']),array('ID'=>$id));}
    update_user_meta($id,'description',$row['description']);
}
foreach($data['posts'] as $row){unset($row['filter']);$wpdb->insert($wpdb->posts,$row);}
foreach($data['meta'] as $post_id=>$keys){foreach($keys as $key=>$values){foreach($values as $value){add_post_meta((int)$post_id,$key,wp_slash($value));}}}
foreach($data['terms'] as $row){
    $wpdb->replace($wpdb->terms,array('term_id'=>$row['term_id'],'name'=>$row['name'],'slug'=>$row['slug'],'term_group'=>$row['term_group']));
    $wpdb->replace($wpdb->term_taxonomy,array('term_taxonomy_id'=>$row['term_taxonomy_id'],'term_id'=>$row['term_id'],'taxonomy'=>$row['taxonomy'],'description'=>$row['description'],'parent'=>$row['parent'],'count'=>$row['count']));
}
foreach($data['relationships'] as $row){$wpdb->insert($wpdb->term_relationships,$row);}
foreach($data['options'] as $key=>$value){update_option($key,$value);}
update_option('home','http://localhost:8890');update_option('siteurl','http://localhost:8890');update_option('blog_public',0);
update_option('infosecnexus_demo_seeded_version','99.0.0');
update_option('infosecnexus_daily_content_schema','12');
update_option('infosecnexus_public_cache_release','design-preview');
update_option('infosecnexus_post_artwork_backfill_version','9');
update_option('infosecnexus_primary_email_migration','1');
$preview_mods=$data['theme_mods'];
$preview_mods['default_color_mode']='dark';
update_option('theme_mods_infosecnexus',$preview_mods);
wp_cache_flush();flush_rewrite_rules(false);
WP_CLI::success(sprintf('Imported %d public posts/pages/attachments, %d public metadata sets and %d category/tag definitions into isolated SQLite. No private production data imported.',count($data['posts']),count($data['meta']),count($data['terms'])));

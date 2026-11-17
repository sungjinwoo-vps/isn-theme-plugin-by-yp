<?php
/** Standalone reviewed-title and description-persistence fixtures. */
declare(strict_types=1);
$flags=array('home'=>false,'singular'=>true,'password'=>false);
$meta=array();$checks=0;
function is_front_page(){return $GLOBALS['flags']['home'];}
function is_singular($type=''){return $GLOBALS['flags']['singular'];}
function get_queried_object_id(){return 100;}
function post_password_required($id=0){return $GLOBALS['flags']['password'];}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function sanitize_text_field($v){return trim(strip_tags($v));}
function __($s,$domain=''){return $s;}
require dirname(__DIR__,2).'/wp-content/themes/infosecnexus/inc/seo.php';
require dirname(__DIR__,2).'/wp-content/themes/infosecnexus/inc/toolkit/class-demo-content.php';
function check($ok,$label){++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($label);}
use InfoSecNexus\Theme\SEO;
use InfoSecNexus\Theme\Toolkit\Demo_Content;
check('Default'===SEO\document_title('Default'),'Unconfigured article title must retain native ownership.');
$meta[100]['_infosecnexus_seo_title']='Reviewed search title';
check('Reviewed search title'===SEO\document_title('Default'),'Reviewed title must drive document/social title without changing H1.');
$meta[100]['_infosecnexus_retirement_state']='staged';
check('Default'===SEO\document_title('Default'),'Retired content must not receive the curated current title.');
$meta[100]['_infosecnexus_retirement_state']='';$meta[100]['_infosecnexus_newsroom_superseded_by']=200;
check('Default'===SEO\document_title('Default'),'Superseded content must preserve existing title behavior.');
$meta[100]['_infosecnexus_newsroom_superseded_by']='';$flags['password']=true;
check('Default'===SEO\document_title('Default'),'Protected content must not expose a curated title.');
$flags['password']=false;$flags['singular']=false;
check('Default'===SEO\document_title('Default'),'Archive and page titles must remain unchanged.');
$method=new ReflectionMethod(Demo_Content::class,'newsroom_description');
check('Generated'===$method->invoke(null,100,'Generated'),'Normal source descriptions must still refresh.');
$meta[100]['_infosecnexus_seo_description']='Reviewed description';$meta[100]['_infosecnexus_seo_description_manual']='1';
check('Reviewed description'===$method->invoke(null,100,'Generated'),'Reviewed rolling description must survive source refreshes.');
$meta[100]['_infosecnexus_seo_description']='';
check('Generated'===$method->invoke(null,100,'Generated'),'An empty reviewed value must fall back to the real generated description.');
define('WPSEO_VERSION','fixture');$flags['singular']=true;
check('Plugin title'===SEO\document_title('Plugin title'),'An active SEO plugin must retain title ownership.');
echo 'PASS: '.$checks." reviewed metadata checks\n";

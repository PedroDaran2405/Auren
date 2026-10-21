<?php
defined('ABSPATH') || exit;

function auren_app_header_gamification_enabled() {
    return is_user_logged_in() && auren_app_gami_type('points') && !isset($_GET['auren_app']);
}

// Never let a page cache reuse one student's server-rendered balance for another.
add_action('init',function(){
    if(auren_app_header_gamification_enabled() && !defined('DONOTCACHEPAGE')){define('DONOTCACHEPAGE',true);}
});
add_action('template_redirect',function(){
    if(auren_app_header_gamification_enabled()){nocache_headers();}
},1);

/** Render private, native GamiPress values before the browser lays out the header. */
function auren_app_header_gamification_markup() {
    $data=auren_app_gamification(false);
    if(!$data['available'] || !$data['credits']){return '';}
    $rank=$data['rank'];$credits=$data['credits'];
    ob_start(); ?>
    <div class="auren-gami" data-auren-gami>
        <?php if($rank && $rank['current']):
            $current=$rank['current'];$percent=$rank['progress']['percent'];
            $name=($current['number']?'Nivel '.$current['number'].' · ':'').$current['name'];
            $description=$rank['next']?'Siguiente nivel: '.$rank['next']['name'].'. ':'Nivel más alto disponible.';
            foreach($rank['progress']['requirements'] as $requirement){
                if(!$requirement['optional']){$description.=$requirement['title'].': '.$requirement['current'].' / '.$requirement['total'].' '.$requirement['unit'].'. ';}
            } ?>
            <div class="auren-gami__level" tabindex="0" title="<?php echo esc_attr($description); ?>" aria-label="<?php echo esc_attr($name.'. '.$description); ?>">
                <?php if($current['image_url']): ?>
                    <img class="auren-gami__medal" src="<?php echo esc_url($current['image_url']); ?>" width="34" height="38" alt="">
                <?php else: ?><span class="auren-gami__medal" aria-hidden="true">★</span><?php endif; ?>
                <div class="auren-gami__info"><span class="auren-gami__name"><?php echo esc_html($name); ?></span>
                    <?php if($percent!==null): ?>
                        <div class="auren-gami__track" role="progressbar" aria-label="<?php echo esc_attr('Avance hacia '.($rank['next']['name']??'el siguiente nivel')); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int)$percent; ?>"><span class="auren-gami__fill" style="width:<?php echo max(0,min(100,(int)$percent)); ?>%"></span></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <span class="auren-gami__credits" tabindex="0" title="Auren Credits" aria-label="<?php echo esc_attr($credits['balance'].' Auren Credits'); ?>">
            <?php if($credits['image_url']): ?><img class="auren-gami__coin" src="<?php echo esc_url($credits['image_url']); ?>" width="30" height="30" alt=""><?php endif; ?>
            <span class="auren-gami__balance"><?php echo esc_html(number_format((int)$credits['balance'],0,'.',',')); ?></span>
            <span class="auren-gami__tooltip">Auren Credits</span>
        </span>
    </div>
    <?php return trim(ob_get_clean());
}

/** Scope buffering to BuddyBoss's two header templates, including child overrides.
 * No parent template copies or edits; WordPress's load_template hooks handle nesting.
 */
add_action('wp_before_load_template',function($file){
    if(!auren_app_header_gamification_enabled()){return;}
    $name=basename($file);
    if(!in_array($name,array('header-aside.php','header-mobile.php'),true)){return;}
    $directory=wp_normalize_path(dirname($file));
    $allowed=array(wp_normalize_path(get_template_directory().'/template-parts'),wp_normalize_path(get_stylesheet_directory().'/template-parts'));
    if(!in_array($directory,$allowed,true)){return;}
    // Compute outside the output handler: PHP forbids nested ob_start there.
    $markup=auren_app_header_gamification_markup();
    $GLOBALS['auren_app_header_buffers'][]=array('file'=>$file,'level'=>ob_get_level()+1);
    ob_start(static function($html)use($name,$markup){
        $slot=$name==='header-aside.php'?'<div class="header-aside-inner">':'<div class="header-aside">';
        $position=strpos($html,$slot);
        if($position===false || strpos($html,'data-auren-gami')!==false){return $html;}
        return substr_replace($html,$markup,$position+strlen($slot),0);
    });
},10,1);
add_action('wp_after_load_template',function($file){
    $buffers=$GLOBALS['auren_app_header_buffers']??array();$last=end($buffers);
    if($last && $last['file']===$file && $last['level']===ob_get_level()){
        array_pop($GLOBALS['auren_app_header_buffers']);ob_end_flush();
    }
},10,1);

// Critical geometry is in <head>, so delayed CSS/JS/images cannot resize the slot.
add_action('wp_head',function(){
    if(!auren_app_header_gamification_enabled()){return;}
    echo '<style id="auren-gamification-css">'.file_get_contents(__DIR__.'/../assets/gamification.css').'</style>';
},99);
add_action('wp_enqueue_scripts',function(){
    if(!auren_app_header_gamification_enabled()){return;}
    $base=plugins_url('../assets/',__FILE__);
    wp_enqueue_script('auren-gamification',$base.'gamification.js',array(),(string)filemtime(__DIR__.'/../assets/gamification.js'),true);
    wp_add_inline_script('auren-gamification','window.aurenGamification='.wp_json_encode(array(
        'endpoint'=>rest_url('auren/v1/gamification'),'nonce'=>wp_create_nonce('wp_rest'),
    )).';','before');
});

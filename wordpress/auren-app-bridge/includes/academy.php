<?php
defined('ABSPATH') || exit;

/** LearnDash adapter. Activation requires an explicitly verified installed version. */
function auren_app_lms_ready() {
    $required = array('sfwd_lms_has_access','learndash_get_course_lessons_list','learndash_get_setting','learndash_is_lesson_complete','learndash_course_progress','learndash_process_mark_complete','ld_lesson_access_from','learndash_is_course_prerequities_completed','learndash_can_complete_step');
    foreach ($required as $fn) {
        if (!function_exists($fn)) { return auren_app_error('lms_unavailable','La academia no está disponible por ahora.',503); }
    }
    if (!defined('LEARNDASH_VERSION') || get_option('auren_app_verified_learndash_version','') !== LEARNDASH_VERSION) {
        return auren_app_error('lms_not_verified','Estamos preparando el acceso a la academia desde la app.',503);
    }
    return true;
}

function auren_app_course_access($course_id) {
    $ready = auren_app_lms_ready();
    if (is_wp_error($ready)) { return $ready; }
    if (get_post_type($course_id) !== 'sfwd-courses' || get_post_status($course_id) !== 'publish') { return auren_app_error('not_found','Este curso no está disponible.',404); }
    if (!sfwd_lms_has_access($course_id,get_current_user_id())) { return auren_app_error('course_forbidden','Tu cuenta no tiene acceso a este curso.',403); }
    if (!learndash_is_course_prerequities_completed($course_id,get_current_user_id())) { return auren_app_error('prerequisites','Completa los requisitos anteriores del curso.',403); }
    return true;
}

function auren_app_progress($course_id) {
    $data = learndash_course_progress(array('user_id'=>get_current_user_id(),'course_id'=>$course_id,'array'=>true));
    if (!is_array($data) || !isset($data['completed'],$data['total'])) { return auren_app_error('lms_contract','No pudimos consultar tu avance.',503); }
    $completed = max(0,(int)$data['completed']);
    $total = max(0,(int)$data['total']);
    return array('completed'=>$completed,'total'=>$total,'percent'=>$total ? min(100,(int)floor(100*$completed/$total)) : 0);
}

function auren_app_lessons($course_id) {
    $items = learndash_get_course_lessons_list($course_id,get_current_user_id(),array('nopaging'=>true));
    if (!is_array($items)) { return auren_app_error('lms_contract','No pudimos consultar las lecciones.',503); }
    $lessons = array();
    foreach ($items as $item) {
        $post = $item['post'] ?? null;
        if ($post instanceof WP_Post && $post->post_status === 'publish') { $lessons[] = $post; }
    }
    return $lessons;
}

function auren_app_course($id, $with_lessons = false) {
    $allowed = auren_app_course_access($id);
    if (is_wp_error($allowed)) { return $allowed; }
    $progress = auren_app_progress($id);
    if (is_wp_error($progress)) { return $progress; }
    $mode = learndash_get_setting($id,'course_price_type');
    $result = array('id'=>$id,'title'=>auren_app_text(get_the_title($id)),'summary'=>auren_app_text(get_the_excerpt($id)),
        'image_url'=>get_the_post_thumbnail_url($id,'large') ?: null,'url'=>get_permalink($id),
        'access'=>array('allowed'=>true,'source'=>'learndash','label'=>in_array($mode,array('free','open'),true) ? 'Gratis' : 'Incluido en tu acceso'),
        'progress'=>$progress);
    if ($with_lessons) {
        $lessons = auren_app_lessons($id);
        if (is_wp_error($lessons)) { return $lessons; }
        $result['lessons'] = array_map(function($post) use ($id) {
            $gate = auren_app_lesson_access($id,$post->ID);
            return array('id'=>$post->ID,'title'=>auren_app_text($post->post_title),'completed'=>(bool)learndash_is_lesson_complete(get_current_user_id(),$post->ID,$id),'available'=>!is_wp_error($gate));
        },$lessons);
    }
    return $result;
}

function auren_app_lesson_access($course_id,$lesson_id) {
    $allowed = auren_app_course_access($course_id);
    if (is_wp_error($allowed)) { return $allowed; }
    $lessons = auren_app_lessons($course_id);
    if (is_wp_error($lessons)) { return $lessons; }
    $ids = array_map(fn($p)=>(int)$p->ID,$lessons);
    $position = array_search($lesson_id,$ids,true);
    if ($position === false || get_post_type($lesson_id) !== 'sfwd-lessons') { return auren_app_error('not_found','No se encontró esta lección en el curso.',404); }
    $available_from = ld_lesson_access_from($lesson_id,get_current_user_id(),$course_id,true);
    if ($available_from && (int)$available_from > time()) { return auren_app_error('lesson_scheduled','Esta lección todavía no está disponible.',403); }
    if (function_exists('learndash_lesson_progression_enabled') ? learndash_lesson_progression_enabled($course_id) : !learndash_get_setting($course_id,'course_disable_lesson_progression')) {
        foreach (array_slice($ids,0,$position) as $previous) {
            if (!learndash_is_lesson_complete(get_current_user_id(),$previous,$course_id)) { return auren_app_error('lesson_locked','Completa las lecciones anteriores para continuar.',403); }
        }
    }
    return true;
}

function auren_app_completion_mode($course_id,$lesson_id) {
    // Advanced learning rules remain in LearnDash's existing web flow.
    $settings = array('lesson_video_enabled','lesson_assignment_upload','lesson_timer_enabled','forced_lesson_time','forced_lesson_time_enabled');
    foreach ($settings as $key) { if (learndash_get_setting($lesson_id,$key)) { return 'web'; } }
    if (!function_exists('learndash_get_topic_list') || !function_exists('learndash_get_lesson_quiz_list')) { return 'web'; }
    if (learndash_get_topic_list($lesson_id,$course_id) || learndash_get_lesson_quiz_list($lesson_id,get_current_user_id(),$course_id)) { return 'web'; }
    return (defined('AUREN_APP_ALLOW_PROGRESS_WRITES') && AUREN_APP_ALLOW_PROGRESS_WRITES) || get_option('auren_app_progress_writes',false) ? 'app' : 'web';
}

function auren_app_lesson($course_id,$lesson_id) {
    $allowed = auren_app_lesson_access($course_id,$lesson_id);
    if (is_wp_error($allowed)) { return $allowed; }
    $post = get_post($lesson_id);
    // No raw embeds, script, shortcode execution or signed URLs guessed by the client.
    $content = strip_shortcodes($post->post_content);
    $video = (string)learndash_get_setting($lesson_id,'lesson_video_url');
    if(!$video){$video=auren_app_block_video(parse_blocks($post->post_content));}
    if (!$video && preg_match('~https://[^\s<>"\x27]+\.(?:mp4|m3u8)(?:\?[^\s<>"\x27]*)?~i',$content,$m)) { $video = $m[0]; }
    $video = esc_url_raw($video,array('https'));
    $direct = $video && preg_match('~\.(mp4|m3u8)(?:\?|$)~i',$video);
    $youtube=auren_app_youtube_id($video);
    $video_data=$youtube?array('kind'=>'youtube','url'=>null,'video_id'=>$youtube):($video?array('kind'=>$direct?'direct':'web','url'=>$direct?$video:null):null);
    if($video){$content=str_replace($video,'',$content);}
    $content=preg_replace('~</(?:p|div|h[1-6]|li)>|<br\s*/?>~i',"\n\n",$content);
    return array('id'=>$lesson_id,'course_id'=>$course_id,'title'=>auren_app_text($post->post_title),
        'text'=>html_entity_decode(wp_strip_all_tags($content),ENT_QUOTES,'UTF-8'),
        'url'=>add_query_arg('ld-course',$course_id,get_permalink($lesson_id)),
        'completed'=>(bool)learndash_is_lesson_complete(get_current_user_id(),$lesson_id,$course_id),
        'completion_mode'=>auren_app_completion_mode($course_id,$lesson_id),
        'video'=>$video_data);
}

function auren_app_text($value){return html_entity_decode(wp_strip_all_tags($value),ENT_QUOTES,'UTF-8');}

function auren_app_block_video($blocks){
    foreach($blocks as $block){
        if($block['blockName']==='core/embed' && !empty($block['attrs']['url'])){return $block['attrs']['url'];}
        if($block['blockName']==='core/video' && preg_match('~src=["\x27](https://[^"\x27]+)~',$block['innerHTML'] ?? '',$m)){return html_entity_decode($m[1]);}
        if(!empty($block['innerBlocks'])){$nested=auren_app_block_video($block['innerBlocks']);if($nested){return $nested;}}
    }
    return '';
}
function auren_app_youtube_id($url){
    $parts=wp_parse_url($url);$host=strtolower($parts['host']??'');$path=$parts['path']??'';$id='';
    if(in_array($host,array('youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com'),true)){
        parse_str($parts['query']??'',$q);$id=$q['v']??'';
        if(preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})$~',$path,$m)){$id=$m[1];}
    }elseif($host==='youtu.be'){$id=ltrim($path,'/');}
    return is_string($id)&&preg_match('/^[A-Za-z0-9_-]{11}$/D',$id)?$id:null;
}

function auren_app_complete($course_id,$lesson_id) {
    $allowed = auren_app_lesson_access($course_id,$lesson_id);
    if (is_wp_error($allowed)) { return $allowed; }
    if (auren_app_completion_mode($course_id,$lesson_id) !== 'app') { return auren_app_error('web_required','Completa esta lección en la academia para respetar sus actividades y requisitos.',409); }
    $uid = get_current_user_id();
    global $wpdb;
    $lock='auren-progress-'.get_current_blog_id().'-'.$uid.'-'.$course_id;
    if((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$lock)) !== 1){return auren_app_error('progress_busy','Tu avance se está guardando. Actualízalo en un momento.',409);}
    try {
    if (!learndash_is_lesson_complete($uid,$lesson_id,$course_id)) {
        if (!learndash_can_complete_step($uid,$lesson_id,$course_id,false)) { return auren_app_error('completion_rejected','Completa las actividades y requisitos de esta lección.',409); }
        // Keep LearnDash as the sole writer of its progress structures and hooks.
        learndash_process_mark_complete($uid,$lesson_id,false,$course_id,false);
    }
    if (!learndash_is_lesson_complete($uid,$lesson_id,$course_id)) { return auren_app_error('completion_rejected','La academia aún no permite completar esta lección.',409); }
    return array('lesson'=>auren_app_lesson($course_id,$lesson_id),'progress'=>auren_app_progress($course_id));
    } finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock)); }
}

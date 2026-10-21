<?php
defined('ABSPATH') || exit;

/** Read the original GamiPress objects. Never award points or ranks on a GET. */
function auren_app_gami_type($kind) {
    $fn=$kind==='points'?'gamipress_get_points_types':'gamipress_get_rank_types';
    if(!function_exists($fn)){return '';}
    $types=$fn();$selected=get_option('auren_app_'.$kind.'_type','');
    if(isset($types[$selected])){return $selected;}
    $preferred=$kind==='points'?'credits':'niveles-auren';
    if(isset($types[$preferred])){return $preferred;}
    return count($types)===1?(string)array_key_first($types):'';
}

function auren_app_branding() {
    $logo=null;
    if(function_exists('buddyboss_theme_get_option')){
        foreach(array('logo_dark','logo','mobile_logo') as $key){
            $value=buddyboss_theme_get_option($key);
            $candidate=is_array($value)?($value['url']??''):(is_string($value)?$value:'');
            $logo=auren_app_safe_url($candidate,true);if($logo){break;}
        }
    }
    if(!$logo){$logo=auren_app_safe_url(wp_get_attachment_image_url((int)get_theme_mod('custom_logo'),'full'),true);}
    return array('name'=>get_bloginfo('name'),'logo_url'=>$logo);
}

function auren_app_gami_requirement($post,$user) {
    $r=gamipress_get_requirement_object($post->ID);$total=max(1,(int)$r['count']);$current=0;$unit='acciones';
    $earned=(bool)gamipress_has_user_earned_achievement($post->ID,$user);
    $trigger=(string)$r['trigger_type'];$supported=true;
    if($trigger==='points-balance'){
        $current=(int)gamipress_get_user_points($user,$r['points_type_required']);$total=max(1,(int)$r['points_required']);
        $unit=$r['points_type_required']===auren_app_gami_type('points')?'Auren Credits':'puntos';
        if($r['points_condition']==='greater_than'){$total++;}
        elseif(!in_array($r['points_condition'],array('greater_or_equal','greater_than'),true)){$supported=false;}
    }elseif($trigger==='earn-points' && function_exists('gamipress_get_user_points_awarded_in_loop')){
        $current=(int)gamipress_get_user_points_awarded_in_loop($user,$r['points_type_required'],$post->ID);$total=max(1,(int)$r['points_required']);$unit='puntos obtenidos';
    }elseif(function_exists('gamipress_get_achievement_activity_count_limited')){
        $current=(int)gamipress_get_achievement_activity_count_limited($user,$post->ID);
    }else{$supported=false;}
    $percent=$earned?100:($supported?min(100,max(0,(int)floor($current*100/$total))):null);
    return array('id'=>$post->ID,'title'=>auren_app_plain_content($post->post_title),'earned'=>$earned,'optional'=>!empty($r['optional']),
        'current'=>max(0,$current),'total'=>$total,'unit'=>$unit,'percent'=>$percent);
}
function auren_app_gami_progress($requirements,$user) {
    $items=array_map(static fn($p)=>auren_app_gami_requirement($p,$user),$requirements);
    $required=array_values(array_filter($items,static fn($r)=>!$r['optional']));
    $known=$required&&!array_filter($required,static fn($r)=>$r['percent']===null);
    return array('percent'=>$known?(int)floor(array_sum(array_column($required,'percent'))/count($required)):null,'requirements'=>$items);
}
function auren_app_gami_rank_item($post,$number=0) {
    return array('id'=>(int)$post->ID,'name'=>auren_app_plain_content($post->post_title),'number'=>$number,
        'image_url'=>auren_app_safe_url(get_the_post_thumbnail_url($post->ID,'medium')));
}

function auren_app_gamification() {
    $empty=array('available'=>false,'credits'=>null,'rank'=>null,'achievements'=>array(),'achievement_total'=>0,'achievement_earned'=>0);
    $points=auren_app_gami_type('points');$user=get_current_user_id();
    if(!$user||!$points||!function_exists('gamipress_get_user_points')){return $empty;}
    $type=gamipress_get_points_type($points);$id=(int)($type['ID']??0);
    $result=$empty;$result['available']=true;
    $result['credits']=array('type'=>$points,'label'=>'Auren Credits','balance'=>(int)gamipress_get_user_points($user,$points),
        'image_url'=>auren_app_safe_url(get_the_post_thumbnail_url($id,'thumbnail')));
    $rank_type=auren_app_gami_type('rank');
    if($rank_type&&function_exists('gamipress_get_user_rank')){
        $current=gamipress_get_user_rank($user,$rank_type);$next=gamipress_get_next_user_rank($user,$rank_type);
        $ranks=gamipress_get_ranks(array('post_type'=>$rank_type,'numberposts'=>100,'post_status'=>'publish','orderby'=>'menu_order','order'=>'ASC'));
        $ids=array_map(static fn($p)=>(int)$p->ID,$ranks);$position=$current?array_search((int)$current->ID,$ids,true):false;
        if($current){$result['rank']=array('type'=>$rank_type,'current'=>auren_app_gami_rank_item($current,$position===false?0:$position+1),
            'next'=>$next?auren_app_gami_rank_item($next,$position===false?0:$position+2):null,
            'progress'=>$next?auren_app_gami_progress(gamipress_get_rank_requirements($next->ID),$user):array('percent'=>null,'requirements'=>array()));}
    }
    if(function_exists('gamipress_get_achievement_types')){
        $types=array_keys(gamipress_get_achievement_types());
        $posts=$types?gamipress_get_achievements(array('post_type'=>$types,'post_status'=>'publish','numberposts'=>50,'orderby'=>'date','order'=>'DESC')):array();
        foreach($posts as $post){
            $earned=(bool)gamipress_has_user_earned_achievement($post->ID,$user);
            if(post_password_required($post)||(!$earned&&gamipress_is_achievement_hidden($post->ID))){continue;}
            $steps=gamipress_get_achievement_steps($post->ID);$mode=gamipress_get_post_meta($post->ID,'_gamipress_earned_by');
            // Existing empty drafts of rules are not presented as achievable rewards.
            if(!$earned&&$mode==='triggers'&&!count($steps)){continue;}
            $progress=auren_app_gami_progress($steps,$user);if($earned){$progress['percent']=100;}
            $reward_type=gamipress_get_post_meta($post->ID,'_gamipress_points_type');
            $result['achievements'][]=array('id'=>(int)$post->ID,'name'=>auren_app_plain_content($post->post_title),
                'description'=>auren_app_plain_content($post->post_excerpt?:$post->post_content),'earned'=>$earned,
                'image_url'=>auren_app_safe_url(get_the_post_thumbnail_url($post->ID,'medium')),'progress'=>$progress,
                'reward'=>$reward_type===$points?(int)gamipress_get_post_meta($post->ID,'_gamipress_points'):null);
        }
    }
    $result['achievement_total']=count($result['achievements']);$result['achievement_earned']=count(array_filter($result['achievements'],static fn($a)=>$a['earned']));
    return $result;
}

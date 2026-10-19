<?php
defined('ABSPATH') || exit;

add_action('rest_api_init',function() {
    $route = static function($path,$method,$callback,$public=false) {
        register_rest_route('auren/v1',$path,array('methods'=>$method,'callback'=>$callback,'permission_callback'=>$public ? '__return_true' : 'auren_app_permission'));
    };
    $route('/auth/start','POST','auren_app_auth_start',true);
    $route('/auth/exchange','POST','auren_app_auth_exchange',true);
    register_rest_route('auren/v1','/logout',array('methods'=>'POST','callback'=>'auren_app_logout','permission_callback'=>function(){
        return auren_app_secure_transport() && is_user_logged_in()?true:auren_app_error('login_required','Inicia sesión para continuar.',401);
    }));
    $route('/me','GET',function() {
        $u = wp_get_current_user();
        $community=auren_app_has_buddyboss_route('/buddyboss/v1/activity');
        $notifications=auren_app_has_buddyboss_route('/buddyboss/v1/notifications');
        return array('id'=>$u->ID,'name'=>$u->display_name,'avatar_url'=>get_avatar_url($u->ID),'site_url'=>home_url('/'),
            'profile_url'=>function_exists('bp_core_get_user_domain')?auren_app_safe_url(bp_core_get_user_domain($u->ID),true):null,
            'community_url'=>function_exists('bp_get_activity_directory_permalink')?auren_app_safe_url(bp_get_activity_directory_permalink(),true):null,
            'joined'=>substr($u->user_registered,0,10),'unread_notifications'=>$notifications?auren_app_unread_count():0,
            'capabilities'=>array('academy'=>auren_app_lms_ready() === true,'community'=>$community,'notifications'=>$notifications,'checkout'=>false),
            'environment'=>wp_get_environment_type());
    });
    $route('/courses','GET',function($r) {
        $ready = auren_app_lms_ready();
        if (is_wp_error($ready)) { return $ready; }
        $page = max(1,min(1000,(int)($r->get_param('page') ?: 1)));
        $query = new WP_Query(array('post_type'=>'sfwd-courses','post_status'=>'publish','posts_per_page'=>20,'paged'=>$page,'orderby'=>'ID','order'=>'ASC'));
        $items = array();
        foreach ($query->posts as $p) {
            $course = auren_app_course($p->ID);
            if (!is_wp_error($course)) { $items[] = $course; }
            elseif ($course->get_error_data()['status'] === 503) { return $course; }
        }
        return array('items'=>$items,'next_page'=>$page < $query->max_num_pages ? $page+1 : null);
    });
    $route('/courses/(?P<course>[1-9][0-9]*)','GET',fn($r)=>auren_app_course((int)$r['course'],true));
    $route('/courses/(?P<course>[1-9][0-9]*)/lessons/(?P<lesson>[1-9][0-9]*)','GET',fn($r)=>auren_app_lesson((int)$r['course'],(int)$r['lesson']));
    $route('/courses/(?P<course>[1-9][0-9]*)/lessons/(?P<lesson>[1-9][0-9]*)/complete','POST',fn($r)=>auren_app_complete((int)$r['course'],(int)$r['lesson']));
    $page=static fn($r)=>max(1,min(1000,(int)($r->get_param('page')?:1)));
    $route('/community','GET',fn($r)=>auren_app_community($page($r)));
    $route('/community/(?P<activity>[1-9][0-9]*)','GET',fn($r)=>auren_app_activity((int)$r['activity']));
    $route('/notifications','GET',fn($r)=>auren_app_notifications($page($r)));
});

add_filter('rest_pre_dispatch',function($result,$server,$request) {
    if (str_starts_with($request->get_route(),'/auren/v1/') && ($request->has_param('user_id') || $request->has_param('user'))) {
        return auren_app_error('invalid_target','La operación solo se permite para tu propia cuenta.',400);
    }
    return $result;
},10,3);

add_filter('rest_post_dispatch',function($response,$server,$request) {
    if (str_starts_with($request->get_route(),'/auren/v1/')) {
        $response = rest_ensure_response($response);
        $response->header('Cache-Control','private, no-store, max-age=0');
        $response->header('Vary','Cookie, Authorization, Origin');
        $response->header('X-Robots-Tag','noindex, nofollow');
    }
    return $response;
},10,3);

<?php
defined('ABSPATH') || exit;

add_action('rest_api_init',function() {
    $route = static function($path,$method,$callback,$public=false) {
        register_rest_route('auren/v1',$path,array('methods'=>$method,'callback'=>$callback,'permission_callback'=>$public ? '__return_true' : 'auren_app_permission'));
    };
    $route('/auth/start','POST','auren_app_auth_start',true);
    $route('/auth/exchange','POST','auren_app_auth_exchange',true);
    $route('/logout','POST','auren_app_logout');
    $route('/me','GET',function() {
        $u = wp_get_current_user();
        return array('id'=>$u->ID,'name'=>$u->display_name,'avatar_url'=>get_avatar_url($u->ID),'site_url'=>home_url('/'),
            'capabilities'=>array('academy'=>auren_app_lms_ready() === true,'community'=>false,'checkout'=>false),
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

<?php
defined('ABSPATH') || exit;

/** Fixed, read-only batch: boot WordPress once, keep every original permission check. */
function auren_app_startup($request=null) {
    if($request instanceof WP_REST_Request && $request->get_params()){return auren_app_error('invalid_request','Esta consulta no acepta parámetros.',400);}
    $read=static function($path,$params=array()){
        $request=new WP_REST_Request('GET','/auren/v1'.$path);
        foreach($params as $key=>$value){$request->set_param($key,$value);}
        $response=rest_do_request($request);
        return $response->get_status()>=200 && $response->get_status()<300?$response->get_data():null;
    };
    $identity=$read('/me');
    if(!$identity){return auren_app_error('startup_unavailable','No pudimos preparar tu academia.',503);}
    return array('identity'=>$identity,'catalog'=>$read('/courses',array('page'=>1)),
        'profile'=>!empty($identity['capabilities']['profile'])?$read('/profile'):null,
        'notifications'=>!empty($identity['capabilities']['notifications'])?$read('/notifications',array('page'=>1,'filter'=>'unread')):null,
        'community'=>!empty($identity['capabilities']['community'])?$read('/community',array('page'=>1,'filter'=>'all','search'=>'')):null);
}

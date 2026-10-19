<?php
defined('ABSPATH') || exit;
add_action('admin_menu',function(){add_options_page('Auren App','Auren App','manage_options','auren-app','auren_app_settings_page');});
add_action('admin_init',function(){
    register_setting('auren_app','auren_app_verified_learndash_version',array('type'=>'string','sanitize_callback'=>'sanitize_text_field'));
    register_setting('auren_app','auren_app_progress_writes',array('type'=>'boolean','sanitize_callback'=>'rest_sanitize_boolean'));
    register_setting('auren_app','auren_app_pilot_restricted',array('type'=>'boolean','sanitize_callback'=>'rest_sanitize_boolean'));
    register_setting('auren_app','auren_app_pilot_users',array('type'=>'string','sanitize_callback'=>function($value){return implode(',',array_filter(array_map('absint',explode(',',(string)$value))));}));
});
function auren_app_settings_page(){
    if(!current_user_can('manage_options')){return;}
    echo '<div class="wrap"><h1>Auren App</h1><p>Los alumnos registrados pueden entrar con su cuenta habitual. Los permisos de cursos y comunidad los decide WordPress.</p>';
    echo '<p>LearnDash instalado: <strong>'.esc_html(defined('LEARNDASH_VERSION')?LEARNDASH_VERSION:'no disponible').'</strong>. Verifica el adaptador y las reglas del curso antes de habilitar escritura.</p>';
    echo '<form method="post" action="options.php">';settings_fields('auren_app');
    echo '<table class="form-table"><tr><th><label for="ldversion">Versión de LearnDash verificada</label></th><td><input id="ldversion" name="auren_app_verified_learndash_version" value="'.esc_attr(get_option('auren_app_verified_learndash_version','')).'"><p class="description">Si no coincide con la versión instalada, la API de cursos permanece cerrada.</p></td></tr>';
    echo '<tr><th>Acceso</th><td><input type="hidden" name="auren_app_pilot_restricted" value="0"><label><input type="checkbox" name="auren_app_pilot_restricted" value="1" '.checked(get_option('auren_app_pilot_restricted',false),true,false).'> Restringir temporalmente a las cuentas del piloto</label><p class="description">Desmarcado: cualquier alumno con una cuenta válida puede iniciar sesión. Sus permisos de cursos no cambian.</p></td></tr>';
    echo '<tr><th><label for="pilot">IDs de alumnos del piloto</label></th><td><input id="pilot" name="auren_app_pilot_users" value="'.esc_attr(get_option('auren_app_pilot_users','')).'"><p class="description">Solo se utiliza si activas la restricción anterior. Separados por comas.</p></td></tr>';
    echo '<tr><th>Progreso</th><td><input type="hidden" name="auren_app_progress_writes" value="0"><label><input type="checkbox" name="auren_app_progress_writes" value="1" '.checked(get_option('auren_app_progress_writes',false),true,false).'> Permitir completar lecciones simples mediante LearnDash</label><p class="description">Videos con progresión obligatoria, tareas, temporizadores y cuestionarios continúan por el flujo web de LearnDash.</p></td></tr></table>';
    submit_button('Guardar ajustes');echo '</form><p><a href="'.esc_url(add_query_arg('auren_app','1',home_url('/'))).'">Abrir Auren App</a></p></div>';
}

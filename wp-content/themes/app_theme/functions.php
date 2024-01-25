<?php
/**
 * app_theme
 *
 */


function fetch_trainer_by_id($id) {
    $response = wp_remote_get("http://localhost/wp-json/wp/v2/trainer/{$id}");

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $trainer = json_decode($body);

    return $trainer;
}


function search_post($email){
	
	 $args = array(
        'post_type'      => 'calendario',
        'posts_per_page' => -1,
        's'              => $email,  // Search term
        'post_title'           => $email,  // Exact match for post name
        // 'meta_query'     => array(
        //     'relation' => 'OR',
        //     array(
        //         'key'     => '_wp_page_template',  // Custom meta key if needed
        //         'value'   => 'template-name.php',   // Custom value if needed
        //         'compare' => '='
        //     )
        // )
    );

    $query = new WP_Query($args);
}
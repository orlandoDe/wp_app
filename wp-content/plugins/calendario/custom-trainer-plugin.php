<?php
/*
Plugin Name: Custom Trainer Plugin
Description: This plugin handles custom trainer functionality.
Version: 1.0
Author: Your Name
*/

function create_new_trainer() {
    $title = sanitize_text_field($_POST['email']); // Get the title from the AJAX request
    $pass = sanitize_text_field($_POST['pass']); // Get the title from the AJAX request

    // Use the WordPress REST API to search for a trainer by title
    $args = array(
        'post_type' => 'trainer',
        'posts_per_page' => 1,
        's' => $title,
    );

    $query = new WP_Query($args);

    $trainers = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Retrieve the information you need about each trainer
            $trainer_data = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'pass' => get_field('pass', get_the_ID()),
                // Add more fields as needed
            );

            $trainers[] = $trainer_data;
        }
    }

    wp_reset_postdata();
    if ($pass==$trainers[0]['pass']) {
    	// Return the results
    	wp_send_json([true,get_the_ID()]);
    }else{
    	wp_send_json([false,'error']);
    }
    
}

// Hook the function to a WordPress action
add_action('wp_ajax_create_trainer', 'create_new_trainer');


function delete_trainer() {
    // Your code to delete a trainer goes here
    echo "HEHE!!";
}

// Hook the function to a WordPress action
add_action('wp_ajax_delete_trainer', 'delete_trainer');
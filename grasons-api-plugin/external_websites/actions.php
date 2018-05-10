<?php 

include_once('account.php');

add_action( 'admin_menu', 'my_admin_menu' );
add_action( 'admin_post_external_websites_form_response', 'external_websites_form_response');

function my_admin_menu() {
    add_menu_page( 'External Website(s) Settings', 'Configuration', 'edit_posts', 'external-websites', 'external_website_display'  );
}

function external_website_display() {
    include_once('display.php');
}

function external_websites_form_response() {
    if(
        isset($_POST['external_website_form_nonce']) && 
        wp_verify_nonce( $_POST['external_website_form_nonce'], 'external_websites_form_response' )
    ) {

        try {
            Account::save($_POST);
            wp_redirect(home_url() . '/wp-admin/admin.php?page=external-websites');
        } catch (PDOException $e) {
            print_r('DATABASE ERROR: ' . $e . '\n');
            print_r('Please contact the Administrator');
        }

    }
}
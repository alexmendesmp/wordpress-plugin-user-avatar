<?php

/* 
  Plugin Name: DC CODE User Avatar
  Plugin URI: 
  Description: Get user profile to use local avatar.
  Version: 1.4
  Author: Alex Mendes
  Author URI: http://www.dccode.com.br
  License: GPLv2
 */

/**
 * Load Text Domain
 * 
 * @since 1.1
 */
add_action( 'plugins_loaded', 'dc_avatar_load_text_domain' );
function dc_avatar_load_text_domain() {
    load_plugin_textdomain( 'dccode-wp-user-avatar', false, dirname( plugin_basename( __FILE__ ) ) );
}

/**
 * Define constants.
 */
$upload_dir = wp_upload_dir();
if( ! defined( 'DCCODE_AVATAR_DIR' ) ) define( 'DCCODE_AVATAR_DIR', $upload_dir['basedir'] . '/avatar_images' );
if( ! defined( 'DCCODE_AVATAR_URL' ) ) define( 'DCCODE_AVATAR_URL', $upload_dir['baseurl'] . '/avatar_images' );

/**
 * Check and create Avatar images folder in case it doesn't exist.
 */
function dccode_user_avatar_activate_function() {
    
    $upload_dir = wp_upload_dir();
    $avatar_folder_name = $upload_dir['basedir'] . '/avatar_images';
    
    if( ! file_exists( $avatar_folder_name ) && ! is_dir( $avatar_folder_name ) ) {
        if( ! wp_mkdir_p( $avatar_folder_name ) ) {
            die( __( 'Sorry. We were not able to create necessary folder for the avatar images', 'dccode-wp-user-avatar' ) );
        }
    }
}
register_activation_hook( __FILE__, 'dccode_user_avatar_activate_function' );

/*
 * Change user's avatar url to local url. 
 * 
 * @since 1.0
 * 
 * @param array $args Avatar especifications.
 * @param int $id_or_email user ID.
 * @return array $args.
 */
add_filter( 'pre_get_avatar_data', 'dccode_user_insert_avatar_url', 10, 2 );
function dccode_user_insert_avatar_url( $args, $id_or_email ) {

    if( ! is_array( $args ) || is_null( $id_or_email )) {
        return $args;
    }
    
    /**
     * Check whether user already has gravatar account.
     */
    if( $args['found_avatar'] ) {
        return $args;
    }

    if( ! is_null( $id_or_email ) && is_numeric( $id_or_email ) ) {
        $avatar_url = get_user_meta( $id_or_email, 'user_avatar', true );
    } else {
        if( ! is_null( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
            if( $user ) {
                $avatar_url = get_user_meta( $user->ID, 'user_avatar', true );
            }
        } else {
            return $args;
        }
    }
    
    if( isset( $avatar_url ) && !empty( $avatar_url ) ) {
        $args['found_avatar'] = ( boolean ) 1;
        $args['url'] = $avatar_url . '?cb=' . floor( rand( 1, 9 ) * 999999 );
    }
    return $args;
    
}

/** 
 * CUSTOM UPLOAD FILES 
 */
function dccode_user_avatar_post_edit_form_tag() {
    echo ' enctype="multipart/form-data"';
}
add_action('user_edit_form_tag', 'dccode_user_avatar_post_edit_form_tag');

/**
 * Add Photo(Avatar) metabox upload field to user profile.
 */
function avatar_image_add_custom_field( $user ) {
    
    define( 'DCCODE_AVATAR_CHANGE_UPLOAD_DIR', true );
    // Save userid to use in the future.
    update_option( 'dccode_avatar_userid', $user->ID );
    
    wp_nonce_field('avatar_image_add_custom_field', 'avatar_image_nonce' );
    $avatar = get_avatar( $user->ID, 105 );
    ?>
    <p>
        <table class="form-table">
            <tbody>
                <tr class="user-foto-colunistas-wrap">
                    <th>
                        <label for="avatar_image"><?php echo __( 'User photo: ', 'dccode-wp-user-avatar' ); ?></label>
                    </th>
                    <td>
                        <!--<input type="file" name="avatar_image" id="avatar_image" />-->
                        <p><div id="dccode_avatar_image"><?php echo $avatar ?></div></p>
                        <p class="howto"><?php echo __( 'Select an image to be your Avatar. ', 'dccode-wp-user-avatar' ); ?></p>
                        <input type="button" id="upload_image_button" name="upload_image_button" value="<?php echo __( 'Select Image ', 'dccode-wp-user-avatar' ); ?>" class="button button-large button-controls" />
                    </td>
                </tr>
            </tbody>
        </table>
    </p>
    
    <?php
}
add_action( 'edit_user_profile', 'avatar_image_add_custom_field' );
add_action( 'show_user_profile', 'avatar_image_add_custom_field' );

/**
 * Saves metadata.
 * THIS FUNCTION HAS BEEN DEPRICATED SINCE 1.4
 * 
 * @since 1.0
 */
function avatar_image_meta_save2($user_id) {

    if(isset( $_POST['avatar_image_nonce'] ) && wp_verify_nonce( $_POST['avatar_image_nonce'], 'avatar_image_add_custom_field' ))
    {
        if( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        if( ! empty( $_FILES['avatar_image'] ) ) 
        {
            //die( print_r( $_FILES ) );
            
            list( $filename, $extension ) = explode( '.', $_FILES['avatar_image']['name'] );
            $tmp_name = $_FILES['avatar_image']['tmp_name'];
            $filename = md5( $user_id ) . '.' . $extension;
            $filename_url = DCCODE_AVATAR_URL . '/' . $filename;
            $destination = DCCODE_AVATAR_DIR . '/' . $filename;
            
            if( file_exists( $destination ) ) {
                unlink( $destination );
            }
           
            if( move_uploaded_file( $tmp_name, $destination ) ) {
                update_user_meta( $user_id, 'user_avatar', $filename_url );
            }
        }

    }
}
//add_action( 'personal_options_update', 'avatar_image_meta_save2' );
//add_action( 'edit_user_profile_update', 'avatar_image_meta_save2' );

/**
 * Scripts
 * 
 * @since 1.4
 */
function my_admin_scripts() {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_register_script('avatar-script-upload', plugin_dir_url( __FILE__ ) . '/js/avatar-script-upload.js', array('jquery','media-upload','thickbox'));
    wp_enqueue_script('avatar-script-upload');
}
function my_admin_styles() {
    wp_enqueue_style('thickbox');
}
add_action('admin_print_scripts', 'my_admin_scripts');
add_action('admin_print_styles', 'my_admin_styles');

/**
 * UPLOAD FILTER
 */
add_action( 'upload_dir', 'dccode_avatar_upload_dir' );
function dccode_avatar_upload_dir( $urls ) {
    
    global $pagenow;
    
    //print_r( $_REQUEST );
    
    /**
     * Custom upload and path urls
     * 
     * @since 1.4
     */
    if( 'async-upload.php' === $pagenow && DCCODE_AVATAR_CHANGE_UPLOAD_DIR ) {
        $urls = array(
            'path'      => DCCODE_AVATAR_DIR,
            'url'       => DCCODE_AVATAR_URL,
            'subdir'    => 'avatar_images',
            'basedir'   => str_replace('/avatar_images', '', DCCODE_AVATAR_DIR ),
            'baseurl'   => str_replace('/avatar_images', '', DCCODE_AVATAR_URL ),
            'error'     => false
        );
        /**
         * Add Custom Image Size
         * 
         * @since 1.4
         */
        add_image_size( 'dccode_avatar-105x90', 105, 90, true);
        add_image_size( 'dccode_avatar-96x96', 96, 96, true);
    }
    /**
     * Remove the action to prevent it to be executed
     * by other functions
     */
    add_action( 'media_send_to_editor', 'dccode_update_avatar', 10, 3 );
    return $urls;
    //update_user_meta( $user_id, 'user_avatar', $filename_url );
    
}

function dccode_update_avatar( $html, $send_id, $attachment ) {
    $user_id = get_option( 'dccode_avatar_userid' );
    $attachment_id = $send_id;
    $url = array();
    if( $tmp = wp_get_attachment_image_src( $attachment_id, 'dccode_avatar-105x90' ) ) {
        $url = $tmp[0];
    } else {
        $url = '';
    }
    // Update userdata.
    update_user_meta( $user_id, 'user_avatar', $url );
    update_user_meta( $user_id, 'user_avatar_attachment_id', $attachment_id );
    
    if( ! function_exists( 'media_send_to_avatar_placeholder' ) ) {
        function media_send_to_avatar_placeholder( $html ) {
        ?>
        <script type="text/javascript">
        var win = window.dialogArguments || opener || parent || top;
        win.send_to_editor( <?php echo wp_json_encode( $html ); ?> );
        </script>
        <?php
        }
        media_send_to_avatar_placeholder( $html );
    }
    delete_option( 'dccode_avatar_userid' );
    remove_action( 'media_send_to_editor', 'dccode_update_avatar' );
}
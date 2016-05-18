// Open up WP File Manager!

jQuery(document).ready(function() {
    jQuery('#upload_image_button').click(function() {
        formfield = jQuery('#upload_image').attr('name');
        tb_show('', 'media-upload.php?type=image&TB_iframe=true&dc_avatar_action=upload');
        return false;
    });
    window.send_to_editor = function(html) {
        //alert( html );
        if( html !== null ) {
            jQuery('#dccode_avatar_image').html(html);
            //imgurl = jQuery('img',html).attr('src'); //image
            //jQuery('#upload_image').val(imgurl);
            tb_remove();
        }
    };
});    
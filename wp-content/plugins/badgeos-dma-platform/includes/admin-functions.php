<?php
add_action('admin_menu', 'badgeos_dma_menu');

function badgeos_dma_menu() {
    add_options_page('BadgeOS - DMA', 'DMA', 'manage_options', 'badgeos-dma', 'badgeos_dma_options');
}

function badgeos_dma_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }   

    $rewards_email          = get_option('rewards_email');
    $elastic_search         = get_option('elastic_search');
    $elastic_search_host    = get_option('elastic_search_host');
    $elastic_search_port    = get_option('elastic_search_port');

    if (!empty($_POST)) {
        $rewards_email          = $_POST['rewards_email'];
        $elastic_search         = $_POST['elastic_search'];
        $elastic_search_host    = $_POST['elastic_search_host'];
        $elastic_search_port    = $_POST['elastic_search_port'];

        update_option('rewards_email', $rewards_email);
        update_option('elastic_search', $elastic_search);
        update_option('elastic_search_host', $elastic_search_host);
        update_option('elastic_search_port', $elastic_search_port); 
?>
<div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
<?php
    }   

?>
<form name="badgeos-settings" method="POST" action="">
    <p>
        <?php _e("Rewards Email", 'rewards_email'); ?>
        <input type="text" name="rewards_email" value="<?php echo $rewards_email; ?>">
    <p>
        <?php _e("Use Elastic Search", 'elastic_search'); ?>
        <input type="checkbox" name="elastic_search" value="1" <?php if ($elastic_search) echo "checked=1"; ?>>
    <p> 
        <?php _e("Elastic Search Host:", 'elastic_search_host' ); ?>  
        <input type="text" name="elastic_search_host" value="<?php echo $elastic_search_host; ?>">
    </p>
    <p> 
        <?php _e("Elastic Search Port:", 'elastic-search-port' ); ?>  
        <input type="text" name="elastic_search_port" value="<?php echo $elastic_search_port; ?>">
    </p>

    <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>
    
</form>
<?php
}


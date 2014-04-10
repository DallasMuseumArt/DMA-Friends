<?php

// Ignore if we are not on the command line
if (!class_exists('WP_CLI_Command')) return;

use Illuminate\Database\Capsule\Manager as Capsule;

class DMA_CLI_Command extends WP_CLI_Command {
    function migrate_logs() {
        Capsule::transaction(function() {
            $log_entries = Capsule::table('dma_log_entries')->get();
            $count = count($log_entries);

            WP_CLI::success($count . " entries to migrate");

            $badgeos_settings = get_option( 'badgeos_settings' );

            foreach ( $log_entries as $delta => $log_entry ) { 
                $log = new $badgeos_settings['log_factory']();
                $title = $log_entry['title'];
                $log_entry['points_earned'] = $log_entry['awarded_points'];
                $log->write($title, $log_entry);

                echo '.';
            }   
        
            WP_CLI::success("Successfully migrated " . $count);
        }); 

    }

    function remove_old_logs() {
        Capsule::transaction(function() {
            $posts = Capsule::table('posts')->where('post_type', '=', 'badgeos-log-entry')->get();
            $count = count($posts);
        
            foreach($posts as $post) {
                wp_delete_post($post['ID'], true);
                echo '.';
            }

            WP_CLI::success('Successfully deleted ' . $count . ' logs');
        });
    }
}

WP_CLI::add_command( 'dma', 'DMA_CLI_Command' );

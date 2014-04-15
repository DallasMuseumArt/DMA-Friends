<?php

// Ignore if we are not on the command line
if (!class_exists('WP_CLI_Command')) return;

use Illuminate\Database\Capsule\Manager as Capsule;

class DMA_CLI_Command extends WP_CLI_Command {

    /**
     * Migrates any log entries in the dma_log_entries table
     * to the new badgeos_logs table
     */
    function migrate_logs() {
        // Do this first so entries are properly named when pushed to the logging data streams
        WP_CLI::line('Clean log entries');
        self::clean_logs();

        Capsule::transaction(function() {
            $log_entries = Capsule::table('dma_log_entries')->get();
            $count = count($log_entries);

            WP_CLI::success($count . " entries to migrate");

            $badgeos_settings = get_option( 'badgeos_settings' );

            foreach ( $log_entries as $i => $log_entry ) { 
                $log = new $badgeos_settings['log_factory']();
                $title = $log_entry['title'];
                unset($log_entry['title']);

                $log_entry['points_earned'] = $log_entry['awarded_points'];
                unset($log_entry['awarded_points']);
                unset($log_entry['admin_id']);

                if ($log_entry['object_id'] == ARTWORK_OBJECT_ID && !empty($log_entry['artwork_id'])) {
                    $title = "{$log_entry['user_id']} liked the work of art {$log_entry['artwork_id']}";
                    $log_entry['object_id'] = $log_entry['artwork_id'];
                    $log_entry['action'] = 'artwork';
                    unset($log_entry['artwork_id']);
                    $log->write($title, $log_entry);
                } else {
                    unset($log_entry['artwork_id']);
                    $log->write($title, $log_entry);
                }

                echo $i . ' records of ' . $count . " processed\n";
            }   
        
            WP_CLI::success("Successfully migrated " . $count);
        }); 

    }

    /**
     * By default badgeos stores logs as "posts".
     * After migration get rid of any of these posts
     * to clean up the table
     */
    function remove_old_logs() {
        Capsule::transaction(function() {
            $posts = Capsule::table('posts')->where('post_type', '=', 'badgeos-log-entry')->get();
            $count = count($posts);
        
            foreach($posts as $i => $post) {
                wp_delete_post($post['ID'], true);
                echo $i . ' records of ' . $count . " processed\n";
            }

            WP_CLI::success('Successfully deleted ' . $count . ' logs');
        });
    }

    function clean_logs() {
        Capsule::transaction(function() {
            // Migrate checked-in logs to read 'checkin' instead
            Capsule::table('dma_log_entries')
                ->where('action', 'checked-in')
                ->update(array('action' => 'checkin'));

            // Clean up some orphaned logs that have no action and are checkins
            Capsule::table('dma_log_entries')
                ->where('title', 'like', '%checked-in at %')
                ->update(array('action' => 'checkin'));
    
            // Do the same update for rewards
            Capsule::table('dma_log_entries')
                ->where('action', 'claimed-reward')
                ->update(array('action' => 'reward'));

            // And clean up triggered events
            Capsule::table('dma_log_entries')
                ->where('title', 'like', '%triggered%')
                ->update(array('action' => 'triggered'));
        });
    }

}

WP_CLI::add_command( 'dma', 'DMA_CLI_Command' );

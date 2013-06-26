<h2>
	<?php _e('Cron Scheduling', 'pmxi_plugin') ?>
</h2>

<p><strong>Trigger Script</strong></p>

<p>Trigger script runs when you want to activate the import for processing. Every time you want to schedule the import, run the trigger script.</p>

<p>To schedule the import to run once every 24 hours, run the trigger script every 24 hours. Most hosts require you to use “wget” to access a URL. Ask your host for details.</p>

<p><i>Example:</i></p>

<p>wget "<?php echo home_url() . '?import_key=' . $cron_job_key . '&import_id=' . $id . '&action=trigger'; ?>"</p>
 
<p><strong>Execution Script</strong></p>

<p>Run the execution script every two minutes. The execution script checks to see if any imports need to be processed. If so, it processes them. It processes in iteration (only importing a few records each time it runs) to optimize server load</p>

<p><i>Example:</i></p>

<p>wget "<?php echo home_url() . '?import_key=' . $cron_job_key . '&import_id=' . $id . '&action=processing'; ?>"</p>
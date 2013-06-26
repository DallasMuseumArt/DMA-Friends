<?php 
$day_msg_stat =	get_option('day_msg_stat');
if (empty($day_msg_stat)):
	$day_msg_stat	= array();
else:
	$day_msg_stat	= json_decode($day_msg_stat, true);
endif;	
?>
<table class="wp-list-table widefat fixed bookmarks">
                <thead>
                    <tr>
                        <th colspan="2">SMS Statistics</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width="200">Remaining SMS Credit</td>                    
                        <td><strong><?php echo get_option('rem_sms_credit'); ?></strong></td>                    
                    </tr>
                    
                    <tr>
                        <td width="200">Total SMS Send</td>                    
                        <td><strong><?php echo get_option('all_msg_send'); ?></strong></td>                    
                    </tr>
                    
                    <tr>
                        <td width="200">SMS Send Today</td>                    
                        <td><strong><?php echo @$day_msg_stat[date('Y-m-d')]; ?></strong></td>                    
                    </tr>
                    
                    
                    <tr>
                    	<td colspan="2" align="center";>
                        	<?php if (!empty($day_msg_stat)): ?>
							<script type="text/javascript" src="https://www.google.com/jsapi"></script>
							<script type="text/javascript">
                              google.load("visualization", "1", {packages:["corechart"]});
                              google.setOnLoadCallback(drawChart);
                              function drawChart() {
                                 var data = google.visualization.arrayToDataTable([
								  ['Date', 'SMS Sent'],
								  <?php foreach ($day_msg_stat as $date=>$smsSend): ?>
								  	['<?php echo $date; ?>',<?php echo $smsSend; ?>],
								  <?php endforeach;?>
								]);
                        
                                var options = {
                                  title: 'SMS Sent',
                                  hAxis: {title: 'Date',  titleTextStyle: {color: 'red'}}
                                };
                        
                                var chart = new google.visualization.ColumnChart(document.getElementById('sms_chart_div'));
                                chart.draw(data, options);
                              }
                            </script>
                            <div id="sms_chart_div" style="width:100%; height:400px; margin-top:15px; margin-bottom:10px;">
                            </div>
                            <?php else: ?>
                            	No sms send records found.
                            <?php endif;?>
                        </td>
                    </tr>
                   	
                    
                </tbody>
            </table>
            <br/>
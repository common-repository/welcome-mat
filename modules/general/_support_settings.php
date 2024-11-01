<?php namespace MaxInbound; 
defined('ABSPATH') or die('No direct access permitted');

if ($view == 'support'): 
	$support_link = MI()->ask('system/support/link'); 

?>
<p><?php _e( sprintf("All support is handled through the %s support forums %s","<a href='$support_link' target='_blank'>", "</a>"), 'maxinbound' ); ?> </p>
<p>	<?php _e("When asking for support please provide an accurate description of the issues.", 'maxinbound'); ?></p>


<?php endif; ?>

<?php if ($view == 'systeminfo'): ?> 
<ul class='option-row'>
<?php foreach ($support_data as $name => $support):  
	$title = $support['title']; 
	$data = $support['data']; 
	$status_code = isset($support['status']) ? $support['status'] : false; 
	
	?> 
	<li><label><?php echo $title ?></label>
	<span <?php if ($status_code) echo 'class="status ' . $status_code . '"' ?> >
	<?php if (is_array($data)) 
	{
		foreach($data as $more_data) 
			echo $more_data . "<br>"; 
	}
	else
		echo $data; 
	?>
	</span>
	</li>
	<?php
	
	endforeach;
?>
</ul>
<?php endif; ?> 
 

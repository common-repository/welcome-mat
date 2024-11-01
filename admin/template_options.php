<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

if (! MI()->whistle->hasOffer('editor/module-options')) 
	return; 
	
	$options =  MI()->collect('editor/module-options'); 
?>

<div class='mb_tab option-container'>
		<div class="title">
			<span class="dashicons dashicons-admin-settings"></span>
			<span class="title"><?php _e("Options","maxinbound"); ?></span>
		</div>
		
		<div class='inside'> 	
			<div class='template-options'>
				
				<?php 
				foreach($options as $page) 
				{
					echo $page; 
				}
				 ?>			
			</div>
		</div>

</div>

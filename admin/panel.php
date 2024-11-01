	<div class='mi_secondary' id='panel-<?php echo $field_name ?>'>
		<div class="side">  </div>
		<div class="title"><h2><?php echo $editor_title ?></h2>
						   <span class='close dashicons dashicons-no-alt'></span>
		</div>
		<div class='inside'>
			<?php 
				echo $editor->panel(); 
			?>
		</div>	
	</div>

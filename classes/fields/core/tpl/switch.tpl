<span class='input %%type%% %%name%% switch_button'>
	{if:label}	<label for='%%id%%' class='switch_label %%name%%'><span>%%label%%</span></label> {/if:label}				
    <label for='%%id%%' tabindex='0'>		
		<input type="checkbox" name="%%name%%" data-field='%%name%%' id='%%id%%'
			{if:inputclass}class="%%inputclass%%"{/if:inputclass} 
			value='%%inputvalue%%' 
			%%checked%% 
		>
	   
		<div class='the_switch' >

		</div>
	</label>
	
</span>


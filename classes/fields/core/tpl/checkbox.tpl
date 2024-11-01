<span class='input %%type%% %%name%%'> 	
		<input type='%%type%%' name='%%name%%' id='%%id%%' data-field='%%name%%' 
			{if:inputclass}class="%%inputclass%%"{/if:inputclass} 
			value='%%inputvalue%%' 
			%%checked%% 
		/>
		<label for='%%id%%' {if:title}title="%%title%%"{/if:title} >
		{if:icon}	<i class='%%icon%%'></i>	{/if:icon}
		{if:label}	%%label%% {/if:label}	
		</label>
</span>

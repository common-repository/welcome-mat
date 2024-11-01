<span class='input %%type%% %%name%%'>
{if:title} <label>%%title%%</label> {/if:title}
<input type='%%type%%' 
		{if:id} id='%%id%%' {/if:id} 
		name='%%name%%' 
		value='%%value%%'
		{if:inputclass} class='%%inputclass%%' {/if:inputclass}
		{if:placeholder} placeholder='%%placeholder%%' {/if:placeholder}
		{if:min} min='%%min%%' {/if:min} 
		{if:max} max='%%max%%' {/if:max} 
		{if:step} step='%%step%%' {/if:step} 
	>
</span>

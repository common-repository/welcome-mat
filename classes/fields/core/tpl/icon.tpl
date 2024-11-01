<span class='input icon %%name%%'>
{for:icons}
<div class='icon_container'>
	<input type='radio' id='%%id%%' name='%%name%%' %%checked%% value='%%key%%' {if:inputclass}class="%%inputclass%%"{/if:inputclass}  >	
	<label for='%%id%%' {if:title} title="%%title%%" {/if:title}>
		<i class='%%item%%'></i>
	</label>
</div>
{/for:icons}
</span>

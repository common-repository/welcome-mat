<div class='input image image-preview' id='%%name%%'>
{if:label} <label>%%title%%</label> {/if:label}
	<p class='placeholder {if:src} has-image {/if:src}'>
		<img {if:src} src='%%src%%' {/if:src} class='editor_preview'>
	</p>
<input type='hidden' name='%%name%%' value='%%value%%'>

<p class='toolbar'>%%toolbar%%</p> 
 </div>

<h3>Screenshots</h3>
{if $playerid != ""}
<p>Player ID: {$playerid}</p>
{/if}
{if $screenshots|@count > 0}
<div class="screenshots">
{foreach from=$screenshots item=shot}
    <div style="display:inline-block;margin:5px;">
        <a href="demos/{$shot}" target="_blank"><img src="demos/{$shot}" alt="{$shot}" style="max-width:300px;max-height:300px;" /></a>
    </div>
{/foreach}
</div>
{else}
<p>No screenshots found.</p>
{/if}

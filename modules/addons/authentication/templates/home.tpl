<link rel="stylesheet" href="{$assets}assets/css/style.css?v61">
<style type="text/css">
    h1 {
        display: none;
    }
</style>

{include file="./navbar.tpl"}

<div class="row">
    <div class="col-md-12">
		{if $notice}
	        {$notice}
	    {/if}
    </div>
    <div class="col-md-12">
        {include file="./$PageName.tpl"}
    </div>
    <div class="col-xs-12 foot text-center">
        <p>Copyright &copy; NeWorld Cloud Ltd. All Rights Reserved.</p>
    </div>
</div>
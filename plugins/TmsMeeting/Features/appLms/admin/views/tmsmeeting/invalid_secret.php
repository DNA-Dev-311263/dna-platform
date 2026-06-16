<?php 
	// Imposto il titolo della pagina
	echo getTitleArea(Lang::t('_TMSMEETING.SECRET_INVALID_TITLE', 'message'));
?>

<div class="std_block">
	<div class="tms-token-message">
		<?php 
			echo Lang::t('_TMSMEETING.SECRET_INVALID_MESSAGE', 'message'); 
		?>
	</div>
	<div style="width:300px;margin-top:100px; margin-left:50px;"> 
	
	<?php
		// Link di accesso
		echo "<a class='forma-button forma-button--green forma-button--orange-hover' href='".$auth_url."' target = '_blank' >".Lang::t('_OPEN', 'standard')."</a>";
	?>
	</div>    
</div>

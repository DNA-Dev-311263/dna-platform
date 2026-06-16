<script type="text/javascript">
YAHOO.util.Event.onDOMReady(function() {

	var dialogEvent = function(e, extraParams = {}) {
		
		 var oConfig = {
			modal: <?php echo $modal ? 'true' : 'false'; ?>,
			close: <?php echo $close ? 'true' : 'false'; ?>,
			visible: <?php echo $visible ? 'true' : 'false'; ?>,
			fixedcenter: <?php echo $fixedCenter ? 'true' : 'false'; ?>,
			constraintoviewport: <?php echo $constrainToViewport ? 'true' : 'false'; ?>,
			draggable: <?php echo $draggable ? 'true' : 'false'; ?>,
			hideaftersubmit: <?php echo $hideAfterSubmit ? 'true' : 'false'; ?>,
			isDynamic: false,
			confirmOnly: <?php echo $confirmOnly ? 'true' : 'false'; ?>,
			directSubmit: <?php echo (isset($directSubmit) && $directSubmit) ? 'true' : 'false'; ?>,
			header: <?php echo $header; ?>,
			body: <?php echo $body; ?>
		};
		
		//ABR (per configurazioni dinamiche all'apertura mediante chiamata JS)
		oConfig = Object.assign(oConfig, extraParams)

		<?php
            //dynamic parameters
            if (isset($width) && $width) {
                echo 'oConfig.width = "' . $width . '";' . "\n";
            }
            if (isset($height) && $height) {
                echo 'oConfig.height = "' . $height . '";' . "\n";
            }
            if (isset($renderEvent) && $renderEvent) {
                echo 'oConfig.renderEvent = ' . $renderEvent . ';' . "\n";
            }
            if (isset($destroyEvent) && $destroyEvent) {
                echo 'oConfig.destroyEvent = ' . $destroyEvent . ';' . "\n";
            }
            if (isset($callback) && $callback) {
                echo 'oConfig.callback = ' . $callback . ';' . "\n";
            }
        ?>

		CreateDialog("<?php echo $id; ?>", oConfig).call(this, e); //ABR
	}

	<?php echo $callEvents; ?>
	<?php echo $callDelegateEvents; //ABR ?> 
	<?php echo $callObjectFunc; //ABR ?>	

});
</script>

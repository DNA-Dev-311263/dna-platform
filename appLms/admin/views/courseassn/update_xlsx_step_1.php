<br />

<?php


$title = array(	Lang::t('_UPDATE_ASSIGNMENT', 'courseassn'),
				$id_org => $org_name. " - File"
			  );
			  

echo	getTitleArea($title)
		.'<div class="std_block">'
		.Form::openForm('update_courseassn', 'index.php?r='.$this->base_link_courseassn.'/updateAssnWizard&id_org='.$id_org, false, false, 'multipart/form-data')
		.Form::openElementSpace()
		.Form::getFilefield( Lang::t('_IMPORT_FILE', 'subscribe').'&emsp;(*.xlsx)', 'file_import', 'file_import')
		.Form::getHidden('step', 'step', '1')
		.Form::closeElementSpace()
		.Form::openButtonSpace()
		.Form::getButton('next', 'next', Lang::t('_NEXT', 'subscription'))
		.Form::getButton('undo', 'undo', Lang::t('_UNDO'))
		.Form::closeButtonSpace()
		.Form::closeForm()
		.'</div>';
		
		
?>

<script type="text/javascript">

//Controllo tipo file
$(function() {
    $('#file_import').change( function() {
        var filename = $(this).val();
        if ( ! /\.xlsx$/.test(filename)) {
            $(this).val('');
            alert("<?php echo Lang::t('_MIME_TYPE_WRONG', 'standard') ?>");
            
        }
    });
});

</script>

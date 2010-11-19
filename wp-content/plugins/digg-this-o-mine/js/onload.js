jQuery(document).ready(function($){
	$("#dtom_bgcolor").css({ cursor : 'pointer' }).click(function(e){
		$("#colorPickerMap").slideToggle();
	});
	$("#colorPickerMap").farbtastic("#dtom_bgcolor");
});
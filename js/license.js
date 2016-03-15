/*
 * license.js - client side code for license form
 */

$(document).ready(function(){

	$("#program_pk").change( function(){
		if ($("#program_pk").val() == "40") {
			$('#campus_id').val("20");
		}else{
			$('#campus_id').val("");
		}
	});

});

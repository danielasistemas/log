/*
 * autoload.js - any code in this file is loaded automatically on every page
 */

$(document).ready(function(){

	//define so console.log won't break script w/o firebug
	if (!console){
		var console = {log: function(){}};
	}

	//display version of jQuery
	var ver = $().jquery;
	console.log("jQuery version: " + ver);

	//hide the js required message and display forms on form pages
	$('#script_div').hide();
	$('#form_div').show();

	if ($("#form1").length > 0){

		$("#form1").validate({
			errorElement: "span",
			errorClass: "invalid",
			errorPlacement: function (error, element) {
				error.insertAfter(element);
			}
		});

		//$(".datepicker").datepicker({ dateFormat: 'yy-mm-dd' });

	} //end if form1

	$("#icon_print").click(function(){ window.print(); });

	$('a.delete').click(function(event){
		event.preventDefault();
		var target = $(this).attr("href");

		var delete_confirm = $('<div></div>')
		.html('<p>This record will be permanently deleted and cannot be recovered. Are you sure?</p>')
		.dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			title: 'Confirm Delete',
			buttons: {
				"Delete": function(){
					$(this).dialog("close");
					window.location.href = target;
				},
				"Cancel": function(){
					$(this).dialog("close");
				}
			}
		});

		delete_confirm.dialog("open");
	});

 // $("table.sortable").tablesorter({ headerTemplate : '{icon}{content}' });

});

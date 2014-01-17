

/* Init the nav panel, since it's outside all pages it doesn't happen automatically */
//$( document ).one( "pagecreate", function() {
//	$( "body > [data-role='panel']" ).panel();
//	$( "body > [data-role='panel'] [data-role='listview']" ).listview();
//	$('#nav_panel').panel( "option", "animate", false );
//});
//$( document ).on( "pageshow", function() {
//	$( "body > [data-role='header']" ).toolbar();
//	$( "body > [data-role='header'] [data-role='navbar']" ).navbar();
//	alert("show");
//	nav_panel_open();
//});

//$(document ).on('pagebeforeshow', function(){       
//$( "[data-role='page']" ).on('pagebeforeshow', function(){       
//	alert("before show");
//	nav_panel_open();
//});


/* Keep the panel open on window resize (or close it if the window gets too small */
/*$(window).resize(function()  {
//    nav_panel_open();
});
*/

$( document ).one( "pagecreate", function( event ) {
	// Global navmenu panel
	$( "body > [data-role='panel']" ).panel();
	$( "body > [data-role='panel'] [data-role='listview']" ).listview();
	$( "body > [data-role='panel'] [data-role='collapsible']" ).collapsible();
	$( "body > [data-role='header']" ).toolbar();
//	$( "body > [data-role='header'] [data-role='navbar']" ).navbar();

//	$( "#leftnav ul" ).listview();
//	$( ".jqm-navmenu-panel ul" ).listview();

//	$( ".leftnav_button" ).on( "click", function() {
//		page.find( ".leftnav_panel:not(.jqm-panel-page-nav)" ).panel( "open" );
//		page.find( "#leftnav" ).panel( "open" );
//	});
});

function leftnav_main_menu_open() {
	$('#leftnav_main').collapsible( "expand" );
}
function leftnav_main_menu_open() {
	$('#leftnav_main').collapsible( "collapse" );
}

$( document ).on( "pagecreate", function( event ) {
	// Attach a submit handler to the form
	$( ".sfiab_form" ).submit(function( event ) {

		var form = $(event.target);
		var form_id = form.attr('id');
		var form_missing_msg = $("#"+form_id+"_missing_msg");
		var form_button = $("#"+form_id+"_submit");
		var page = form.closest("div[data-role=page]");

		// Stop form from submitting normally
		event.preventDefault();
		$.post( form.attr('action'), form.serialize(), function( data ) {

			$('#'+form_id+' button').each(function(index) {
				$(this).attr('disabled', true);
				$(this).text($(this).attr('data-alt2'));
			});

			// Clear all errors and add
			$("#"+form_id+" label").removeClass('error');
			var nav_li = $('#left_nav_'+page.attr('id')+' span');
			if(data.missing.length > 0) {
				nav_li.show();
				nav_li.text(data.missing.length);
				form_missing_msg.show();
				for(var i=0; i<data.missing.length; i++) {
					var label = $("label[for='"+form_id+"_"+data.missing[i]+"']");
					label.addClass('error');
				}
			} else {
				nav_li.hide();
				form_missing_msg.hide();
			}

			// Redo any specified leftnav error counts 
			for(var i=0; i<data.left_error_count.length; i++) {
				var nav_li=$("#left_nav_"+data.left_error_id[i]+" span");
				var count=data.left_error_count[i];

				if(count > 0) {
					nav_li.show();
					nav_li.text(count);
				} else {
					nav_li.hide();
				}
			}

			if(data.location != '') {
				window.location = data.location;
			}

			return false;
		}, "json");
		// Stop any more actions
		return false;
	});

	/* Catch all button clicks in a form.  Use the button's value= to copy
	 * into a <input type=hidden name=action, then submit the form which
	 * basically calls the above function */
	$( ".sfiab_form button" ).click(function(e) {
		e.preventDefault();
		var form = $(this).closest('form');
		var hidden = $('#'+form.attr('id')+' input.sfiab_form_action');
		hidden.attr('value', $(this).attr('value'));
		form.submit();
	});

	$( ".sfiab_form :input" ).change(function() {
		var form_id= $(this).closest('form').attr('id');
		$('#'+form_id+' button').each(function(index) {
			$(this).removeAttr('disabled');
			$(this).text($(this).attr('data-alt1'));
		});
	});

	$( ".sfiab_form :input" ).keyup(function() {
		var form_id= $(this).closest('form').attr('id');
		$('#'+form_id+' button').each(function(index) {
			$(this).removeAttr('disabled');
			$(this).text($(this).attr('data-alt1'));
		});
	});

});

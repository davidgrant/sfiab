

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

function sfiab_form_update_vals(form_id, vals)
{
	// Change field values based on the response
	for(var i=0; i<vals.length; i++) {
		var v = vals[i];
		var e = $("#"+form_id+"_"+v[0]);
		var type = e.prop('type');
		if(type == 'checkbox') {
			e.prop('checked', v[1]);
			e.checkboxradio("refresh");
		} else if(type == 'select-one') {
			e.val(v[1]);
			e.selectmenu("refresh");
		} else if(type == 'fieldset') {
			// radio buttons or check group
			var handled = false;
			e.find('input').each(function(index) {
				// prop checks active state
				// attr checks html state on load
				$(this).prop('checked', false);
				if($(this).attr('type') == 'radio') {
					var checked = ($(this).val() == v[1]) ? true : false;
					$(this).prop('checked', checked);
					handled = true;
				}
			});
			if(!handled) {
				alert("implement check group update_vals");
			}
			e.find('input').each(function(index) {
				$(this).checkboxradio("refresh");
			});
		} else if(e.prop('tagName') == 'SPAN') {
			e.html(v[1]);
		} else {
			e.val(v[1]);
		}
	}

}

$( document ).on( "pagecreate", function( event ) {
	// Attach a submit handler to the form
	$( ".sfiab_form_ajax" ).submit(function( event ) {

		// Stop form from submitting normally
		event.preventDefault();

		var form = $(event.target);
		var form_id = form.attr('id');
		var form_button = $("#"+form_id+"_submit");
		var page = form.closest("div[data-role=page]");
		var page_id = page.attr('id');
		var form_missing_msg = $("#"+page_id+"_missing_msg");
		var form_error_msg = $("#"+page_id+"_error_msg");
		var form_happy_msg = $("#"+page_id+"_happy_msg");
		var form_info_msg = $("#"+page_id+"_info_msg");
		var submit_value = $('#'+form_id+' input.sfiab_form_action').attr('value');

		var pre_submit_fn = window[form_id + '_pre_submit'];
		if(typeof pre_submit_fn === 'function') {
			pre_submit_fn(form);
		}

		$.post( form.attr('action'), form.serialize(), function( data ) {

			/* Run a function before anything else happens.
			 * We use this in the awards editor to catch a "New Sponsor" and insert
			 * the new sponsor into the sponsor list before the update_vals() function
			 * below changes the select to point to the new sponsor */
			var pre_post_submit_fn = window[form_id + '_pre_post_submit'];
			if(typeof pre_post_submit_fn === 'function') {
				pre_post_submit_fn(form, data);
			}

			/* Disable the button if the status is 0, else 
			 * leave it alone because there was an error and 
			 * we want the user to click again
			 * Don't touch buttons without a data-alt2 attr */
			if(data.status == 0) {
				/* Disable all buttons with a data-alt2 attribute */
				$('#'+form_id+' button[data-alt2]').attr('disabled', true);

				/* Set the text of the buttons that match the
				 * submit value (ie., only the button that was
				 * pressed, or all identical to it) to the alt2
				 * text */
				$('#'+form_id+' button[value="'+submit_value+'"]').each(function(index) {
					if($(this).is("[data-alt2]")) {
						/* Only change the text to alt2 if it is the same
						 * as the submit value on the hidden item */
						$(this).text($(this).attr('data-alt2'));
					}
				});
			}

			// Any error message?
			if(data.error != '') {
				form_error_msg.html(data.error);
				form_error_msg.show();
			} else {
				form_error_msg.hide();
			}
			// Or a happy message ?
			if(data.happy != '') {
				form_happy_msg.html(data.happy);
				form_happy_msg.show();
			} else {
				form_happy_msg.hide();
			}
			// Or an info message ?
			if(data.info != '') {
				form_info_msg.html(data.info);
				form_info_msg.show();
			} else {
				form_info_msg.hide();
			}

			// Change field values based on the response
			sfiab_form_update_vals(form_id, data.val);

			/* Use the incomplete fields to update the count in the left nav menu */
			$("#"+form_id+" label").removeClass('error');
			var nav_li = $('#left_nav_'+page.attr('id')+' span');
			var menu_div_id = $(nav_li).closest("div.ui-collapsible").attr('id');
			var menu_span = $('#'+menu_div_id+" h3 a span.ui-li-count");
			var old_error_count = menu_span.text();

			var old_li_count = nav_li.text();
			var new_li_count = 0;
			if(data.missing.length > 0) {
				nav_li.show();
				nav_li.text(data.missing.length);
				new_li_count = data.missing.length;
				form_missing_msg.show();
				for(var i=0; i<data.missing.length; i++) {
					var label = $("label[for='"+form_id+"_"+data.missing[i]+"']");
					label.addClass('error');
				}
			} else {
				nav_li.text(0);
				nav_li.hide();
				form_missing_msg.hide();
			}

			var li_delta = new_li_count - parseInt(old_li_count);
			var new_error_count = parseInt(old_error_count) + li_delta;

			if(new_error_count == 0) {
				menu_span.text(0);
				menu_span.hide();
			} else {
				menu_span.show();
				menu_span.text(new_error_count);
			}

	//		alert(old_li_count + ' ' + new_li_count + ' ' + old_error_count + ' ' + new_error_count);

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

			var post_submit_fn = window[form_id + '_post_submit'];
			if(typeof post_submit_fn === 'function') {
				post_submit_fn(form, data);
			}

			if(data.location != '') {
				if(data.location == 'back') {
					window.history.back();
				} else {
					window.location = data.location;
				}
			}

			return false;
		}, "json");
		// Stop any more actions
		return false;
	});

	/* Catch all button clicks in a form.  Use the button's value= to copy
	 * into a <input type=hidden name=action, then submit the form which
	 * basically calls the above function */
	/* $( ".sfiab_form button" ).click(function(e) {
	 * Use the on() notation attached to the parent so these all get called
	 * for dynamically created children in the form */
	$( ".sfiab_form" ).on('click','button', function(e) {
		e.preventDefault();
		var form = $(this).closest('form');
		var hidden = $('#'+form.attr('id')+' input.sfiab_form_action');

		/* If there is a data-confirm attribute, print that as a confirm
		 * message, and cancel the submit if that returns false */
		if($(this).is("[data-confirm]")) {
			c = confirm($(this).attr('data-confirm'));
			if(c == false) return;
		}
		hidden.attr('value', $(this).attr('value'));
		form.submit();
	});

	function sfiab_form_changed(obj) 
	{
		var form_id= obj.closest('form').attr('id');
		var button_enable_fn = window[form_id + '_button_enable'];
		var enable = true;
		/* See if there is a function that enables/disables form buttons on a condition */
		if(typeof button_enable_fn === 'function') {
			/* Yes, start by disabling all buttons */
			$('#'+form_id+' button').each(function(index) {
				$(this).attr('disabled', 'disabled');
			});
			/* Should they be enabled? */
			enable = button_enable_fn();
		}
		
		if(enable) {
			/* Buttons should be enabled, do that now */
			$('#'+form_id+' button').each(function(index) {
				$(this).removeAttr('disabled');
				$(this).text($(this).attr('data-alt1'));
			});
			/* Update the word count on any text areas */
			update_word_count(obj);
		}
	}

	$( ".sfiab_form" ).on('change',':input', function() {
		sfiab_form_changed($(this));
	});

	$( ".sfiab_form" ).on('keyup',':input', function() {
		sfiab_form_changed($(this));
	});
});

function update_word_count(obj) 
{
	if(obj.attr('data-word-count') != 'true') 
		return;
	var wordarray=obj.val().trim().replace(/\s+/g," ").split(" ");
	var countbox = $('#'+obj.attr('id')+'_count');
	countbox.text(wordarray.length);
}


	function user_list_info_delete(id) {
		if(confirm('Really delete this user? ' + id) == false) return false;
		$.post('c_user_edit.php', { action: "del", uid: id }, function(data) {
			window.history.back();
		}, "json");
		return false;
	}
	function user_list_info_purge(id) {
		if(confirm('Really purge this user?\nPurging user deletes all record of them, their project, their juding info, everything.') == false) return false;
		$.post('c_user_edit.php', { action: "purge", uid: id }, function(data) {
			window.history.back();
		}, "json");
		return false;
	}
	function user_list_info_resend_welcome(id) {
		if(confirm('Really re-send the welcome email to this user?\nThis will also reset their password.') == false) return false;
		$.post('c_user_edit.php', { action: "resend", uid: id }, function(data) {
			$("#resend_reg").html("Email Sent");
		}, "json");
		return false;
	}


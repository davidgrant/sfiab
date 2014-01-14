

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
		

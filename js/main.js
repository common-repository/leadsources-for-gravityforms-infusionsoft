;( function( $ ) {
	'use strict';
	
	// From W3Schools
	// http://www.w3schools.com/js/js_cookies.asp
	function getCookie(cname) {
		var name = cname + "=";
		var ca = document.cookie.split( ';' );
		for( var i = 0; i < ca.length; i++ ) {
			var c = ca[ i ];
			while ( c.charAt( 0 ) == ' ' ) {
				c = c.substring( 1 );
			}
			if ( c.indexOf( name ) == 0 ) {
				return c.substring( name.length, c.length );
			}
		}
		return "";
	}
	
	if (
		document.referrer.indexOf( 
			window.origin || (
				window.location.protocol + "//" + window.location.hostname + (
					window.location.port ? ':' + window.location.port: ''
				)
			)
		) === -1 && getCookie( 'leadsources_for_gravityforms_infusionsoft_landing_page_parameters' ) === '' ) {
		// First page load on this site; store parameter string for one month
		var expiry = new Date();
		expiry.setMonth( expiry.getMonth() + 1 );
		
		document.cookie = 
			'leadsources_for_gravityforms_infusionsoft_landing_page_parameters=' +
			btoa( window.location.search.substring( 1 ) ) +
			';expires=' + expiry.toUTCString() +
			';path=/;domain=' + window.location.hostname +
			( window.location.protocol === 'https' ? ';secure' : '' );
	}
	
	// Support hosting providers that block cookies (e.g. WPEngine)
	// by appending our cookie to the form's action URL
	$( document ).bind( 'gform_post_render', function(event, form_id, current_page) {
		var gform = $( '#gform_' + form_id );
		var gformAction = gform.attr( 'action' );
		var cookieParam =
			encodeURIComponent(
				'leadsources_for_gravityforms_infusionsoft_landing_page_parameters'
			) + '=' +
			encodeURIComponent(
				getCookie( 'leadsources_for_gravityforms_infusionsoft_landing_page_parameters' )
			);
		
		if ( gformAction.indexOf( cookieParam ) === -1 ) {
			var separator = '?';
			if ( gformAction.indexOf( '?' ) !== -1 ) {
				separator = '&';
			}
			
			gform.attr( 'action', gformAction + separator + cookieParam );
		}
	} );
} )( jQuery );
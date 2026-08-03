( function () {
	'use strict';

	if ( ! window.SPDBDashboard || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var apiFetch = window.wp.apiFetch;
	var config = window.SPDBDashboard;
	apiFetch.use( apiFetch.createNonceMiddleware( config.nonce ) );

	function filtersSummary( filters ) {
		var keys = Object.keys( filters || {} );
		if ( ! keys.length ) {
			return '';
		}

		return keys.map( function ( key ) {
			var value = Array.isArray( filters[ key ] ) ? filters[ key ].join( ', ' ) : filters[ key ];
			return key + ': ' + value;
		} ).join( ' · ' );
	}

	function createListItem( view ) {
		var item = document.createElement( 'li' );
		var content = document.createElement( 'div' );
		var title = document.createElement( 'strong' );
		var details = document.createElement( 'small' );
		var button = document.createElement( 'button' );

		item.setAttribute( 'data-view-id', view.id );
		title.textContent = view.label;
		details.textContent = filtersSummary( view.filters );
		button.type = 'button';
		button.className = 'spdb-button spdb-button--secondary';
		button.setAttribute( 'data-spdb-delete-view', view.id );
		button.textContent = config.strings.deleteLabel;

		content.appendChild( title );
		content.appendChild( details );
		item.appendChild( content );
		item.appendChild( button );
		return item;
	}

	function collectFilters( formElement ) {
		var fields = [ 'status', 'provider', 'sort' ];
		var filters = {};

		fields.forEach( function ( field ) {
			var input = formElement.elements[ field ];
			if ( input && input.value ) {
				filters[ field ] = input.value;
			}
		} );

		return filters;
	}

	function initializeShell( shell ) {
		var form = shell.querySelector( '[data-spdb-saved-view-form]' );
		var list = shell.querySelector( '[data-spdb-saved-view-list]' );
		var emptyState = shell.querySelector( '.spdb-empty-state' );
		var status = shell.querySelector( '[data-spdb-status]' );

		function announce( message, isError ) {
			if ( ! status ) {
				return;
			}
			status.textContent = message || '';
			status.setAttribute( 'data-error', isError ? 'true' : 'false' );
		}

		function setEmptyState( isEmpty ) {
			if ( emptyState ) {
				emptyState.hidden = ! isEmpty;
			}
		}

		if ( ! form || ! list ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var submit = form.querySelector( 'button[type="submit"]' );
			var labelInput = form.elements.label;
			var label = labelInput ? labelInput.value.trim() : '';

			if ( ! label || ! submit ) {
				if ( labelInput ) {
					labelInput.focus();
				}
				return;
			}

			submit.disabled = true;
			announce( config.strings.loading, false );

			apiFetch( {
				path: '/spdb/v1/saved-views',
				method: 'POST',
				data: {
					label: label,
					filters: collectFilters( form )
				}
			} ).then( function ( view ) {
				list.appendChild( createListItem( view ) );
				setEmptyState( false );
				form.reset();
				announce( config.strings.created, false );
			} ).catch( function ( error ) {
				announce( error && error.message ? error.message : config.strings.createFailed, true );
			} ).finally( function () {
				submit.disabled = false;
			} );
		} );

		list.addEventListener( 'click', function ( event ) {
			var target = event.target;
			if ( ! target || 'function' !== typeof target.closest ) {
				return;
			}

			var button = target.closest( '[data-spdb-delete-view]' );
			if ( ! button || ! list.contains( button ) ) {
				return;
			}

			var id = button.getAttribute( 'data-spdb-delete-view' );
			if ( ! id ) {
				return;
			}

			button.disabled = true;
			apiFetch( {
				path: '/spdb/v1/saved-views/' + encodeURIComponent( id ),
				method: 'DELETE'
			} ).then( function () {
				var item = button.closest( 'li' );
				if ( item ) {
					item.remove();
				}
				setEmptyState( 0 === list.children.length );
				announce( config.strings.deleted, false );
			} ).catch( function ( error ) {
				button.disabled = false;
				announce( error && error.message ? error.message : config.strings.deleteFailed, true );
			} );
		} );
	}

	document.querySelectorAll( '.spdb-shell' ).forEach( initializeShell );
}() );

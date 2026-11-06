/**
 * Laukku child theme – progressive enhancements (no jQuery dependency).
 *
 * 1. Quantity steppers: big −/+ buttons around WooCommerce quantity inputs.
 *    Tiny native number spinners are hard to hit on phones; most of the
 *    store's traffic is mobile.
 * 2. Re-applies itself after WooCommerce re-renders the cart via AJAX.
 */
( function () {
	'use strict';

	const i18n = window.laukkuChild || { decrease: 'Decrease quantity', increase: 'Increase quantity' };

	function step( input, direction ) {
		const stepSize = parseFloat( input.step ) || 1;
		const min = input.min !== '' ? parseFloat( input.min ) : 0;
		const max = input.max !== '' ? parseFloat( input.max ) : Infinity;
		let value = ( parseFloat( input.value ) || 0 ) + direction * stepSize;
		value = Math.min( Math.max( value, min ), max );

		if ( String( value ) === input.value ) {
			return;
		}
		input.value = value;
		// WooCommerce listens for these to enable "Update cart" etc.
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function makeButton( input, direction ) {
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'laukku-qty__btn laukku-qty__btn--' + ( direction < 0 ? 'minus' : 'plus' );
		button.setAttribute( 'aria-label', direction < 0 ? i18n.decrease : i18n.increase );
		button.textContent = direction < 0 ? '−' : '+';
		button.addEventListener( 'click', function () {
			step( input, direction );
		} );
		return button;
	}

	function enhance( root ) {
		root.querySelectorAll( '.quantity:not(.laukku-qty) input.qty' ).forEach( function ( input ) {
			if ( input.type === 'hidden' || input.readOnly ) {
				return;
			}
			const wrapper = input.closest( '.quantity' );
			wrapper.classList.add( 'laukku-qty' );
			input.before( makeButton( input, -1 ) );
			input.after( makeButton( input, 1 ) );
		} );
	}

	enhance( document );

	// WooCommerce fires these jQuery events after replacing cart HTML.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'updated_cart_totals updated_wc_div wc_fragments_refreshed', function () {
			enhance( document );
		} );
	}
}() );

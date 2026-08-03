/**
 * Classic editor integration: adds "Unlisted" to the status dropdown in
 * the Publish box and keeps the status display label in sync.
 */
const config = window.unlistedPostsClassic || {};
const STATUS = config.status || 'unlisted';
const LABEL = config.label || 'Unlisted';

function init() {
	const select = document.getElementById( 'post_status' );

	if ( ! select ) {
		return;
	}

	if ( ! select.querySelector( `option[value="${ STATUS }"]` ) ) {
		select.add( new Option( LABEL, STATUS ) );
	}

	if ( config.isUnlisted ) {
		select.value = STATUS;

		const hidden = document.getElementById( 'hidden_post_status' );
		if ( hidden ) {
			hidden.value = STATUS;
		}

		const display = document.getElementById( 'post-status-display' );
		if ( display ) {
			display.textContent = LABEL;
		}

		// Keep the primary button reading "Update" rather than "Publish".
		const publish = document.getElementById( 'publish' );
		if ( publish && publish.value !== publish.dataset.updateText ) {
			const original = document.getElementById( 'original_publish' );
			if ( original ) {
				original.value = 'Update';
			}
		}
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

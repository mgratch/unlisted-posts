/**
 * Quick Edit + Bulk Edit integration on post list tables.
 *
 * Core populates the status <select> from hidden per-row data, so all we
 * need is the extra <option> in both the inline-edit and bulk-edit
 * templates. Core's inline-edit.js then selects it automatically for
 * posts that already have the status.
 */
const config = window.unlistedPostsQuickEdit || {};
const STATUS = config.status || 'unlisted';
const LABEL = config.label || 'Unlisted';

function addStatusOption( select ) {
	if ( ! select || select.querySelector( `option[value="${ STATUS }"]` ) ) {
		return;
	}

	select.add( new Option( LABEL, STATUS ) );
}

function init() {
	// Quick Edit and Bulk Edit hidden row templates.
	document
		.querySelectorAll(
			'#inline-edit select[name="_status"], #bulk-edit select[name="_status"], .inline-edit-row select[name="_status"]'
		)
		.forEach( addStatusOption );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

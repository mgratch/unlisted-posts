/**
 * Block editor integration: "Unlisted" toggle + copyable hash URL,
 * rendered alongside the native status controls in the post sidebar.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo, store as editorStore } from '@wordpress/editor';
import { ToggleControl, Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { useCopyToClipboard } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

const STATUS = 'unlisted';

function UnlistedPostStatusInfo() {
	const { status, savedStatus, unlistedUrl } = useSelect( ( select ) => {
		const editor = select( editorStore );

		return {
			status: editor.getEditedPostAttribute( 'status' ),
			savedStatus: editor.getCurrentPostAttribute( 'status' ),
			unlistedUrl: editor.getCurrentPostAttribute( 'unlisted_url' ),
		};
	}, [] );

	const { editPost } = useDispatch( editorStore );
	const { createNotice } = useDispatch( noticesStore );

	const isUnlisted = status === STATUS;

	const copyRef = useCopyToClipboard( unlistedUrl, () => {
		createNotice(
			'info',
			__( 'Unlisted link copied to clipboard.', 'unlisted-posts' ),
			{ isDismissible: true, type: 'snackbar' }
		);
	} );

	const onToggle = ( checked ) => {
		if ( checked ) {
			editPost( { status: STATUS } );
			return;
		}

		// Restore the last saved status, or fall back to draft.
		editPost( {
			status:
				savedStatus && savedStatus !== STATUS ? savedStatus : 'draft',
		} );
	};

	return (
		<PluginPostStatusInfo className="unlisted-posts-panel">
			<div style={ { width: '100%' } }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Unlisted', 'unlisted-posts' ) }
					help={
						isUnlisted
							? __(
									'Only people with the secret link can view this post. It is hidden from archives, feeds, search, sitemaps, and search engines.',
									'unlisted-posts'
							  )
							: __(
									'Publish to a secret, non-crawlable link.',
									'unlisted-posts'
							  )
					}
					checked={ isUnlisted }
					onChange={ onToggle }
				/>
				{ isUnlisted && unlistedUrl && (
					<div style={ { marginTop: '8px' } }>
						<Button
							__next40pxDefaultSize
							variant="secondary"
							ref={ copyRef }
							style={ { width: '100%', justifyContent: 'center' } }
						>
							{ __( 'Copy unlisted link', 'unlisted-posts' ) }
						</Button>
						<p
							style={ {
								fontSize: '11px',
								wordBreak: 'break-all',
								color: 'var(--wp-components-color-gray-700, #757575)',
							} }
						>
							{ unlistedUrl }
						</p>
					</div>
				) }
				{ isUnlisted && ! unlistedUrl && (
					<p style={ { fontSize: '12px' } }>
						{ __(
							'Save the post to generate its secret link.',
							'unlisted-posts'
						) }
					</p>
				) }
			</div>
		</PluginPostStatusInfo>
	);
}

registerPlugin( 'unlisted-posts', {
	render: UnlistedPostStatusInfo,
} );

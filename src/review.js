import { useState, useEffect, useCallback, RawHTML } from '@wordpress/element';
import { Button, Notice, Spinner, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const FIELD_IS_HTML = [ 'long_description', 'short_description' ];

const ValueBox = ( { label, value, field, empty } ) => (
	<div className="draftpilot-value">
		<span className="draftpilot-value-label">{ label }</span>
		{ value ? (
			FIELD_IS_HTML.includes( field ) ? (
				<RawHTML className="draftpilot-value-body">{ value }</RawHTML>
			) : (
				<p className="draftpilot-value-body">{ value }</p>
			)
		) : (
			<p className="draftpilot-value-body draftpilot-empty">{ empty }</p>
		) }
	</div>
);

const ReviewTab = ( { onPendingChange } ) => {
	const [ drafts, setDrafts ] = useState( null );
	const [ busy, setBusy ] = useState( 0 );
	const [ error, setError ] = useState( null );

	const load = useCallback( () => {
		apiFetch( { path: '/draftpilot/v1/drafts?status=pending' } ).then(
			( res ) => {
				setDrafts( res.drafts );
				onPendingChange( res.pending );
			}
		);
	}, [ onPendingChange ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const decide = async ( draft, decision ) => {
		setBusy( draft.id );
		setError( null );
		try {
			const res = await apiFetch( {
				path: `/draftpilot/v1/drafts/${ draft.id }/${ decision }`,
				method: 'POST',
			} );
			setDrafts( ( prev ) =>
				prev.filter( ( d ) => d.id !== draft.id )
			);
			onPendingChange( res.pending );
		} catch ( err ) {
			setError( err.message || __( 'Request failed.', 'draftpilot-for-woocommerce' ) );
		}
		setBusy( 0 );
	};

	if ( drafts === null ) {
		return (
			<div className="draftpilot-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="draftpilot-review">
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }

			{ ! drafts.length && (
				<p className="draftpilot-empty-queue">
					{ __(
						'No drafts waiting for review. Generate some copy from the Products tab.',
						'draftpilot-for-woocommerce'
					) }
				</p>
			) }

			{ drafts.map( ( draft ) => (
				<Card key={ draft.id } className="draftpilot-draft">
					<CardBody>
						<div className="draftpilot-draft-head">
							<div>
								<strong>{ draft.product_name }</strong>
								<span className="draftpilot-chip">
									{ draft.field_label }
								</span>
								{ draft.tone && (
									<span className="draftpilot-chip draftpilot-chip-tone">
										{ draft.tone }
									</span>
								) }
							</div>
							<div className="draftpilot-draft-actions">
								<Button
									variant="primary"
									isBusy={ busy === draft.id }
									disabled={ !! busy }
									onClick={ () =>
										decide( draft, 'approve' )
									}
								>
									{ __( 'Approve', 'draftpilot-for-woocommerce' ) }
								</Button>
								<Button
									isDestructive
									disabled={ !! busy }
									onClick={ () =>
										decide( draft, 'reject' )
									}
								>
									{ __( 'Reject', 'draftpilot-for-woocommerce' ) }
								</Button>
							</div>
						</div>

						<div className="draftpilot-compare">
							<ValueBox
								label={ __( 'Current', 'draftpilot-for-woocommerce' ) }
								value={ draft.current_value }
								field={ draft.field }
								empty={ __( '(empty)', 'draftpilot-for-woocommerce' ) }
							/>
							<ValueBox
								label={ __( 'Proposed', 'draftpilot-for-woocommerce' ) }
								value={ draft.proposed_value }
								field={ draft.field }
								empty={ __( '(empty)', 'draftpilot-for-woocommerce' ) }
							/>
						</div>
					</CardBody>
				</Card>
			) ) }
		</div>
	);
};

export default ReviewTab;

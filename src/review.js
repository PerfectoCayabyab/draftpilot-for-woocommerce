import { useState, useEffect, useCallback, RawHTML } from '@wordpress/element';
import { Button, Notice, Spinner, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const FIELD_IS_HTML = [ 'long_description', 'short_description' ];

const ValueBox = ( { label, value, field, empty } ) => (
	<div className="copypilot-value">
		<span className="copypilot-value-label">{ label }</span>
		{ value ? (
			FIELD_IS_HTML.includes( field ) ? (
				<RawHTML className="copypilot-value-body">{ value }</RawHTML>
			) : (
				<p className="copypilot-value-body">{ value }</p>
			)
		) : (
			<p className="copypilot-value-body copypilot-empty">{ empty }</p>
		) }
	</div>
);

const ReviewTab = ( { onPendingChange } ) => {
	const [ drafts, setDrafts ] = useState( null );
	const [ busy, setBusy ] = useState( 0 );
	const [ error, setError ] = useState( null );

	const load = useCallback( () => {
		apiFetch( { path: '/copypilot/v1/drafts?status=pending' } ).then(
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
				path: `/copypilot/v1/drafts/${ draft.id }/${ decision }`,
				method: 'POST',
			} );
			setDrafts( ( prev ) =>
				prev.filter( ( d ) => d.id !== draft.id )
			);
			onPendingChange( res.pending );
		} catch ( err ) {
			setError( err.message || __( 'Request failed.', 'copypilot-for-woocommerce' ) );
		}
		setBusy( 0 );
	};

	if ( drafts === null ) {
		return (
			<div className="copypilot-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="copypilot-review">
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }

			{ ! drafts.length && (
				<p className="copypilot-empty-queue">
					{ __(
						'No drafts waiting for review. Generate some copy from the Products tab.',
						'copypilot-for-woocommerce'
					) }
				</p>
			) }

			{ drafts.map( ( draft ) => (
				<Card key={ draft.id } className="copypilot-draft">
					<CardBody>
						<div className="copypilot-draft-head">
							<div>
								<strong>{ draft.product_name }</strong>
								<span className="copypilot-chip">
									{ draft.field_label }
								</span>
								{ draft.tone && (
									<span className="copypilot-chip copypilot-chip-tone">
										{ draft.tone }
									</span>
								) }
							</div>
							<div className="copypilot-draft-actions">
								<Button
									variant="primary"
									isBusy={ busy === draft.id }
									disabled={ !! busy }
									onClick={ () =>
										decide( draft, 'approve' )
									}
								>
									{ __( 'Approve', 'copypilot-for-woocommerce' ) }
								</Button>
								<Button
									isDestructive
									disabled={ !! busy }
									onClick={ () =>
										decide( draft, 'reject' )
									}
								>
									{ __( 'Reject', 'copypilot-for-woocommerce' ) }
								</Button>
							</div>
						</div>

						<div className="copypilot-compare">
							<ValueBox
								label={ __( 'Current', 'copypilot-for-woocommerce' ) }
								value={ draft.current_value }
								field={ draft.field }
								empty={ __( '(empty)', 'copypilot-for-woocommerce' ) }
							/>
							<ValueBox
								label={ __( 'Proposed', 'copypilot-for-woocommerce' ) }
								value={ draft.proposed_value }
								field={ draft.field }
								empty={ __( '(empty)', 'copypilot-for-woocommerce' ) }
							/>
						</div>
					</CardBody>
				</Card>
			) ) }
		</div>
	);
};

export default ReviewTab;

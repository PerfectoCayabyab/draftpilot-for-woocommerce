import { useState, useEffect, useCallback, RawHTML } from '@wordpress/element';
import { Button, Notice, Spinner, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const FIELD_IS_HTML = [ 'long_description', 'short_description' ];

const ValueBox = ( { label, value, field, empty } ) => (
	<div className="copyquill-value">
		<span className="copyquill-value-label">{ label }</span>
		{ value ? (
			FIELD_IS_HTML.includes( field ) ? (
				<RawHTML className="copyquill-value-body">{ value }</RawHTML>
			) : (
				<p className="copyquill-value-body">{ value }</p>
			)
		) : (
			<p className="copyquill-value-body copyquill-empty">{ empty }</p>
		) }
	</div>
);

const ReviewTab = ( { onPendingChange } ) => {
	const [ drafts, setDrafts ] = useState( null );
	const [ busy, setBusy ] = useState( 0 );
	const [ error, setError ] = useState( null );

	const load = useCallback( () => {
		apiFetch( { path: '/copyquill/v1/drafts?status=pending' } ).then(
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
				path: `/copyquill/v1/drafts/${ draft.id }/${ decision }`,
				method: 'POST',
			} );
			setDrafts( ( prev ) =>
				prev.filter( ( d ) => d.id !== draft.id )
			);
			onPendingChange( res.pending );
		} catch ( err ) {
			setError( err.message || __( 'Request failed.', 'copyquill-for-woocommerce' ) );
		}
		setBusy( 0 );
	};

	if ( drafts === null ) {
		return (
			<div className="copyquill-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="copyquill-review">
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }

			{ ! drafts.length && (
				<p className="copyquill-empty-queue">
					{ __(
						'No drafts waiting for review. Generate some copy from the Products tab.',
						'copyquill-for-woocommerce'
					) }
				</p>
			) }

			{ drafts.map( ( draft ) => (
				<Card key={ draft.id } className="copyquill-draft">
					<CardBody>
						<div className="copyquill-draft-head">
							<div>
								<strong>{ draft.product_name }</strong>
								<span className="copyquill-chip">
									{ draft.field_label }
								</span>
								{ draft.tone && (
									<span className="copyquill-chip copyquill-chip-tone">
										{ draft.tone }
									</span>
								) }
							</div>
							<div className="copyquill-draft-actions">
								<Button
									variant="primary"
									isBusy={ busy === draft.id }
									disabled={ !! busy }
									onClick={ () =>
										decide( draft, 'approve' )
									}
								>
									{ __( 'Approve', 'copyquill-for-woocommerce' ) }
								</Button>
								<Button
									isDestructive
									disabled={ !! busy }
									onClick={ () =>
										decide( draft, 'reject' )
									}
								>
									{ __( 'Reject', 'copyquill-for-woocommerce' ) }
								</Button>
							</div>
						</div>

						<div className="copyquill-compare">
							<ValueBox
								label={ __( 'Current', 'copyquill-for-woocommerce' ) }
								value={ draft.current_value }
								field={ draft.field }
								empty={ __( '(empty)', 'copyquill-for-woocommerce' ) }
							/>
							<ValueBox
								label={ __( 'Proposed', 'copyquill-for-woocommerce' ) }
								value={ draft.proposed_value }
								field={ draft.field }
								empty={ __( '(empty)', 'copyquill-for-woocommerce' ) }
							/>
						</div>
					</CardBody>
				</Card>
			) ) }
		</div>
	);
};

export default ReviewTab;

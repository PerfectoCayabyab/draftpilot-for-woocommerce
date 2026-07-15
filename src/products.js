import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	SelectControl,
	SearchControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const ProductsTab = ( { config, onPendingChange } ) => {
	const [ products, setProducts ] = useState( [] );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ page, setPage ] = useState( 1 );
	const [ search, setSearch ] = useState( '' );
	const [ loading, setLoading ] = useState( true );
	const [ selected, setSelected ] = useState( [] );
	const [ fields, setFields ] = useState( [
		'long_description',
		'short_description',
	] );
	const [ tone, setTone ] = useState( config.settings.default_tone );
	const [ progress, setProgress ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	const loadProducts = useCallback( () => {
		setLoading( true );
		apiFetch( {
			path: `/copypilot/v1/products?search=${ encodeURIComponent(
				search
			) }&page=${ page }`,
		} ).then( ( res ) => {
			setProducts( res.products );
			setTotalPages( res.total_pages );
			setLoading( false );
		} );
	}, [ search, page ] );

	useEffect( () => {
		loadProducts();
	}, [ loadProducts ] );

	const toggleField = ( key, checked ) => {
		setFields( ( prev ) =>
			checked ? [ ...prev, key ] : prev.filter( ( f ) => f !== key )
		);
	};

	const toggleProduct = ( id, checked ) => {
		setSelected( ( prev ) =>
			checked ? [ ...prev, id ] : prev.filter( ( p ) => p !== id )
		);
	};

	const generate = async () => {
		setNotice( null );
		const errors = [];
		let done = 0;

		for ( const productId of selected ) {
			setProgress( {
				done,
				total: selected.length,
				name: products.find( ( p ) => p.id === productId )?.name || '',
			} );
			try {
				const res = await apiFetch( {
					path: '/copypilot/v1/generate',
					method: 'POST',
					data: { product_id: productId, fields, tone },
				} );
				onPendingChange( res.pending );
			} catch ( err ) {
				errors.push( err.message || __( 'Request failed.', 'copypilot-for-woocommerce' ) );
			}
			done++;
		}

		setProgress( null );
		setSelected( [] );

		if ( errors.length ) {
			setNotice( { status: 'error', text: errors[ 0 ] } );
		} else {
			setNotice( {
				status: 'success',
				text: sprintf(
					/* translators: %d: number of products. */
					__(
						'Drafts generated for %d product(s). Review them in the Review queue tab.',
						'copypilot-for-woocommerce'
					),
					done
				),
			} );
		}
	};

	const allVisible = products.map( ( p ) => p.id );
	const allSelected =
		allVisible.length > 0 &&
		allVisible.every( ( id ) => selected.includes( id ) );

	return (
		<div className="copypilot-products">
			{ ! config.settings.has_api_key && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Add your Gemini API key in the Settings tab before generating copy.',
						'copypilot-for-woocommerce'
					) }
				</Notice>
			) }

			{ notice && (
				<Notice
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.text }
				</Notice>
			) }

			<div className="copypilot-toolbar">
				<SearchControl
					value={ search }
					onChange={ ( value ) => {
						setSearch( value );
						setPage( 1 );
					} }
					placeholder={ __( 'Search products…', 'copypilot-for-woocommerce' ) }
					__nextHasNoMarginBottom
				/>

				<div className="copypilot-generate-controls">
					<div className="copypilot-fields">
						{ Object.entries( config.fields ).map(
							( [ key, label ] ) => (
								<CheckboxControl
									key={ key }
									label={ label }
									checked={ fields.includes( key ) }
									onChange={ ( checked ) =>
										toggleField( key, checked )
									}
									__nextHasNoMarginBottom
								/>
							)
						) }
					</div>

					<SelectControl
						label={ __( 'Tone', 'copypilot-for-woocommerce' ) }
						value={ tone }
						options={ Object.entries( config.tones ).map(
							( [ value, label ] ) => ( { value, label } )
						) }
						onChange={ setTone }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>

					<Button
						variant="primary"
						disabled={
							! selected.length ||
							! fields.length ||
							!! progress ||
							! config.settings.has_api_key
						}
						isBusy={ !! progress }
						onClick={ generate }
					>
						{ progress
							? sprintf(
									/* translators: 1: done count, 2: total, 3: product name. */
									__( 'Generating %1$d/%2$d — %3$s', 'copypilot-for-woocommerce' ),
									progress.done + 1,
									progress.total,
									progress.name
							  )
							: sprintf(
									/* translators: %d: selected count. */
									__( 'Generate for %d selected', 'copypilot-for-woocommerce' ),
									selected.length
							  ) }
					</Button>
				</div>
			</div>

			{ loading ? (
				<div className="copypilot-loading">
					<Spinner />
				</div>
			) : (
				<table className="wp-list-table widefat fixed striped copypilot-table">
					<thead>
						<tr>
							<td className="check-column">
								<CheckboxControl
									checked={ allSelected }
									onChange={ ( checked ) =>
										setSelected(
											checked ? allVisible : []
										)
									}
									__nextHasNoMarginBottom
								/>
							</td>
							<th>{ __( 'Product', 'copypilot-for-woocommerce' ) }</th>
							<th>{ __( 'Price', 'copypilot-for-woocommerce' ) }</th>
							<th>{ __( 'Description', 'copypilot-for-woocommerce' ) }</th>
							<th>{ __( 'Short description', 'copypilot-for-woocommerce' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ products.map( ( product ) => (
							<tr key={ product.id }>
								<th className="check-column">
									<CheckboxControl
										checked={ selected.includes(
											product.id
										) }
										onChange={ ( checked ) =>
											toggleProduct(
												product.id,
												checked
											)
										}
										__nextHasNoMarginBottom
									/>
								</th>
								<td>
									<div className="copypilot-product-cell">
										{ product.image && (
											<img
												src={ product.image }
												alt=""
											/>
										) }
										<a
											href={ product.edit_link }
											target="_blank"
											rel="noreferrer"
										>
											{ product.name }
										</a>
									</div>
								</td>
								<td>{ product.price_html }</td>
								<td>
									{ product.has_long_description
										? '✅'
										: '—' }
								</td>
								<td>
									{ product.has_short_description
										? '✅'
										: '—' }
								</td>
							</tr>
						) ) }
						{ ! products.length && (
							<tr>
								<td colSpan="5">
									{ __( 'No products found.', 'copypilot-for-woocommerce' ) }
								</td>
							</tr>
						) }
					</tbody>
				</table>
			) }

			{ totalPages > 1 && (
				<div className="copypilot-pagination">
					<Button
						variant="secondary"
						disabled={ page <= 1 }
						onClick={ () => setPage( page - 1 ) }
					>
						{ __( '← Previous', 'copypilot-for-woocommerce' ) }
					</Button>
					<span>
						{ sprintf(
							/* translators: 1: current page, 2: total pages. */
							__( 'Page %1$d of %2$d', 'copypilot-for-woocommerce' ),
							page,
							totalPages
						) }
					</span>
					<Button
						variant="secondary"
						disabled={ page >= totalPages }
						onClick={ () => setPage( page + 1 ) }
					>
						{ __( 'Next →', 'copypilot-for-woocommerce' ) }
					</Button>
				</div>
			) }
		</div>
	);
};

export default ProductsTab;

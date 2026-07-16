import { createRoot, useState, useEffect } from '@wordpress/element';
import { TabPanel, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import ProductsTab from './products';
import ReviewTab from './review';
import SettingsTab from './settings';
import './style.scss';

const App = () => {
	const [ config, setConfig ] = useState( null );
	const [ pending, setPending ] = useState( 0 );

	useEffect( () => {
		apiFetch( { path: '/copyquill/v1/settings' } ).then( ( res ) => {
			setConfig( res );
		} );
		apiFetch( { path: '/copyquill/v1/drafts?status=pending' } ).then( ( res ) => {
			setPending( res.pending );
		} );
	}, [] );

	if ( ! config ) {
		return (
			<div className="copyquill-loading">
				<Spinner />
			</div>
		);
	}

	const tabs = [
		{ name: 'products', title: __( 'Products', 'copyquill-for-woocommerce' ) },
		{
			name: 'review',
			title:
				__( 'Review queue', 'copyquill-for-woocommerce' ) +
				( pending > 0 ? ` (${ pending })` : '' ),
		},
		{ name: 'settings', title: __( 'Settings', 'copyquill-for-woocommerce' ) },
	];

	const urlTab = new URLSearchParams( window.location.search ).get(
		'cq-tab'
	);
	const initialTab = tabs.some( ( t ) => t.name === urlTab )
		? urlTab
		: 'products';

	return (
		<div className="copyquill-app">
			<div className="copyquill-header">
				<h1>
					Copy<span>quill</span>
				</h1>
				<p>
					{ __(
						'AI product copy with human approval — nothing publishes until you say so.',
						'copyquill-for-woocommerce'
					) }
				</p>
			</div>

			<TabPanel
				tabs={ tabs }
				className="copyquill-tabs"
				initialTabName={ initialTab }
			>
				{ ( tab ) => {
					if ( tab.name === 'products' ) {
						return (
							<ProductsTab
								config={ config }
								onPendingChange={ setPending }
							/>
						);
					}
					if ( tab.name === 'review' ) {
						return <ReviewTab onPendingChange={ setPending } />;
					}
					return (
						<SettingsTab config={ config } onSaved={ setConfig } />
					);
				} }
			</TabPanel>
		</div>
	);
};

const rootEl = document.getElementById( 'copyquill-root' );
if ( rootEl ) {
	createRoot( rootEl ).render( <App /> );
}

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
		apiFetch( { path: '/copypilot/v1/settings' } ).then( ( res ) => {
			setConfig( res );
		} );
		apiFetch( { path: '/copypilot/v1/drafts?status=pending' } ).then( ( res ) => {
			setPending( res.pending );
		} );
	}, [] );

	if ( ! config ) {
		return (
			<div className="copypilot-loading">
				<Spinner />
			</div>
		);
	}

	const tabs = [
		{ name: 'products', title: __( 'Products', 'copypilot-for-woocommerce' ) },
		{
			name: 'review',
			title:
				__( 'Review queue', 'copypilot-for-woocommerce' ) +
				( pending > 0 ? ` (${ pending })` : '' ),
		},
		{ name: 'settings', title: __( 'Settings', 'copypilot-for-woocommerce' ) },
	];

	const urlTab = new URLSearchParams( window.location.search ).get(
		'cp-tab'
	);
	const initialTab = tabs.some( ( t ) => t.name === urlTab )
		? urlTab
		: 'products';

	return (
		<div className="copypilot-app">
			<div className="copypilot-header">
				<h1>
					Copy<span>Pilot</span>
				</h1>
				<p>
					{ __(
						'AI product copy with human approval — nothing publishes until you say so.',
						'copypilot-for-woocommerce'
					) }
				</p>
			</div>

			<TabPanel
				tabs={ tabs }
				className="copypilot-tabs"
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

const rootEl = document.getElementById( 'copypilot-root' );
if ( rootEl ) {
	createRoot( rootEl ).render( <App /> );
}

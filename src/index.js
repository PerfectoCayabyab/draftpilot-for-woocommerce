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
		apiFetch( { path: '/draftpilot/v1/settings' } ).then( ( res ) => {
			setConfig( res );
		} );
		apiFetch( { path: '/draftpilot/v1/drafts?status=pending' } ).then( ( res ) => {
			setPending( res.pending );
		} );
	}, [] );

	if ( ! config ) {
		return (
			<div className="draftpilot-loading">
				<Spinner />
			</div>
		);
	}

	const tabs = [
		{ name: 'products', title: __( 'Products', 'draftpilot-for-woocommerce' ) },
		{
			name: 'review',
			title:
				__( 'Review queue', 'draftpilot-for-woocommerce' ) +
				( pending > 0 ? ` (${ pending })` : '' ),
		},
		{ name: 'settings', title: __( 'Settings', 'draftpilot-for-woocommerce' ) },
	];

	const urlTab = new URLSearchParams( window.location.search ).get(
		'dp-tab'
	);
	const initialTab = tabs.some( ( t ) => t.name === urlTab )
		? urlTab
		: 'products';

	return (
		<div className="draftpilot-app">
			<div className="draftpilot-header">
				<h1>
					Draft<span>Pilot</span>
				</h1>
				<p>
					{ __(
						'AI product copy with human approval — nothing publishes until you say so.',
						'draftpilot-for-woocommerce'
					) }
				</p>
			</div>

			<TabPanel
				tabs={ tabs }
				className="draftpilot-tabs"
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

const rootEl = document.getElementById( 'draftpilot-root' );
if ( rootEl ) {
	createRoot( rootEl ).render( <App /> );
}

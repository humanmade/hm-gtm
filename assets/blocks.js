const addFilter = wp.hooks.addFilter;
const InspectorControls = wp.blockEditor.InspectorControls;
const TextControl = wp.components.TextControl;
const SelectControl = wp.components.SelectControl;
const PanelBody = wp.components.PanelBody;
const __ = wp.i18n.__;

function HMGTMEvents(BlockEdit) {
	return function (props) {
		const attributes = props.attributes;
		const setAttributes = props.setAttributes;

		const supportsGTM = attributes?.gtm !== undefined;

		// Check if the block supports "gtm"
		if (!supportsGTM) {
			return wp.element.createElement(BlockEdit, props);
		}

		function update( settings ) {
			setAttributes({ gtm: { ...(attributes.gtm || {}), ...settings } });
		}

		return (
			wp.element.createElement(
				wp.element.Fragment,
				null,
				wp.element.createElement(
					BlockEdit,
					props
				),
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Google Tag Manager', 'hm-gtm' ), initialOpen: false },
						wp.element.createElement(
							'p',
							{},
							__( 'The following options allow you to push an event to GTM when this block is interacted with.', 'hm-gtm' )
						),
						wp.element.createElement(
							SelectControl,
							{
								label: __( 'Event Trigger', 'hm-gtm' ),
								value: attributes?.gtm?.trigger || 'click',
								options: [
									{ label: __( 'Click', 'hm-gtm' ), value: 'click' },
									{ label: __( 'Submit', 'hm-gtm' ), value: 'submit' },
									{ label: __( 'Focus', 'hm-gtm' ), value: 'focusin' },
									{ label: __( 'Blur', 'hm-gtm' ), value: 'focusout' },
									{ label: __( 'Mouseover', 'hm-gtm' ), value: 'mouseenter' },
									{ label: __( 'Mouseout', 'hm-gtm' ), value: 'mouseleave' }
								],
								onChange: function (value) {
									update({ trigger: value });
								}
							}
						),
						wp.element.createElement(
							TextControl,
							{
								label: __( 'Event Name', 'hm-gtm' ),
								value: attributes.gtm?.event || '',
								onChange: function (value) {
									update({ event: value });
								}
							}
						),
						wp.element.createElement(
							TextControl,
							{
								label: __( 'Action', 'hm-gtm' ),
								value: attributes.gtm?.action || '',
								onChange: function (value) {
									update({ action: value });
								}
							}
						),
						wp.element.createElement(
							TextControl,
							{
								label: __( 'Category', 'hm-gtm' ),
								value: attributes.gtm?.category || '',
								onChange: function (value) {
									update({ category: value });
								}
							}
						),
						wp.element.createElement(
							TextControl,
							{
								label: __( 'Label', 'hm-gtm' ),
								value: attributes.gtm?.label || '',
								onChange: function (value) {
									update({ label: value });
								}
							}
						),
						wp.element.createElement(
							TextControl,
							{
								label: __( 'Value', 'hm-gtm' ),
								value: attributes.gtm?.value || '',
								onChange: function (value) {
									update({ value: value });
								}
							}
						)
					)
				)
			)
		);
	};
}

addFilter(
	'editor.BlockEdit',
	'hm-gtm/events',
	HMGTMEvents
);

function addGTMAttribute(settings) {
	if ( settings.supports?.gtm === false ) {
		return settings;
	}

	return {
		...settings,
		supports: {
			...( settings.supports || {} ),
			gtm: true,
		},
		attributes: {
			...settings.attributes,
			gtm: {
				type: 'object',
				default: {},
				properties: {
					trigger: {
						type: 'string',
						enum: [ 'click', 'submit', 'focusin', 'focusout', 'mouseenter', 'mouseleave' ]
					},
					event: {
						type: 'string'
					},
					action: {
						type: 'string'
					},
					category: {
						type: 'string'
					},
					label: {
						type: 'string'
					},
					value: {
						type: 'string'
					}
				}
			},
		},
	};
}

wp.hooks.addFilter(
	'blocks.registerBlockType',
	'hm-gtm/add-gtm-attribute',
	addGTMAttribute
);

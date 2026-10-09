/**
 * WPCalibrate Vehicle Fitment - Gutenberg Block Editor
 */

(function(wp) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;

	registerBlockType('wpcalibrate-vehicle-fitment/finder', {
		title: __('Vehicle Fitment & Parts Finder', 'wpcalibrate-vehicle-fitment'),
		description: __('Display responsive vehicle finder dropdowns for automotive parts filtering.', 'wpcalibrate-vehicle-fitment'),
		category: 'widgets',
		icon: 'car',
		keywords: [__('vehicle', 'wpcalibrate-vehicle-fitment'), __('fitment', 'wpcalibrate-vehicle-fitment'), __('parts', 'wpcalibrate-vehicle-fitment')],
		supports: {
			html: false,
		},
		attributes: {
			title: {
				type: 'string',
				default: __('Find Parts for Your Vehicle', 'wpcalibrate-vehicle-fitment'),
			},
			layout: {
				type: 'string',
				default: 'horizontal',
			},
			button_text: {
				type: 'string',
				default: __('Find Parts', 'wpcalibrate-vehicle-fitment'),
			},
		},
		edit: function(props) {
			var atts = props.attributes;

			return el(
				'div',
				{ className: 'wpcalibrate-vf-block-editor-wrap', style: { padding: '20px', border: '1px dashed #0073aa', background: '#f8fafc', borderRadius: '6px' } },
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Finder Settings', 'wpcalibrate-vehicle-fitment'), initialOpen: true },
						el(TextControl, {
							label: __('Title', 'wpcalibrate-vehicle-fitment'),
							value: atts.title,
							onChange: function(val) { props.setAttributes({ title: val }); },
						}),
						el(SelectControl, {
							label: __('Layout', 'wpcalibrate-vehicle-fitment'),
							value: atts.layout,
							options: [
								{ label: __('Horizontal', 'wpcalibrate-vehicle-fitment'), value: 'horizontal' },
								{ label: __('Vertical', 'wpcalibrate-vehicle-fitment'), value: 'vertical' },
							],
							onChange: function(val) { props.setAttributes({ layout: val }); },
						}),
						el(TextControl, {
							label: __('Button Text', 'wpcalibrate-vehicle-fitment'),
							value: atts.button_text,
							onChange: function(val) { props.setAttributes({ button_text: val }); },
						})
					)
				),
				el('h4', { style: { margin: '0 0 10px 0', color: '#1e293b' } }, '🚗 ' + (atts.title || __('Vehicle Finder', 'wpcalibrate-vehicle-fitment'))),
				el('p', { style: { color: '#64748b', fontSize: '13px', margin: 0 } },
					__('Preview: Year → Make → Model dropdown cascade with dynamic fitment filtering.', 'wpcalibrate-vehicle-fitment') + ' (' + atts.layout + ')'
				)
			);
		},
		save: function() {
			return null; // Server rendered via Plugin / Shortcode
		},
	});
})(window.wp);

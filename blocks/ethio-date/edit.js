import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { EthiopianDatePicker } from './datepicker';

export default function Edit( { attributes, setAttributes } ) {
    const blockProps = useBlockProps();
    const { language, numerals, format } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Display', 'ethio-cal' ) } initialOpen>
                    <SelectControl
                        label={ __( 'Language', 'ethio-cal' ) }
                        value={ language }
                        options={ [
                            { label: __( '— Site default —', 'ethio-cal' ), value: '' },
                            { label: __( 'English', 'ethio-cal' ), value: 'en' },
                            { label: __( 'Amharic (አማርኛ)', 'ethio-cal' ), value: 'am' },
                            { label: __( 'Both', 'ethio-cal' ), value: 'both' },
                        ] }
                        onChange={ ( val ) => setAttributes( { language: val } ) }
                    />
                    <SelectControl
                        label={ __( 'Numerals', 'ethio-cal' ) }
                        value={ numerals }
                        options={ [
                            { label: __( '— Site default —', 'ethio-cal' ), value: '' },
                            { label: __( 'Arabic (1 2 3)', 'ethio-cal' ), value: 'arabic' },
                            { label: __( "Ge'ez (፩ ፪ ፫)", 'ethio-cal' ), value: 'geez' },
                        ] }
                        onChange={ ( val ) => setAttributes( { numerals: val } ) }
                    />
                    <TextControl
                        label={ __( 'Format', 'ethio-cal' ) }
                        value={ format }
                        placeholder={ __( '— Site default —', 'ethio-cal' ) }
                        onChange={ ( val ) => setAttributes( { format: val } ) }
                        help={ __(
                            'Tokens: F (month name), j (day), d (0-padded day), n (month), m (0-padded month), Y (year).',
                            'ethio-cal',
                        ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <EthiopianDatePicker
                    attributes={ attributes }
                    setAttributes={ setAttributes }
                />
            </div>
        </>
    );
}

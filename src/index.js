import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import {
    TextControl,
    PanelBody
} from '@wordpress/components';
import {
    useBlockProps,
    InspectorControls
} from '@wordpress/block-editor';

registerBlockType( 'artstation/gallery', {
    apiVersion: 2,
    title: 'ArtStation: Gallery',
    icon: 'format-gallery',
    category: 'embed',
    attributes: {
        username: {
            type: 'string',
        },
        anchor: {
            type: 'string',
            default: '',
        },
        align: {
            type: 'string',
            default: '',
        }
    },
    supports: {
        align: ['full'],
        anchor: true,
    },

    edit: function ( props ) {
        const blockProps = useBlockProps();

        function onChangeUsername( username ) {
            props.setAttributes( { username: username } );
        }

        function reload_js() {
            const script = document.getElementById('cloudflare');
            if (script == null) {
                return;
            }
            parent = script.parentElement;
            parent.removeChild(script);

            const newscript = document.createElement('script');
            newscript.type  = 'text/javascript';
            newscript.id  = 'cloudflare';
            newscript.src = script.getAttribute('src');
            parent.appendChild(newscript);
        }

        window.setTimeout(reload_js, 1000 );

        return (
            <div { ...blockProps }>
                <InspectorControls key="setting">
                    <PanelBody title={__('Settings')}>
                        <TextControl
                            label="Username"
                            help="https://www.artstation.com/<username>"
                            value={ props.attributes.username }
                            onChange={ onChangeUsername }
                        />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender
                    block="artstation/gallery"
                    attributes={ props.attributes }
                />
            </div>
        );
    },
    save: function ( props ) {
        return null;
    },
} );

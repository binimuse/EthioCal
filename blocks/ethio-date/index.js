import './style.scss';
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: Edit,
    save: () => null, // dynamic block — rendered server-side via render_callback
} );

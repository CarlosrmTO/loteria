( function( wp ) {
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var ServerSideRender = wp.serverSideRender;

    registerBlockType( 'loteria/horizontal', {
        edit: function() {
            var blockProps = useBlockProps();
            return el( 'div', blockProps,
                el( ServerSideRender, {
                    block: 'loteria/horizontal'
                })
            );
        },
        save: function() {
            return null;
        }
    });
})( window.wp );

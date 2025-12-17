( function( wp ) {
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var ServerSideRender = wp.serverSideRender;

    registerBlockType( 'loteria/comprobador', {
        edit: function() {
            var blockProps = useBlockProps();
            return el( 'div', blockProps,
                el( ServerSideRender, {
                    block: 'loteria/comprobador'
                })
            );
        },
        save: function() {
            return null;
        }
    });
})( window.wp );

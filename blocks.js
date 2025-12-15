(function(blocks, element, components, editor) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;

    // ICONOS SVG (Simples)
    const iconHorizontal = el('svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
        el('path', { d: 'M4 6h16v12H4z M2 4h20v16H2z M6 10h12v4H6z' })
    );
    const iconCheck = el('svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
        el('path', { d: 'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z' })
    );

    // 1. BLOQUE HORIZONTAL
    registerBlockType('loteria/horizontal', {
        title: 'Lotería: Premios Horizontal',
        icon: iconHorizontal,
        category: 'widgets',
        description: 'Muestra la tira horizontal de premios (Gordo, 2º, etc.).',
        edit: function() {
            return el('div', { style: { padding: '20px', border: '2px dashed #FFE032', background: '#fffaf0', textAlign: 'center' } },
                el('strong', {}, 'Lotería Navidad: Widget Horizontal'),
                el('p', {}, 'Se mostrará aquí la tira de premios.')
            );
        },
        save: function() { return null; } // Rendered via PHP
    });

    // 2. BLOQUE COMPROBADOR
    registerBlockType('loteria/comprobador', {
        title: 'Lotería: Comprobador',
        icon: iconCheck,
        category: 'widgets',
        description: 'Formulario para comprobar décimos.',
        edit: function() {
            return el('div', { style: { padding: '20px', border: '2px dashed #FFE032', background: '#fffaf0', textAlign: 'center' } },
                el('strong', {}, 'Lotería Navidad: Comprobador'),
                el('p', {}, 'Se mostrará aquí el formulario de comprobación.')
            );
        },
        save: function() { return null; } // Rendered via PHP
    });

})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.editor);

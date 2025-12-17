/**
 * blocks.js - Loteria Navidad 2025 v7.6
 * Registro de bloques Gutenberg con ServerSideRender
 */
(function() {
    'use strict';

    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var ServerSideRender = wp.serverSideRender;

    console.log('Loteria v7.6: Iniciando registro de bloques...');

    // Placeholder para el editor cuando SSR no está disponible
    function EditorPlaceholder(props) {
        var blockProps = useBlockProps({
            style: {
                padding: '20px',
                border: '2px dashed #FFE032',
                background: '#fffbe6',
                textAlign: 'center'
            }
        });
        return el('div', blockProps,
            el('strong', { style: { fontSize: '16px', display: 'block', marginBottom: '8px' } }, props.title),
            el('p', { style: { margin: 0, fontSize: '12px', color: '#666' } }, props.description)
        );
    }

    // 1. BLOQUE HORIZONTAL
    registerBlockType('loteria/horizontal', {
        apiVersion: 2,
        title: 'Loteria Horizontal',
        description: 'Muestra los 5 premios principales en horizontal',
        icon: 'awards',
        category: 'widgets',
        keywords: ['loteria', 'navidad', 'sorteo', 'premios'],
        supports: { html: false },
        edit: function() {
            if (ServerSideRender) {
                return el('div', useBlockProps(),
                    el(ServerSideRender, { block: 'loteria/horizontal' })
                );
            }
            return el(EditorPlaceholder, {
                title: 'Loteria: Premios Horizontal',
                description: 'Tira de 5 premios principales'
            });
        },
        save: function() { return null; }
    });

    // 2. BLOQUE COMPROBADOR
    registerBlockType('loteria/comprobador', {
        apiVersion: 2,
        title: 'Loteria Comprobador',
        description: 'Formulario para comprobar numeros de loteria',
        icon: 'search',
        category: 'widgets',
        keywords: ['loteria', 'navidad', 'comprobador', 'comprobar'],
        supports: { html: false },
        edit: function() {
            if (ServerSideRender) {
                return el('div', useBlockProps(),
                    el(ServerSideRender, { block: 'loteria/comprobador' })
                );
            }
            return el(EditorPlaceholder, {
                title: 'Loteria: Comprobador',
                description: 'Formulario para comprobar numeros'
            });
        },
        save: function() { return null; }
    });

    // 3. BLOQUE PEDREA
    registerBlockType('loteria/pedrea', {
        apiVersion: 2,
        title: 'Loteria Pedrea',
        description: 'Tabla completa de la pedrea con pestanas',
        icon: 'editor-table',
        category: 'widgets',
        keywords: ['loteria', 'navidad', 'pedrea', 'tabla'],
        supports: { html: false },
        edit: function() {
            if (ServerSideRender) {
                return el('div', useBlockProps(),
                    el(ServerSideRender, { block: 'loteria/pedrea' })
                );
            }
            return el(EditorPlaceholder, {
                title: 'Loteria: Pedrea',
                description: 'Tabla completa de numeros premiados'
            });
        },
        save: function() { return null; }
    });

    // 4. BLOQUE PREMIOS
    registerBlockType('loteria/premios', {
        apiVersion: 2,
        title: 'Loteria Premios',
        description: 'Lista de los 5 premios principales',
        icon: 'money-alt',
        category: 'widgets',
        keywords: ['loteria', 'navidad', 'premios', 'gordo'],
        supports: { html: false },
        edit: function() {
            if (ServerSideRender) {
                return el('div', useBlockProps(),
                    el(ServerSideRender, { block: 'loteria/premios' })
                );
            }
            return el(EditorPlaceholder, {
                title: 'Loteria: Premios',
                description: 'Lista de 5 premios principales'
            });
        },
        save: function() { return null; }
    });

    console.log('Loteria v7.6: Bloques registrados OK');

})();

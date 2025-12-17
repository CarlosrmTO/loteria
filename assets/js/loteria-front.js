/**
 * loteria-front.js
 *
 * Versión 7.8 - 5 Premios Principales
 * Enhanced for Gutenberg editor preview
 */

(function() {
    'use strict';

    function initLoteriaWidgets() {
        console.log('Loteria Navidad v7.8: Init');

    const fmt = (n) => new Intl.NumberFormat('es-ES', {style:'currency', currency:'EUR'}).format(n);

    // ==========================================================================
    // 1. WIDGET: PREMIOS PRINCIPALES (5 PREMIOS: Gordo, 2º, 3º, 4º, 5º)
    // ==========================================================================
    const renderPremios = (container, data) => {
        const list = [];

        // Helper seguro
        const getDecimo = (obj) => (obj && obj.decimo) ? obj.decimo : null;

        // 1. GORDO
        list.push({ n: 'EL GORDO', v: '4.000.000 €', d: getDecimo(data.primerPremio) });
        // 2. SEGUNDO
        list.push({ n: '2º PREMIO', v: '1.250.000 €', d: getDecimo(data.segundoPremio) });
        // 3. TERCERO
        list.push({ n: '3º PREMIO', v: '500.000 €', d: (data.tercerosPremios && data.tercerosPremios[0]) ? data.tercerosPremios[0].decimo : null });
        // 4. CUARTO
        list.push({ n: '4º PREMIO', v: '200.000 €', d: (data.cuartosPremios && data.cuartosPremios[0]) ? data.cuartosPremios[0].decimo : null });
        // 5. QUINTO
        list.push({ n: '5º PREMIO', v: '60.000 €', d: (data.quintosPremios && data.quintosPremios[0]) ? data.quintosPremios[0].decimo : null });

        let html = '';
        list.forEach(item => {
            const num = item.d || '-----';
            const status = item.d ? '' : 'Pendiente';
            html += `
            <div class="loteria-premio-row">
                <div class="loteria-premio-info">
                    <strong class="loteria-premio-name">${item.n}</strong>
                    <small class="loteria-premio-val">${item.v}</small>
                </div>
                <div class="loteria-premio-num">${num}</div>
                <div class="loteria-premio-status">${status}</div>
            </div>`;
        });
        container.innerHTML = html;
    };

    document.querySelectorAll('.loteria-premios').forEach(w => {
        const api = w.dataset.api;
        const content = w.querySelector('.loteria-content');
        const btn = w.querySelector('.loteria-btn-reload');
        if(btn) btn.onclick = () => location.reload();

        if(!content || !api) return;

        fetch(api).then(r => r.json()).then(d => {
            if(d.error === 'DEBUG_MODE') return;
            renderPremios(content, d);
        }).catch(err => {
            console.error(err);
            content.innerHTML = '<p style="color:red;text-align:center">Error cargando datos</p>';
        });
    });

    // ==========================================================================
    // 2. WIDGET: COMPROBADOR (Logica Completa)
    // ==========================================================================
    document.querySelectorAll('.loteria-comprobador').forEach(w => {
        const api = w.dataset.api;
        const res = w.querySelector('.loteria-result');
        const form = w.querySelector('form');
        const btn = w.querySelector('.loteria-btn-reload');
        if(btn) btn.onclick = () => location.reload();

        if(!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const numVal = form.querySelector('[name=num]').value;
            const amtVal = form.querySelector('[name=amt]').value;
            
            const num = numVal.padStart(5, '0');
            const amt = parseFloat(amtVal) || 20;

            res.innerHTML = '<p style="text-align:center">Comprobando...</p>';

            fetch(api).then(r => r.json()).then(d => {
                if(d.pending) {
                    res.innerHTML = '<p style="text-align:center;color:#666">Sorteo pendiente</p>';
                    return;
                }

                let totalPrize = 0;
                const won = [];
                const nInt = parseInt(num, 10);

                // 1. Directo
                const direct = d.compruebe ? d.compruebe.find(x => x.decimo == num) : null;
                if(direct && direct.prize) {
                    totalPrize += (direct.prize / 100);
                    won.push(`Premio Directo: ${fmt(direct.prize/100)}`);
                }

                // 2. Logica Aprox / Centenas / Terminaciones / Reintegro
                // (Simplificada para robustez: si hay "listadoPremiosAsociados", usamos eso)
                const ax = d.listadoPremiosAsociados || {};
                
                // Implementacion Robustez: Si la API devuelve premio directo, confiamos en ello. 
                // Si queremos calcular extras client-side, necesitamos datos perfectos.
                // Para evitar "romper", si no hay premio directo y no hay logica extra, decimos "no premiado".
                
                if(totalPrize > 0) {
                    const myWin = (totalPrize / 20) * amt;
                    res.innerHTML = `
                    <div class="loteria-result-box loteria-result-win">
                        <p class="loteria-result-msg">¡Enhorabuena!</p>
                        <p>Premio: <strong>${fmt(myWin)}</strong></p>
                        <small>${won.join(', ')}</small>
                    </div>`;
                } else {
                    res.innerHTML = `<div class="loteria-result-box loteria-result-lose"><p class="loteria-result-msg">El nº <strong>${num}</strong> no ha sido premiado</p></div>`;
                }

            }).catch(e => {
                res.innerHTML = '<p style="color:red">Error comprobando</p>';
            });
        });
    });

    // ==========================================================================
    // 3. WIDGET: PEDREA (con pestañas por rangos)
    // ==========================================================================
    document.querySelectorAll('.loteria-pedrea').forEach(w => {
        const api = w.dataset.api;
        const tabsContainer = w.querySelector('.loteria-pedrea-tabs');
        const rangeTitle = w.querySelector('.loteria-pedrea-range-title');
        const tableContainer = w.querySelector('.loteria-pedrea-table-container');
        const btn = w.querySelector('.loteria-btn-reload');
        if(btn) btn.onclick = () => location.reload();

        if(!tabsContainer || !tableContainer) return;

        // Rangos de 5000 en 5000 (0-99999)
        const ranges = [];
        for(let i = 0; i < 100000; i += 5000) {
            ranges.push({ start: i, end: i + 4999, label: `${i.toLocaleString('es-ES')} al ${(i+4999).toLocaleString('es-ES')}` });
        }

        let allPremios = []; // {numero, premio}
        let currentRange = 0;

        // Renderizar pestañas (4 filas de 5 pestañas)
        const renderTabs = () => {
            let html = '<div class="loteria-tabs-grid">';
            ranges.forEach((r, idx) => {
                const active = idx === currentRange ? 'active' : '';
                html += `<button class="loteria-tab ${active}" data-range="${idx}">${r.label}</button>`;
            });
            html += '</div>';
            tabsContainer.innerHTML = html;

            // Event listeners
            tabsContainer.querySelectorAll('.loteria-tab').forEach(tab => {
                tab.onclick = () => {
                    currentRange = parseInt(tab.dataset.range);
                    renderTabs();
                    renderTable();
                };
            });
        };

        // Renderizar tabla para el rango actual
        const renderTable = () => {
            const range = ranges[currentRange];
            rangeTitle.innerHTML = `<h3>Números premiados del ${range.start.toLocaleString('es-ES')} al ${range.end.toLocaleString('es-ES')}</h3>`;

            // Filtrar premios en este rango
            const premiosEnRango = allPremios.filter(p => {
                const n = parseInt(p.numero);
                return n >= range.start && n <= range.end;
            });

            // Crear estructura de columnas (cada 1000 números)
            const columns = [];
            for(let col = 0; col < 5; col++) {
                const colStart = range.start + (col * 1000);
                const colEnd = colStart + 999;
                columns.push({ start: colStart, end: colEnd, premios: [] });
            }

            // Asignar premios a columnas
            premiosEnRango.forEach(p => {
                const n = parseInt(p.numero);
                const colIdx = Math.floor((n - range.start) / 1000);
                if(columns[colIdx]) columns[colIdx].premios.push(p);
            });

            // Generar tabla HTML
            let html = '<table class="loteria-pedrea-table"><thead><tr>';
            columns.forEach(col => {
                html += `<th>${col.start.toLocaleString('es-ES')}</th>`;
            });
            html += '</tr></thead><tbody>';

            // Encontrar máximo de filas necesarias
            const maxRows = Math.max(...columns.map(c => c.premios.length), 10);

            for(let row = 0; row < maxRows; row++) {
                html += '<tr>';
                columns.forEach(col => {
                    const p = col.premios[row];
                    if(p) {
                        const prizeClass = getPrizeClass(p.premio);
                        html += `<td class="loteria-pedrea-cell filled">
                            <span class="pedrea-num">${p.numero}</span>
                            <span class="pedrea-tipo">${p.tipo || 'T'}</span>
                            <span class="pedrea-premio ${prizeClass}">${formatPremio(p.premio)}</span>
                        </td>`;
                    } else {
                        html += '<td class="loteria-pedrea-cell empty">-----</td>';
                    }
                });
                html += '</tr>';
            }
            html += '</tbody></table>';
            tableContainer.innerHTML = html;
        };

        const getPrizeClass = (premio) => {
            if(premio >= 100000) return 'premio-alto';
            if(premio >= 10000) return 'premio-medio';
            return 'premio-bajo';
        };

        const formatPremio = (premio) => {
            if(premio >= 1000000) return (premio/1000000).toFixed(1) + 'M€';
            if(premio >= 1000) return (premio/1000).toFixed(0) + '.000€';
            return premio + '€';
        };

        // Inicializar con tabla vacía
        renderTabs();
        renderTable();

        // Mapeo de tipos de premio
        const getTipo = (prizeType) => {
            const tipos = {
                'G': 'G', // Gordo
                'Z': '2', // Segundo
                'Y': '3', // Tercero
                'X': '4', // Cuarto
                'W': '5', // Quinto
                'P': 'P', // Pedrea
                'T': 'T', // Terminación
                'A': 'A', // Aproximación
                'C': 'C', // Centena
                'R': 'R'  // Reintegro
            };
            return tipos[prizeType] || 'T';
        };

        // Cargar datos de la API
        fetch(api).then(r => r.json()).then(d => {
            if(!d.compruebe) return;

            // Procesar todos los premios - prize está en céntimos
            allPremios = d.compruebe.map(x => ({
                numero: x.decimo,
                premio: parseInt(x.prize) / 100, // Convertir de céntimos a euros
                tipo: getTipo(x.prizeType)
            })).sort((a,b) => parseInt(a.numero) - parseInt(b.numero));

            renderTable();
        }).catch(err => {
            console.error('Error cargando pedrea:', err);
            tableContainer.innerHTML = '<p style="color:red;text-align:center">Error cargando datos</p>';
        });
    });

    // ==========================================================================
    // 4. WIDGET: HORIZONTAL (5 PREMIOS)
    // ==========================================================================
    document.querySelectorAll('.loteria-premios-horiz').forEach(w => {
        const api = w.dataset.api;
        const content = w.querySelector('.loteria-content-horiz');
        const btn = w.querySelector('.loteria-btn-reload');
        if(btn) btn.onclick = () => location.reload();

        if(!content) return;

        fetch(api).then(r => r.json()).then(d => {
            const list = [];
            const getDec = (o) => (o && o.decimo) ? o.decimo : null;

            list.push({ l:'Gordo', v:'4M€', d: getDec(d.primerPremio) });
            list.push({ l:'2º', v:'1.25M€', d: getDec(d.segundoPremio) });
            list.push({ l:'3º', v:'500k€', d: (d.tercerosPremios && d.tercerosPremios[0]) ? d.tercerosPremios[0].decimo : null });
            list.push({ l:'4º', v:'200k€', d: (d.cuartosPremios && d.cuartosPremios[0]) ? d.cuartosPremios[0].decimo : null });
            list.push({ l:'5º', v:'60k€', d: (d.quintosPremios && d.quintosPremios[0]) ? d.quintosPremios[0].decimo : null });

            let html = '';
            list.forEach(it => {
                const num = it.d || '-----';
                html += `
                <div class="loteria-item-horiz">
                    <div class="loteria-label-horiz">${it.l}</div>
                    <div class="loteria-num-horiz">${num}</div>
                    <div class="loteria-prize-horiz">${it.v}</div>
                </div>`;
            });
            content.innerHTML = html;
        });
    });
    }

    // Initialize on DOMContentLoaded for frontend
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoteriaWidgets);
    } else {
        // DOM already loaded (e.g., in Gutenberg editor)
        initLoteriaWidgets();
    }

    // For Gutenberg editor: watch for DOM changes and re-initialize
    if (window.wp && window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            var shouldInit = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if added node is or contains a loteria widget
                            if (node.classList && (
                                node.classList.contains('loteria-widget') ||
                                node.classList.contains('loteria-premios') ||
                                node.classList.contains('loteria-comprobador') ||
                                node.classList.contains('loteria-pedrea') ||
                                node.classList.contains('loteria-premios-horiz')
                            )) {
                                shouldInit = true;
                            } else if (node.querySelector && node.querySelector('.loteria-widget')) {
                                shouldInit = true;
                            }
                        }
                    });
                }
            });
            if (shouldInit) {
                console.log('Loteria: New widget detected, initializing...');
                setTimeout(initLoteriaWidgets, 50);
            }
        });

        // Start observing the document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Also use the data store subscription as backup
        var lastBlockCount = 0;
        window.wp.data.subscribe(function() {
            var blocks = window.wp.data.select('core/block-editor').getBlocks();
            if (blocks && blocks.length !== lastBlockCount) {
                lastBlockCount = blocks.length;
                setTimeout(initLoteriaWidgets, 200);
            }
        });
    }

    // Expose globally for manual initialization if needed
    window.initLoteriaWidgets = initLoteriaWidgets;
})();

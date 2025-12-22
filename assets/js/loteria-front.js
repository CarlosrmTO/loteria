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
        
        // Render Main Prizes (G, 2, 3)
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

        // 4. CUARTOS (2 premios) - Compacto
        html += '<div class="loteria-compact-section">';
        html += '<span class="loteria-compact-title">4º PREMIOS</span>';
        html += '<span class="loteria-compact-val">200.000 €</span>';
        html += '<div class="loteria-compact-grid">';
        for(let i=0; i<2; i++) {
            const decimo = (data.cuartosPremios && data.cuartosPremios[i] && data.cuartosPremios[i].decimo) ? data.cuartosPremios[i].decimo : '-----';
            html += `<div class="loteria-compact-num">${decimo}</div>`;
        }
        html += '</div></div>';

        // 5. QUINTOS (8 premios) - Compacto
        html += '<div class="loteria-compact-section">';
        html += '<span class="loteria-compact-title">5º PREMIOS</span>';
        html += '<span class="loteria-compact-val">60.000 €</span>';
        html += '<div class="loteria-compact-grid">';
        for(let i=0; i<8; i++) {
            const decimo = (data.quintosPremios && data.quintosPremios[i] && data.quintosPremios[i].decimo) ? data.quintosPremios[i].decimo : '-----';
            html += `<div class="loteria-compact-num">${decimo}</div>`;
        }
        html += '</div></div>';

        container.innerHTML = html;
    };

    document.querySelectorAll('.loteria-premios').forEach(w => {
        console.log('Loteria Widget found:', w);
        const api = w.dataset.api;
        console.log('API URL:', api);
        const content = w.querySelector('.loteria-content');
        console.log('Content element:', content);
        const btn = w.querySelector('.loteria-btn-reload');
        if(btn) btn.onclick = () => location.reload();

        if(!content || !api) {
            console.error('Missing content or api:', {content, api});
            return;
        }

        console.log('Fetching from:', api);
        fetch(api).then(r => {
            console.log('Fetch response:', r);
            return r.json();
        }).then(d => {
            if(d.error === 'DEBUG_MODE') return;
            console.log('Loteria API Data:', d);
            console.log('primerPremio:', d.primerPremio);
            console.log('segundoPremio:', d.segundoPremio);
            console.log('tercerosPremios:', d.tercerosPremios);
            console.log('cuartosPremios:', d.cuartosPremios);
            console.log('quintosPremios:', d.quintosPremios);
            renderPremios(content, d);
        }).catch(err => {
            console.error('Fetch error:', err);
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
                    // SELAE returns prize in cents (céntimos)
                    // Pedrea (P5) is 100€ per décimo, NOT 120€
                    // 120€ only happens when pedrea (100€) + reintegro (20€) are combined
                    let prizeAmount = direct.prize / 100; // Convert from cents to euros
                    
                    totalPrize += prizeAmount;
                    
                    let typeName = 'Premio';
                    if(direct.prizeType) {
                        const types = {
                            'G': 'El Gordo', 'Z': '2º Premio', 'Y': '3º Premio',
                            'H': '4º Premio', 'I': '5º Premio', 'P5': 'Pedrea'
                        };
                        typeName = types[direct.prizeType] || 'Premio';
                    }
                    won.push(`${typeName}: ${fmt(prizeAmount)}`);
                }

                // 2. Check for Reintegro (last digit matches El Gordo)
                const gordo = d.primerPremio?.decimo;
                if(gordo && num.slice(-1) === gordo.slice(-1)) {
                    const reintegro = d.listadoPremiosAsociados?.reintegro_gordo?.prize || 2000; // 2000 cents = 20€
                    const reintegroAmount = reintegro / 100;
                    totalPrize += reintegroAmount;
                    won.push(`Reintegro: ${fmt(reintegroAmount)}`);
                }

                // 3. Logica Aprox / Centenas / Terminaciones
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

            // Get El Gordo's last digit for reintegro calculation
            const gordo = d.primerPremio?.decimo;
            const gordoLastDigit = gordo ? gordo.slice(-1) : null;
            const reintegroAmount = d.listadoPremiosAsociados?.reintegro_gordo?.prize ? 
                d.listadoPremiosAsociados.reintegro_gordo.prize / 100 : 20; // 20€ default

            // Procesar todos los premios - prize está en céntimos
            allPremios = d.compruebe.map(x => {
                let premio = parseInt(x.prize) / 100; // Convertir de céntimos a euros
                
                // Check if this number has reintegro (last digit matches El Gordo)
                if(gordoLastDigit && x.decimo && x.decimo.slice(-1) === gordoLastDigit) {
                    premio += reintegroAmount; // Add reintegro (20€)
                }
                
                return {
                    numero: x.decimo,
                    premio: premio,
                    tipo: getTipo(x.prizeType)
                };
            }).sort((a,b) => parseInt(a.numero) - parseInt(b.numero));

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

            // 1. GORDO
            list.push({ l:'El Gordo', v:'4.000.000€', d: getDec(d.primerPremio), type: 'single' });
            // 2. SEGUNDO
            list.push({ l:'2º Premio', v:'1.250.000€', d: getDec(d.segundoPremio), type: 'single' });
            // 3. TERCERO
            list.push({ l:'3º Premio', v:'500.000€', d: (d.tercerosPremios && d.tercerosPremios[0]) ? d.tercerosPremios[0].decimo : null, type: 'single' });
            
            // 4. CUARTOS (2 premios) -> Grouped
            const cuartos = [];
            for(let i=0; i<2; i++) {
                cuartos.push((d.cuartosPremios && d.cuartosPremios[i] && d.cuartosPremios[i].decimo) ? d.cuartosPremios[i].decimo : '-----');
            }
            list.push({ l:'4º Premio', v:'200.000€', data: cuartos, type: 'group-4' });

            // 5. QUINTOS (8 premios) -> Grouped
            const quintos = [];
            for(let i=0; i<8; i++) {
                quintos.push((d.quintosPremios && d.quintosPremios[i] && d.quintosPremios[i].decimo) ? d.quintosPremios[i].decimo : '-----');
            }
            list.push({ l:'5º Premio', v:'60.000€', data: quintos, type: 'group-5' });

            let html = '';
            list.forEach(it => {
                if(it.type === 'single') {
                    const num = it.d || '-----';
                    html += `
                    <div class="loteria-item-horiz single">
                        <div class="loteria-label-horiz">${it.l}</div>
                        <div class="loteria-num-horiz main-num">${num}</div>
                        <div class="loteria-prize-horiz">${it.v}</div>
                    </div>`;
                } else if (it.type === 'group-4') {
                    html += `
                    <div class="loteria-item-horiz group-4">
                        <div class="loteria-label-horiz">${it.l}</div>
                        <div class="loteria-grid-4">
                            ${it.data.map(n => `<span class="mini-num">${n}</span>`).join('')}
                        </div>
                        <div class="loteria-prize-horiz">${it.v}</div>
                    </div>`;
                } else if (it.type === 'group-5') {
                    html += `
                    <div class="loteria-item-horiz group-5">
                        <div class="loteria-label-horiz">${it.l}</div>
                        <div class="loteria-grid-5">
                            ${it.data.map(n => `<span class="mini-num">${n}</span>`).join('')}
                        </div>
                        <div class="loteria-prize-horiz">${it.v}</div>
                    </div>`;
                }
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

/**
 * Select2 Global Configuration
 * Inicializa Select2 en todos los elementos select de la aplicación
 */

$(document).ready(function() {
    // Función para inicializar Select2
    function initializeSelect2(container) {
        const $container = container || $(document);
        console.log('Inicializando Select2 en:', container ? 'contenedor específico' : 'documento completo');
        
        // Configuración global de Select2
        const select2Config = {
            theme: 'bootstrap4',
            language: 'es',
            placeholder: function() {
                return $(this).data('placeholder') || 'Seleccione una opción...';
            },
            allowClear: true,
            width: '100%'
        };

        // Inicializar Select2 en todos los elementos select del contenedor
        $container.find('select').each(function() {
            const $this = $(this);
            
            // Skip if already initialized
            if ($this.hasClass('select2-hidden-accessible')) {
                console.log('Select ya tiene Select2:', $this.attr('id') || $this.attr('name'));
                return;
            }

            // Skip if explicitly disabled
            if ($this.hasClass('no-select2')) {
                console.log('Select excluido de Select2:', $this.attr('id') || $this.attr('name'));
                return;
            }

            console.log('Inicializando Select2 en:', $this.attr('id') || $this.attr('name'));

            // Configuración específica para este select
            const localConfig = Object.assign({}, select2Config);

            // Detectar si está dentro de un modal
            const $modal = $this.closest('.modal');
            if ($modal.length > 0) {
                localConfig.dropdownParent = $modal;
                console.log('Select2 en modal detectado, configurando dropdownParent');
            }

            // Si es un select múltiple
            if ($this.prop('multiple')) {
                localConfig.placeholder = $this.data('placeholder') || 'Seleccione una o más opciones...';
            }

            // Si tiene datos remotos
            if ($this.data('ajax-url')) {
                localConfig.ajax = {
                    url: $this.data('ajax-url'),
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items,
                            pagination: {
                                more: (params.page * 30) < data.total_count
                            }
                        };
                    }
                };
                localConfig.minimumInputLength = 1;
            }

            // Si no permite limpiar
            if ($this.data('allow-clear') === false || $this.prop('required')) {
                localConfig.allowClear = false;
            }

            // Inicializar Select2
            try {
                $this.select2(localConfig);
                console.log('Select2 inicializado exitosamente en:', $this.attr('id') || $this.attr('name'));
            } catch (e) {
                console.warn('Error inicializando Select2:', e);
            }
        });
    }

    // Inicializar al cargar la página
    initializeSelect2();

    // Re-inicializar cuando se agrega contenido dinámico
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).find('select').length > 0) {
            setTimeout(function() {
                initializeSelect2();
            }, 100);
        }
    });

    // Para compatibilidad con contenido dinámico moderno
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.tagName === 'SELECT' || $(node).find('select').length > 0) {
                                shouldReinit = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                setTimeout(function() {
                    initializeSelect2();
                }, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Event handlers para formularios modales - Mejorado
    $(document).on('shown.bs.modal', function(e) {
        const $modal = $(e.target);
        console.log('Modal abierto, inicializando Select2...');
        
        setTimeout(function() {
            // Inicializar Select2 específicamente en el modal
            $modal.find('select').each(function() {
                const $select = $(this);
                
                // Skip si ya está inicializado
                if ($select.hasClass('select2-hidden-accessible')) {
                    console.log('Select2 ya inicializado en:', $select.attr('id') || $select.attr('name'));
                    return;
                }
                
                // Skip si está excluido
                if ($select.hasClass('no-select2')) {
                    return;
                }
                
                console.log('Inicializando Select2 en modal para:', $select.attr('id') || $select.attr('name'));
                
                const localConfig = Object.assign({}, select2Config);
                
                // IMPORTANTE: Configurar dropdownParent para modales
                localConfig.dropdownParent = $modal;
                
                // Si es múltiple
                if ($select.prop('multiple')) {
                    localConfig.placeholder = $select.data('placeholder') || 'Seleccione una o más opciones...';
                }
                
                // Si no permite limpiar
                if ($select.data('allow-clear') === false || $select.prop('required')) {
                    localConfig.allowClear = false;
                }
                
                try {
                    $select.select2(localConfig);
                    console.log('Select2 inicializado exitosamente en modal');
                } catch (e) {
                    console.error('Error inicializando Select2 en modal:', e);
                }
            });
        }, 250); // Aumentar el delay para modales
    });

    // Limpiar Select2 antes de remover elementos
    $(document).on('DOMNodeRemoved', function(e) {
        $(e.target).find('.select2-hidden-accessible').each(function() {
            $(this).select2('destroy');
        });
    });

    // Limpiar Select2 cuando se cierra un modal
    $(document).on('hidden.bs.modal', function(e) {
        const $modal = $(e.target);
        $modal.find('.select2-hidden-accessible').each(function() {
            try {
                $(this).select2('destroy');
                console.log('Select2 destruido al cerrar modal');
            } catch (error) {
                console.warn('Error al destruir Select2:', error);
            }
        });
    });

    // Función para reinicializar Select2 manualmente (útil para contenido dinámico)
    window.reinitializeSelect2 = function(container) {
        const $container = container ? $(container) : $(document);
        console.log('Reinicializando Select2 manualmente...');
        
        $container.find('select:not(.no-select2)').each(function() {
            const $this = $(this);
            if ($this.hasClass('select2-hidden-accessible')) {
                try {
                    $this.select2('destroy');
                    console.log('Select2 destruido para reinicialización:', $this.attr('id') || $this.attr('name'));
                } catch (e) {
                    console.warn('Error al destruir Select2:', e);
                }
            }
        });
        
        setTimeout(function() {
            initializeSelect2($container);
        }, 100);
    };
    
    // Función específica para modales
    window.initializeSelect2InModal = function(modalId) {
        const $modal = $(modalId);
        if ($modal.length === 0) {
            console.warn('Modal no encontrado:', modalId);
            return;
        }
        
        console.log('Inicializando Select2 específicamente en modal:', modalId);
        
        $modal.find('select:not(.no-select2)').each(function() {
            const $select = $(this);
            
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            
            const config = {
                theme: 'bootstrap4',
                language: 'es',
                placeholder: $select.data('placeholder') || 'Seleccionar...',
                allowClear: true,
                width: '100%',
                dropdownParent: $modal
            };
            
            if ($select.prop('multiple')) {
                config.placeholder = $select.data('placeholder') || 'Seleccionar opciones...';
            }
            
            try {
                $select.select2(config);
                console.log('Select2 inicializado en modal para:', $select.attr('id') || $select.attr('name'));
            } catch (e) {
                console.error('Error inicializando Select2 en modal:', e);
            }
        });
    };
});

// Configuración de idioma español para Select2
if (typeof $.fn.select2 !== 'undefined') {
    $.fn.select2.defaults.set('language', {
        errorLoading: function () {
            return 'No se pueden cargar los resultados.';
        },
        inputTooLong: function (args) {
            var overChars = args.input.length - args.maximum;
            var message = 'Elimina ' + overChars + ' caracter';
            if (overChars != 1) {
                message += 'es';
            }
            return message;
        },
        inputTooShort: function (args) {
            var remainingChars = args.minimum - args.input.length;
            var message = 'Ingresa ' + remainingChars + ' o más caracteres';
            return message;
        },
        loadingMore: function () {
            return 'Cargando más resultados…';
        },
        maximumSelected: function (args) {
            var message = 'Solo puedes seleccionar ' + args.maximum + ' elemento';
            if (args.maximum != 1) {
                message += 's';
            }
            return message;
        },
        noResults: function () {
            return 'No se encontraron resultados';
        },
        searching: function () {
            return 'Buscando…';
        },
        removeAllItems: function () {
            return 'Eliminar todos los elementos';
        }
    });
} else {
    console.warn('Select2 no está disponible. Asegúrate de que la librería esté cargada correctamente.');
}

// Función de debug para verificar el estado de Select2
window.debugSelect2 = function() {
    console.log('=== DEBUG SELECT2 ===');
    console.log('Elementos select encontrados:', $('select').length);
    console.log('Elementos con Select2:', $('.select2-hidden-accessible').length);
    console.log('Elementos excluidos (.no-select2):', $('.no-select2').length);
    
    $('select').each(function(index) {
        const $this = $(this);
        const id = $this.attr('id') || $this.attr('name') || 'sin-id-' + index;
        const hasSelect2 = $this.hasClass('select2-hidden-accessible');
        const isExcluded = $this.hasClass('no-select2');
        const inModal = $this.closest('.modal').length > 0;
        
        console.log(`Select ${id}:`, {
            hasSelect2: hasSelect2,
            excluded: isExcluded,
            inModal: inModal,
            placeholder: $this.data('placeholder')
        });
    });
    console.log('=== FIN DEBUG ===');
};
/**
 * JavaScript público del plugin
 * Archivo: assets/js/public.js
 */

(function($) {
    'use strict';
    
    var QuinielaApp = {
        
        /**
         * Inicializar aplicación
         */
        init: function() {
            this.bindEvents();
            this.validateDeadlines();
            this.autoSave();
        },
        
        /**
         * Vincular eventos
         */
        bindEvents: function() {
            $('#save-predictions').on('click', this.savePredictions);
            $('.pred-score').on('change', this.markAsModified);
            $('.final-select').on('change', this.markAsModified);
            $('.trivia-select').on('change', this.markAsModified);
        },
        
        /**
         * Marcar como modificado
         */
        markAsModified: function() {
            $('#save-predictions').addClass('modified').text('Guardar Cambios *');
        },
        
        /**
         * Validar plazos de edición
         */
        validateDeadlines: function() {
            $('.quiniela-table tbody tr.editable').each(function() {
                var $row = $(this);
                var matchId = $row.data('match-id');
                
                // Aquí podrías hacer validación adicional si es necesario
            });
        },
        
        /**
         * Guardar pronósticos
         */
        savePredictions: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            
            // Confirmar
            if (!confirm(quinielaAjax.strings.confirm)) {
                return;
            }
            
            // Preparar datos
            var predictions = {};
            var finalPredictions = {};
            var triviaAnswers = {};
            
            // Recopilar pronósticos de partidos
            $('.quiniela-table tbody tr.editable').each(function() {
                var $row = $(this);
                var matchId = $row.data('match-id');
                var team1 = $row.find('input[name^="pred_team1_"]').val();
                var team2 = $row.find('input[name^="pred_team2_"]').val();
                
                if (team1 !== '' && team2 !== '') {
                    predictions[matchId] = {
                        team1: parseInt(team1),
                        team2: parseInt(team2)
                    };
                }
            });
            
            // Recopilar predicciones finales
            finalPredictions.champion_id = $('select[name="champion_id"]').val();
            finalPredictions.runner_up_id = $('select[name="runner_up_id"]').val();
            finalPredictions.third_place_id = $('select[name="third_place_id"]').val();
            finalPredictions.fourth_place_id = $('select[name="fourth_place_id"]').val();
            finalPredictions.top_scorer = $('input[name="top_scorer"]').val();
            
            // Recopilar respuestas de trivia
            $('.trivia-select').each(function() {
                var $select = $(this);
                var questionId = $select.attr('name').replace('trivia_', '');
                var answer = $select.val();
                
                if (answer !== '') {
                    triviaAnswers[questionId] = answer;
                }
            });
            
            // Deshabilitar botón
            $button.prop('disabled', true).text(quinielaAjax.strings.saving);
            
            // Enviar datos
            $.ajax({
                url: quinielaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_all_predictions',
                    nonce: quinielaAjax.nonce,
                    predictions: predictions,
                    final_predictions: finalPredictions,
                    trivia_answers: triviaAnswers
                },
                success: function(response) {
                    if (response.success) {
                        QuinielaApp.showMessage(quinielaAjax.strings.saved, 'success');
                        $button.removeClass('modified').text('Guardar Pronósticos');
                        
                        // Recargar después de 1 segundo
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        QuinielaApp.showMessage(response.data || quinielaAjax.strings.error, 'error');
                    }
                },
                error: function() {
                    QuinielaApp.showMessage(quinielaAjax.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Autoguardado cada 2 minutos
         */
        autoSave: function() {
            if ($('#save-predictions').length === 0) {
                return;
            }
            
            setInterval(function() {
                if ($('#save-predictions').hasClass('modified')) {
                    $('#save-predictions').trigger('click');
                }
            }, 120000); // 2 minutos
        },
        
        /**
         * Mostrar mensaje
         */
        showMessage: function(message, type) {
            var $message = $('<div class="quiniela-message ' + type + '">' + message + '</div>');
            $('body').append($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Validar formulario
         */
        validatePredictions: function() {
            var isValid = true;
            var errors = [];
            
            // Validar que los goles sean números válidos
            $('.pred-score').each(function() {
                var value = $(this).val();
                if (value !== '' && (isNaN(value) || parseInt(value) < 0 || parseInt(value) > 20)) {
                    isValid = false;
                    errors.push('Los goles deben ser números entre 0 y 20');
                    return false;
                }
            });
            
            // Validar que no se repitan equipos en predicciones finales
            var selectedTeams = [];
            $('.final-select').each(function() {
                var value = $(this).val();
                if (value !== '') {
                    if (selectedTeams.indexOf(value) !== -1) {
                        isValid = false;
                        errors.push('No puedes seleccionar el mismo equipo en diferentes posiciones');
                        return false;
                    }
                    selectedTeams.push(value);
                }
            });
            
            if (!isValid) {
                this.showMessage(errors.join('<br>'), 'error');
            }
            
            return isValid;
        },
        
        /**
         * Countdown para partidos próximos
         */
        initCountdowns: function() {
            $('.quiniela-table tbody tr.editable').each(function() {
                var $row = $(this);
                // Aquí podrías implementar un countdown si lo deseas
            });
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        QuinielaApp.init();
    });
    
    // Exponer globalmente
    window.QuinielaApp = QuinielaApp;
    
})(jQuery);

/**
 * Handler adicional para AJAX combinado
 */
jQuery(document).ready(function($) {
    
    // Guardar todos los pronósticos en una sola llamada
    $(document).on('submit', '#quiniela-predictions-form', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=save_all_predictions';
        formData += '&nonce=' + quinielaAjax.nonce;
        
        $.ajax({
            url: quinielaAjax.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#save-predictions').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    QuinielaApp.showMessage('Pronósticos guardados correctamente', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    QuinielaApp.showMessage(response.data || 'Error al guardar', 'error');
                }
            },
            error: function() {
                QuinielaApp.showMessage('Error de conexión', 'error');
            },
            complete: function() {
                $('#save-predictions').prop('disabled', false).text('Guardar Pronósticos');
            }
        });
    });
    
    // Prevenir envío accidental del formulario
    $(document).on('keypress', '.pred-score, .final-select, .trivia-select', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            return false;
        }
    });
    
    // Resaltar fila al modificar
    $('.pred-score').on('focus', function() {
        $(this).closest('tr').addClass('editing');
    }).on('blur', function() {
        $(this).closest('tr').removeClass('editing');
    });
    
});

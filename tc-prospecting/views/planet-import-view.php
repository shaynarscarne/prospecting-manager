<?php if (!defined('ABSPATH')) exit;

/**
 * Renders the UI for selecting an external planet and importing it.
 */
function render_planet_import_view() {
    ?>
    <div class="wrap">
        <h2>Add Planet From External DB</h2>
        <p>Select a planet from the external SWC database and import it into TC Prospecting. This might take up to 30 seconds.</p>
        <button id="go-back-btn" class="button" style="margin-bottom:15px;">Go Back</button>
        <select id="planet-select" style="width:100%;"></select>
        <button id="add-planet-btn" class="button" disabled style="margin-top:15px;">
            Add Selected Planet
        </button>
        <div id="loadingSpinner" style="display: none;"></div>
        <div id="message-area" style="margin-top:10px;"></div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#planet-select').select2({
            placeholder: 'Search for a planet...',
            data: planetData.planets.map(function(p){
                return { id: p.uid, text: p.name };
            }),
            minimumInputLength: 3,
            width: '100%'
        });

        $('#planet-select').on('change', function(){
            $('#add-planet-btn').prop('disabled', !this.value);
        });

        $('#add-planet-btn').on('click', function(){
            const planetUid = $('#planet-select').val();
            $('#loadingSpinner').show();
            if (!planetUid) return;

            $.post(planetData.ajaxurl, {
                action: 'add_selected_planet',
                nonce: planetData.nonce,
                planet_uid: planetUid
            })
            .done(function(response){
                $('#loadingSpinner').hide();
                let cls = response.success ? 'notice-success' : 'notice-error';
                let msg = response.data ? response.data.message : 'Unknown response';
                if (response.data && response.data.debug) {
                    msg += '<br><small>Debug: '+ JSON.stringify(response.data.debug) +'</small>';
                }

                $('#message-area').html(`
                    <div class="notice ${cls} is-dismissible">
                        <p>${msg}</p>
                    </div>
                `);

                if (response.success) {
                    $('#planet-select').val(null).trigger('change');
                }
            })
            .fail(function(xhr){
                $('#loadingSpinner').hide();
                $('#message-area').html(`
                    <div class="notice notice-error is-dismissible">
                        <p>Error ${xhr.status}: ${xhr.statusText}</p>
                        <pre>${xhr.responseText}</pre>
                    </div>
                `);
            });
        });

        $('#go-back-btn').on('click', function(){
            window.location.href = '/prospecting-database/';
        });
    });
    </script>
    <?php
}
?>

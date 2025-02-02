/************************************************
 * planet-designer.js
 * 
 * Handles all UI and AJAX logic for planet_designer_view.php
 ************************************************/

(function($){
    let selectedX = 0, selectedY = 0;

    $('#modifyPlanetBtn').on('click', function(){
        $('#modifyPlanetModal').show();
    });
    $('#cancelModifyPlanetBtn').on('click', function(){
        $('#modifyPlanetModal').hide();
    });

    $('#savePlanetInfoBtn').on('click', function(){
        const planetId   = $('#modifyPlanetId').val();
        const name       = $('#modifyPlanetName').val();
        const system     = $('#modifySystem').val();
        const sector     = $('#modifySector').val();
        const location   = $('#modifyLocation').val();
        const size       = $('#modifySize').val();

        $.post(tcProspectingData.ajaxurl, {
            action: 'update_planet_info',
            security: tcProspectingData.nonce,
            planet_id: planetId,
            planet_name: name,
            system: system,
            sector: sector,
            location: location,
            size: size
        }).done(function(resp){
            if (!resp.success) {
                alert('Failed to update planet: ' + (resp.data ? resp.data.message : 'Unknown error'));
            } else {
                window.location.reload();
            }
        }).fail(function(){
            alert('Error updating planet info');
        });

        $('#modifyPlanetModal').hide();
    });

    // Open terrain modal (called by each <td> in the grid)
    window.openTerrainModal = function(x, y) {
        selectedX = x;
        selectedY = y;
        $('#terrainModal').show();
    };

    // Close terrain modal
    $('#cancelTerrainBtn').on('click', function(){
        $('#terrainModal').hide();
    });

    // When user picks a terrain type
    window.selectTerrain = function(terrainCode) {
        const planetId = window.currentPlanetId || 0;
        $.post(tcProspectingData.ajaxurl, {
            action: 'save_terrain_cell',
            security: tcProspectingData.nonce,
            planet_id: planetId,
            x: selectedX,
            y: selectedY,
            terrain_code: terrainCode
        }).done(function(resp){
            if (!resp.success) {
                alert('Error saving terrain: ' + (resp.data ? resp.data.message : 'Unknown error'));
                return;
            }
            // Update the cell in the DOM
            const cell = $(`td[data-x='${selectedX}'][data-y='${selectedY}']`);
            cell.html(`
                <img src="https://images.swcombine.com/galaxy/terrains/${terrainCode}/terrain.gif" 
                     alt="${terrainCode}" height="60" width="60">
            `);
            
            // Initialize or update the global modified terrain tracker
            if (!window.modifiedTerrainData) {
                window.modifiedTerrainData = [];
            }
            // Remove any existing entry for this cell
            window.modifiedTerrainData = window.modifiedTerrainData.filter(function(item) {
                return !(item.x === selectedX && item.y === selectedY);
            });
            // Add the updated cell data to the tracker
            window.modifiedTerrainData.push({ x: selectedX, y: selectedY, terrain: terrainCode });
        }).fail(function(){
            alert('AJAX error saving terrain');
        });
        $('#terrainModal').hide();
    };
    

    // Save Planet Terrain button
    $('#savePlanetBtn').on('click', function(){
        if (!confirm('Save planet terrain changes now?')) return;
        
        $.post(tcProspectingData.ajaxurl, {
        action: 'save_planet_terrain_bulk',
        planet_id: window.currentPlanetId,
        security: tcProspectingData.nonce,
        terrain_data: JSON.stringify(window.modifiedTerrainData || [])
        }).done(function(resp){
        if (resp.success) {
        alert('Planet & terrain changes saved successfully!');
        window.modifiedTerrainData = [];
        } else {
        alert('Failed to save planet terrain: ' + (resp.data ? resp.data.message : 'Unknown'));
        }
        }).fail(function(){
        alert('Error saving planet terrain');
        });
    });
    

    $(window).on('click', function(evt){
        if (evt.target.id === 'modifyPlanetModal') {
            $('#modifyPlanetModal').hide();
        } else if (evt.target.id === 'terrainModal') {
            $('#terrainModal').hide();
        }
    });
})(jQuery);

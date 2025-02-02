(function($) {
    /** Global State Variables **/
    // For ADD deposit modal
    var currentPlanetId = 0,
        currentX = 0,
        currentY = 0,
        selectedResource = '',
        selectedResourceCode = '';
  
    // For MODIFY deposit modal
    var selectedModifyResource = '',
        selectedModifyResourceCode = '';
  
    /** Grid Click Handler **/
    window.handleGridClick = function(cell, planetId, x, y) {
      var $cell = $(cell);
      var $depositEl = $cell.find('.deposit-modify');
      if ($depositEl.length > 0) {
        openModifyModal($depositEl.get(0));
      } else {
        openResourceModal(planetId, x, y);
      }
    };
    function refreshGrid() {
        $.post(tcProspectingData.ajaxurl, {
            action: 'load_planet_view',
            security: tcProspectingData.nonce,
            planet_id: currentPlanetId 
          },
          function(resp) {
            if (resp.success && resp.data.html) {
              // Completely remove the old view and replace it with the new HTML.
              $('#planetViewContainer').html(resp.data.html);
            } else {
              console.error("Error refreshing planet view: " + (resp.data ? resp.data.message : "Unknown error"));
            }
          },
          'json'
        ).fail(function(xhr) {
          console.error("AJAX error during planet view refresh: " + xhr.status + " " + xhr.statusText);
        });
      }
      
    /** Planet View AJAX Loading **/
    $(document).on('click', '.viewPlanetLink', function(e) {
      var planetId = $(this).data('id');
      currentPlanetId = planetId;
      $.post(tcProspectingData.ajaxurl, {
        action: 'load_planet_view',
        security: tcProspectingData.nonce,
        planet_id: planetId
      }, function(resp) {
        if (resp.success) {
          $('#planetViewContainer').html(resp.data.html);
          $('html, body').animate({
            scrollTop: $('#planetViewContainer').offset().top - 200
          }, 500);
        } else {
          alert("Error loading planet view: " + (resp.data ? resp.data.message : "Unknown error"));
        }
      }, 'json').fail(function(xhr) {
        alert("AJAX error: " + xhr.status + " " + xhr.statusText);
        console.error(xhr.responseText);
      });
    });
      
    /** Add Deposit Modal Functions **/
    function openResourceModal(planetId, x, y) {
      currentPlanetId = planetId;
      currentX = x;
      currentY = y;
      selectedResource = '';
      selectedResourceCode = '';
      // Reset resource options opacity.
      $('.resource-option').css('opacity', '1');
      $('#resourceAmount').val('');
      $('#prospectorName').val(tcProspectingData.currentUser).text(tcProspectingData.currentUser);
      $('#prospectorSkill').val('5');
      $('#prospectorVehicle').val('SX-65 Groundhog');
      $('#resourceModal').show();
    }
  
    function closeResourceModal() {
      $('#resourceModal').hide();
    }
  
    function selectResource(resourceName, resourceCode, el) {
      selectedResource = resourceName;
      selectedResourceCode = resourceCode;
      $('.resource-option').css('opacity', '0.4');
      $(el).css('opacity', '1');
    }
  
    /** Modify Deposit Modal Functions **/
    // Build the resource grid for use in the modify modal.
    function renderModifyResourceGrid() {
      var resources = {
        'Quantum': 1,
        'Meleenium': 2,
        'Ardanium': 3,
        'Rudic': 4,
        'Ryll': 5,
        'Duracrete': 6,
        'Alazhi': 7,
        'Laboi': 8,
        'Adegan': 9,
        'Rockivory': 10,
        'Tibannagas': 11,
        'Nova': 12,
        'Varium': 13,
        'Varmigio': 14,
        'Lommite': 15,
        'Hibridium': 16,
        'Durelium': 17,
        'Lowickan': 18,
        'Vertex': 19,
        'Berubian': 20
      };
      var gridHTML = '';
      $.each(resources, function(name, code) {
        gridHTML += '<div class="modify-resource-option" data-resource-name="' + name + '" onclick="selectModifyResource(\'' + name + '\', \'' + code + '\', this)">';
        gridHTML += '<img src="https://images.swcombine.com/materials/' + code + '/deposit.gif" alt="' + name + '" height="32" width="32">';
        gridHTML += '<p style="margin: 5px 0;">' + name + '</p></div>';
      });
      gridHTML += '<div class="modify-resource-option" data-resource-name="No deposit" onclick="selectModifyResource(\'No deposit\', \'0\', this)">';
      gridHTML += '<img src="https://cdn-icons-png.flaticon.com/512/1828/1828843.png" alt="No deposit" height="32" width="32">';
      gridHTML += '<p>No Deposit</p></div>';
      $('#modifyResourceGrid').html(gridHTML);
    }
  
    function openModifyModal(depositElement) {
      var rawEl = depositElement instanceof jQuery ? depositElement.get(0) : depositElement;
      var depositId = rawEl.dataset.depositId;
      $('#modifyDepositId').val(depositId);
      $('#modifyResourceAmount').val(rawEl.dataset.resourceAmount || 0);
      $('#modifyProspectorName').val(rawEl.dataset.prospector || '');
      $('#modifyProspectingTime').val(rawEl.dataset.prospectingTime || '');
      $('#modifyProspectorSkill').val('5');
      $('#modifyProspectorVehicle').val('SX-65 Groundhog');
  
      // Render the resource grid for the modify modal.
      renderModifyResourceGrid();
  
      selectedModifyResource = rawEl.dataset.resourceType;
      selectedModifyResourceCode = getResourceCode(selectedModifyResource);
  
      // After rendering, highlight the currently selected resource.
      $('.modify-resource-option').css('opacity', '0.4').each(function() {
        if ($(this).data('resource-name') === selectedModifyResource) {
          $(this).css('opacity', '1');
        }
      });
  
      var lastUpdatedVal = rawEl.dataset.lastUpdated || '';
      $('#lastUpdatedValue').text(lastUpdatedVal);
      $('#depositChangelogLink').attr('href', '?action=grid_changelog&deposit_id=' + depositId);
  
      $.post(tcProspectingData.ajaxurl, {
        action: 'fetch_grid_changelog_summary',
        target_id: depositId,
        security: tcProspectingData.nonce
      }).done(function(res) {
        var list = $('#changelogList').empty();
        if (res.success && res.data.changelog.length) {
          res.data.changelog.forEach(function(entry) {
            list.append('<li>' + entry.user + ' [' + entry.event_type + '] ' + entry.event + ' (' + entry.datetime + ')</li>');
          });
        }
      }).fail(function() {
        console.error('Could not retrieve the changelog.');
      });
      $('#modifyDepositModal').show();
    }
  
    function closeModifyModal() {
      $('#modifyDepositModal').hide();
    }
  
    function selectModifyResource(resourceName, resourceCode, el) {
      selectedModifyResource = resourceName;
      selectedModifyResourceCode = resourceCode;
      $('.modify-resource-option').css('opacity', '0.4');
      $(el).css('opacity', '1');
      if (resourceName === 'No deposit') {
        $('#modifyResourceAmount').val('0');
      }
    }
  
    /** Event Handlers **/
    // Add deposit modal: Resource option selection.
    $(document).on('click', '.resource-option', function() {
      var resourceName = $(this).data('resource-name');
      if (!resourceName) {
        resourceName = $(this).find('p').text().trim();
      }
      var resourceCode = getResourceCode(resourceName);
      selectResource(resourceName, resourceCode, this);
    });
  
    // Add deposit modal: Save button.
    $(document).on('click', '#saveResourceBtn', function() {
      var amount = $('#resourceAmount').val() || 0;
      var prospector = $('#prospectorName').val() || 'Unknown';
      var prospectingTime = $('#prospectingTime').val() || '';
      var skill = $('#prospectorSkill').val() || 5;
      var vehicle = $('#prospectorVehicle').val() || 'SX-65 Groundhog';
  
      if (!selectedResource) {
        alert('Please select a deposit type (or No Deposit).');
        return;
      }
  
      $.post(tcProspectingData.ajaxurl, {
        action: 'save_resource',
        security: tcProspectingData.nonce,
        planet_id: currentPlanetId,
        x: currentX,
        y: currentY,
        resource: selectedResource,
        amount: amount,
        prospector: prospector,
        prospecting_time: prospectingTime,
        prospector_skill: skill,
        prospector_vehicle: vehicle
      }).done(function(resp) {
        if (!resp.success) {
          alert('Failed to save deposit: ' + (resp.data ? resp.data.message : 'Unknown error'));
        } else {
          refreshGrid();
        }
      }).fail(function() {
        alert('Error saving deposit via AJAX');
      });
      closeResourceModal();
    });
  
    $(document).on('click', '#cancelResourceBtn', function() {
      closeResourceModal();
    });
  
    // Modify deposit modal: Save (update) button.
    $(document).on('click', '#modifyResourceBtn', function() {
      var depositId = $('#modifyDepositId').val();
      var amount = $('#modifyResourceAmount').val() || 0;
      var prospector = $('#modifyProspectorName').val() || 'Unknown';
      var prospectingTime = $('#modifyProspectingTime').val() || '';
      var prospectorSkill = $('#modifyProspectorSkill').val() || 5;
      var prospectorVehicle = $('#modifyProspectorVehicle').val() || 'SX-65 Groundhog';
  
      $.post(tcProspectingData.ajaxurl, {
        action: 'update_deposit',
        deposit_id: depositId,
        resource: selectedModifyResource,
        amount: amount,
        prospector: prospector,
        prospecting_time: prospectingTime,
        prospector_skill: prospectorSkill,
        prospector_vehicle: prospectorVehicle,
        security: tcProspectingData.nonce
      }).done(function(res) {
        if (res.success) {
          refreshGrid();
        } else {
          alert('Failed to update deposit: ' + (res.data ? res.data.message : 'Unknown error'));
        }
      }).fail(function() {
        alert('Error updating deposit via AJAX');
      });
      closeModifyModal();
    });
  
    // Modify deposit modal: Cancel button.
    $(document).on('click', '#cancelModifyBtn', function() {
      closeModifyModal();
    });
  
    // Modify deposit modal: Delete button.
    $(document).on('click', '#deleteResourceBtn', function() {
      var depositId = $('#modifyDepositId').val();
      if (!depositId) {
        alert('No Deposit ID found.');
        return;
      }
      if (!confirm('Are you sure you want to delete this deposit? This action cannot be undone.')) {
        return;
      }
      $.post(tcProspectingData.ajaxurl, {
        action: 'delete_deposit',
        deposit_id: depositId,
        security: tcProspectingData.nonce
      }).done(function(r) {
        if (r.success) {
          refreshGrid();
        } else {
          alert('Failed to delete deposit: ' + (r.data ? r.data.message : 'Unknown error'));
        }
      }).fail(function() {
        alert('AJAX error deleting deposit');
      });
      closeModifyModal();
    });
  
    /** Tab Switching & Planet Changelog **/
    window.openTab = function(tabId) {
        console.log(currentPlanetId);
      $('#planetDetailsTab').hide();
      $('#planetChangelogTab').hide();
      $('#' + tabId).show();
      if (tabId === 'planetChangelogTab') {
        $.post(tcProspectingData.ajaxurl, {
          action: 'fetch_planet_changelog',
          planet_id: currentPlanetId,
          security: tcProspectingData.nonce
        }).done(function(res) {
          if (!res.success) {
            alert('Error retrieving changelog: ' + (res.data ? res.data.message : 'Unknown error'));
            return;
          }
          var list = $('#planetChangelogList').empty();
          var logs = res.data.changelog || [];
          if (!logs.length) {
            list.append('<li>No changelog entries found.</li>');
          } else {
            logs.forEach(function(entry) {
              list.append('[' + entry.datetime + '] ' + entry.user + ' - ' +
                          entry.event_type + ': ' + entry.event + '<br/>');
            });
          }
        }).fail(function() {
          alert('Failed to fetch planet changelog');
        });
      }
    };
  
    /** XML Upload Modal & Functions **/
    window.openXmlUploadModal = function() {
      $('#xmlUploadModal').show();
    };
  
    window.closeXmlUploadModal = function() {
      $('#xmlUploadModal').hide();
    };
  
    function uploadXml() {
      var fd = new FormData(document.getElementById('xmlUploadForm'));
      console.log('uploadxml_called');
      $('#loadingSpinner').show();
      fd.append('action', 'process_xml_upload');
      $.ajax({
        url: tcProspectingData.ajaxurl,
        data: fd,
        type: 'POST',
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
          $('#loadingSpinner').hide();
          var statusDiv = $('#xmlUploadStatus').empty();
          if (resp.success) {
            statusDiv.html('<div class="notice notice-success"><p>' + resp.data.message + '</p></div>');
            refreshGrid(); 
          } else {
            statusDiv.html('<div class="notice notice-error"><p>' + resp.data.message + '</p></div>');
          }
        },
        error: function() {
          $('#loadingSpinner').hide();
          $('#xmlUploadStatus').html('<div class="notice notice-error"><p>Error processing XML file</p></div>');
        }
      });
    }
  
    $(document).on('click', '#processXmlBtn', function() {
      uploadXml();
    });
  
    $(document).on('click', '#cancelXmlBtn', function() {
      closeXmlUploadModal();
    });
  
    /** Utility: Resource Code Lookup **/
    function getResourceCode(name) {
      var map = {
        'Quantum': 1,
        'Meleenium': 2,
        'Ardanium': 3,
        'Rudic': 4,
        'Ryll': 5,
        'Duracrete': 6,
        'Alazhi': 7,
        'Laboi': 8,
        'Adegan': 9,
        'Rockivory': 10,
        'Tibannagas': 11,
        'Nova': 12,
        'Varium': 13,
        'Varmigio': 14,
        'Lommite': 15,
        'Hibridium': 16,
        'Durelium': 17,
        'Lowickan': 18,
        'Vertex': 19,
        'Berubian': 20,
        'No deposit': 9999
      };
      return map[name] || null;
    }
    window.getResourceCode = getResourceCode;
  
    /** Expose Functions Globally **/
    window.openResourceModal = openResourceModal;
    window.closeResourceModal = closeResourceModal;
    window.selectResource = selectResource;
    window.openModifyModal = openModifyModal;
    window.closeModifyModal = closeModifyModal;
    window.selectModifyResource = selectModifyResource;
    window.openXmlUploadModal = openXmlUploadModal;
    window.closeXmlUploadModal = closeXmlUploadModal;
   
  
  })(jQuery);
  
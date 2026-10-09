/**
 * WPCalibrate Vehicle Fitment & Parts Finder - Admin Metabox Scripts
 */

(function($) {
	'use strict';

	var config = window.wpcalibrateVfAdmin || {};
	var restUrl = config.restUrl || '/wp-json/wpcalibrate-vehicle-fitment/v1';

	$(document).ready(function() {
		initProductMetabox();
	});

	function initProductMetabox() {
		var $typeSelect   = $('#wpcalibrate-vf-assign-type');
		var $singleField  = $('#wpcalibrate-vf-single-field');
		var $rangeFields  = $('#wpcalibrate-vf-range-fields');
		var $searchInput  = $('#wpcalibrate-vf-meta-search');
		var $resultsDrop  = $('#wpcalibrate-vf-meta-search-results');
		var $selectedId   = $('#wpcalibrate-vf-meta-selected-id');
		var $selectedLbl  = $('#wpcalibrate-vf-meta-selected-label');
		var $notesInput   = $('#wpcalibrate-vf-meta-notes');
		var $assignBtn    = $('#wpcalibrate-vf-btn-add-meta-fitment');
		var $tableBody    = $('#wpcalibrate-vf-meta-table-body');

		// Toggle assignment type
		$typeSelect.on('change', function() {
			if ($(this).val() === 'range') {
				$singleField.hide();
				$rangeFields.show();
				loadRangeMakes();
			} else {
				$rangeFields.hide();
				$singleField.show();
			}
		});

		// Autocomplete Search
		var debounce;
		$searchInput.on('input', function() {
			clearTimeout(debounce);
			var term = $(this).val().trim();
			if (term.length < 2) {
				$resultsDrop.hide();
				return;
			}

			debounce = setTimeout(function() {
				$.ajax({
					url: restUrl + '/admin/vehicles',
					data: { search: term, limit: 15 },
					headers: { 'X-WP-Nonce': config.nonce },
					success: function(res) {
						if (!res.items || res.items.length === 0) {
							$resultsDrop.html('<div style="padding:8px;color:#666;">No vehicles found</div>').show();
							return;
						}
						var html = '';
						res.items.forEach(function(v) {
							html += '<div style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #eee;" data-id="' + v.id + '" data-name="' + v.display_name + '">' + v.display_name + '</div>';
						});
						$resultsDrop.html(html).show();
					}
				});
			}, 300);
		});

		$resultsDrop.on('click', 'div', function() {
			var id = $(this).data('id');
			var name = $(this).data('name');
			$selectedId.val(id);
			$selectedLbl.text('Selected: ' + name);
			$searchInput.val(name);
			$resultsDrop.hide();
		});

		// Range makes & models
		function loadRangeMakes() {
			var $makeSel = $('#wpcalibrate-vf-range-make');
			if ($makeSel.children('option').length > 1) return;

			$makeSel.html('<option value="">Loading makes...</option>');
			$.ajax({
				url: restUrl + '/makes',
				success: function(makes) {
					var mHtml = '<option value="">Select Make</option>';
					if (makes && makes.length > 0) {
						makes.forEach(function(m) {
							mHtml += '<option value="' + m.id + '">' + m.name + '</option>';
						});
					}
					$makeSel.html(mHtml);
				},
				error: function() {
					$makeSel.html('<option value="">Select Make</option>');
				}
			});
		}

		function loadRangeModels() {
			var makeId = $('#wpcalibrate-vf-range-make').val();
			var $modelSel = $('#wpcalibrate-vf-range-model');
			$modelSel.html('<option value="">Select Model</option>').prop('disabled', true);
			if (!makeId) return;

			var startYr = parseInt($('#wpcalibrate-vf-range-start').val(), 10);
			var url = restUrl + '/models?make_id=' + makeId;
			if (startYr && startYr >= 1900) {
				url += '&year=' + startYr;
			}

			$modelSel.html('<option value="">Loading models...</option>');

			$.ajax({
				url: url,
				success: function(models) {
					var moHtml = '<option value="">Select Model</option>';
					if (models && models.length > 0) {
						models.forEach(function(mo) {
							moHtml += '<option value="' + mo.id + '">' + mo.name + '</option>';
						});
						$modelSel.html(moHtml).prop('disabled', false);
					} else {
						// Fallback: fetch all models for make without year filter
						$.ajax({
							url: restUrl + '/models?make_id=' + makeId,
							success: function(allModels) {
								var allHtml = '<option value="">Select Model</option>';
								if (allModels && allModels.length > 0) {
									allModels.forEach(function(mo) {
										allHtml += '<option value="' + mo.id + '">' + mo.name + '</option>';
									});
									$modelSel.html(allHtml).prop('disabled', false);
								} else {
									$modelSel.html('<option value="">No models found</option>').prop('disabled', true);
								}
							}
						});
					}
				},
				error: function() {
					$.ajax({
						url: restUrl + '/models?make_id=' + makeId,
						success: function(allModels) {
							var allHtml = '<option value="">Select Model</option>';
							if (allModels && allModels.length > 0) {
								allModels.forEach(function(mo) {
									allHtml += '<option value="' + mo.id + '">' + mo.name + '</option>';
								});
								$modelSel.html(allHtml).prop('disabled', false);
							} else {
								$modelSel.html('<option value="">Select Model</option>').prop('disabled', true);
							}
						}
					});
				}
			});
		}

		$('#wpcalibrate-vf-range-make').on('change', loadRangeModels);

		// Assign Fitment Click
		$assignBtn.on('click', function() {
			var productId = $(this).data('product-id');
			var isRange = ($typeSelect.val() === 'range');
			var payload = {
				product_id: productId,
				notes: $notesInput.val().trim(),
				is_range: isRange
			};

			if (isRange) {
				payload.start_year = parseInt($('#wpcalibrate-vf-range-start').val(), 10);
				payload.end_year = parseInt($('#wpcalibrate-vf-range-end').val(), 10);
				payload.make_id = parseInt($('#wpcalibrate-vf-range-make').val(), 10);
				payload.model_id = parseInt($('#wpcalibrate-vf-range-model').val(), 10);

				if (!payload.make_id || !payload.model_id) {
					alert('Please select both Make and Model for the year range.');
					return;
				}
			} else {
				payload.vehicle_id = parseInt($selectedId.val(), 10);
				if (!payload.vehicle_id) {
					alert(config.i18n.selectVehicle);
					return;
				}
			}

			$assignBtn.prop('disabled', true).text('Assigning...');

			$.ajax({
				url: restUrl + '/admin/fitments/assign',
				method: 'POST',
				data: payload,
				headers: { 'X-WP-Nonce': config.nonce },
				success: function(res) {
					window.location.reload();
				},
				error: function(xhr) {
					alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error assigning fitment.');
					$assignBtn.prop('disabled', false).text('Assign Fitment');
				}
			});
		});

		// Remove Fitment Click
		$tableBody.on('click', '.wpcalibrate-vf-btn-delete-fitment', function() {
			if (!confirm(config.i18n.confirmRemove)) return;

			var fitmentId = $(this).data('fitment-id');
			var $row = $(this).closest('tr');

			$.ajax({
				url: restUrl + '/admin/fitments/remove',
				method: 'POST',
				data: { fitment_id: fitmentId },
				headers: { 'X-WP-Nonce': config.nonce },
				success: function() {
					$row.fadeOut(300, function() {
						$(this).remove();
						if ($tableBody.children('tr').length === 0) {
							$tableBody.html('<tr class="wpcalibrate-vf-empty-row"><td colspan="7">No vehicles assigned.</td></tr>');
						}
					});
				}
			});
		});
	}
})(jQuery);

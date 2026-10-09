/**
 * WPCalibrate Vehicle Fitment & Parts Finder - Frontend Logic
 */

(function() {
	'use strict';

	var config = window.wpcalibrateVfData || {};
	var restUrl = config.restUrl || '/wp-json/wpcalibrate-vehicle-fitment/v1';

	document.addEventListener('DOMContentLoaded', function() {
		initFinderForms();
		initGaragePanel();
		initProductTabPagination();
	});

	function initFinderForms() {
		var finders = document.querySelectorAll('.wpcalibrate-vf-finder-wrap');
		finders.forEach(function(wrap) {
			var yearSelect   = wrap.querySelector('.wpcalibrate-vf-select-year');
			var makeSelect   = wrap.querySelector('.wpcalibrate-vf-select-make');
			var modelSelect  = wrap.querySelector('.wpcalibrate-vf-select-model');
			var trimSelect   = wrap.querySelector('.wpcalibrate-vf-select-trim');
			var engineSelect = wrap.querySelector('.wpcalibrate-vf-select-engine');
			var vehicleInput = wrap.querySelector('.wpcalibrate-vf-input-vehicle-id');
			var submitBtn    = wrap.querySelector('.wpcalibrate-vf-submit-btn');
			var resetBtn     = wrap.querySelector('.wpcalibrate-vf-reset-btn');
			var spinner      = wrap.querySelector('.wpcalibrate-vf-spinner');
			var msgBox       = wrap.querySelector('.wpcalibrate-vf-finder-msg');

			if (!yearSelect || !makeSelect || !modelSelect) return;

			// Year Changed -> Fetch Makes
			yearSelect.addEventListener('change', function() {
				var year = yearSelect.value;
				resetSelect(makeSelect, config.i18n.selectMake);
				resetSelect(modelSelect, config.i18n.selectModel);
				if (trimSelect) resetSelect(trimSelect, config.i18n.selectTrim);
				if (engineSelect) resetSelect(engineSelect, config.i18n.selectEngine);
				vehicleInput.value = '';
				submitBtn.disabled = true;

				if (!year) return;

				setLoading(true);
				fetch(restUrl + '/makes?year=' + encodeURIComponent(year))
					.then(function(r) { return r.json(); })
					.then(function(makes) {
						populateSelect(makeSelect, makes, config.i18n.selectMake);
						makeSelect.disabled = false;
					})
					.catch(function() { showMsg(config.i18n.error, 'error'); })
					.finally(function() { setLoading(false); });
			});

			// Make Changed -> Fetch Models
			makeSelect.addEventListener('change', function() {
				var year = yearSelect.value;
				var makeId = makeSelect.value;
				resetSelect(modelSelect, config.i18n.selectModel);
				if (trimSelect) resetSelect(trimSelect, config.i18n.selectTrim);
				if (engineSelect) resetSelect(engineSelect, config.i18n.selectEngine);
				vehicleInput.value = '';
				submitBtn.disabled = true;

				if (!makeId) return;

				setLoading(true);
				fetch(restUrl + '/models?year=' + encodeURIComponent(year) + '&make_id=' + encodeURIComponent(makeId))
					.then(function(r) { return r.json(); })
					.then(function(models) {
						populateSelect(modelSelect, models, config.i18n.selectModel);
						modelSelect.disabled = false;
					})
					.catch(function() { showMsg(config.i18n.error, 'error'); })
					.finally(function() { setLoading(false); });
			});

			// Model Changed -> Fetch Trims or Engines or Resolve
			modelSelect.addEventListener('change', function() {
				var year = yearSelect.value;
				var makeId = makeSelect.value;
				var modelId = modelSelect.value;
				if (trimSelect) resetSelect(trimSelect, config.i18n.selectTrim);
				if (engineSelect) resetSelect(engineSelect, config.i18n.selectEngine);
				vehicleInput.value = '';
				submitBtn.disabled = true;

				if (!modelId) return;

				if (trimSelect) {
					setLoading(true);
					fetch(restUrl + '/trims?year=' + encodeURIComponent(year) + '&make_id=' + encodeURIComponent(makeId) + '&model_id=' + encodeURIComponent(modelId))
						.then(function(r) { return r.json(); })
						.then(function(trims) {
							if (trims && trims.length > 0) {
								populateSelect(trimSelect, trims, config.i18n.selectTrim);
								trimSelect.disabled = false;
							} else {
								// No trims for this model, skip to engine or resolve
								checkEngineOrResolve();
							}
						})
						.catch(function() { showMsg(config.i18n.error, 'error'); })
						.finally(function() { setLoading(false); });
				} else {
					checkEngineOrResolve();
				}
			});

			// Trim Changed -> Fetch Engines or Resolve
			if (trimSelect) {
				trimSelect.addEventListener('change', function() {
					if (engineSelect) resetSelect(engineSelect, config.i18n.selectEngine);
					checkEngineOrResolve();
				});
			}

			// Engine Changed -> Resolve Vehicle
			if (engineSelect) {
				engineSelect.addEventListener('change', function() {
					resolveVehicle();
				});
			}

			function checkEngineOrResolve() {
				var year = yearSelect.value;
				var makeId = makeSelect.value;
				var modelId = modelSelect.value;
				var trimId = trimSelect ? (trimSelect.value || 0) : 0;

				if (engineSelect) {
					setLoading(true);
					fetch(restUrl + '/engines?year=' + encodeURIComponent(year) + '&make_id=' + encodeURIComponent(makeId) + '&model_id=' + encodeURIComponent(modelId) + '&trim_id=' + encodeURIComponent(trimId))
						.then(function(r) { return r.json(); })
						.then(function(engines) {
							if (engines && engines.length > 0) {
								populateSelect(engineSelect, engines, config.i18n.selectEngine);
								engineSelect.disabled = false;
							} else {
								resolveVehicle();
							}
						})
						.catch(function() { showMsg(config.i18n.error, 'error'); })
						.finally(function() { setLoading(false); });
				} else {
					resolveVehicle();
				}
			}

			function resolveVehicle() {
				var year = yearSelect.value;
				var makeId = makeSelect.value;
				var modelId = modelSelect.value;
				var trimId = trimSelect ? (trimSelect.value || 0) : 0;
				var engineId = engineSelect ? (engineSelect.value || 0) : 0;

				setLoading(true);
				fetch(restUrl + '/resolve-vehicle?year=' + encodeURIComponent(year) + '&make_id=' + encodeURIComponent(makeId) + '&model_id=' + encodeURIComponent(modelId) + '&trim_id=' + encodeURIComponent(trimId) + '&engine_id=' + encodeURIComponent(engineId))
					.then(function(r) { return r.json(); })
					.then(function(res) {
						if (res.success && res.vehicle) {
							vehicleInput.value = res.vehicle.id;
							submitBtn.disabled = false;
						} else {
							submitBtn.disabled = false; // still allow filtering by make/model
						}
					})
					.catch(function() { submitBtn.disabled = false; })
					.finally(function() { setLoading(false); });
			}

			// Reset Button
			if (resetBtn) {
				resetBtn.addEventListener('click', function() {
					yearSelect.value = '';
					resetSelect(makeSelect, config.i18n.selectMake);
					resetSelect(modelSelect, config.i18n.selectModel);
					if (trimSelect) resetSelect(trimSelect, config.i18n.selectTrim);
					if (engineSelect) resetSelect(engineSelect, config.i18n.selectEngine);
					vehicleInput.value = '';
					submitBtn.disabled = true;
					if (msgBox) msgBox.textContent = '';
				});
			}

			function setLoading(isLoading) {
				if (spinner) spinner.style.display = isLoading ? 'inline-block' : 'none';
			}

			function showMsg(text, type) {
				if (!msgBox) return;
				msgBox.textContent = text;
				msgBox.className = 'wpcalibrate-vf-finder-msg is-' + type;
			}
		});
	}

	function resetSelect(sel, placeholder) {
		sel.innerHTML = '<option value="">' + placeholder + '</option>';
		sel.disabled = true;
	}

	function populateSelect(sel, items, placeholder) {
		var html = '<option value="">' + placeholder + '</option>';
		if (Array.isArray(items)) {
			items.forEach(function(item) {
				html += '<option value="' + item.id + '">' + item.name + '</option>';
			});
		}
		sel.innerHTML = html;
	}

	function initGaragePanel() {
		var modal = document.getElementById('wpcalibrate-vf-garage-modal');
		if (!modal) return;

		// Triggers to open garage
		document.addEventListener('click', function(e) {
			if (e.target && e.target.closest('[data-action="open-garage"]')) {
				modal.style.display = 'flex';
			}
			if (e.target && e.target.closest('[data-action="close-garage"]')) {
				modal.style.display = 'none';
			}
		});

		// Set Active
		modal.addEventListener('click', function(e) {
			var btn = e.target.closest('[data-action="set-active"]');
			if (btn) {
				var id = btn.getAttribute('data-id');
				postGarage('/garage/active', { vehicle_id: id }, function() {
					window.location.reload();
				});
			}

			var renameBtn = e.target.closest('[data-action="rename"]');
			if (renameBtn) {
				var rId = renameBtn.getAttribute('data-id');
				var newLabel = prompt('Enter friendly name (e.g. Work Truck):');
				if (newLabel !== null) {
					postGarage('/garage/rename', { vehicle_id: rId, label: newLabel }, function() {
						window.location.reload();
					});
				}
			}

			var removeBtn = e.target.closest('[data-action="remove"]');
			if (removeBtn) {
				var rmId = removeBtn.getAttribute('data-id');
				if (confirm('Remove this vehicle from garage?')) {
					postGarage('/garage/remove', { vehicle_id: rmId }, function() {
						window.location.reload();
					});
				}
			}

			var clearBtn = e.target.closest('[data-action="clear-garage"]');
			if (clearBtn) {
				if (confirm('Clear all vehicles from garage?')) {
					postGarage('/garage/clear', {}, function() {
						window.location.reload();
					});
				}
			}
		});

		function postGarage(endpoint, data, cb) {
			fetch(restUrl + endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce || ''
				},
				body: JSON.stringify(data)
			})
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (cb) cb(res);
			});
		}
	}

	function initProductTabPagination() {
		var paginationWrap = document.querySelector('.wpcalibrate-vf-pagination');
		if (!paginationWrap) return;

		var loadMoreBtn = paginationWrap.querySelector('.wpcalibrate-vf-load-more-btn');
		var tableBody   = document.querySelector('.wpcalibrate-vf-fitment-rows');
		var tabWrap     = document.querySelector('.wpcalibrate-vf-tab-wrapper');

		if (!loadMoreBtn || !tableBody || !tabWrap) return;

		var productId = tabWrap.getAttribute('data-product-id');
		var currentPage = 1;

		loadMoreBtn.addEventListener('click', function() {
			currentPage++;
			loadMoreBtn.disabled = true;
			loadMoreBtn.textContent = 'Loading...';

			fetch(restUrl + '/vehicles?product_id=' + encodeURIComponent(productId) + '&page=' + currentPage + '&per_page=20')
				.then(function(r) { return r.json(); })
				.then(function(res) {
					if (res.items && res.items.length > 0) {
						res.items.forEach(function(item) {
							var v = item.vehicle || {};
							var tr = document.createElement('tr');
							tr.innerHTML = '<td><strong>' + (v.year || '') + '</strong></td>' +
								'<td>' + (v.make_name || '') + '</td>' +
								'<td>' + (v.model_name || '') + '</td>' +
								'<td>' + (v.trim_name || '—') + '</td>' +
								'<td>' + (v.engine_name || '—') + '</td>' +
								'<td>' + (item.notes || '—') + '</td>';
							tableBody.appendChild(tr);
						});

						if (currentPage >= res.total_pages) {
							paginationWrap.style.display = 'none';
						} else {
							loadMoreBtn.disabled = false;
							loadMoreBtn.textContent = 'Load More Vehicles...';
						}
					} else {
						paginationWrap.style.display = 'none';
					}
				});
		});
	}
})();

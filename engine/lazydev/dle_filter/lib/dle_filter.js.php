let dleFilter = {
	path: window.location.pathname,
	title: document.title,
	content: false,
	speedbar: false,
	reset: false,
};

$(function() {
	let setFilterParam = function() {
		let filterParam = decodeURIComponent(location.href).split('/f/');
		if (!filterParam[1]) {
			return;
		}
		if (filterParam[1].slice(-1) === '/') {
			filterParam[1] = filterParam[1].slice(0, -1);
		}

		filterParam = filterParam[1].split('/');
		let arrayParam = [];
		for (let i = 0; i < filterParam.length; i++){
			arrayParam[i] = filterParam[i].split('=');
			if (arrayParam[i][1]) {
				arrayParam[i][0] = arrayParam[i][0].replace(/\+/g, ' ');
				arrayParam[i][1] = arrayParam[i][1].replace(/\+/g, ' ');
			}
		}

		$('[data-dlefilter*=dle-filter] input[type="text"], [data-dlefilter*=dle-filter] textarea').each(function() {
			let nameElem = $(this).attr('name');
			if (nameElem !== undefined && nameElem.length > 0) {
				for (let i = 0; i < arrayParam.length; i++) {
					if (nameElem === arrayParam[i][0]) {
						if (nameElem.indexOf('r.') + 1) {
							let slider = $(this).data('ionRangeSlider');
							let sliderData = arrayParam[i][1].split(';');
							slider.update({
								from: sliderData[0],
								to: sliderData[1]
							});
						} else {
							$(this).val(arrayParam[i][1]);
						}
					}
				}
			}
		});

		$('[data-dlefilter*=dle-filter] select').each(function() {
			let nameElem = $(this).attr('name');
			if (nameElem !== undefined && nameElem.length > 0) {
				for (let i = 0; i < arrayParam.length; i++) {
					if (nameElem === arrayParam[i][0]) {
						let selectData = arrayParam[i][1].split(',');

						$(this).find('option').each(function(s, n) {
							if ($.inArray(n.value, selectData) >= 0) {
								$(this).attr('selected', true);
							}
						});
					}
				}
				let getTail = $('.tail-select');
				if (getTail.length > 0) {
					tail.select('[data-dlefilter*=dle-filter] select[name="' + nameElem + '"]').reload();
				}
			}
		});

		let getChosen = $('.chosen-results');
		if (getChosen.length > 0) {
			$('[data-dlefilter*=dle-filter] select').trigger('chosen:updated');
		}

		$('[data-dlefilter*=dle-filter] input[type="radio"], [data-dlefilter*=dle-filter] input[type="checkbox"]').each(function() {
			let nameElem = $(this).attr('name');
			if (nameElem !== undefined && nameElem.length > 0) {
				for (let i = 0; i < arrayParam.length; i++) {
					if (nameElem === arrayParam[i][0]) {
						let selectData = arrayParam[i][1].split(',');

						$(this).each(function(s, n) {
							if ($.inArray(n.value, selectData) >= 0) {
								$(this).attr('checked', 'checked');
							}
						});
					}
				}
			}
		});
	};

	let filterClear = function() {
		dleFilter.reset = true;

		let formFilter = $(this).closest('form');

		history.pushState(null, dleFilter.title, dleFilter.path);
		document.title = dleFilter.title;

		$('#dle-content').html(dleFilter.content);
		if ($('#dle-speedbar').length > 0) {
			$('#dle-speedbar').html(dleFilter.speedbar);
		}

		$(formFilter).find('input[type="text"]').each(function() {
			let nameElem = $(this).prop('name');
			if (nameElem.length > 0) {
				if (nameElem.indexOf('r.') + 1) {
					let slider = $(this).data('ionRangeSlider');
					slider.update({
						from: slider.options.min,
						to: slider.options.max
					});
				} else {
					$(this).val('');
				}
			}
		});

		$(formFilter).find('select').each(function() {
			let nameElem = $(this).prop('name');
			if (nameElem.length > 0) {
				$(this).children('option').each(function() {
					$(this).attr('selected', false);
				});
			}
		});

		$(formFilter).find('input[type="radio"], input[type="checkbox"]').each(function() {
			let nameElem = $(this).prop('name');
			if (nameElem.length > 0) {
				$(this).attr('checked', false);
			}
		});

		dleFilter.reset = false;
	}

	let filterWork = function(data) {
		data = $.parseJSON(data);		if ($('#dle-speedbar').length > 0) {
			$('#dle-speedbar').html($('#dle-speedbar', data.speedbar).html());
		}
		$('#dle-content').html(data.content);		$('#dle-content').lazyLoadXT();		document.title = data.title;
		history.pushState(null, data.title, data.url);	};

	let filterAjax = function(elem) {
		if (dleFilter.reset) {
			return;
		}

		let data = '', elemTag = elem.target.tagName.toUpperCase(), elemType = elem.target.type.toUpperCase();

		if (elemTag === 'TEXTAREA' || elemTag === 'INPUT' && (elemType === 'TEXT' || elemType === 'RESET')) {
			return;
		}

		data = elemTag !== 'FORM' ? $(this).closest('form').serialize() : $(this).serialize();

		$.ajax({
			beforeSend: function() {
				ShowLoading('');
			},
			url: dle_root + 'engine/lazydev/dle_filter/ajax.php',
			type: 'POST',
			data: {
				data: data,
				url: dleFilter.path,
				dle_hash: dle_login_hash,
			},
			success: function(output) {
				if (output.error) {
					DLEalert(output.text, dle_info);
				} else {
					filterWork(output);
				}
			},
			error: function(output) {
				DLEalert(output.responseText, dle_info);
			}
		}).always(function() {
			HideLoading();
		});
	};

	if ($('#dle-speedbar').length > 0) {
		dleFilter.speedbar = $('#dle-speedbar').html();
	}

	dleFilter.content = $('#dle-content').html();

	$('body').on('click', '[data-dlefilter=submit]', filterAjax);	$('body').on('change', '[data-dlefilter*=dle-filter]', filterAjax);	$('body').on('click', '[data-dlefilter=reset]', filterClear);

	$('[data-dlefilter*=dle-filter]').find('input[type="text"][name*="r."]').each(function() {
		let sliderVars = $(this).data('slider-config');
		if (sliderVars !== undefined && sliderVars.length > 0) {
			let sliderConfig = {};
			sliderVars = sliderVars.split(';');
			for (let i = 0; i < sliderVars.length; i++) {
				let attempt = sliderVars[i].split(':');
				attempt[0] = attempt[0].trim();
				switch (attempt[0]) {
					case 'Одиночный слайдер':
						sliderConfig.type = 'single';
						break;
					case 'Двойной слайдер':
						sliderConfig.type = 'double';
						break;
					case 'Минимальное значение':
						if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
							sliderConfig.min = attempt[1];
						}
						break;
					case 'Максимальное значение':
						if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
							sliderConfig.max = attempt[1];
						}
						break;
					case 'Начало слайдера':
						if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
							sliderConfig.from = attempt[1];
						}
						break;
					case 'Конец слайдера':
						if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
							sliderConfig.to = attempt[1];
						}
						break;
					case 'Шаг':
						if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
							sliderConfig.step = attempt[1];
						}
						break;
					case 'Шаблон':
						if (attempt[1] !== undefined && attempt[1] !== '') {
							sliderConfig.skin = attempt[1];
						}
						break;
					case 'Префикс':
						if (attempt[1] !== undefined && attempt[1] !== '') {
							sliderConfig.prefix = attempt[1];
						}
						break;
					case 'Постфикс':
						if (attempt[1] !== undefined && attempt[1] !== '') {
							sliderConfig.postfix = attempt[1];
						}
						break;
					case 'Сетка':
						sliderConfig.grid = true;
						break;
					case 'Красивые числа':
						sliderConfig.prettify_enabled = true;
						break;
					case 'Скрыть MinMax':
						sliderConfig.hide_min_max = true;
						break;
					case 'Скрыть FromTo':
						sliderConfig.hide_from_to = true;
						break;
				}
			}
			if (!sliderConfig.prettify_enabled) {
				sliderConfig.prettify_enabled = false;
			}
			$(this).ionRangeSlider(sliderConfig);
		}
	});

	Array.prototype.diff = function(a) {
		return this.filter(function(i) {return a.indexOf(i) < 0;});
	};

	let showAndHideFilter = function(e) {
		let elemTag = $(e).prop('tagName').toUpperCase();
		let elemType = $(e).prop('type').toUpperCase();

		let showNode = $(e).data('dlefilter-show');
		let objShow = {};
		showNode = showNode.split(';');
		$.each(showNode, function(index, value) {
			let tempObj = value.split(':');
			objShow[tempObj[0]] = tempObj[1].split(',');
		});

		let showList = {};
		let hideList = {};

		if (elemTag === 'SELECT') {
			$(e).find('option').each(function(p, elem) {
				$.each(objShow, function(index, value) {
					if ($(elem).prop('selected') && $.inArray($(elem).val(), value) >= 0) {
						showList[index] = '[data-dlefilter-hide="' + index + '"]';
					} else {
						hideList[index] = '[data-dlefilter-hide="' + index + '"]';
					}
				});
			});
		} else if (elemTag === 'INPUT') {
			$.each(objShow, function(index, value) {
				if (elemType === 'TEXT') {
					if ($.inArray($(e).val(), value) >= 0) {
						showList[index] = '[data-dlefilter-hide="' + index + '"]';
					} else {
						hideList[index] = '[data-dlefilter-hide="' + index + '"]';
					}
				} else if (elemType === 'RADIO' || elemType === 'CHECKBOX') {
					if ($(e).prop('checked') && $.inArray($(e).val(), value) >= 0) {
						showList[index] = '[data-dlefilter-hide="' + index + '"]';
					} else {
						hideList[index] = '[data-dlefilter-hide="' + index + '"]';
					}
				}
			});
		} else if (elemTag === 'TEXTAREA') {
			$.each(objShow, function(index, value) {
				if ($.inArray($(e).val(), value) >= 0) {
					showList[index] = '[data-dlefilter-hide="' + index + '"]';
				} else {
					hideList[index] = '[data-dlefilter-hide="' + index + '"]';
				}
			});
		}

		let whatHide = Object.values(hideList).diff(Object.values(showList));
		if (whatHide) {
			$(whatHide.join(', ')).hide();
		}
		$(Object.values(showList).join(', ')).show();
	}

	if (dleFilter.path.indexOf('/f/') + 1) {
		setFilterParam();
	}

	$('[data-dlefilter-hide]').hide();

	$('body').find('[data-dlefilter-show]').each(function(p, e) {
		showAndHideFilter(e);
	});

	$('body').on('change', '[data-dlefilter-show]', function(e) {
		showAndHideFilter(this);
	});
});
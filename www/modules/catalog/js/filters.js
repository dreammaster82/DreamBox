(function($){
	$.filters = function(obj, options){
		if(!obj){
			return false;
		}
		this.filterBox = obj;
		
		var o = this;
		this.filterBox.on('setSort.filters', function(e, param){
			o.setSort(param);
		});
		this.options = options;
		var id = parseInt($("[name='catId']", this.filterBox)[0].value);
		if(isNaN(id)) this.catalogId = 0
			else this.catalogId = id;
		
		var timeOutId = 0;
		if(this.options.slider){
			this.sliderOptions = {
				priceFrom: new $(),
				priceTo: new $(),
				change: {min: 0, max: 0}
			};
		}
		this.href = "";
		this.showHideWaiter = function(){
			if(this.options.waiter){
				if(timeOutId){
					clearTimeout(timeOutId);
					timeOutId = 0;
				}
				if(this.waiter.hasClass('hide') || (arguments.length && arguments[0] == true)){
					var o = this;
					this.waiter.animate({'width': this.innerWidth}, 200, function(){o.waiter.removeClass('hide');});
					if(timeOutId){
						clearTimeout(timeOutId);
						timeOutId = 0;
					}
					timeOutId = setTimeout(function(){
						this.showHideWaiter();
					}, this.options.showTimeOut);
				} else {
					this.waiter.animate({'width': this.waiterWidth}, 200, function(){o.waiter.addClass('hide');});
				}
			}
		}
	
		this.setWaiterPosition = function(){
			if(this.coordObj.length){
				this.waiter.animate({'top': this.coordObj.position().top + this.coordObj.height() / 2 - this.waiter.height() / 2}, 500);
				this.addWaiter.animate({'top': this.coordObj.position().top + this.coordObj.height() / 2 - this.waiter.height() / 2 + 1}, 500);
				if(timeOutId){
					clearTimeout(timeOutId);
					timeOutId = 0;
				}
				timeOutId = setTimeout(function(){
					this.showHideWaiter();
				}, this.options.showTimeOut);
				this.showHideWaiter(true);
			}
		}
		
		this.filtersData = {
			blocks: {}
		};
	}
	
	$.filters.prototype = {
		options: {},
		catalogId: 0,
		filterBox: new $(),
		selectedFiltersBlock: new $(),
		slider: new $(),
		coordObj: new $(),
		init: function(){
			if(!this.catalogId){
				return;
			}
			this.loadFilters();
		},
		loadCatalogAjax: function(href, content){
			if(typeof loadCatalogAjax == "function"){
				return loadCatalogAjax(href, content, function(){
					initListProducts();
					buildViews();
				}, true);
			}
			return false;
		},
		loadFilters: function(){
			$.ajax({
				url: '/modules/catalog/service/get_filters_sql.php',
				data: this.filterBox.serialize() + '&catId=' + this.catalogId,
				context: this,
				success: function (a){
					this.href = a.href;
					this.filterBox.html(a.data);
					if(this.options.waiter){
						this.waiter = $('<div id="waiter"></div>');
						this.waiter.addClass('hide').append(function(){
							var box = $('<div class="contener"></div>'),
							span = $('<span></span>'),
							link = $('<a href="#">Показать</a>');
							link.on('click.filters', function(){
								/*-----for ajax -----*/
								if(this.loadCatalogAjax(a.href, this.options.catalogBlock)){
									window.location.href = a.href;
								}
								return false;
							});
							span.append(link).append($('<b>' + a.pcnt + '</b>'));
							box.append(span);
							return $('<div class="box"></div>').append(box);
						});
						this.addWaiter = $('<div class="add_waiter"></div>');
						this.filterBox.append(this.waiter).append(this.addWaiter);
						this.waiterWidth = this.waiter.width();
						this.innerWidth = $('.contener', this.waiter).innerWidth() - 5;
						this.waiter.on('click.filters', function(){
							this.showHideWaiter();
						});
					}
					this.filterBox.css({
						'position': 'relative',
						'z-index': 0
					});
					var o = this;
					this.selectedFiltersBlock = $('#selected_filter .list_selected');
					if(a.selected_filters.length){
						this.selectedFiltersBlock.html(a.selected_filters.join('; '));
						$('#selected_filter').slideDown(function(){
							if(o.options.waiter){
								o.setWaiterPosition(a);
							}
						});
					} else {
						this.selectedFiltersBlock.empty();
						$('#selected_filter').slideUp(function(){
							if(o.options.waiter){
								o.setWaiterPosition(a);
							}
						});
					}
					this.startSelects();
					if(a.errors){
						console.log(a.errors);
					}
				}
			});
		},
		startSelects: function(){
			var o = this;
			if($('.item', this.filterBox).length){
				$('.item', this.filterBox).each(function(){
					var input = $('input', this);
					$('a', this).on('click.filters', function(){
						if(!input.is(":disabled")){
							if(input.is(':checked')){
								input.prop('checked', false);
							} else {
								input.prop('checked', true);
							}
							input.trigger('change.filters');
						}
						return false;
					});
					input.on('change.filters', function(){
						var fb = $('.filtr_bot', o.filterBox);
						if(fb.length && fb.is(':hidden')){
							fb.show();
						}
						o.coordObj = $(this).parent('li');
						o.filterChange();
					});
				});
				if($('#one_fil_slider').length){
					if(this.options.slider){
						if($.isFunction($().slider)){
							this.slider = $('#price_slider_contener');
							var sliderBox = this.slider.parents('.filter_box');
							if(this.slider.length){
								this.sliderOptions.priceFrom = $('input[name="price_from"]', sliderBox),
								this.sliderOptions.priceTo = $('input[name="price_to"]', sliderBox);
								var leftValue  = parseInt(this.sliderOptions.priceFrom.val()),
								rightValue = parseInt(this.sliderOptions.priceTo.val()),
								priceMin = parseInt($('input[name="min_price"]', sliderBox).val()),
								priceMax = parseInt($('input[name="max_price"]', sliderBox).val()),
								differ = priceMax - priceMin,
								stepDetailChange = Math.round(differ / 1000),
								stepChange = 1;
								if(differ > 1000){
									stepChange = 10;
								} else if(differ > 5000){
									stepChange = 50;
								} else if(differ > 10000){
									stepChange = 100;
								} else if(differ > 20000){
									stepChange = 250;
								} else if(differ > 35000){
									stepChange = 500;
								} else if(differ > 50000){
									stepChange = 1000;
								}
								this.slider.slider({
									range: true,
									step: stepDetailChange,
									orientation: 'horizontal',
									animate: false,
									min: priceMin,
									max: priceMax,
									values: [leftValue, rightValue],
									stop: function(event, ui){
										if(leftValue != ui.values[0]){
											leftValue = Math.round(ui.values[0] / stepChange) * stepChange;
										}
										if(rightValue != ui.values[1]){
											rightValue = Math.round(ui.values[1] / stepChange) * stepChange;
										}
										if(leftValue < priceMin || leftValue >= rightValue){
											leftValue = priceMin;
											$(this).slider('values', 0, leftValue);
										}
										if(rightValue > priceMax || rightValue <= leftValue){
											rightValue = priceMax;
											$(this).slider('values', 1, rightValue);
										}
										o.sliderOptions.priceFrom.val(leftValue);
										o.sliderOptions.priceTo.val(rightValue);
										o.coordObj = o.slider.parent();
										o.filterChange();
									},
									slide: function (event, ui){
										if(leftValue != ui.values[0]){
											leftValue = Math.round(ui.values[0] / stepChange) * stepChange;
											o.sliderOptions.change.min = 1;
										}
										if(rightValue != ui.values[1]){
											rightValue = Math.round(ui.values[1] / stepChange) * stepChange;
											o.sliderOptions.change.max = 1;
										}
									}
								});
								this.sliderOptions.priceFrom.on('change', function(){
									if(leftValue != parseInt($(this).val())){
										leftValue = parseInt($(this).val());
										if(leftValue < priceMin || leftValue >= rightValue){
											leftValue = priceMin;
											$(this).val(priceMin);
										}
										o.slider.slider('values', 0, leftValue);
										o.coordObj = o.slider.parent();
										o.sliderOptions.change.min = 1;
										o.filterChange();
									}
								});
								this.sliderOptions.priceTo.on('change', function(){
									if(rightValue != parseInt($(this).val())){
										rightValue = parseInt($(this).val());
										if(rightValue > priceMax || rightValue <= leftValue){
											rightValue = priceMax;
											$(this).val(priceMax);
										}
										o.slider.slider('values', 1, rightValue);
										o.coordObj = o.slider.parent();
										o.sliderOptions.change.max = 1;
										o.filterChange();
									}
								});
							}
						} else {
							$('#one_fil_slider .filter_box').html('Ошибка загрузки слайдера!');
						}
					} else {
						$('#one_fil_slider').remove();
					}
				}
			}
			if($('.filtr_bot button', this.filterBox).length){
				$('.filtr_bot button', this.filterBox).on('click.filters', function(){
					/*-----for ajax -----*/
					if(!o.loadCatalogAjax(o.href, o.options.catalogBlock)){
						window.location.href = o.href;
					}
					return false;
				});
			}
			if($('.clear_filtr', this.filterBox).length){
				$('.clear_filtr', this.filterBox).on('click', function(){
					$('.item', o.filterBox).each(function(){
						$('input', $(this)).prop('checked', false).prop('disabled', false);
					});
					if(o.slider.length){
						var par = o.slider.parents('.filter_box'),
						min = parseInt($('input[name="min_price"]', par).val()),
						max = parseInt($('input[name="max_price"]', par).val());
						$('input[name="price_from"]', par).val(min);
						$('input[name="price_to"]', par).val(max);
						o.slider.slider('values', 0, min);
						o.slider.slider('values', 1, max);
						o.sliderOptions.change.min = 0;
						o.sliderOptions.change.max = 0;
					}
					o.filterChange();
					return false;
				});
			}
			$('.one_filter', this.filterBox).each(function(){
				var id = parseInt($(this).attr('id').replace('one_fil_', ''));
				if(id){
					o.filtersData.blocks[id] = $(this);
				}
				var el = $(this);
				$('.title', el).on('click.filters', function(){
					$('.filter_box', el).slideToggle(function(){
						if($(this).is(':visible')){
							el.removeClass('closed').addClass('opened');
						} else {
							el.removeClass('opened').addClass('closed');
						}
						if(o.options.waiter){
							o.setWaiterPosition();
						}
					});
				})
			});
			$('.clear[data-index]', this.filterBox).on('click.filters', function(){
				o.filtersData.blocks[$(this).data('index')].find('.item').each(function(){
					$(this).removeClass('checked');
					$('input', $(this)).prop('checked', false);
					o.filterChange();
				});
				return false;
			});
			if(this.options.waiter){
				this.waiter.css({'top': 0});
				this.addWaiter.css({'top' : 1});
			}
		},
		filterChange: function(){
			var data = this.filterBox.serializeArray();
			data.push({name: "catId", value: this.catalogId});
			if(this.options.slider){
				data.push({name: "json", value: 1}, {name: "min", value: this.sliderOptions.change.min}, {name: "max", value: this.sliderOptions.change.max});
			}
			$.ajax({
				url: '/modules/catalog/service/get_filters_sql.php',
				data: data,
				context: this,
				success: function (a){
					this.href = a.href;
					this.loadCatalogAjax(this.href, this.options.catalogBlock);
					this.updateSelects(a);
					if(a.errors){
						console.log(a.errors);
					}
				}
			});
		},
		updateSelects: function(data){
			for(var i in this.filtersData.blocks){
				var b = false, a = this.filtersData.blocks[i].find('.clear');
				if(data.data[i]){
					this.filtersData.blocks[i].find('.item').each(function(){
						var y = $(this).data('item');
						if(data.data[i][y]){
							switch(data.data[i][y][0]){
								case 0:
									$(this).removeClass('checked').removeClass('disabled');
									$('input', $(this)).prop('checked', false).prop('disabled', false);
									break;
								case 1:
									$(this).removeClass('disabled').addClass('checked');
									$('input', $(this)).prop('checked', true).prop('disabled', false);
									b = true;
									break;
								case 2:
									$(this).removeClass('checked').addClass('disabled');
									$('input', $(this)).prop('checked', false).prop('disabled', true);
									break;
							}
							$('b', this).html(data.data[i][y][1]);
						}
					});
				}
				if(a.length){
					if(b){
						a.show();
					} else {
						a.hide();
					}
				}
			}
			/*for(var i in data.data){
				var li = $('#one_fil_'+ i).find('.item');
				for(var y in data.data[i]){
					var this_li = li.filter('[item="'+ y +'"]');
					switch(data.data[i][y][0]){
						case 0:
							this_li.removeClass('checked').removeClass('disabled');
							$('input', this_li).prop('checked', false).prop('disabled', false);
							break;
						case 1:
							this_li.removeClass('disabled').addClass('checked');
							$('input', this_li).prop('checked', true).prop('disabled', false);
							break;
						case 2:
							this_li.removeClass('checked').addClass('disabled');
							$('input', this_li).prop('checked', false).prop('disabled', true);
							break;
					}
				}
			}*/
			if(this.slider.length){
				if(!this.sliderOptions.change.min){
					var from = parseInt(data.prices[0]);
					this.slider.slider('values', 0, from);
					this.sliderOptions.priceFrom.val(from);
				}
				if(!this.sliderOptions.change.max){
					var to = parseInt(data.prices[1]);
					this.slider.slider('values', 1, to);
					this.sliderOptions.priceTo.val(to);
				}
			}
			if(this.options.waiter){
				$('b', this.waiter).html(data.pcnt);
			}
			var o = this;
			if(data.selected_filters.length){
				this.selectedFiltersBlock.html(data.selected_filters.join('; '));
				$('#selected_filter').slideDown(function(){
					if(o.options.waiter){
						o.setWaiterPosition(data);
					}
				});
			} else {
				this.selectedFiltersBlock.empty();
				$('#selected_filter').slideUp(function(){
					if(o.options.waiter){
						o.setWaiterPosition(data);
					}
				});
			}
		},
		setSort: function(sort){
			if(!!sort){
				$('input[name="sort"]', this.filterBox).val(sort);
			}
		}
	}
	
	$.fn.filters = function(options){
		var options = options || {};
		options.waiter = options.waiter || false;
		options.slider = options.slider || true;
		options.showTimeOut = options.showTimeOut || 5000;
		options.catalogBlock = options.catalogBlock || '#catalog_content';
		
		return $(this).each(function(){
			var f = new $.filters($(this), options);
			if(typeof options.method == "function"){
				f[options.method]();
			} else {
				f.init();
			}
		});
	}
})(jQuery);


$(document).ready(function(){
		var fileref=document.createElement("link")
		fileref.setAttribute("rel", "stylesheet")
		fileref.setAttribute("type", "text/css")
		fileref.setAttribute("href", "/modules/catalog/css/filters.css");
		if (!!fileref) document.getElementsByTagName("head")[0].appendChild(fileref);
		var fileref1=document.createElement("link")
		fileref1.setAttribute("rel", "stylesheet")
		fileref1.setAttribute("type", "text/css")
		fileref1.setAttribute("href", "/modules/catalog/css/ui.theme.slider.css");
		if (!!fileref1) document.getElementsByTagName("head")[0].appendChild(fileref1);
		$(window.filterBlock ? window.filterBlock : '#filters_block form').filters();
});


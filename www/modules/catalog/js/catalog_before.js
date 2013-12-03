function buildViews(){
	$('.sort_views').each(function(){
		$('.sort', this).on('click.init', function(){
			var href = window.location.pathname + $(this).attr('href').replace(window.location.pathname, '')
			$.ajax({
				url: "/modules/catalog/service/load_catalog.php",
				data: {href: href},
				context: this,
				beforeSend: function(){
					$('ul.product_items').append('<div class="black_fon" style="position:absolute;background-color:white"><img class="loader" src="/images/loader.gif" /></div>');
				},
				success: function(data){
					if(!!data.errors){
						console.log(data.errors);
					}
					if(!!data.ok){
						$('ul.product_items').empty().append(data.html);
						initListProducts();
						$(this).parent().find('.sort').removeClass('active');
						$(this).addClass('active');
						if(supports_history_api()){
							window.history.pushState({href: window.location.href}, !!data.title ? data.title : '', href);
						}
					}
					var match = /sort=([a-z]+)/.exec(this.search);
					if(match != null){
						$('#filter_form').trigger('setSort', [match[1]]);
					}
				},
				complete: function(){
					$('ul.product_items .black_fon').remove();
				}
			});
			return false;
		});
		var spans = $('.views span', this);
		spans.on('click.init', function(){
			spans.removeClass('active');
			$(this).addClass('active');
			if($(this).hasClass('list')){
				$('ul.product_items').addClass('list');
				setCookie("view", "list");
			} else {
				$('ul.product_items').removeClass('list');
				deleteCookie("view");
			}
		});
	});
}

function initListProducts(){
	$('.to_basket').on('click.init', function(){
		$(this).toBasket({image:$(this).parents('.box').find('.image img'), basket:$('#basket')}).off('.init').html('Куплено').removeClass('to_basket').addClass('in_basket disable').attr('href', '/catalog/basket/');
		return false;
	});
}

function loadCatalogAjax(href, content){
	if(loadingAjax){
		return false;
	}
	var params = Array.prototype.slice.call(arguments);
	var callback, showAll = 0;
	if(params.length){
		for(var i = 0; i < params.length; i++){
			if(typeof params[i] == 'function'){
				callback = params[i];
			} else if(typeof params[i] == 'boolean'){
				showAll = params[i] ? 1 : 0;
			}
		}
	}
	var contentObject = !!content ? $(content) : $();
	$.ajax({
		url: "/modules/catalog/service/load_catalog.php",
		data: {href: href, showAll: showAll},
		beforeSend: function(){
			loadingAjax = true;
			if(contentObject.length){
				contentObject.css('position', 'relative')
				.append('<div class="black_fon" style="position:absolute;background-color:white"><img class="loader" src="/images/loader.gif" /></div>');
			}
		},
		success: function(data){
			if(!!data.errors){
				console.log(data.errors);
			}
			if(!!data.ok){
				if(contentObject.length){
					contentObject.empty().append(data.html);
				}
				if(typeof callback == 'function'){
					callback();
				}
				if(supports_history_api()){
					window.history.pushState({href: window.location.href}, !!data.title ? data.title : '', href);
				}
			}
		},
		complete: function(){
			loadingAjax = false;
			if(contentObject.length){
				contentObject.css('position', '').find('.black_fon').remove();
			}
		}
	});
	if(supports_history_api()){
		return false;
	}
	return true;
}
var loadingAjax = false, isIndex = true, initAjax = false, timeOutId = 0;
if(!window.console)window.console={};if(!window.console.log)window.console.log=function(){}

window.onload = function(){
	if(supports_history_api()){
		window.setTimeout(function() {
			window.addEventListener("popstate", function(e) {
				var returnLocation = history.location || document.location;
				var obj = jQuery();
				obj = $('<a href="'+ returnLocation + '" target="' + (window.history.state != null ? window.history.state.note : '') + '"></a>');
				sendAjax(obj, true);
				obj.remove();
			}, false);
		}, 1); 
	}
}

$(document).ready(function(){
	initContent(document);
	quotesLinks();
	if(typeof $.fn.chosen == 'function'){
		$('select').chosen({disable_search_threshold: 10});
	}
	buildCallForm();
	buildCatalogMenu();
	$('.find .example u').on('click.init', function(){
		$(this).parent().parent().find('[name="find"]').val($(this).html()).trigger('focus');
	});
});

function buildCatalogMenu(){
	var arr = {
		length: 0
	};
	$('menu [class^="for_open_"]').each(function(){
		var id = parseInt($(this).attr("class").replace("for_open_", ""));
		if(id){
			arr["id[" + id + "]"] = id;
			arr.length += 1;
		}
	});
	if(arr.length){
		$.ajax({
			url: "/modules/catalog/service/show_catalog_menu.php",
			data: arr,
			success: function(data){
				if(!!data.ok){
					var menu = $("menu");
					for(var i in data.data){
						$(".for_open_" + i, menu).removeClass("for_open_" + i).append(data.data[i]);
					}
				}
			},
			complete: function(){
				$('.catalog_menu menu .absolute ul').each(function(){
					$(this).css('left', ($(this).parent().width() - $(this).innerWidth()) / 2);
				});
			}
		});
	}
}


function buildCallForm(){
	var callForm =
	{
		type: 'div',
		attributes: {
			id: 'call_form'
		},
		children: [
			{
				type: 'div',
				attributes: {
					'class': 'box'
				},
				children: [
					{
						type: 'div',
						attributes: {
							'class': 'title'
						},
						children: [
							{
								type: 'a',
								attributes: {
									href: '#'
								},
								innerHtml: 'Заказать звонок'
							}
						]
					},
					{
						type: 'form',
						attributes: {
							method: 'POST',
							action: '/service/send_form.php'
						},
						children: [
							{
								type: 'div',
								children: [
									{
										type: 'label',
										innerHtml: 'Имя'
									},
									{
										type: 'input',
										attributes: {
											type: 'text',
											name: 'name',
											value: '',
											required: ''
										}
									}
								]
							},
							{
								type: 'div',
								children: [
									{
										type: 'label',
										innerHtml: 'Телефон'
									},
									{
										type: 'input',
										attributes: {
											type: 'tel',
											name: 'tel',
											value: '',
											required: ''
										}
									}
								]
							},
							{
								type: 'div',
								children: [
									{
										type: 'label',
										innerHtml: 'Код защиты'
									},
									{
										type: 'input',
										attributes: {
											type: 'text',
											name: 'secret',
											value: '',
											required: ''
										}
									}
								]
							},
							{
								type: 'div',
								children: [
									{
										type: 'img',
										attributes: {
											'class': 'secr_img',
											src: window.secretImage
										}
									},
									{
										type: 'a',
										attributes: {
											href: '#',
											'class': 'rel_link'
										},
										innerHtml: 'Не видно'
									},
									{
										type: 'input',
										attributes: {
											type: 'hidden',
											name: 'secret_crypted',
											value: window.secretCrypted
										}
									}
								]
							},
							{
								type: 'div',
								attributes: {
									style: 'text-align: center;'
								},
								children: [
									{
										type: 'button',
										attributes: {
											type: 'submit'
										},
										children: [
											{
												type: 'span',
												innerHtml: 'Отправить'
											}
										]
									}
								]
							},
							{
								type: 'div',
								attributes: {
									'class': 'return'
								}
							}
						]
					}
				]
			}
		]
	};
	var call_form = new jQuery();
	$('#get_call').on('click.init', function(){
		if(!call_form.length){
			call_form = generateItems(callForm);
			call_form.appendTo('body');
			var cform = call_form.find('form');
			call_form.find('.title a').on('click.init', function(){
				return clickShowHide(this, call_form);;
			});
			var rel_link = call_form.find('.rel_link');
			rel_link.on('click', function(){
				return reloadImg(rel_link);
			});
			cform.submit(function(){
				return submitShowHide(call_form, cform);
			});
		}
		return clickShowHide(this, call_form);
	});
}



function clickShowHide(obj, block){
	if(block.is(':visible')){
		block.fadeToggle('1300', function(){
			block.find('.title').css('text-align', '').find('a').css({'margin-right':''});
		});
		$(obj).removeClass('active');
	} else {
		$(obj).addClass('active');
		var offset=$(obj).offset(),
		title = block.find('.title a');
		block.css('margin-left', -9999);
		block.show();
		var off = title.position(),
		off2 = title.parent().position();
		off.top += off2.top;
		off.left += off2.left;
		off.top = offset.top - (off.top + title.innerHeight() / 2 - $(obj).height() / 2) - 1;
		off.left = offset.left - (off.left + title.innerWidth() / 2 - $(obj).width() / 2) - 1;
		if(off.left + block.outerWidth() > $(document).width()){
			off.left -= off.left + block.outerWidth() - $(document).width();
			title.parent().css('text-align', 'right');
			title.css('margin-right', -(offset.left - off.left - title.position().left));
		} else if(off.left < 0){
			off.left += Math.abs(off.left);
			title.parent().css('text-align', 'left');
			title.css('margin-right', -(offset.left - off.left - title.position().left));
		}
		block.hide().css('margin-left', '');
		block.css({
			'top': off.top,
			'left': off.left
		});
		block.fadeToggle('1300');
	}
	return false;
}

function submitShowHide(block, form){
	$.ajax({
		url: form.attr('action'),
		type: form.attr('method'),
		data: form.serialize(),
		dataType: 'json',
		cache: false,
		success: function(data, textStatus, xhr) {
			if (!data.error){
				block.find('.return').html(data.message)
				block.fadeToggle('1300');
			} else if (data.error) {
				block.find('.return').html(data.message)
			} else {
				alert('Произошла неизвестная ошибка');
			}
		},
		error: function(xhr, textStatus, errorObj) {
			block.find('.return').html('Произошла непредвиденная ошибка! Сообщение отправлено не было.');
		}
	}); 
	return false;
}


/*---services functions---*/
(function(){
$.fn.effectTransfer = function(o){
	o.notRemove = o.notRemove || false;
	o.width = o.width || o.to.innerWidth();
	o.height = o.height || o.to.height();
        return this.queue(function(){
            var elem = $(this),
            target = o.to,
			bd = $('body').offset(),
			eoffset = elem.offset(),
			toffset = target.offset(),
			startPosition = bd.left ? {left: eoffset.left - bd.left, top: eoffset.top - bd.top} : eoffset,
            endPosition = bd.left ? {left: toffset.left - bd.left, top: toffset.top - bd.top} : toffset,
            animation = {
                top: endPosition.top,
                left: endPosition.left,
                height: o.height,
                width: o.width
            },
            speed = o.speed || 1,
            len = Math.ceil(Math.sqrt((endPosition.top-startPosition.top) * (endPosition.top-startPosition.top) + (endPosition.left-startPosition.left) * (endPosition.left-startPosition.left))),
            duration = len/speed,
			img = '';
			if(!!$(this).attr('src')){
				img = '<img src="' + $(this).attr('src') + '" title="" style="width:100%" />';
				animation.width = animation.width * animation.height / $(this).width();
			}
            var transfer = $('<div class="transferBorder">' + img + '</div>').appendTo(document.body).css({
                top: startPosition.top,
                left: startPosition.left,
                height: $(this).height(),
                width: $(this).width(),
                position: 'absolute',
                'z-index': 10
            });
			if(img != ''){
				transfer.addClass('nbg');
			}
			transfer.animate(animation, duration, function(){
                if(!o.notRemove){
					transfer.remove();
				}
                elem.dequeue();
            });
        });
    };
})(jQuery);

function supports_history_api() {
  return !!(window.history && history.pushState);
}

function setHeight(){
	var h = $('header').outerHeight(true) + $('footer').outerHeight(true);
	if(h + $('#content').outerHeight(true) < $(window).height()){
		$('#content').css('min-height', $(window).height() - h);
	}
}

function getMail(host, name, obj_a) { 
	obj_a.innerHTML = name+'@'+host;
	obj_a.href = 'mailto:'+name+'@'+host;
	return false;
}

function initAjaxLinks(obj){
	if(initAjax && supports_history_api()){
		$('[data-ajax]', obj).off('.ajax_link').on('click.ajax_link', function(){
			sendAjax($(this));
			return false;
		});
	}
}

function sendAjax(obj, type){return false;
	if(supports_history_api()){
		var blackFon = $('<div id="black_fon"></div>');
		$.ajax({
			'url': '/service/load_content.php',
			'data': {
				'type': obj.data('ajax'),
				'href': obj.attr('href').replace(window.location.protocol + '//' + window.location.hostname, '')
			},
			'beforeSend': function(){
				if(loadingAjax){
					return false;
				} else {
					loadingAjax = true;
					var loader = $('<div id="loader_img" style="text-align:center"><img src="/images/loader.gif" alt="" /></div>');
					$('section.content').css({'position':'relative','z-index':0}).append(blackFon);
					blackFon.css('position', 'absolute').append(loader);
					loader.css('padding-top', (blackFon.height() - loader.height()) / 2);
				}
			},
			'success': function (data, status, jsobj){
				if(data.warning.length){
					console.log(data.warning);
				}
				if(data.complete == 1){
					$(document).off('.init');
					$('section.content').html(data.html);
					if(isIndex){
						$('<a href="/"></a>').append($('header .logo img')).appendTo($('header .logo'));
						$('header .right .navigate li.home').empty().append($('<a href="/">Home</a>'));
						$('footer .logo1').empty().append($('<a href="/">© «Топаз»</a>'));
						isIndex = false;
					}
				} else {
					window.location.href = obj.attr('href');
				}
				if(type !== true){
					window.history.pushState({note: obj.attr('title')}, obj.attr('href'), obj.attr('href'));
				}
			},
			'complete': function(){
				loadingAjax = false;
				$('section.content').removeAttr('style');
				blackFon.remove();
			}
		});
		return true;
	} else {
		return false;
	}
}

function initContent(obj){
	initAjaxLinks(obj);
}

function setTitle(title, description, keywords){
	$('head title').html(title);
	$('head meta[itemprop="name"]').attr('content', title);
	$('head meta[itemprop="description"]').attr('content', description);
	$('head meta[name="description"]').attr('content', description);
	$('head meta[name="keywords"]').attr('content', keywords);
}

$.ajaxSetup({
    dataType: 'json',
	cache: false,
	type: 'post',
	error: function(xhr, textStatus, errorObj) {
		if(xhr.status == 0){
			console.log('You are offline!!\n Please Check Your Network.');
		} else if(xhr.status == 404){
			console.log('Requested URL not found.');
		} else if(xhr.status == 500){
			console.log('Internel Server Error.');
		} else if(textStatus == 'parsererror'){
			console.log('Error.\nParsing JSON Request failed.');
		} else if(textStatus == 'timeout'){
			console.log('Request Time out.');
		} else {
			console.log('Unknow Error.\n'+xhr.responseText);
		}
	}
});

function reloadImg(obj){
    $.ajax({
		'url': '/service/dimages/get_image_ajax.php',
		'success': function(data, textStatus, xhr) {
			if (data.key){
				$(obj).parent().find('img').attr('src', '/service/dimages/get_image_crypted.php?key='+ data.key);
				$(obj).parent().find('input[type="hidden"]').attr('value', data.key);
			} else {
				alert('Произошла неизвестная ошибка');
			}
		},
		'error': function(xhr, textStatus, errorObj) {
			console.log('Произошла непредвиденная ошибка! Запрос не обработан.');
		}
    });
    return false;
}

function getCookie(cookieName){
	var results = document.cookie.match ( '(^|;) ?' + cookieName + '=([^;]*)(;|$)' );
	if(results){
		return (unescape(results[2]));
	} else {
		return null;
	}
}

function quotesLinks(){
	$('.quotes').each(function(){
		if($(this).attr('title') != ''){
			$(this).attr('quote', $(this).attr('title')).removeAttr('title');
			$(this).hover(function(){
				$(this).css({
					'position': 'relative'
				});
				var q = $('<span class="quote_text"><i>' + $(this).attr('quote') + '</i><u></u></span>');
				
				q.css({
					position: 'absolute',
					top: -9999,
					left: 0
				});
				q.appendTo($(this)).show();
				var h = q.outerHeight(true), w = q.width();
				q.hide();
				q.css({
					top: -h
				}).fadeIn();
			}, function(){
				$('.quote_text', this).stop().remove();
			});
		}
	});
}

function isTouchDevice(){
  return !!('ontouchstart' in window) // works on most browsers 
      || !!('msPointerEnabled' in window.navigator); // works on ie10
}

function getCookie(name) {
	var matches = document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}
function setCookie(name, value, props) {
	props = props || {};
	var exp = props.expires;
	if (typeof exp == "number" && exp) {
		var d = new Date()
		d.setTime(d.getTime() + exp*1000);
		exp = props.expires = d;
	}
	if(exp && exp.toUTCString) { props.expires = exp.toUTCString() }
	if(!props.path){
		props.path = '/';
	}
	value = encodeURIComponent(value);
	var updatedCookie = name + "=" + value;
	for(var propName in props){
		updatedCookie += "; " + propName;
		var propValue = props[propName];
		if(propValue !== true){ updatedCookie += "=" + propValue }
	}
	document.cookie = updatedCookie;
}
function deleteCookie(name) {
	setCookie(name, null, { expires: -1 })
}
Number.prototype.format = function() {
    return (this.toFixed() + ' ').replace(/(\d)(?=(\d{3})+ )/g, "$1 ");
};

$.fn.toBasket = function(data, count){
	if(!!$(this).data('index')){
		var ajaxData = {
			id: parseInt($(this).data('index')),
			count: !!count ? count : 1
		};
		$.ajax({
			url: '/modules/catalog/service/to_basket.php',
			data: ajaxData,
			context: this,
			success: function (returnData){
				if(!!returnData.errors){
					console.log(returnData.errors);
				}
				if(returnData.ok === true){
					if(!!data.basket){
						data.basket.html(returnData.html);
						if(!!data.image){
							data.image.effectTransfer({to: data.basket, speed: 0.8, height: 40});
						}
					}
				} else {
					console.log('Ошибка запроса.');
				}
			}
		});
	}
	return $(this);
};

function generateItems(o){
	var r = new $();
	if(!!o.type){
		if(!!o.attributes){
			r = $('<' + o.type + '>', o.attributes);
		} else {
			r = $('<' + o.type + '>');
		}
		if(!!o.children && o.children.length){
			for(var x = 0; x < o.children.length; x++){
				r.append(generateItems(o.children[x]));
			}
		} else if(!!o.innerHtml){
			r.html(o.innerHtml);
		}
	}
	return r;
}
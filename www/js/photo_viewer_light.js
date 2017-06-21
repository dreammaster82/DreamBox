/* 
версия: закругленные белые края, + листание кнопками. 
Несколько коллекций. Одиночные фотки. Возможность принудительного отказа от навигации. 
Опции можно создавать в разделе создания объекта в массиве options. 
*/

jQuery.WMphoto = function(obj, options) 
{
		var photoShowed = false;
		var photo_nav_height = 0;
		var setPosition = -1;

		var images = new Array();

		$(obj).find('a').each(function (i) 
		{
			if ($(this).attr('jqLink'))
			{
				images.push($(this));
			}
			$(this).on('click.photo', function (){
				if ($(this).attr('jqLink'))
				{
					$('#black_overlay').css('filter', 'alpha(opacity=55)');//костыль 
					$('#black_overlay').show();

					open(this);

					return false;
				}
			});
		});

		function bind_keys()
		{
			if (images.length > 1) 
			{
				$(document).bind('keydown.photo', function (e) 
				{
					escapeKey = 27;
					if (e == null) 
					{ // ie
						keycode = event.keyCode;
					}
					else if (e.which) 
					{ // safari
						keycode = e.which;
					}
					else 
					{ // mozilla
						keycode = e.keyCode;
						escapeKey = e.DOM_VK_ESCAPE;
					}

					if (keycode == escapeKey) close();
					else if (keycode == 39) _set_next_prev_image(setPosition+1);
					else if (keycode == 37) _set_next_prev_image(setPosition-1);
				});
			}
		}

		function _set_next_prev_image(newPosition)
		{
			if (typeof newPosition !== "undefined") 
			{
				if ( photoShowed && (images[newPosition]) ) open(images[newPosition]);
			}
		}

		$(window).scroll(function () {$scrollPos = _getScroll()});

        function _getScroll() 
		{
			return {scrollTop: $(window).scrollTop(), scrollLeft: $(window).scrollLeft()};
        }

		function _getSizeWindow()
		{
			windowHeight = $(window).height();
			windowWidth = $(window).width();
		}

		function _resizeWindow()
		{
			_getSizeWindow();
			if (typeof setPosition !== "undefined") 
			{
				if (photoShowed && images[setPosition]) open(images[setPosition], options.resizeSpeed);
			}
		}

		function close()
		{
			$(document).unbind('.photo');
			$(window).unbind('.photo');
			photoShowed = false;
			container_top = Math.round( windowHeight/2 - ((options.padding_border * 2) + options.loaderIconSize)/2 );
			container_left = Math.round( windowWidth/2 - ((options.padding_border * 2) + options.loaderIconSize)/2 );						

			$('#photo_huge_container').fadeOut(options.animationSpeed, function ()
			{
				$('#photo_hover_container').hide();
				$('#photo_title').hide();
				$('#photo_description').hide();	
				$('#photo_nav').hide();
				$('#photo_title').html('');
				$('#photo_description').html('');

				$('#photo_content').css({
					'height': options.loaderIconSize
				});
				$('#photo_container').css({
					'top': container_top,
					'left': container_left,
					'height': ((options.padding_border * 2) + options.loaderIconSize),
					'width': ((options.padding_border * 2) + options.loaderIconSize)
				});
				$('#photo_loaderIcon').show();
				$('#photo_container').hide();
				$('#black_overlay').fadeOut(options.animationSpeed);
			});		
		}

		function init()
		{
			if (!$('body').find('#black_overlay').is('div')) 
			{
				$('body').append('<div id="black_overlay"></div><div id="photo_container"><div class="photo_top"><div class="photo_left"></div><div class="photo_middle"></div><div class="photo_right"></div></div><div id="photo_hover_container"><a class="photo_next" href="#"></a><a class="photo_previous" href="#"></a></div><div id="photo_loaderIcon"><img src="/images/loader.gif" width="24" height="24" border="0"></div><div id="photo_closer"></div><div id="photo_middle"><div id="photo_details"><div id="photo_content"><div id="photo_title"></div><div id="photo_huge_container"><img id="huge" src="/images/null.gif" border="0"></div><div id="photo_description"></div><div id="photo_nav"><a id="aPrev"></a><p id="current_text">0 из 0</p><a id="aNext"></a><div id="photo_nav_info">(Клавиши Esc, &larr;, &rarr;)</div></div></div></div></div><div class="photo_bottom"><div class="photo_left"></div><div class="photo_middle"></div><div class="photo_right"></div></div></div>');
				$('#black_overlay').on('click.photo', function()
				{
					close();
					return false;
				});

				$('#photo_closer').on('click.photo', function()
				{
					$(this).hide();
					close();
					return false;
				});

				$scrollPos = _getScroll();
				_getSizeWindow();

				close();
			}
		}

		function open (a, animationSpeed)
		{
			if (typeof animationSpeed == "undefined") 
			{
				animationSpeed = options.animationSpeed;
			}
			if (!photoShowed) 
			{
				$(window).bind('resize.photo', function () 
				{
					_resizeWindow();
				});
				bind_keys();
			}
			photoShowed = true;

			$(images).each(function (i) 
			{
				if (this.attr('jqLink') == $(a).attr('jqLink'))
				{
					setPosition = i;
					$('#aNext').unbind('click.photo');
					$('#aPrev').unbind('click.photo');
					$('#photo_hover_container a.photo_next').unbind('click.photo');
					$('#photo_hover_container a.photo_previous').unbind('click.photo');
					if (images[setPosition+1])
					{
						$('#aNext').removeClass('photo_nav_inactive');
						$('#photo_hover_container a.photo_next').css('visibility', 'visible');
						$('#aNext, #photo_hover_container a.photo_next').on('click.photo', function () 
						{
							open(images[setPosition+1]);
							return false;
						});
					}
					else 
					{
						$('#aNext').addClass('photo_nav_inactive');
						$('#photo_hover_container a.photo_next').css('visibility', 'hidden');
					}

					if (images[setPosition-1])
					{
						$('#aPrev').removeClass('photo_nav_inactive');
						$('#photo_hover_container a.photo_previous').css('visibility', 'visible');
						$('#aPrev, #photo_hover_container a.photo_previous').on('click.photo', function () 
						{
							open(images[setPosition-1]);
							return false;
						});
					}
					else 
					{
						$('#aPrev').addClass('photo_nav_inactive');
						$('#photo_hover_container a.photo_previous').css('visibility', 'hidden');
					}
					$('#current_text').text( (setPosition+1) + options.counter_separator_label + images.length);
				}
			});

			var jqThumb = new Array();
			jqThumb['link'] = $(a).attr('jqLink');
			jqThumb['title'] = $(a).attr('title');
			jqThumb['alt'] = $(a).find('img').attr('alt');
			
			$('#photo_closer').hide();
			$('#photo_hover_container').hide();
			$('#photo_huge_container').fadeOut(options.resizeSpeed, function ()
			{
				$('#photo_loaderIcon').show();
				$('#photo_nav').hide();
				$('#photo_description').hide();
				$('#photo_title').hide();
				$('#photo_title').html('');
				$('#photo_description').html('');
				$('#photo_container').show();

				var imgPreloader = new Image();
				imgPreloader.onload = function () 
				{
					if (jqThumb['alt']) $('#photo_description').html(jqThumb['alt']);
					if (jqThumb['title']) $('#photo_title').html(jqThumb['title']);

					var preloader_height = imgPreloader.height;
					var preloader_width = imgPreloader.width;
					var container_width = (options.padding_border * 2);

					var all_half_padding = 0;

					if (jqThumb['alt']) 
					{
						$('#photo_description').css({'padding-top' : options.padding_inner});
						all_half_padding += options.padding_inner;
					}
					if (jqThumb['title'])
					{
						$('#photo_title').css({'padding-bottom' : options.padding_inner});
						all_half_padding += options.padding_inner;
					}
					if ( (images.length > 1) && !options.showAlong) 
					{
						$('#photo_nav').css({'padding-top' : options.padding_inner});
						all_half_padding += options.padding_inner;
					}

					var noramalView = true;
					for (var i=1; i<=3; i++)
					{
						if (jqThumb['alt']) $('#photo_description').width(preloader_width);
						if (jqThumb['title']) $('#photo_title').width(preloader_width);
						if ( (images.length > 1) && !options.showAlong) 
						{
							$('#photo_nav').width(preloader_width);
							photo_nav_height = $('#photo_nav').height();
						}
						container_height = $('#photo_description').height() + $('#photo_title').height() + photo_nav_height;
						container_height += (options.padding_border * 2) + all_half_padding;
						var differ_height = container_height + preloader_height;
						if ( differ_height > ( windowHeight - options.window_padding ) )
						{
							noramalView = false;
							preloader_height = windowHeight - options.window_padding - container_height;
							preloader_width = Math.round(preloader_height * imgPreloader.width / imgPreloader.height);
							differ_height = windowHeight - options.window_padding;
						}
						var differ_width = preloader_width + container_width;
						if ( differ_width > ( windowWidth - options.window_padding ) )
						{
							noramalView = false;
							preloader_width = windowWidth - options.window_padding - container_width;
							preloader_height = Math.round(preloader_width * imgPreloader.height / imgPreloader.width);
							differ_width = windowWidth - options.window_padding;
						}
						if ( differ_width < (300 - options.window_padding) )
						{
							noramalView = false;	
							preloader_width = options.minContainerSize - options.window_padding - container_width;
							preloader_height = Math.round(preloader_width * imgPreloader.height / imgPreloader.width);
							differ_width = options.minContainerSize;
						}
						var differ_left = Math.round( windowWidth/2 - differ_width/2 );
						var differ_top = Math.round( windowHeight/2 - differ_height/2 );
						if (noramalView) break;
					}

					$('#photo_loaderIcon').hide();
					$('#photo_content').animate({
						'height': differ_height - (options.padding_border * 2) 
					}, animationSpeed);

					$('#photo_container').animate({
						'top': differ_top,
						'left': differ_left,
						'height': differ_height,
						'width': differ_width
					}, animationSpeed, function ()
					{
						var t = options.padding_border;
						if (jqThumb['title']) t += $('#photo_title').height() + options.padding_inner;
						$('#photo_hover_container').css({'left' : options.padding_border, 'width' : preloader_width, 'top' : t, 'height' : preloader_height});

						if (jqThumb['title']) $('#photo_title').show();  /* закрыть, чтобы не выводилось поле title*/

						$('#huge').attr('src', jqThumb['link']).width(preloader_width).height(preloader_height);
						$('#photo_huge_container').fadeIn(animationSpeed, function ()
						{
							if (jqThumb['alt']) $('#photo_description').show();
							if ( (images.length > 1) && !options.showAlong) $('#photo_nav').show();
							$('#photo_closer').show();
							$('#photo_hover_container').show();
						});
					});
				};

				imgPreloader.src = jqThumb['link'];

				return false;
			});
		}
		init();
}

jQuery.fn.WMphoto = function(options) 
{
	options = options || {};

	options.animationSpeed = options.animationSpeed || 500;
	options.resizeSpeed = options.resizeSpeed || 100;
	options.padding_inner = options.padding_inner || 10;
	options.counter_separator_label = options.counter_separator_label || ' из ';
	options.showAlong = options.showAlong && true;
	options.loaderIconSize = options.loaderIconSize || 24;
	options.minContainerSize = options.minContainerSize || 300;
	options.window_padding = options.window_padding || 20;
	options.padding_border = options.padding_border || 20;

	this.each(function() 
	{ 
		new jQuery.WMphoto(this, options);
	});

	return this;
}
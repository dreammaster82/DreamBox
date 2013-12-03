jQuery.CImager = function(obj, options){
	
	CImager = {
		images: new Array(),
		photoShowed: false,
		photo_nav_height: 0,
		setPosition: -1,
		$scrollPos: {},
		windowHeight: 0,
		windowWidth: 0,
		prev: jQuery(),
		next: jQuery(),
		bigImage: jQuery(),
		options: {},
		setCurrentImage: function(obj){
			if(!!this.images[this.setPosition].parent()){
				this.images[this.setPosition].parent().removeClass('active');
			}
			var ind = parseInt($(obj).data('index'));
			if(this.images[ind].parent()){
				this.images[ind].parent().addClass('active');
			}
			this.bigImage.attr({
				'src': '/preview/type_' + this.options.prev_type + this.images[ind].data('image'),
				'title': this.images[ind].attr('title')
			});
			$(this.options.big_image, obj).find('a').eq(0).attr('href', this.images[ind].data('image'));
			this.setPosition = ind;
			if (this.images[this.setPosition+1]){
				this.next.removeClass('nonactive');
			} else {
				this.next.addClass('nonactive');
			}
			if (this.images[this.setPosition-1]){
				this.prev.removeClass('nonactive');
			} else {
				this.prev.addClass('nonactive');
			}
			if(typeof options.after == 'function'){
				this.options.after(this);
			}
		},
		
		bindKeys: function(){
			if (this.images.length > 1){
				var o = this;
				$(document).on('keydown.CImager', function (e){
					var escapeKey = 27;
					var keycode = 0;
					if (e == null){ // ie
						keycode = e.keyCode;
					} else if(e.which){ // safari
						keycode = e.which;
					} else { // mozilla
						keycode = e.keyCode;
						escapeKey = e.DOM_VK_ESCAPE;
					}
					if(keycode == escapeKey){
						close();
					} else if(keycode == 39){
						o.setNextPrevImage(o.setPosition+1);
					} else if(keycode == 37){
						o.setNextPrevImage(o.setPosition-1);
					}
				});
			}
		},
		
		setNextPrevImage: function(newPosition){
			if(typeof newPosition !== 'undefined'){
				if (this.photoShowed && (this.images[newPosition]) ){
					//this.open(newPosition);
				}
			}
		},
		
		getScroll: function(){
			return {scrollTop: $(window).scrollTop(), scrollLeft: $(window).scrollLeft()};
		},
		
		getSizeWindow: function(){
			this.windowHeight = $(window).height();
			this.windowWidth = $(window).width();
		},
		
		resizeWindow: function(){
			this.getSizeWindow();
			if (typeof setPosition != 'undefined'){
				if(this.photoShowed && this.images[this.setPosition]){
					//this.open(this.setPosition, options.resizeSpeed);
				}
			}
		},
		
		close: function(){
			$(document).off('.CImager');
			$(window).off('.CImager')
			this.photoShowed = false;
			var containerTop = Math.round(this.windowHeight/2 - ((this.options.padding_border * 2) + this.options.loaderIconSize)/2 );
			var containerLeft = Math.round(this.windowWidth/2 - ((this.options.padding_border * 2) + this.options.loaderIconSize)/2 );
			var o = this;
			$('#photo_huge_container').fadeOut(this.options.animationSpeed, function (){
				$('#photo_hover_container').hide();
				$('#photo_title').hide();
				$('#photo_description').hide();	
				$('#photo_nav').hide();
				$('#photo_title').html('');
				$('#photo_description').html('');
				$('#photo_content').css({
					'height': o.options.loaderIconSize
				});
				$('#photo_container').css({
					'top': containerTop,
					'left': containerLeft,
					'height': ((o.options.padding_border * 2) + o.options.loaderIconSize),
					'width': ((o.options.padding_border * 2) + o.options.loaderIconSize)
				});
				$('#photo_loaderIcon').show();
				$('#photo_container').hide();
				$('#black_overlay').fadeOut(o.options.animationSpeed);
			});		
			
		},
		
		init: function(options){
			var o = this;
			o.options = options;
			if(o.options.big_image != ''){
				o.bigImage = $(o.options.big_image, obj).find('img').eq(0);
			} else {
				o.bigImage = $('img', obj).eq(0);
			}
			if($(o.options.elements, obj).length){
				$(o.options.elements, obj).find('[data-image]').each(function(index){
					if($(this).parent().hasClass('active')){
						o.setPosition = index;
					} else if(o.setPosition == -1){
						o.setPosition = index;
					}
					$(this).attr('data-index', index);
					o.images.push($(this));
				});
			}
			if($(o.options.big_image, obj).find('a').length && !o.images.length){
				o.setPosition = 0;
				var img = o.bigImage.clone();
				img.attr('data-index', 0);
				o.images.push(img);
			}
			
			if(!o.images.length){
				return false;
			}
			
			$(window).scroll(function () {o.$scrollPos = o.getScroll()});
			$(window).on('resize.CImager', function(){
				if (!o.photoShowed){
					o.resizeWindow();
				}
			});
			this.getSizeWindow();
			this.prev = $('<a href="">Предыдущее</a>');
			this.next = $('<a href="">Следующее</a>');
			this.next.on('click.CImager', function (){
				if(o.images[o.setPosition+1]){
					o.setCurrentImage(o.images[o.setPosition+1]);
				}
				return false;
			});
			this.prev.on('click.CImager', function (){
				if(o.images[o.setPosition-1]){
					o.setCurrentImage(o.images[o.setPosition-1]);
				}
				return false;
			});
			if(!this.images[this.setPosition+1]){
				this.next.addClass('nonactive');
			}
			$('.navigate .next', obj).append(this.next);
			if (!this.images[this.setPosition-1]){
				this.prev.addClass('nonactive');
			}
			$('.navigate .prev', obj).append(this.prev);
			$('.item_imgs', obj).find('.active').removeClass('active');
			this.images[this.setPosition].parent().addClass('active');
			$(o.options.big_image, obj).find('a').eq(0).on('click.CImager', function(){
				o.open(o.setPosition);
				return false;
			});
			for(var i in this.images){
				this.images[i].each(function(){
					var obj = $(this);
					if(obj.parent()){
						obj.parent().on('click.CImager', function(){
							o.setCurrentImage(obj); 
							return false;
						});
					} else {
						obj.on('click.CImager', function(){
							o.setCurrentImage(obj); 
							return false;
						});
					}
				});
			}
			if (!$('body').find('#black_overlay').is('div')) {
				$('body').append('<div id="black_overlay"></div><div id="photo_container"><div class="photo_top"><div class="photo_left"></div><div class="photo_middle"></div><div class="photo_right"></div></div><div id="photo_hover_container"><a class="photo_next" href="#"></a><a class="photo_previous" href="#"></a></div><div id="photo_loaderIcon"><img src="/images/loader.gif" width="24" height="24" border="0"></div><div id="photo_closer"></div><div id="photo_middle"><div id="photo_details"><div id="photo_content"><div id="photo_title"></div><div id="photo_huge_container"><img id="huge" src="/images/null.gif" border="0"></div><div id="photo_description"></div><div id="photo_nav"><a id="aPrev"></a><p id="current_text">0 из 0</p><a id="aNext"></a><div id="photo_nav_info">(Клавиши Esc, &larr;, &rarr;)</div></div></div></div></div><div class="photo_bottom"><div class="photo_left"></div><div class="photo_middle"></div><div class="photo_right"></div></div></div>');
				$('#black_overlay').click(function(){
					o.close();
					return false;
				});
				$('#photo_closer').click(function(){
					$(this).hide();
					o.close();
					return false;
				});
				this.$scrollPos = this.getScroll();
				this.close();
			}
		},
		
		open: function(ind, animationSpeed){
			if(typeof animationSpeed == 'undefined'){
				animationSpeed = this.options.animationSpeed;
			}
			if(!this.photoShowed){
				this.bindKeys();
			}
			this.photoShowed = true;
			this.setCurrentImage(this.images[ind]);
			this.setPosition = ind;
			$('#aNext').off('click');
			$('#aPrev').off('click');
			$('#photo_hover_container a.photo_next').off('click');
			$('#photo_hover_container a.photo_previous').off('click');
			var o = this;
			if(this.images[this.setPosition+1]){
				$('#aNext').removeClass('photo_nav_inactive');
				$('#photo_hover_container a.photo_next').css('visibility', 'visible');
				$('#aNext, #photo_hover_container a.photo_next').on('click', function (){
					o.open(o.setPosition+1);
					return false;
				});
			} else {
				$('#aNext').addClass('photo_nav_inactive');
				$('#photo_hover_container a.photo_next').css('visibility', 'hidden');
			}
			if(this.images[this.setPosition-1]){
				$('#aPrev').removeClass('photo_nav_inactive');
				$('#photo_hover_container a.photo_previous').css('visibility', 'visible');
				$('#aPrev, #photo_hover_container a.photo_previous').on('click', function (){
					o.open(o.setPosition-1);
					return false;
				});
			} else {
				$('#aPrev').addClass('photo_nav_inactive');
				$('#photo_hover_container a.photo_previous').css('visibility', 'hidden');
			}
			$('#black_overlay').show();
			$('#current_text').text((this.setPosition+1) + options.counter_separator_label + this.images.length);
			var jqThumb = new Array();
			jqThumb['link'] = this.images[this.setPosition].data('image');
			jqThumb['title'] = this.images[this.setPosition].attr('title');
			$('#photo_closer').hide();
			$('#photo_hover_container').hide();
			$('#photo_huge_container').fadeOut(this.options.resizeSpeed, function (){
				$('#photo_loaderIcon').show();
				$('#photo_nav').hide();
				$('#photo_description').hide();
				$('#photo_title').hide();
				$('#photo_title').html('');
				$('#photo_description').html('');
				$('#photo_container').show();
				var imgPreloader = new Image();
				imgPreloader.onload = function (){
					if(jqThumb['title']){
						$('#photo_title').html(jqThumb['title']);
					}
					var preloaderHeight = imgPreloader.height;
					var preloaderWidth = imgPreloader.width;
					var containerWidth = (o.options.padding_border * 2);
					var allHalfPadding = 0;
					if(jqThumb['title']){
						$('#photo_title').css({'padding-bottom' : o.options.padding_inner});
						allHalfPadding += o.options.padding_inner;
					}
					if((o.images.length > 1) && !o.options.showAlong){
						$('#photo_nav').css({'padding-top' : o.options.padding_inner});
						allHalfPadding += o.options.padding_inner;
					}
					var noramalView = true;
					for (var i=1; i<=3; i++){
						if(jqThumb['title']){
							$('#photo_title').width(preloaderWidth);
						}
						if((o.images.length > 1) && !o.options.showAlong){
							$('#photo_nav').width(preloaderWidth);
							o.photo_nav_height = $('#photo_nav').height();
						}
						var containerHeight = $('#photo_description').height() + $('#photo_title').height() + o.photo_nav_height;
						containerHeight += (options.padding_border * 2) + allHalfPadding;
						var differHeight = containerHeight + preloaderHeight;
						if(differHeight > (o.windowHeight - o.options.window_padding)){
							noramalView = false;
							preloaderHeight = o.windowHeight - o.options.window_padding - containerHeight;
							preloaderWidth = Math.round(preloaderHeight * imgPreloader.width / imgPreloader.height);
							differHeight = o.windowHeight - o.options.window_padding;
						}
						var differWidth = preloaderWidth + containerWidth;
						if (differWidth > (o.windowWidth - o.options.window_padding ) ){
							noramalView = false;
							preloaderWidth = o.windowWidth - o.options.window_padding - containerWidth;
							preloaderHeight = Math.round(preloaderWidth * imgPreloader.height / imgPreloader.width);
							differWidth = o.windowWidth - o.options.window_padding;
						}
						if (differWidth < (300 - o.options.window_padding)){
							noramalView = false;	
							preloaderWidth = o.options.minContainerSize - o.options.window_padding - containerWidth;
							preloaderHeight = Math.round(preloaderWidth * imgPreloader.height / imgPreloader.width);
							differWidth = o.options.minContainerSize;
						}
						var differLeft = Math.round(o.windowWidth/2 - differWidth/2 );
						var differTop = Math.round(o.windowHeight/2 - differHeight/2 );
						if (noramalView){
							break;
						}
					}
					$('#photo_loaderIcon').hide();
					$('#photo_content').animate({
						'height': differHeight - (o.options.padding_border * 2) 
					}, animationSpeed);
					$('#photo_container').animate({
						'top': differTop,
						'left': differLeft,
						'height': differHeight,
						'width': differWidth
					}, animationSpeed, function(){
						var t = o.options.padding_border;
						if(jqThumb['title']){
							t += $('#photo_title').height() + o.options.padding_inner;
						}
						$('#photo_hover_container').css({'left' : o.options.padding_border, 'width' : preloaderWidth, 'top' : t, 'height' : preloaderHeight});
						if(jqThumb['title']){
							$('#photo_title').show();  /* закрыть, чтобы не выводилось поле title*/
						}
						$('#huge').attr('src', jqThumb['link']).width(preloaderWidth).height(preloaderHeight);
						$('#photo_huge_container').fadeIn(animationSpeed, function (){
							if ((o.images.length > 1) && !o.options.showAlong){
								$('#photo_nav').show();
							}
							$('#photo_closer').show();
							$('#photo_hover_container').show();
						});
					});
				};
				imgPreloader.src = jqThumb['link'];
				return false;
			});
		}
		
	}
	
	CImager.init(options);
}

jQuery.fn.CImager = function(options) {
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
	options.elements = options.elements || '.images';
	options.big_image = options.big_image || '';
	options.prev_type = options.prev_type || 'big';
	options.after = options.after || null;
	

	return this.each(function(){
		new jQuery.CImager(this, options);
	});
}
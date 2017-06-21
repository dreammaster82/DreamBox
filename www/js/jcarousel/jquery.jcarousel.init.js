$(document).ready(function(){
	$('#slider').jcarousel({
		scroll:1,
		auto:3,
		wrap:'both',
		initCallback: function(e){
			e.startAuto();
			e.options.controls = $('<div class="jcarousel-controls"></div>')
			var width = 0;
			for(var i = 0; ++i <= e.options.size;){
				var b = $('<a href="#" data-jcarouselindex="' + i + '"></a>');
				b.on('click.jcarousel', function(){
					e.scroll(parseInt($(this).data('jcarouselindex')));
					return false;
				});
				if(e.options.scroll == i){
					b.addClass('active');
				}
				e.options.controls.append(b);
				width += 24;
				e.list.find('[jcarouselindex="' + i + '"]').children().eq(0).attr('data-jcarouselindex', i);
			}
			e.options.contol_items = $('a', e.options.controls);
			//e.options.controls.width(width).css('marginLeft', -width/2);
			e.container.append(e.options.controls);
		},
		itemVisibleOutCallback: {
			onAfterAnimation: function(e){
				e.options.contol_items.removeClass('active');
				$('[data-jcarouselindex="' + e.list.find('[jcarouselindex="' + e.first + '"]').find('[data-jcarouselindex]').data('jcarouselindex') + '"]', e.options.controls).addClass('active');
			}
		}
	});
	$('#slider_bot').jcarousel({
		scroll:1,
		auto:3,
		wrap:'both'
	});
	$('#slider_header').jcarousel({
		buttonNextHTML: '',
		buttonPrevHTML: '',
		scroll:1,
		auto:3,
		wrap:'both'
	});
});
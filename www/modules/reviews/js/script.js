$(document).ready(function(){
	$('#send_form_button').on('click', function(){
		if($('#send_review').is(':hidden')){
			$('#send_review').slideDown();
		}
		return false;
	});
	$('#to_send_button').on('click', function(){
		$('#send_form_button').trigger('click');
		return true;
	});
	$('input[type="range"]').range();
	
	$('.raiting_value').each(function(){
		var rait = +$(this).find('span').data('raiting');
		$(this).empty();
		for(var i = 0; i++ < rait;){
			$(this).append('<span class="one_rait">&#9733</span>');
		}
	});
	
	var form = $('#send_review_form');
	form.on('submit', function(){
		var valid = 1, code = Math.random() * (100 - 1) + 1;
		$(':input', this).each(function(){
			if(typeof this.validity == 'object' && $(this).attr('required')){
				var cv = this.checkValidity();
				valid &= cv;
				if(cv){
					$(this).css('border', '');
				} else {
					$(this).css('border', '1px solid rgb(255,0,0)');
				}
			} else {
				if($(this).attr('required') && $(this).val() == ''){
					valid = 0;
					$(this).css('border', '1px solid rgb(255,0,0)');
				} else {
					$(this).css('border', '');
				}
			}
		});
		if(valid && !getCookie('verify')){
			$(this).append('<input type="hidden" name="code" value="' + code + '" id="secretCode" />');
			setCookie('code', code, {path: '/', expires: 1000});
			$.ajax({
				url: this.action,
				data: $(this).serializeArray(),
				context: this,
				success: function (returnData){
					if(returnData.errors){
						console.log(returnData.errors);
					}
					if(returnData.ok === true){
						var form = $(this);
						form.find('.return').html(returnData.data).slideDown(function(){
							setTimeout(function(){form.find('.return').slideUp();$('#send_review').slideUp();}, 3000);
						});
						setCookie('verify', code, {path: '/', expires: 30});
					} else {
						console.log('Ошибка запроса.');
					}
				},
				complete: function(){
					$(this).find('#secretCode').remove();
					deleteCookie('code');
				}
			});
		} else {
			if(getCookie('verify')){
				var ret = form.find('.return');
				ret.html('Вы можете повторить отправку раз в 30 сек.');
				setTimeout(function(){ret.empty();}, 3000);
			}
		}
		return false;
	}).attr('action', '/modules/reviews/service/ajax.php?action=send_review').find('button').attr('type', 'submit');
});

$.fn.range = function(){
	return $(this).each(function(){
		$.range.init(this);
	});
}

$.range = {
	index: 0,
	init: function(cur){
		var  div = $('<div class="jquery_range_box" data-index="' + this.index + '"></div>');
		
		for(var i = 0, max = +cur.max || 5; i++ < max;){
			div.append('<span data-value="' + (max - i + 1) + '">&#9734</span>');
		}
		var spans = div.find('span');
		spans.on('click', function(){
			spans.removeClass('active');
			$(this).addClass('active');
			$(cur).val($(this).data('value'));
		})
		div.insertAfter(cur);
		$(cur).hide();
		this.index++;
	}
}
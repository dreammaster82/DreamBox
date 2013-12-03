function changeInput(){
	var timeout = 0;
	if($('input[name^="cnt["]').length){
		$('input[name^="cnt["]').each(function(){
			var input = $(this),
			ind = parseInt(input.attr('name').replace('cnt[', '').replace(']', '')),
			price = $('input[name="price[' + ind + ']"]');
			$('button[name="plus"]', input.parent()).on('click.basket', function(){
				input.val(parseInt(input.val()) + 1);
				input.trigger('change.basket');
			});
			$('button[name="minus"]', input.parent()).on('click.basket', function(){
				var cnt = parseInt(input.val()) - 1;
				if(cnt < 1){
					cnt = 1;
				}
				input.val(cnt);
				input.trigger('change.basket');
			});
			input.on('change.basket', function(){
				if(isNaN(parseInt($(this).val())) || parseInt($(this).val()) < 1){
					$(this).val(1);
				}
				var par = input.parents('.prices_block');
				if(price.length){
					var pr = price.val(), cnt = parseInt(input.val());
					$('.itog', par).html('= ' + parseInt((pr * cnt).toFixed()).format() + ' <span>руб./м.</span>');
					setItogo();
					if(timeout){
						window.clearTimeout(timeout);
					}
					timeout = window.setTimeout(function(){
						$.ajax({
							url: '/modules/catalog/service/to_basket.php',
							data: {id:ind, count:input.val()},
							success: function (returnData){
								if(!!returnData.errors){
									console.log(returnData.errors);
								}
								if(returnData.ok === true){
									$('#basket').html(returnData.html);
								}
							}
						});
					}, 1000);
				}
			});
		});
	}
}

function setItogo(){
	var allPrice = 0;
	$('.one_item').each(function(){
		allPrice += parseInt($('[name^="cnt["]', this).val()) * parseInt($('[name^="price["]', this).val());
	})
	$('.itogo .right').html(allPrice.format() + ' <b>руб./м.</b>');
}

function initDelete(){
	var count = 0;
	$('.one_item').each(function(){
		count++;
		var div = $(this),
		id = parseInt($('input[name^="cnt["]', div).attr('name').replace('cnt[', '').replace(']', ''));
		$('.delete', div).on('click.bf', function(){
			$.ajax({
				url: '/modules/catalog/service/to_basket.php',
				data: {id:id,'delete':1},
				success: function (returnData){
					if(!!returnData.errors){
						console.log(returnData.errors);
					}
					if(returnData.ok === true){
						if(!!$('#basket_check').length){
							$('#basket_check').html(returnData.html);
						}
						div.slideUp(function(){
							div.remove();
							count--;
							if(!count){
								$('section.basket').html('<div class="warning">По вашим параметрам ничего не найдено</div>');
							}
							setItogo();
						});
					} else {
						console.log('Ошибка запроса.');
					}
				}
			});
			return false;
		});
	});
}

function submitBasket(){
	$('.basket form').on('submit.init', function(){
		var h = $(this).find('input[name="hash[0]"]'),
		valid = 1;
		$(':input', this).each(function(){
			if(typeof this.validity == 'object'){
				valid &= this.checkValidity();
			} else {
				if(!!$(this).attr('reuqired') && $(this).val() == ''){
					valid = 0;
				}
			}
		});
		if(h.length && h.val() != "" && valid){
			return true;
		} else {
			return false;
		}
	});
	$('.basket form [name="go"]').on('click.init', function(){
		$.ajax({
			'url': '/modules/catalog/service/get_hash.php',
			'data': {name:'hash',type:0},
			'success': function (data){
				if(!!data.ok && !!data.hash){
					var h = $('.basket form [name="hash[0]"]');
					if($('.basket form [name="hash[0]"]').length){
						h.val(data.hash);
					} else {
						$('.basket form').append('<input type="hidden" name="hash[0]" value="' + data.hash + '" />').attr('action', '/catalog/order/').trigger('submit');
					}
				}
			}
		});
		return false;
	});
}
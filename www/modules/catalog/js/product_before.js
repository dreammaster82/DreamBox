function changeInput(){
	var input = $('input[name^="cnt"]'), timeout = 0;
	if(input.length){
		var price = $('input[name="price[' + input.attr('name').replace('cnt[', '').replace(']', '') + ']"]');
		$('button[name="plus"]').on('click.basket', function(){
			input.val(parseInt(input.val()) + 1);
			input.trigger('change.basket');
		});
		$('button[name="minus"]').on('click.basket', function(){
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
			if(price.length){
				var b = $('.in_basket');
				if(b.length){
					if(timeout){
						window.clearTimeout(timeout);
					}
					timeout = window.setTimeout(function(){
						$.ajax({
							url: '/modules/catalog/service/to_basket.php',
							data: {id:parseInt(b.data('index')), count:input.val()},
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
			}
		});
	}
}
$(document).ready(function(){
	$('.to_basket').on('click.init', function(){
		$(this).toBasket({image:$(this).parents('.box').find('.image img'), basket:$('#basket')}).off('.init').html('Куплено').removeClass('to_basket').addClass('in_basket disable').attr('href', '/catalog/basket/');
		return false;
	});
});
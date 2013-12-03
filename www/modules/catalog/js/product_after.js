$(document).ready(function(){
	$('.to_basket').on('click.init', function(){
		var cnt = 1,
		inp = $('input[name="cnt[' + $(this).data('index') + ']"]');
		if(inp.length){
			cnt = parseInt(inp.val());
			var c = parseInt(inp.val());
			if(!isNaN(c)){
				cnt = c;
			}
		}
		$(this).toBasket({image:$('section.product .image img').eq(0),basket:$('#basket')}, cnt);
		return false;
	});
	changeInput();
	$('.prod_info_block form').on('submit', function(){return false;});
});
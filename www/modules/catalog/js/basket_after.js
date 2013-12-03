$(document).ready(function(){
	changeInput();
	initDelete();
	submitBasket();
	$('#deliv').on('change.basket', function(){
		if($(this).val() == "1"){
			$('#deliv_adres').removeClass('hidden');
			$('#deliv_adres input').attr('required', 'true');
		} else {
			$('#deliv_adres').addClass('hidden');
			$('#deliv_adres input').removeAttr('required');
		}
	});
});
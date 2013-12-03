$(document).ready(function(){
	if(!!$('body').data('contentid')){
		$('.admin_menu ul > li[data-contentid="' + $('body').data('contentid') + '"]').addClass('active');
	}
});
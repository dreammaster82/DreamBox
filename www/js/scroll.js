$(document).ready(function(){
	pages = [$('.pages .before'), $('.pages .after')];
	$(document).on('keydown', function (e){
		if(e.ctrlKey){
			if(e.keyCode == 39){
				if(!!$._data(pages[1][0], 'events').click){
					pages[1].trigger('click');
				} else {
					pages[1][0].click();
				}
			}
			if(e.keyCode == 37){
				if(!!$._data(pages[0][0], 'events').click){
					pages[0].trigger('click');
				} else {
					pages[0][0].click();
				}
			}
		}
	});
});

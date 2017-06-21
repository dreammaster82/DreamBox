$(document).ready(function(){
	var callForm =
	{
		type: 'form',
		attributes: {
			method: 'post',
			'class': 'send_form'
		},
		children: [
			{
				type: 'div',
				attributes: {
					'class': 'line'
				},
				children: [
					{
						type: 'label',
						innerHtml: 'Имя <i class="red">*</i>'
					},
					{
						type: 'input',
						attributes: {
							type: 'text',
							name: 'name',
							value: '',
							required: ''
						}
					}
				]
			},
			{
				type: 'div',
				attributes: {
					'class': 'line'
				},
				children: [
					{
						type: 'label',
						innerHtml: 'E-mail <i class="red">*</i>'
					},
					{
						type: 'input',
						attributes: {
							type: 'email',
							name: 'email',
							value: '',
							required: ''
						}
					}
				]
			},
			{
				type: 'div',
				attributes: {
					'class': 'line'
				},
				children: [
					{
						type: 'label',
						innerHtml: 'Комментарий'
					},
					{
						type: 'textarea',
						attributes: {
							name: 'comment',
							value: ''
						}
					}
				]
			},
			{
				type: 'div',
				attributes: {
					'class': 'line'
				},
				children: [
					{
						type: 'button',
						attributes: {
							type: 'button',
							name: 'go'
						},
						innerHtml: '<span>Отправить</span>'
					}
				]
			},
			{
				type: 'div',
				attributes: {
					'class': 'return'
				}
			}
		]
	};
	callForm = generateItems(callForm);
	$('#call_back_form').append(callForm);
	callForm.find('button').on('click', function(){
		var valid = 1, code = Math.random() * (100 - 1) + 1;
		$(':input', callForm).each(function(){
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
		if(valid){
			callForm.append('<input type="hidden" name="code" value="' + code + '" id="secretCode" />');
			setCookie('code', code, {path: '/', expires: 1000});
			$.ajax({
				url: '/service/send_form.php',
				data: callForm.serializeArray(),
				context: this,
				success: function (returnData){
					if(returnData.errors){
						console.log(returnData.errors);
					}
					if(returnData.ok === true){
						callForm.find('.return').html(returnData.data).slideDown(function(){
							setTimeout(function(){callForm.find('.return').slideUp();}, 3000);
						});
					} else {
						console.log('Ошибка запроса.');
					}
				},
				complete: function(){
					callForm.find('#secretCode').remove();
					deleteCookie('code');
				}
			});
		}
		return false;
	});
});
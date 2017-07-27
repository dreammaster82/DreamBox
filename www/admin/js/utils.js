function addRow(tableId, data) {
	if($('#' + tableId).length){
		var table = $('#' + tableId), index = 0;
		while(table.find('tr').is('.row' + index)){
			index++;
		}
		//index++;
		var newTr = $('<tr class="row' + index + ' addon"></tr>');
		if(!!data){
			for(var i = 0; i < data.length; i++){
				newTr.append(function(){
					var td = $('<td></td>');
					for(var i1 = 0; i1 < data[i].length; i1++){
						if(/index$|index\+(\d+)$/gi.test(data[i][i1].type)){
							var arr = /index$|index\+(\d+)$/gi.exec(data[i][i1].type);
							td.append('<b>' + (index + (!!arr[1] ? parseInt(arr[1]) : '')) + '<b> ');
						} else {
							switch(data[i][i1].type){
								case 'html':
									td.append('<span>' + data[i][i1].value + '</span> ');
									break;
								case 'hidden':
								case 'text':
								case 'email':
								case 'radio':
								case 'checkbox':
									td.append(function(){
										var input = $('<input />');
										input.attr(data[i][i1]);
										input.attr('name', data[i][i1].name + '[' + index + ']');
										return input;
									}).append(' ');
									break;
								default:
									td.append(data[i][i1]);
							}
						}
					}
					return td;
				});
			}
		} else {
			newTr.append('<td><input type="hidden" name="image['+index+']" value="1" /><span>img <b>'+index+'</b>:</span></td>');
			newTr.append('<td><input type="file" name="files['+index+']" value="1" /><span> или URL: </span><input type="text" name="url['+index+']" value="" /></td>');
			newTr.append(function(){
				var but = $('<input type="button" name="delrow['+index+']" value="удалить" />');
				but.on('click', function(){
					removeRow(tableId, index);
				});
				return $('<td></td>').append(but);
			});
		}
		if($('tr', table).length){
			if(index){
				newTr.insertAfter($('tr.row' + (index - 1), table));
			} else {
				newTr.insertBefore($('tr.row' + (index + 1), table));
			}
		} else {
			table.append(newTr);
		}
	}
}

function removeRow(tableId, index){
	$('#' + tableId).find('.row' + index).remove();
}

function InsText(idArea, text){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
    AddText(area, text);
}

function AddText(textArea, text){
	if (textArea.createTextRange && textArea.caretPos){
		var caretPos = textArea.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		setfocus(textArea);
	} else {
		textArea.value += text;
		setfocus(textArea);
	}
}

function CheckTextInclude(textArea, word){
	if (textArea.value.indexOf(word) >= 0){
		return true;
	} else {
		return false;
	}
}

function slideshow_insert(idArea, catId, name){
	if (!catId){
		window.open('/admin/gallery/?window=1&idArea=' + (!!idArea ? idArea : ''), "gallery_choose", 'width=500, height=400, resizable=yes, scrollbars=yes, menubar=no, status=yes');
	} else if(catId) {
		if(idArea != ''){
			var area = document.getElementById(idArea);
		}
		if(area == null){
			var area = document.forms[0].getElementsByTagName('textarea')[0];
		}
		AddTxt = "\n<!-- Слайд-шоу. Категория: " + name + " -->\n";
		AddTxt += "[SlideShow][" + catId + "][/SlideShow]"; 
		AddTxt += "\n<!-- /Слайд-шоу -->\n";
		if (!CheckTextInclude(area, '[SlideShow]')){
			AddText(area, AddTxt);
		} else {
			alert('Условие: только одно слайд-шоу на страницу!');
		}
	}
}

function preview_insert(idArea, id, name){
	if (!id){
		window.open('/admin/gallery/?window=1&preview=1&idArea=' + idArea, "preview_choose", 'width=600, height=400, resizable=yes, scrollbars=yes, menubar=no, status=yes');
	} else if (id){
		if(idArea != ''){
			var area = document.getElementById(idArea);
		}
		if(area == null){
			var area = document.forms[0].getElementsByTagName('textarea')[0];
		}
		AddTxt = "<!-- Предпросмотр -->";
		AddTxt += "[preview][" + id + "][/preview]"; 
		AddTxt += "<!-- /Предпросмотр -->";
		AddText(area, AddTxt);
	}
}

function nbsp(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	AddTxt = "&nbsp;";
	AddText(area, AddTxt);
}

function enter(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	AddTxt = "<br>";
	AddText(area, AddTxt);
}

function email(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt2 = prompt("Введите текст ссылки\nЕсли оставите пустым, будет показан адрес",""); 
	if (txt2 != null){
		var txt = prompt("Введите домен:","");
		var txt1 = prompt("Введите имя:","");
		if (txt != null){
			if (txt2 == ""){
				AddTxt = "<a href=\"mailto:Скрыт в целях борьбы со спамом\" onmouseover=\"return getMail('" + txt + "', '" + txt1 + "', this);\">" + txt + "</a>";
			} else {
				AddTxt = "<a href=\"mailto:Скрыт в целях борьбы со спамом\" onmouseover=\"return getMail('" + txt + "', '" + txt1 + "', this);\">" + txt2 + "</a>";
			}
			AddText(area, AddTxt);
		}
	}
}

function bold(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt = prompt("Введите текст: ","");
	if (txt != null){
		AddTxt="<b>" + txt + "</b>";
		AddText(area, AddTxt);
	}
}

function italicize(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt = prompt("Введите текст ","");
	if (txt != null){
		AddTxt = "<i>" + txt + "</i>";
		AddText(area, AddTxt);
	}
}

function paragraph(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt=prompt("Выравнивание параграфа: 1-left, 2-center, 3-right, 4-justify","1");
	if(txt == null){
		AddTxt = "\r<p></p>";
		AddText(area, AddTxt);
	} else {
		var align = "";
		if(txt == 2) align = " align=\"center\"";
		if(txt == 3) align = " align=\"right\"";
		if(txt == 4) align = " align=\"justify\"";
		AddTxt="\r<p" + align + ">\r</p>";
		AddText(area, AddTxt);
	}
}

function quote(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt = prompt("Введите текст ","");
	if(txt != null){
		AddTxt = "\r<quote>\r" + txt + "\r</quote>";
		AddText(area, AddTxt);
	}
}

function center(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
 	var txt = prompt("Введите текст ","");
	if (txt != null){
		AddTxt = "\r<center>" + txt + "</center>";
		AddText(area, AddTxt);
	}
}

function hyperlink(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt2 = prompt("Текст ссылки?\nЕсли Вы оставите это поле пустым, ссылка будет в виде адреса",""); 
	if (txt2 != null){
		var txt = prompt("Введите адрес гиперссылки ","http://");
		if(txt != null){
			if (txt2 == ""){
				AddTxt = "<a href=\"" + txt + "\">" + txt + "</a>";
				AddText(area, AddTxt);
			} else {
				AddTxt = "<a href=\"" + txt + "\">" + txt2 + "</a>";
				AddText(area, AddTxt);
			}
		}
	}
}

function hiperLink2(idArea){
    if(!idArea){
		return;
    }
    window.open('/admin/plugins/hiperLink2/?id=' + idArea + '&window=1', 'hiperLink2', 'width=500, height=400, resizable=yes, scrollbars=yes, menubar=no, status=yes');
}

function image(idArea, img_num){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt = '';
	if (!img_num){
		txt = prompt("Введите номер изображения","");
	} else {
		txt = img_num;
	}
	var txt2 = prompt("Выравнивание изображение (L - left, R - right). Пустое значение - выравнивание отсутствует","");
	var imgalign = "";
	if((txt2 == "R") || (txt2 == "r")) {
		imgalign = "float:right;margin-left:20px";
	}
	if((txt2 == "L") || (txt2 == "l")){
		imgalign = "float:left;margin-right:20px;";
	}
	if((txt != null) && (txt != "")){
			AddTxt = "<img src=\"src[" + txt + "]\" style=\"width:width[" + txt + "]px;height:height[" + txt + "]px;"+ imgalign +"\" border=\"0\" alt=\"\" />";
	} else {
		AddTxt = "<img src=\"\" style=\"" + imgalign + "\" border=\"0\" alt=\"\" vspace=\"0\" hspace=\"0\" />";
	}
	AddText(area, AddTxt);
}

function image2(idArea, file){
    if(!idArea){
	return;
    }
    file = typeof(file) != 'undefined' ? file : '';
    window.open('/admin/plugins/image/?action=form&file=' + file + '&idArea=' + idArea, 'insert_image', 'width=500, height=400, resizable=yes, scrollbars=yes, menubar=no, status=yes');
}

function code(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt = prompt("Введите текст ","");     
	if (txt != null){
		AddTxt = "\r<pre>\r" + txt + "\r</pre>";
		AddText(area, AddTxt);
	}
}

function list(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var AddTxt = "\r<ul>\r\n";
	var txtend = "1";
	while((txtend != "") && (txtend != null)){
		txtend = prompt("Элемент списка:\nЧтобы закончить список, оставьте поле пустым",""); 
		if (txtend != ""){
			AddTxt += "\t<li>" + txtend + "</li>\r"; 
		}                   
	}
	AddTxt += "</ul>";
	AddText(area, AddTxt); 
}

function underline(idArea){
	if(idArea != ''){
		var area = document.getElementById(idArea);
	}
	if(area == null){
		var area = document.forms[0].getElementsByTagName('textarea')[0];
	}
	var txt=prompt("Введите текст ","");     
	if(txt != null){
		AddTxt = "<u>" + txt + "</u>";
		AddText(area, AddTxt);
	}
}

function storeCaret(textEl){
	if (textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
}

function setfocus(textArea){
	textArea.focus();
}

function translit(str, spaceReplacement) {
        var _associations = {
            "а": "a",
            "б": "b",
            "в": "v",
            "ґ": "g",
            "г": "g",
            "д": "d",
            "е": "e",
            "ё": "e",
            "є": "ye",
            "ж": "zh",
            "з": "z",
            "и": "i",
            "і": "i",
            "ї": "yi",
            "й": "i",
            "к": "k",
            "л": "l",
            "м": "m",
            "н": "n",
            "о": "o",
            "п": "p",
            "р": "r",
            "с": "s",
            "т": "t",
            "у": "u",
            "ф": "f",
            "x": "h",
            "ц": "c",
            "ч": "ch",
            "ш": "sh",
            "щ": "sh'",
            "ъ": "",
            "ы": "i",
            "ь": "",
            "э": "e",
            "ю": "yu",
            "я": "ya"
        };

    if (!str) {
        return "";
    }

    var new_str = "";
    for (var i = 0; i < str.length; i++) {
        var strLowerCase = str[i].toLowerCase();
        if (strLowerCase === " " && spaceReplacement) {
            new_str += spaceReplacement;
            continue;
        }
        if (!_associations[strLowerCase]) {
            new_str += strLowerCase;
        }
        else {
            new_str += _associations[strLowerCase];
        }
    }
    return new_str;

};

function throttle(func, s){

    var isThrottled = false,
        savedArgs,
        savedThis;

    function wrapper() {
        if (isThrottled) {
            savedArgs = arguments;
            savedThis = this;
            return;
        }

        func.apply(this, arguments);

        isThrottled = true;

        setTimeout(function() {
            isThrottled = false;
            if (savedArgs) {
                wrapper.apply(savedThis, savedArgs);
                savedArgs = savedThis = null;
            }
        }, s * 1000);
    }

    return wrapper;
}
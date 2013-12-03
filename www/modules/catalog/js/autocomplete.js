/* -------------------------------------------------------------------------------------------
	SEARCH AUTOCOMPLETE
------------------------------------------------------------------------------------------- */
(function($){
$.autocomplete = function(obj, options){
	
	var autocomplete = {
		input: new $(),
		results: $('<div></div>'),
		options: {},
		timeout: null,
		prev: '',
		active: -1,
		cache: {
			'data': {},
			'dataArray': []
		},
		keyb: false,
		hasFocus: false,
		lastKeyPressCode: false,
		
		init: function(obj, options){
			this.input = $(obj);
			this.options = options;
			
			this.input.attr('autocomplete', 'off');
			// Apply inputClass if necessary
			if (this.options.inputClass){
				this.input.addClass(this.options.inputClass);
			}
			
			this.results.hide().addClass(this.options.resultsClass).css('position', 'absolute');
			if (this.options.width > 0 ){
				this.results.width(this.options.width);
			}
			
			// Add to body element
			$('body').append(this.results);
			var me = this;
			this.input.on('keydown.autocomplete', function(e){
				// track last key pressed
				me.lastKeyPressCode = e.keyCode;
				switch(me.lastKeyPressCode){
					case 38: // up
						e.preventDefault();
						me.moveSelect(-1);
						break;
					case 40: // down
						e.preventDefault();
						me.moveSelect(1);
						break;
					case 9:  // tab
					case 13: // return
						if(me.selectCurrent()){
							// make sure to blur off the current field
							me.input.trigger('blur.autocomplete');
							e.preventDefault();
						}
						break;
					default:
						me.active = -1;
						if(me.timeout){
							clearTimeout(me.timeout);
						}
						me.timeout = setTimeout(function(){me.onChange();}, me.options.delay);
						break;
				}
			}).on('focus.autocomplete', function(){
				// track whether the field has focus, we shouldn't process any results if the field no longer has focus
				me.hasFocus = true;
				me.onFocus();
			}).on('blur.autocomplete', function(){
				// track whether the field has focus
				me.hasFocus = false;
				me.hideResults();
			});
			
			this.hideResultsNow();
		},
		
		onChange: function(){
			// ignore if the following keys are pressed: [del] [shift] [capslock]
			if(this.lastKeyPressCode == 46 || (this.lastKeyPressCode > 8 && this.lastKeyPressCode < 32)){
				return this.results.hide();
			}
			var value = this.input.val();
			if(value == this.prev){
				return this.results;
			}
			this.prev = value;
			this.goRequest(value);
		},
		
		goRequest: function(value){
			value = value.trim();
			if (value.length >= options.minChars){
				this.input.addClass(options.loadingClass);
				this.requestData(value);
			} else {
				this.input.removeClass(options.loadingClass);
				this.results.hide();
			}
		},
		
		onFocus: function(){
			this.goRequest(this.input.val());
		},
		
		moveSelect: function(step){
			var li = $('li', this.results);
			if(!li.length){
				return;
			}
			this.active += step;
			if(this.active < 0){
				this.active = 0;
			} else if(this.active >= li.length){
				this.active = li.length - 1;
			}
			li.removeClass(options.overClass);
			li.eq(this.active).addClass(options.overClass);
		},
		
		selectCurrent: function(){
			var li = $('li.'+options.overClass, this.results).get(0);
			if(!li.length){
				li = $('li', this.results).get(0);
			}
			if(li.length){
				selectItem(li);
				return true;
			} else {
				return false;
			}
		},
		
		selectItem: function(li){
			if(!li){
				li = $('li');
				li.extra = [];
				li.selectValue = '';
			}
			var v = $.trim(li.selectValue ? li.selectValue : li.html());
			this.input.get(0).lastSelected = v;
			this.prev = v;
			this.results.html('');
			this.input.val(v);
			this.hideResultsNow();
			var me = this;
			if(typeof this.options.onItemSelect == 'function'){
				setTimeout(function(){
					me.options.onItemSelect(li)
				}, 1);
			} 
		},
		// selects a portion of the input string
		createSelection: function(start, end){
			var field = this.input.get(0);
			if(field.createTextRange){
				var selRange = field.createTextRange();
				selRange.collapse(true);
				selRange.moveStart("character", start);
				selRange.moveEnd("character", end);
				selRange.select();
			} else if(field.setSelectionRange){
				field.setSelectionRange(start, end);
			} else {
				if(field.selectionStart){
					field.selectionStart = start;
					field.selectionEnd = end;
				}
			}
			field.focus();
		},
		// fills in the input box w/the first match (assumed to be the best match)
		autoFill: function(value){
			// if the last user key pressed was backspace, don't autofill
			if(this.lastKeyPressCode != 8){
				// fill in the value (keep the case the user has typed)
				this.input.val(this.input.val() + value.substring(this.prev.length));
				// select the portion of the value not typed by the user (so the next character will erase)
				this.createSelection(this.prev.length, value.length);
			}
		},
		
		showResults: function(){
			// get the position of the input field right now (in case the DOM is shifted)
			var pos = this.findPos(this.input),
			// either use the specified width, or autocalculate based on form element
			iWidth = (this.options.width > 0) ? this.options.width : this.input.width(),
			// reposition
			rleft = pos.x + this.options.offsetWidth,
			me = this;
			this.results.show(function(){
				if(me.options.viewUp){
					var rtop = pos.y - me.results.height() - me.options.offsetHeight;
				} else {
					var rtop = pos.y + me.input.outerHeight(false) + me.options.offsetHeight;
				}
				me.results.css({
					width: parseInt(iWidth),
					top: rtop,
					left: rleft
				});
			});
		},
		
		hideResults: function(){
			if(this.timeout){
				clearTimeout(this.timeout);
			}
			var me = this;
			this.timeout = setTimeout(function(){
				me.hideResultsNow();
			}, 200);
		},
		
		hideResultsNow: function(){
			if (this.timeout){
				clearTimeout(this.timeout);
			}
			this.input.removeClass(this.options.loadingClass);
			if(this.results.is(':visible')){
				this.results.hide();
			}
			if(this.options.mustMatch){
				if (this.input.val() != this.input[0].lastSelected){
					this.selectItem(null);
				}
			}
		},
		
		requestData: function(value){
			if(value.length >= this.options.minChars){
				var cache = this.getCache(value);
				if(cache == null){
					var me = this;
					$.post(
						this.options.url,
						this.makeData(value),
						function(data){
							if(data.str != ''){
								var d = me.parseData(data.str);
								me.addToCache(value, d);
								me.receiveData(value, d);
							} else {
								me.hideResultsNow();
							}
						},
						'json'
					);
				} else {
					this.receiveData(value, cache);
				}
			}
		},
		
		makeData: function(value){
			var data =  {};
			data.q = encodeURI(value);
			for(var i in this.options.extraParams){
				data[i] = encodeURI(options.extraParams[i]);
			}
			return data;
		},
		
		parseData: function(data){
			if(typeof data != 'string'){
				return null;
			}
			var parsed = [];
			var rows = data.split(this.options.lineSeparator);
			for (var i=0; i < rows.length; i++){
				var row = $.trim(rows[i]);
				if(row){
					parsed[parsed.length] = row.split(this.options.cellSeparator);
				}
			}
			return parsed;
		},
		
		receiveData: function(value, data){
			if(data.length){
				this.input.removeClass(this.options.loadingClass);
				this.results.empty();
				// if the field no longer has focus or if there are no matches, do not display the drop down
				if(!this.hasFocus){
					this.hideResultsNow();
					return;
				}
				var div = $('<div class="list-results"></div>');
				div.append(this.dataToDom(value, data));
				if(typeof this.options.formatCloser == 'function'){
					var paragraph = $('<div class="results"></div>');
					var pdata = $(this.options.formatCloser(data.length, this.options.maxItemsToShow));
					var me = this;
					$('a', pdata).on('click.autocomplete', function(e){
						e.preventDefault();
						me.hideResultsNow();
					});
					paragraph.html(pdata);
					div.append(paragraph);
				}
				// autofill in the complete box w/the first match as long as the user hasn't entered in more data
				//if( options.autoFill && ($input.val().toLowerCase() == q.toLowerCase()) ) autoFill(data[0][0]);
				this.results.append(div);
				this.showResults();
			} else {
				this.hideResultsNow();
			}
		},
		
		dataToDom: function(value, data){
			var ul = $('<ul></ul>'),
			num = data.length;
			// limited results to a max number
			if((this.options.maxItemsToShow > 0) && (this.options.maxItemsToShow < num)){
				num = this.options.maxItemsToShow;
			}
			for (var i=0; i < num; i++){
				var row = data[i];
				if(!row){
					continue;
				}
				//--выделяем совпадение
				value = value.toLowerCase();
				value = value.replace(/\s+/g, ' ');
				var textToFind = row[1].toLowerCase(),
				mainText = row[1],
				valueArray = value.split(' '),
				valuePos = 0,
				before = '',
				after = '',
				adding = 0;
				for(var y in valueArray){
					var valueItem = valueArray[y];
					valuePos = textToFind.indexOf(valueItem);
					if(valuePos != -1){
						before = '';
						if(valuePos){
							before = mainText.substr(0, valuePos + adding);
						}
						after = mainText.substr(valuePos + valueItem.length + adding, mainText.length - valuePos - valueItem.length);
						mainText = before + '{' + valueItem + '}' + after;
						adding += 2;
					}
				}
				mainText = mainText.replace(/{/g, '<b>');
				mainText = mainText.replace(/}/g, '</b>');
				// выделяем совпадение--

				var li = $('<li></li>'),
				table = document.createElement('table');
				var newRow = table.insertRow(0);
				if(this.options.formatImage){
					var newCell = newRow.insertCell(0);
					newCell.className = 'td1';
					$(newCell).append(this.options.formatImage(row));
				}
				var newCell = newRow.insertCell(1);
				if(this.options.formatItem){
					newCell.className = 'td2';
					$(newCell).append(this.options.formatItem(mainText, row));
					li.get(0).selectValue = row[0];
				} else {
					newCell.html(mainText);
					li.get(0).selectValue = row[0];
				}
				if (options.formatCount){
					var newCell = newRow.insertCell(2);
					newCell.className = 'td3';
					$(newCell).append(this.options.formatCount(row));
				}
				li.append(table);
				var extra = null;
				if(row.length > 1){
					extra = [];
					for(var j=0; j < row.length; j++){
						extra[extra.length] = row[j];
					}
				}
				li.get(0).extra = extra;
				ul.append(li);
				var me = this;
				li.hover(function(){ 
					$('li', ul).removeClass(me.options.overMouseClass); 
					$(this).addClass(me.options.overMouseClass);
					me.active = $('li', ul).indexOf($(this).get(0));
				}, function(){ 
					$(this).removeClass(me.options.overMouseClass);
				}).on('click.autocomplete', function(e){ 
					e.preventDefault(); 
					e.stopPropagation(); 
					me.selectItem(this) 
				});
			}
			return ul;
		},
		
		addToCache: function(value, data){
			if(!data || !value || !this.options.cacheLength){
				return;
			}
			if(!this.cache.data[value]){
				if(this.cache.dataArray.length > this.options.cacheLength){
					var e = this.cache.dataArray.shift();
					delete this.cache.data[e];
				}
				this.cache.dataArray.push(data);
				this.cache.data[value] = this.cache.dataArray.length - 1;
			}
		},
		
		getCache: function(value){
			if(!!this.cache.data[value]){
				return this.cache.dataArray[this.cache.data[value]];
			} else {
				return null;
			}
		},
		
		findPos: function(obj){
			var offset = obj.offset();
			if(this.options.viewRigth){
				offset.left += obj.innerWidth() - (this.options.width + this.options.padding[1] + this.options.padding[3]); 
			}
			return {x:offset.left,y:offset.top};
		}
	}
	
	autocomplete.init(obj, options);
};


$.fn.autocomplete = function(url, options, data) 
{
	// Make sure options exists
	options = options || {};
	// Set url as option
	options.url = url;

	// Set default values for required options
	options.inputClass = options.inputClass || "";
	options.overClass = options.overClass || "";
	options.overMouseClass = options.overMouseClass || "";
	options.resultsClass = options.resultsClass || "";
	options.lineSeparator = options.lineSeparator || "\n";
	options.cellSeparator = options.cellSeparator || "|";
	options.minChars = options.minChars || 1;
	options.delay = options.delay || 400;
	options.matchCase = options.matchCase || 0;
	options.matchSubset = options.matchSubset || 1;
	options.matchContains = options.matchContains || 0;
	options.cacheLength = options.cacheLength || 1;
	options.mustMatch = options.mustMatch || 0;
	options.extraParams = options.extraParams || {};
	options.loadingClass = options.loadingClass || "";
	options.selectFirst = options.selectFirst || false;
	options.selectOnly = options.selectOnly || false;
	options.maxItemsToShow = options.maxItemsToShow || -1;
	options.autoFill = options.autoFill || false;
	options.width = parseInt(options.width, 10) || 0;
	options.priceOverClass = options.priceOverClass || "";
	options.priceTag = options.priceTag || "span";
        options.offsetHeight = options.offsetHeight || 0;
        options.offsetWidth = options.offsetWidth || 0;
        options.viewUp = options.viewUp || 0;

	this.each(function() 
	{
		var input = this;
		new $.autocomplete(input, options);
	});

	// Don't break the chain
	return this;
}

$.fn.autocompleteArray = function(data, options) 
{
	return this.autocomplete(null, options, data);
}

$.fn.indexOf = function(e)
{
	for( var i=0; i<this.length; i++ )
	{
		if( this[i] == e ) return i;
	}
	return -1;
};
})(jQuery);

$(document).ready(function(){
    function liFormat (main_text, row){
        var result = '<a href="/catalog' + row[3] + '/'+ row[0] + '_' + row[2] + '.html">' + main_text + '</a>';
        return result;
    }
    function priceFormat (row){
		if(!!row[6]){
			return '<span class="price">' + row[6] + ' руб./м.</span>';
		} else {
			return '';
		}
        
    }
    function imgFormat (row) {
		var result = $("<div class='img'>");
		if(row[4]){
			result.css('background', 'url("/preview/type_aut_imgs'+ row[5] + row[4] +'") no-repeat center center');
		} else {
			result.css('background', 'url("/preview/type_aut_imgs/images/no_photo.jpg") no-repeat center center');
		}
        return result;
    }

	function closerFormat (dataLength, maxItemsToShow) 
	{
		var matchesStr = (dataLength == 1) ? " товар" : "";
		matchesStr = (dataLength > 1) && (dataLength < 5) ? " товара" : matchesStr;
		matchesStr = (dataLength > 4) ? " товаров" : matchesStr;

		if (dataLength > maxItemsToShow)
		{
			matchesStr = ', показано <b>' + maxItemsToShow + '</b>' + matchesStr;
		}

		var result = '<table><tr><td>Найдено <b>' + dataLength + '</b>' + matchesStr + "</td><td><a href='#'>Закрыть</a></td></tr></table>";
		
		return result;
	}

	function selectItem(li) 
	{
		if (li.extra[0]) 
		{
			window.location = '/catalog' + li.extra[3] + '/'+ li.extra[0] + '_' + li.extra[2] +'.html';
		}
	}
	
	$("#find_input").autocomplete("/modules/catalog/service/autocomplete.php", 
	{
		width:375,
		padding: Array(0, 0, 0, 0),
		delay:700,
		minChars:3,
		matchSubset:1,
		autoFill:false,
		matchContains:1,
		cacheLength:8,
		selectFirst:false,
		formatItem:liFormat,
		formatImage:imgFormat,
		formatCount: priceFormat,
		maxItemsToShow:8,
		onItemSelect:selectItem,
		resultsClass:"autocomplete_results",
		inputClass:"ac_input",
		loadingClass:"ac_loading",
		overClass:"over",
		overMouseClass:"over_mouse",
		//priceOverClass:"price_a",
		priceTag:"span",
		formatCloser:closerFormat,
                cellSeparator: '@@@',
                viewUp: false,
                offsetHeight: 4,
                offsetWidth: 0,
		viewRigth: false
	}); 
});
String.prototype.trim = function(charlist){
	charlist = !charlist ? " \s\xA0" : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, "\$1");
	var re = new RegExp("^[" + charlist + "]+|[" + charlist + "]+$", "g");
	return this.valueOf().replace(re, '');
}
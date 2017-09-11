"use strict";
/*
* Shipchung payment integrate
*/

(function (exports){

	var __data = {};
	var ShipChung = function (){
		
		var me  = this;
		this._elements 	= [];
		this.configs 	= {
			"global": {
				"style": "css/sc-style.css",
				"utils": "scripts/sc-utils.js",
				"popup": "scripts/sc-popup.js"
			},
			"scStylev1": '//api.shipchung.vn/v1.2/shipchung_style.css',
			"scStylev3": '//api.shipchung.vn/v1.3/shipchung_style.css',
			"templates": {
				"stylesheetUrL"	:	"assets/templates/{templateName}/style.css"
			}
		};
		this.options 	= {
			apiKey: '123'
		};
		/*this.SCProducts = {};*/

		this.buttonClass 		= 'shipchung';
		this._assetApi 			= '//services.shipchung.vn/sdk/'; //http://services.shipchung.vn/public/sdk/  - http://10.0.1.199/ussdtest/popup-lib/
		this.styleElement 		= document.createElement('style');
		this.styleElement.type 	= "text/css";
		this.scriptElement 		= document.createElement('script');
		this.scriptElement.type = "text/javascript";
		this.styleStr 			= "";
		this.scriptStr 			= "";
		this.listButtonTemplates = {};
		

		this.jx = {
			//Create a xmlHttpRequest object - this is the constructor. 
			getHTTPObject : function() {
				var http = false;
				//Use IE's ActiveX items to load the file.
				if(typeof ActiveXObject != 'undefined') {
					try {http = new ActiveXObject("Msxml2.XMLHTTP");}
					catch (e) {
						try {http = new ActiveXObject("Microsoft.XMLHTTP");}
						catch (E) {http = false;}
					}
				//If ActiveX is not available, use the XMLHttpRequest of Firefox/Mozilla etc. to load the document.
				} else if (window.XMLHttpRequest) {
					try {http = new XMLHttpRequest();}
					catch (e) {http = false;}
				}
				/*http.withCredentials = true;*/
				return http;
			},

			post : function (url, data, callback, format, contentType) {
				var http = this.getHTTPObject(); //The XMLHttpRequest object is recreated at every call - to defeat Cache problem in IE
				if(!http||!url) return;
				if (http.overrideMimeType) http.overrideMimeType('text/xml');

				if(!format) var format = "text";//Default return type is 'text'
				format = format.toLowerCase();
				
				//Kill the Cache problem in IE.
				var now = "t=" + new Date().getTime();
				url += (url.indexOf("?")+1) ? "&" : "?";
				url += now;

				

				http.open("POST", url, true);
				if(contentType){
					http.setRequestHeader('Content-Type', contentType);
				}else {
					data = JSON.stringify(data);
					http.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
				}
			    
    			/*http.setRequestHeader('Content-Length', data.length);*/


				http.onreadystatechange = function () {//Call a function when the state changes.
					if (http.readyState == 4) { //Ready State will be 4 when the document is loaded.
						if(http.status 	== 200) {
							var result = "";
							if(http.responseText) result = http.responseText;

							
							//If the return is in JSON format, eval the result before returning it.
							if(format.charAt(0) == "j") {
								//\n's in JSON string, when evaluated will create errors in IE
								try{
									result = result.replace(/[\n\r]/g,"");
									result = eval('('+result+')'); 
								}catch(err){
									 if(callback) callback(true, {});
									 return;
								}
								
							}
			
							//Give the data to the callback function.
							if(callback) callback(null,result);
						} else { //An error occured
							if(callback) callback(http.status, null);
						}
					}
				}
				http.send(data);
			},


			load : function (url,callback,format) {
				var http = this.getHTTPObject(); //The XMLHttpRequest object is recreated at every call - to defeat Cache problem in IE
				if(!http||!url) return;
				if (http.overrideMimeType) http.overrideMimeType('text/xml');

				if(!format) var format = "text";//Default return type is 'text'
				format = format.toLowerCase();
				
				//Kill the Cache problem in IE.
				var now = "t=" + new Date().getTime();
				url += (url.indexOf("?")+1) ? "&" : "?";
				url += now;

				http.open("GET", url, true);

				http.onreadystatechange = function () {//Call a function when the state changes.
					if (http.readyState == 4) { //Ready State will be 4 when the document is loaded.
						if(http.status == 200) {
							var result = "";
							if(http.responseText) result = http.responseText;
							
							//If the return is in JSON format, eval the result before returning it.

							if(format.charAt(0) == "j") {
								//\n's in JSON string, when evaluated will create errors in IE
								try{
									result = result.replace(/[\n\r]/g,"");
									result = eval('('+result+')'); 
								}catch(err){
									 if(callback) callback(true, {});
									 return;
								}
							}
			
							//Give the data to the callback function.
							if(callback) callback(null, result);
						} else { //An error occured
							if(callback) callback(http.status, null);
						}
					}
				}
				http.send(null);
			}
		};



		// Initial scripts  // 

		var eles = document.getElementsByClassName(this.buttonClass);

		// Lấy danh sách các template đựơc sử dụng

		for(var i = 0; i < eles.length; i++){
			this.listButtonTemplates[eles[i].getAttribute('data-sc-style') || 'default'] = true;
		}

		// Load các scripts và css liên quan
		this.initScript();
		this.handlerIframeEvent();
		
		
		setTimeout(function (){
			me.parseElements();
		}, 100);

	};




	
	// Load file style và append vaò dom
	ShipChung.prototype.loadStyle = function (link){
		var me = this,
			SCcssNode = document.createElement('link'),
			SC_headID = document.getElementsByTagName('head')[0];

		SCcssNode.type = 'text/css';
		SCcssNode.rel = 'stylesheet';
		SCcssNode.href = link;
		SCcssNode.media = 'screen';
		SC_headID.appendChild(SCcssNode);
	}

	// Load file script và append vaò dom
	ShipChung.prototype.loadScript = function (link){
		var me = this;
		var script = document.createElement('script');

		script.type = 'text/javascript';
		script.src = link;
		document.body.appendChild(script);

		script.onload = function (event){
			if(event.target.attributes[1].value.indexOf('sc-popup.js') >= 0){
				me.checkResult();
			}
		}
	}



	// Nhận và xử lý event giưã popup với window 
	ShipChung.prototype.handlerIframeEvent = function (){
		var me = this;

		var receiveMessage = function(event) {
		    if(event.data && event.data == 'close-popup'){
				exports.Popup.closePopup();
				return ;
		    }

		    if(event.data && event.data.toString() == "[object Object]"){
		    	switch (event.data.name){
		    		case 'closeAndRemovePopup': 
		    			exports.Popup.closeAndRemovePopup(event.data.data);
		    			break;
	    			case 'redirectUrl':
	    				window.location.href = event.data.data;
	    			break;
	    			case 'NLCheckoutLoad':
	    				/*exports.Popup.iframe.src = 'http://dantri.com';*/
	    			break;
	    			default:
	    			break;
		    	}
		    }
		}

		function scAddEvent(obj, type, SCfn) {
			if(obj.addEventListener) {
				obj.addEventListener(type, SCfn, false);
				return true;

			}else if (obj.attachEvent){
				obj['e' + type + SCfn] = SCfn;
				obj[type + SCfn] = function() { obj['e' + type + SCfn]( window.event );}
				var r = obj.attachEvent('on' + type, obj[type + SCfn]);
				return r;

			} else {
				obj['on' + type] = SCfn;
				return true;
			}
		}
		scAddEvent(window,"message",receiveMessage);
	}

	// Loading require scripts 
	ShipChung.prototype.initScript = function (callback){
		var SC_head   = document.getElementsByTagName("head")[0],
			me = this;
			SC_head.appendChild(me.styleElement);
			SC_head.appendChild(me.scriptElement);

			var resp = me.configs;
			var templates = resp['templates'];

			// load global scripts and stylesheet !
			for(var script in resp['global']){
				var path = resp['global'][script];

				// Update 3/4/15 : prevent method toJSONString() in jquery

				if(typeof path == 'function' || script == 'toJSONString'){continue;}; 

				var _path = path.split('.'),
					ext   = _path.reverse()[0];

					if(ext == 'css'){
						me.loadStyle(me._assetApi + path);
					}else {
						me.loadScript(me._assetApi + path);

					}
			}
			// Load style for template used 

			for(var template in me.listButtonTemplates){
				if(typeof me.listButtonTemplates[path] == 'function' || template == 'toJSONString'){continue;}; 
				var styleUrl   = resp['templates']['stylesheetUrL'].replace('{templateName}', template);
				me.loadStyle(me._assetApi + styleUrl);
			}
			
			typeof callback == 'undefined' || callback();
		
	}


	ShipChung.prototype.checkResult = function (){
		var urlParams = this.parseSCUrl();
		if(Object.keys(urlParams).length > 0 && urlParams.hasOwnProperty('SCcode')){
			SC.Popup.openPopup("result", {}, urlParams);
		}
	}
	
	ShipChung.prototype.parseSCUrl = function (){
		var urlArray = document.URL.split('#'),
			params = {};
		if(urlArray.length > 1){
			for (var i = urlArray.length - 1; i >= 0; i--) {
				if(urlArray[i].indexOf('SC') > -1){
					var _tmp = urlArray[i].split('=');
					params[_tmp[0]] = _tmp[1];
				}
			};
		}

		return params;
		
		
	}


	// Phân tích các button element của SC
	ShipChung.prototype.parseElements = function (){
		if(document.getElementsByTagName('shipchung').length > 0){
			this.loadStyle(this.configs['scStylev1']);
			this.parseElementV1();
		}else if(document.getElementById('sc-root')){
			this.loadStyle(this.configs['scStylev3']);
			this.parseElementsV3();
		}
		
		var eles = document.getElementsByClassName(this.buttonClass),
			elSize,
			elStyle,
			elImages,
			buttonText;

		for(var i = 0; i < eles.length; i ++){

			eles[i].id = 'sc-button-' + this.randomId();
			elSize     = 	eles[i].getAttribute('sc-size') 
						 || eles[i].getAttribute('data-sc-size') 
						 || 'medium';

			elStyle    = 	eles[i].getAttribute('sc-style') 
				 		 || eles[i].getAttribute('data-sc-style') 
						 || 'default';

			elImages   = 	eles[i].getAttribute('sc-image') 
						 || eles[i].getAttribute('data-sc-image') 
						 || null;

		 	buttonText = 	eles[i].getAttribute('sc-text') 
						 || eles[i].getAttribute('data-sc-text') 
						 || "Thanh toán và vận chuyển";


			var buttonHTML = "<button type='button' class='sc-button btn-size-" + elSize + " btn-style-" + elStyle + "'>" + buttonText + "</button>";

			if(elImages){

				eles[i].innerHTML += "<img src='" + elImages + "' class='sc-button-image btn-size-" + elSize + "'/>";
				__data[eles[i].id] = this.getItemsFromButton(eles[i].getElementsByTagName('img')[0], eles[i].id);
				this.fireEvent(eles[i].getElementsByTagName('img')[0], 'click');

			}else {

				eles[i].innerHTML += buttonHTML;
				__data[eles[i].id] = this.getItemsFromButton(eles[i].getElementsByTagName('button')[0], eles[i].id);
				this.fireEvent(eles[i].getElementsByTagName('button')[0], 'click');
			}
			this._elements.push(eles[i]);
		}

		return ;
	}

	ShipChung.prototype.parseElementV1 = function (){
		var me 					  = this;
		var SC_html_content       = '';
		var sc_stick_free_ship    = '&nbsp;';
		var sc_text_free_ship     = '';

		var sc_stick_free_cod     = '&nbsp;';
		var sc_text_free_cod      = '';

		var sc_stick_free_protected = '&nbsp;';
		var sc_text_free_protected  = '';

		var shipchung_frame_v2        = '';
		var sc_disable_li_ship        = '';
		var sc_disable_li_protected   = '';
		var sc_disable_li_cod         = '';
		var sc_title_nl_logo		  = '';



		var sc_button_size 		   = document.getElementsByTagName('shipchung')[0].getAttribute('size'); 
		var sc_button_type 		   = document.getElementsByTagName('shipchung')[0].getAttribute('type');
		var sc_button_method 		 = document.getElementsByTagName('shipchung')[0].getAttribute('method');
		var sc_button_id   		   = document.getElementsByTagName('shipchung')[0].getAttribute('id');

		var sc_button_nl_level	 = document.getElementsByTagName('shipchung')[0].getAttribute('level');
		var sc_button_nl_url	   = document.getElementsByTagName('shipchung')[0].getAttribute('nganluong_url');

		var sc_free_shipping     = document.getElementsByTagName('shipchung')[0].getAttribute('free_shipping'); 

		var sc_free_cod          = document.getElementsByTagName('shipchung')[0].getAttribute('free_cod');
		var sc_free_protected    = document.getElementsByTagName('shipchung')[0].getAttribute('free_protected');

		var sc_button_cod_option = document.getElementsByTagName('shipchung')[0].getAttribute('cood'); 

		var sc_button_cod			= (sc_button_cod_option && sc_button_cod_option!='undefined' && (sc_button_cod_option.toLowerCase() == 'yes' || sc_button_cod_option.toLowerCase() == '1')) ? 'cod' : 'pas';
		var sc_ajax_file_option   	= document.getElementsByTagName('shipchung')[0].getAttribute('ajax_file');
		var SC_AJAX					= (sc_ajax_file_option && sc_ajax_file_option!='undefined') ? sc_ajax_file_option : 'shipchung/ajax.php';
		var sc_tooltip 				= "<li>Tính phí vận chuyển Online hoàn toàn</li><li>Giao hàng thu tiền (CoD) toàn quốc</li><li>Hỗ trợ thanh toán trực tuyến qua NgânLượng.vn</li><li>Tra cứu hành trình hàng hóa mọi lúc mọi nơi</li><li>Hỗ trợ giải quyết khiếu nại chuyên nghiệp</li>";
		var SC_QUANTITY 			= document.getElementsByTagName('shipchung')[0].getAttribute('input_quantity');
		var SC_NOTE  			    = document.getElementsByTagName('shipchung')[0].getAttribute("input_note");
		if(sc_free_shipping && sc_free_shipping != 'undefined' && sc_free_shipping == 'yes')
		{
			sc_stick_free_ship      = '<i>&nbsp;</i>';
			sc_text_free_ship       = 'shipchung_text_free';      
		}
		else 
		{
			sc_disable_li_ship      = ' style="color: #ccc;"';
		}

		if(sc_free_cod && sc_free_cod != 'undefined' && sc_free_cod == 'yes')
		{
			sc_stick_free_cod      = '<i>&nbsp;</i>';
			sc_text_free_cod       = 'shipchung_text_free';
		}
		else 
		{
			sc_disable_li_cod      = ' style="color: #ccc;"';
		}

		if((sc_free_protected && sc_free_protected != 'undefined' && sc_free_protected == 'yes') || sc_button_nl_level)
		{
			sc_stick_free_protected      = '<i>&nbsp;</i>';
			sc_text_free_protected       = 'shipchung_text_free';
		}
		else 
		{
			sc_disable_li_protected      = ' style="color: #ccc;"';
			shipchung_frame_v2           = 'height: 90px;';
		}

		if(sc_button_type == 'detail') // Chi tiet
		{  
			if((sc_free_protected && sc_free_protected != 'undefined') || (sc_free_cod && sc_free_cod != 'undefined' && sc_free_cod == 'yes') || (sc_free_shipping && sc_free_shipping != 'undefined'))
			{
				if(sc_button_nl_level == 'gold'){
					sc_title_nl_logo = 'Ngân Lượng đảm bảo hạng Vàng';
				}
				else if(sc_button_nl_level == 'silver'){
					sc_title_nl_logo = 'Ngân Lượng đảm bảo hạng Bạc';
				}
				else if(sc_button_nl_level == 'diamond'){
					sc_title_nl_logo = 'Ngân Lượng đảm bảo hạng Kim Cương';
				}
				else{
					sc_title_nl_logo = 'ShipChung cổng giao vận toàn quốc hàng đầu Việt Nam';
				}

				var SC_html_content       = 
					    '<div style="' + shipchung_frame_v2 + '" class="shipchung_frame_v2 shipchung_full">'
					+   '   <span class="shipchung_title_v2">Thanh toán nhanh</span>'
					+   '   <a class="shipchung_logo" href="http://shipchung.vn" target="_blank">ShipChung.VN</a>'
					+   '   <a shipchung_ajax = "' + SC_AJAX + '" shipchung_type="'+ sc_button_type.toLowerCase() +'" shipchung_method="'+ sc_button_method.toLowerCase() +'" class="shipchung_' + sc_button_cod + '_button" href="javascript:void(0);" cod="' + ((sc_button_cod_option) ? sc_button_cod_option.toLowerCase() : '') + '" rel="' + sc_button_id + '" id="sc_event_click_button"></a>'
					+   '   <div id="ship_chung_nldb">'
					+   '   	<a class="shipchung_logo_nl shipchung_' + ((sc_button_nl_level) ? sc_button_nl_level.toLowerCase() : 'nonedb') + '" href="' + ((sc_button_nl_url) ? sc_button_nl_url : 'http://shipchung.vn')+ '" target="_blank" title="' + sc_title_nl_logo + '">' + sc_title_nl_logo + '</a>'
					+   '       <ul class="shipchung_config">'
					+   '           <li' + sc_disable_li_ship + '><span class="shipchung_checkbox">' + sc_stick_free_ship + '</span>&nbsp;<span class="' + sc_text_free_ship + '">Miễn phí</span> vận chuyển</li>'
					+   '           <li' + sc_disable_li_cod + '><span class="shipchung_checkbox">' + sc_stick_free_cod + '</span>&nbsp;<span class="' + sc_text_free_cod + '">Miễn phí</span> thu tiền tận nơi</li>'
					+   (((sc_free_protected && sc_free_protected != 'undefined' && sc_free_protected == 'yes') || sc_button_nl_level != 'undefined') ? '<li' + sc_disable_li_protected + '><span class="shipchung_checkbox">' + sc_stick_free_protected + '</span>&nbsp;<span class="' + sc_text_free_protected + '">Bảo hiểm</span> giao dịch</li>' : '')
					+   '       </ul>'
					+   '   </div>'
					+   '</div>';
			}
			else
			{ 
			var SC_html_content       = 
			'<div class="shipchung_frame_v2 shipchung_tiny">'
			+   '   <span class="shipchung_title_v2">Thanh toán nhanh</span>'
			+   '   <a class="shipchung_logo" href="http://shipchung.vn" target="_blank">ShipChung.VN</a>'
			+   '   <a shipchung_ajax = "' + SC_AJAX + '" shipchung_type="'+ sc_button_type.toLowerCase() +'" shipchung_method="'+ sc_button_method.toLowerCase() +'" class="shipchung_' + sc_button_cod + '_button" href="javascript:void(0);" cod="' + ((sc_button_cod_option) ? sc_button_cod_option.toLowerCase() : '') + '" rel=' + sc_button_id + ' id="sc_event_click_button"></a>'
			+   '   <div class="shipcchung_slogan">Thanh toán, giao hàng toàn quốc</div>'
			+   '</div>';
			}  
		}
		else if(sc_button_type == 'payment' && sc_button_cod == 'pas') // Gio hang PAS
		{
			var SC_html_content       = '<a shipchung_ajax = "' + SC_AJAX + '" shipchung_type="'+ sc_button_type.toLowerCase() +'" shipchung_method="'+ sc_button_method.toLowerCase() +'" class="shipchung_pas_button" href="javascript:();" cod="" rel="' + sc_button_id + '" id="sc_event_click_button"></a><br/>';
		}
		else if(sc_button_type == 'payment' && sc_button_cod == 'cod') // Gio hang COD
		{
			var SC_html_content       = '<a shipchung_ajax = "' + SC_AJAX + '" shipchung_type="'+ sc_button_type.toLowerCase() +'" shipchung_method="'+ sc_button_method.toLowerCase() +'" class="shipchung_cod_button" href="javascript:();" cod="' + ((sc_button_cod_option) ? sc_button_cod_option.toLowerCase() : '') + '" rel="' + sc_button_id + '" id="sc_event_click_button">Giao hàng & Thu tiền</a><br/>';
		}

		document.getElementsByTagName('shipchung')[0].innerHTML = SC_html_content;

		document.getElementById('sc_event_click_button').addEventListener('click', function (){
			SC.Popup.createIframeElement(sc_button_id);
		    var quantity = (document.getElementById(SC_QUANTITY)) ? parseInt(document.getElementById(SC_QUANTITY).value) : ((document.getElementsByName(SC_QUANTITY).length > 0 &&  parseInt(document.getElementsByName(SC_QUANTITY)[0].value) > 0) ? parseInt(document.getElementsByName(SC_QUANTITY)[0].value) : 1);
			me.jx.post(SC_AJAX, 'id='+sc_button_id+'&quantity='+quantity, function (err, resp){
				if(!err){
					if(resp.result_code !== 100){
						SC.Popup.iframe.src = resp.data.SCFrameUrl;
						SC.Popup.popup.appendChild(SC.Popup.iframe);
						document.body.appendChild(SC.Popup.popup);
						SC.Popup.popup.className	= "sc-popup-wrap";
					}else {
						alert(resp.result_description);
						SC.Popup.closePopup();
					}
				}
			}, 'json', 'application/x-www-form-urlencoded');

			// Tạo backdrop
			SC.Popup.createBackdrop();
			// Lắng nghe sự kiện resize của window và điểu chỉnh lại vị trí của popup 
			SC.Popup.iframeResizeHander();
		});
	} // End parse element v1



	ShipChung.prototype.parseElementsV3 = function (){
		var me 					= this,
			buttonEl 			= document.getElementById('sc-root'),
			SC_ID 				= buttonEl.getAttribute('data-id') || buttonEl.getAttribute('id'),
			SC_INPUT_QUANTITY 	= buttonEl.getAttribute('input_quantity'),
			SC_AJAX_FILE 		= buttonEl.getAttribute('ajax_file');

		var BUTTON_HTML = '<div class="shipchung_frame_v2 shipchung_tiny">' 
				+ '<span class="shipchung_title_v2">Thanh toán nhanh</span>'
				+ '<a class="shipchung_logo" href="http://shipchung.vn" target="_blank">ShipChung.VN</a>'
				+ '<span shipchung_type="detail" shipchung_method="ajax" style="cursor: pointer;" class="shipchung_pas_button" cod="" rel="' + SC_ID + '"></span>'
				+ '<div class="shipcchung_slogan">Thanh toán, giao hàng toàn quốc</div>'
			    + '</div>';

		buttonEl.innerHTML = BUTTON_HTML;

	    // Handler Event
	    document.getElementsByClassName('shipchung_pas_button')[0].addEventListener('click', function (){
	    	
			SC.Popup.createIframeElement(SC_ID);
			// Get quantity by input id or input name

			var SC_QUANTITY  = (document.getElementById(SC_INPUT_QUANTITY)) ? parseInt(document.getElementById(SC_INPUT_QUANTITY).value) : ((document.getElementsByName(SC_INPUT_QUANTITY).length > 0 &&  parseInt(document.getElementsByName(SC_INPUT_QUANTITY)[0].value) > 0) ? parseInt(document.getElementsByName(SC_INPUT_QUANTITY)[0].value) : 1);

			me.jx.post(SC_AJAX_FILE, 'id='+SC_ID+'&quantity='+SC_QUANTITY, function (err, resp){
				if(!err){
					if(resp.result_code !== 100){
						SC.Popup.iframe.src = resp.data.SCFrameUrl;
						SC.Popup.popup.appendChild(SC.Popup.iframe);
						document.body.appendChild(SC.Popup.popup);
						SC.Popup.popup.className = "sc-popup-wrap";
					}else {
						alert(resp.result_description);
						SC.Popup.closePopup();
					}
				}
			}, 'json', 'application/x-www-form-urlencoded');

			// Tạo backdrop
			SC.Popup.createBackdrop();
			// Lắng nghe sự kiện resize của window và điểu chỉnh lại vị trí của popup 
			SC.Popup.iframeResizeHander();
		});
	}




	ShipChung.prototype.getItemsFromButton = function (buttonEl, data_id){
		var itemElements = buttonEl.parentNode.getElementsByTagName('items');
		var self 	= this;
		var sendData = {
		    "Order"			: {
		        "ProductName" 	: "",
		        "Weight" 		: 0,
		        "Quantity" 		: 0,
		        "Amount" 		: 0
		    },
		    "Item" 			: []
		};

		var orderNameArr = [];

		for(var i = 0; i < itemElements.length; i ++){
			
			try{
				var productName,
					productPrice,
					productImage,
					productLink,
					productWeight,
					productQuantity;
					
					if(itemElements[i].hasAttribute('item-quantity') || itemElements[i].hasAttribute('item-quantity-id')){
						productQuantity = itemElements[i].getAttribute('item-quantity') || itemElements[i].getAttribute('item-quantity-id');
						console.log(itemElements[i].getAttribute('item-quantity') || itemElements[i].getAttribute('item-quantity-id'));
					}else{
						alert('Vui lòng kiểm tra trường quantity');
						return;
					}


					if(itemElements[i].hasAttribute('item-name')){
						productName 	=	itemElements[i].getAttribute('item-name');
					}else{
						alert('Vui lòng kiểm tra trường name');
						return ;
					}


					if(itemElements[i].hasAttribute('item-price')){
						productPrice 	=	itemElements[i].getAttribute('item-price');
					}else{
						alert('Vui lòng kiểm tra trường price');
						return ;
					}


					if(itemElements[i].hasAttribute('item-image')){
						productImage 	=	itemElements[i].getAttribute('item-image');
					}else{
						alert('Vui lòng kiểm tra trường image');
						return ;
					}

					if(itemElements[i].hasAttribute('item-link')){
						productLink 	=	itemElements[i].getAttribute('item-link');
					}else{
						alert('Vui lòng kiểm tra trường link');
						return ;
					}

					if(itemElements[i].hasAttribute('item-weight')){
						productWeight 	=	itemElements[i].getAttribute('item-weight');
					}else{
						alert('Vui lòng kiểm tra trường weight');
						return ;
					}

					if(itemElements[i].hasAttribute('item-quantity-id')){
						var productQuantityEl = document.getElementById(itemElements[i].getAttribute('item-quantity-id'));

						if(productQuantityEl && productQuantityEl.value < 1){
							alert('Vui lòng kiểm tra trường quantity');
							return;
						}

						productQuantityEl.addEventListener('change', function (){

							var ListOrderItem = __data[data_id]['Item'];



							__data[data_id]['Order']['Amount'] = 0;
							__data[data_id]['Order']['Quantity'] = 0;
							__data[data_id]['Order']['Weight'] = 0;

							for (var h = ListOrderItem.length - 1; h >= 0; h--) {
								if(ListOrderItem[h].Name == productName){
									__data[data_id]['Item'][h]["Quantity"] = productQuantityEl.value;
								}

								__data[data_id]['Order']['Quantity'] += parseInt(__data[data_id]['Item'][h]["Quantity"]);
								__data[data_id]['Order']['Weight'] += parseInt(__data[data_id]['Item'][h]["Weight"]);
								__data[data_id]['Order']['Amount'] += parseInt(__data[data_id]['Item'][h]["Price"]) * parseInt(__data[data_id]['Item'][h]["Quantity"]);
							};


						});

						productQuantity = productQuantityEl.value;
					}

					sendData['Item'].push({
						"Name" 		: productName,
			            "Quantity" 	: productQuantity,
			            "Price" 	: productPrice,
			            "Image" 	: productImage,
			            "Link" 		: productLink,
			            "Weight" 	: productWeight,
					});

					orderNameArr.push(productName);
					sendData['Order']['Weight'] 	 += parseInt(productWeight);
					sendData['Order']['Amount'] 	 += (parseInt(productPrice) * parseInt(productQuantity));
					sendData['Order']['Quantity'] 	 += parseInt(productQuantity);

			}catch(err){
				console.log(err);
				alert('Lỗi ! vui lòng kiểm tra dữ liệu đầu vào .');
				break;
			}
		}

		
		for (var i = itemElements.length - 1; i >= 0; i--) {
			buttonEl.parentNode.removeChild(itemElements[i]);
		};


		sendData['Order']['ProductName'] = orderNameArr.join(' ,');
		sendData['Order']['Quantity'] = sendData['Item'].length;
		sendData['Domain'] = window.location.host;
		sendData['ReturnUrl'] = document.URL;
		return sendData;
	}

	ShipChung.prototype.fireEvent  = function (el, ev, callback){
		var scRef   = el.parentNode.id.split("-")[2],
			me 		= this,
			Items;

		el.addEventListener(ev, function (){
			if(__data.hasOwnProperty(el.parentNode.id)){
				Items = __data[el.parentNode.id];
			}else {
				alert('Lỗi dữ liệu đầu vào, vui lòng kiểm tra lại');
				return;
			}
			
			if(Items){
				SC.Popup.openPopup(scRef, Items);

			}
			
			typeof callback == 'undefined' || callback();
		})
	}
	// Generate random id 
	ShipChung.prototype.randomId = function (){
		var str = "asdfghjklzxcvbnmqwertyuiop0987654321"+ new Date().getTime(),
			newId = "";
		for(var i = 0; i < 8; i++){
			newId += str[Math.floor((Math.random() * (str.length - 1)) + 1)];
		}

		return newId;
	}
	// Extend object 
	ShipChung.prototype.extend = function (dst) {
		for (var i = 1, ii = arguments.length; i < ii; i++) {
			var obj = arguments[i];
			if (obj) {
				var keys = Object.keys(obj);
				for (var j = 0, jj = keys.length; j < jj; j++) {
					var key = keys[j];
					dst[key] = obj[key];
				}
			}
		}
		return dst;
	}
	


	if(typeof exports.Button == 'undefined'){
		exports.Button = new ShipChung();
	}

})(typeof window.SC == 'undefined' ? window.SC = {} : window.SC);



"use strict";
(function (exports){
	
	var ShipChung = function (){
		var me 					= this;
		this._popupList 		= {};
		this.version 			= '0.0.0';
		this.backdrop 			= document.createElement('div');
		this.backdrop.className = "sc-popup-backdrop";
		this.loadingIcon = new Image();
		this.loadingIcon.src = 'http://api.shipchung.vn/v1.2/loader.gif';
		this.urlResult 		  = "http://services.shipchung.vn/sdk/popup/index.html#/result/";

		this.backdrop.appendChild(this.loadingIcon);


		// Tắt popup khi click vào backdrop 

		this.backdrop.addEventListener('click', function (event){
			event.preventDefault();
			me.closePopup();
		});

		document.onkeydown = function(evt) {
		    if(evt.keyCode == 27){
		    	/*me.closePopup();*/
		    }
		}
	};

	ShipChung.prototype.closeAndRemovePopup = function (id){
		var popup = document.getElementById('sc-popup-' + id);
		if(!popup){
			this.closeAllPopup();
		}else {
			document.body.removeChild(popup);
			document.body.removeChild(this.backdrop);
		}
		
	}


	ShipChung.prototype.closePopup = function(){
		// Lặp tất cả các popup elements gán clas sc-popup-hidden 
		var listPopup = document.getElementsByClassName('sc-popup-wrap');
		for(var i = 0; i < listPopup.length; i++) {
			listPopup[i].style.opacity = 0;
			listPopup[i].className = "sc-popup-wrap sc-popup-hidden";
			
		}
		document.body.removeChild(this.backdrop); // Xóa backdrop element 
	};

	ShipChung.prototype.closeAllPopup = function(){
		// Lặp tất cả các popup elements gán clas sc-popup-hidden 
		var listPopup = document.getElementsByClassName('sc-popup-wrap');
		for(var i = 0; i < listPopup.length; i++) {
			document.body.removeChild(listPopup[i]);
		}
		document.body.removeChild(this.backdrop); // Xóa backdrop element 
	};

	ShipChung.prototype.createIframeElement = function (scUniq){
			var me 			= this;
			this.popup 		= document.createElement('div');
			this.iframe		= document.createElement("iframe");
			this.popup.style.opacity = 1;
			this.popup.id	= "sc-popup-"+ scUniq;
			this.popup.style.width	= "100%";
			this.popup.style.height	= "500px";

			this.popup.style.top = function (){
				var _top = (window.innerHeight - parseInt(me.popup.style.height)) / 2;
				 return _top > 0 ? _top : 0;
			}() + 'px';

			
			this.iframe.frameBorder 		= 0;
			this.iframe.border 				= 0;
			this.iframe.scrolling 			= "no";
			this.iframe.allowTransparency 	= "true";
			this.iframe.width 				= "100%";
			this.iframe.height 				= "100%";
			if(scUniq){
				this.popup.id 					= "sc-popup-" + scUniq;
			}else 
			{
				this.popup.id 				= SC.Button.randomId();
			}
			
	}

	ShipChung.prototype.createBackdrop = function (){
		var me = this;
		if(document.getElementsByClassName('sc-popup-backdrop').length == 0){
			document.body.appendChild(this.backdrop);
			var loadingEl 					= this.backdrop.getElementsByTagName('img')[0];

			if(loadingEl){
				loadingEl.style.position	= "fixed";
				loadingEl.style.width		= "128px";
				loadingEl.style.top 		= (parseInt(this.popup.style.top) + 40) + 'px';

				loadingEl.style.left= function (){
					var _top = (window.innerWidth - parseInt(loadingEl.style.width)) / 2;
					return _top > 0 ? _top : 0;
				}() + 'px';
			}
		}
	}

	ShipChung.prototype.iframeResizeHander = function(){
		var loadingEl = this.backdrop.getElementsByTagName('img')[0];
		window.addEventListener('resize', function (){
			loadingEl.style.left= function (){
					var _top = (window.innerWidth - parseInt(loadingEl.style.width)) / 2;
					return _top > 0 ? _top : 0;
				}() + 'px';
				
			var listPopup = document.getElementsByClassName('sc-popup-wrap');

			for(var i = 0; i < listPopup.length; i++){
				listPopup[i].style.top = function (){
					var _top = (window.innerHeight - parseInt(listPopup[i].style.height)) / 2;
					return _top > 0 ? _top : 0;
				}() + 'px';

				loadingEl.style.top = parseInt(listPopup[i].style.top) + 3;				
			}
		})
	}



	ShipChung.prototype.openPopup = function (scUniq, scProduct, scResult){
		// Nếu popup đã tồn tại trên dom thì remove class sc-popup-hidden
		if(document.getElementById('sc-popup-' + scUniq)){
			document.getElementById('sc-popup-' + scUniq).className = "sc-popup-wrap";
			document.getElementById('sc-popup-' + scUniq).style.opacity = 1;

		}else {
			// Taọ element mới trên dom 
			var me = this;
			this.createIframeElement(scUniq);

			if(!scResult){
				SC.Button.jx.post(SDK_URI, scProduct , function (err, resp){
					if(!err){
						if(!resp.error){
							me.iframe.src = resp.data.SCFrameUrl;
							me.popup.appendChild(me.iframe);
							document.body.appendChild(me.popup);
							me.popup.className	= "sc-popup-wrap";
						}else {
							alert('You don\'t have permission , please check API key or contact to administrator !');
							me.closePopup();
						}
					}
				}, 'json');
			}else {
				me.iframe.src = this.urlResult + scResult['SCcode'];
				me.popup.appendChild(me.iframe);
				document.body.appendChild(me.popup);
				me.popup.className	= "sc-popup-wrap";
			}
			
			this.iframe.onload = function (){
				me.loadingIcon.style.display = "none";
			}
		}



		// Tạo backdrop
		this.createBackdrop();


		// Lắng nghe sự kiện resize của window và điểu chỉnh lại vị trí của popup 

		this.iframeResizeHander();
		
	};



	if(typeof exports.Popup == 'undefined'){
		exports.Popup = new ShipChung();
	}

})(typeof window.SC == 'undefined' ? window.SC = {} : window.SC);

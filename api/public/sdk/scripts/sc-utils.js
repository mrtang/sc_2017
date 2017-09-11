"use strict";



(function (exports){
	
	var ShipChung = function (){
		var me = this;
	};

	/*
	* Extends object 
	*/
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
	


	if(typeof exports.Utils == 'undefined'){
		exports.Utils = new ShipChung();
	}

})(typeof window.SC == 'undefined' ? window.SC = {} : window.SC);

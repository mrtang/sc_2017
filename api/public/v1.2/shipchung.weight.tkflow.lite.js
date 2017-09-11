/*	jQuery API ShipChung.VN
	Author by KienNT
	Email: kiennt@peacesoft.net
	Develop by 1Top.vn & ShipChung.vn Team
*/
document.createElement("sc_weight");

var sc_api_weight = 'http://api.shipchung.vn/shipchung_api_weight.php';

var css = "";
    lj = document.createElement("style");
	css += ".sc_button_weight {background: url('http://api.shipchung.vn/v1.1/button_weight.png') no-repeat scroll -3px -4px transparent;border: medium none;border-radius: 10px;height: 30px;overflow: hidden;width: 106px;z-index : 99999;}";
	css += ".sc_button_weight:active {background: url('http://api.shipchung.vn/v1.1/button_weight.png') no-repeat scroll -3px -40px transparent;}";
	css += "#sc_button_submit_weight{background: url('http://api.shipchung.vn/v1.1/button_weight.png') no-repeat scroll -256px -4px transparent;border: medium none;height: 24px;left: 100px;overflow: hidden;position: absolute;top: 30px;width: 35px;}";
	css += "#sc_button_submit_weight:active{background: url('http://api.shipchung.vn/v1.1/button_weight.png') no-repeat scroll -256px -33px transparent;}";
	css += ".sc_popup_weight{background: url('http://api.shipchung.vn/v1.1/button_weight.png') no-repeat scroll -115px -2px transparent;height: 73px;left: -20px;position: absolute;top: -75px;width: 137px;z-index: 9999;";
	lj.type = "text/css";
	if (lj.styleSheet) {
		lj.styleSheet.cssText = css;
	} 
	else {
		lj.appendChild(document.createTextNode(css));
	}
document.getElementsByTagName("head")[0].appendChild(lj);

$(document).ready(function() {
	var overlay = $("<div id='sc_main_overlay' style='position: fixed;z-index:100;top: 0px;left: 0px;height:100%;width:100%;background: #000;opacity:0.1;'>&nbsp;</div>");
	var sc_popuphtml = '<div class="sc_popup_weight" id="sc_popup_weight"><input id="sc_button_input_weight" placeholder="0.00" type="text" value="" style="background: none repeat scroll 0 0 transparent;border: medium none;left: 8px;outline: medium none;position: absolute;text-align: right;top: 32px;width: 85px;"/><input id="sc_button_submit_weight" rel="" type="submit" value=""/></div>';
	
    // Open popup update weight
	$('.sc_button_weight').sc_query('click',function(e){
	    if(!MerchantToken){
            alert('Bạn khai báo thiếu MerchantToken!');
            return false;
        }
        else if(MerchantToken.length != 32){
            alert('MerchantToken sai định dạng!');
            return false;
        }
        sc_weight_id 		= $(this).attr("rel");
                
		$("body").append(overlay);
		$("#sc_main_overlay").fadeTo(200, 0.1);
		$("#sc_show_popup_" + sc_weight_id).append(sc_popuphtml).show();
		$('#sc_button_submit_weight').attr("rel",$(this).attr("rel"));
        
        $.ajax({
    		url: sc_api_weight,
    		data: {
                token     : MerchantToken,
                id        : $(this).attr("rel")
            },
            type: "post",
            timeout: 3000,
    		dataType: "jsonp",
    		jsonp : "kiennt",
    		jsonpCallback: '_weight'
   		});
        
        e.stopPropagation();
	});
	
	// Click accept weight
	$('input#sc_button_submit_weight').sc_query('click',function(){
		sc_value_weight = $('input#sc_button_input_weight').val();
        if(!MerchantToken){
            alert('Bạn khai báo thiếu MerchantToken!');
            return false;
        }
        else if(MerchantToken.length != 32){
            alert('MerchantToken sai định dạng!');
            return false;
        }
        else if(!sc_value_weight > 0){
            alert('Bạn chưa nhập khối lượng mới!');
            return false;
        }
        $.ajax({
    		url: sc_api_weight,
    		data: {
                token     : MerchantToken,
                id        : $(this).attr('rel'),
                weight    : sc_value_weight
            },
            type: "post",
            timeout: 3000,
    		dataType: "jsonp",
    		jsonp : "kiennt",
    		jsonpCallback: '_result'
   		});
	});

	//Click to close form & mark
	$("#sc_main_overlay").sc_query('click',function(){
			$('#sc_popup_weight,#sc_main_overlay').remove();
	});
		
	// Write button
	$('sc_weight').replaceWith(function(){
		sc_weight_lang 		= $('sc_weight').attr("lang");
		sc_weight_id 		= $('sc_weight').attr("id");
		button_weight_html  = '<div class="sc_update_weight" style="position: relative;"><span id="sc_show_popup_'+ sc_weight_id +'"></span>';
		button_weight_html += '<input class="sc_button_weight" rel="'+ sc_weight_id +'" type="submit" value=""/>';
		button_weight_html += '</div>';
	  return button_weight_html;
	});

});

$(document).keypress(function(e) {
    if (e.keyCode == 27){
        $('#sc_popup_weight,#sc_main_overlay').remove();
    };
});
      
// callback function
function _result(data) { 
        alert(data.error);
    if(data.code == '00'){
        $('#sc_popup_weight,#sc_main_overlay').remove();
    }
    else{
        $('#sc_button_input_weight').focus();
    }
}

function _weight(data) {
    $('#sc_button_input_weight').focus(); 
    if(data.code == '00'){
        $('#sc_button_input_weight').val(data.weight);
    }
}

(function($) {
$.extend($.fn, {
	sc_query: function(type, fn, fn2) {
		var self = this, q;

		// Handle different call patterns
		if ($.isFunction(type))
			fn2 = fn, fn = type, type = undefined;

		// See if Live Query already exists
		$.each( $.sc_query.queries, function(i, query) {
			if ( self.selector == query.selector && self.context == query.context &&
				type == query.type && (!fn || fn.$lqguid == query.fn.$lqguid) && (!fn2 || fn2.$lqguid == query.fn2.$lqguid) )
					// Found the query, exit the each loop
					return (q = query) && false;
		});

		// Create new Live Query if it wasn't found
		q = q || new $.sc_query(this.selector, this.context, type, fn, fn2);

		// Make sure it is running
		q.stopped = false;

		// Run it immediately for the first time
		q.run();

		// Contnue the chain
		return this;
	},

	expire: function(type, fn, fn2) {
		var self = this;

		// Handle different call patterns
		if ($.isFunction(type))
			fn2 = fn, fn = type, type = undefined;

		// Find the Live Query based on arguments and stop it
		$.each( $.sc_query.queries, function(i, query) {
			if ( self.selector == query.selector && self.context == query.context &&
				(!type || type == query.type) && (!fn || fn.$lqguid == query.fn.$lqguid) && (!fn2 || fn2.$lqguid == query.fn2.$lqguid) && !this.stopped )
					$.sc_query.stop(query.id);
		});

		// Continue the chain
		return this;
	}
});

$.sc_query = function(selector, context, type, fn, fn2) {
	this.selector = selector;
	this.context  = context;
	this.type     = type;
	this.fn       = fn;
	this.fn2      = fn2;
	this.elements = [];
	this.stopped  = false;

	// The id is the index of the Live Query in $.sc_query.queries
	this.id = $.sc_query.queries.push(this)-1;

	// Mark the functions for matching later on
	fn.$lqguid = fn.$lqguid || $.sc_query.guid++;
	if (fn2) fn2.$lqguid = fn2.$lqguid || $.sc_query.guid++;

	// Return the Live Query
	return this;
};

$.sc_query.prototype = {
	stop: function() {
		var query = this;

		if ( this.type )
			// Unbind all bound events
			this.elements.unbind(this.type, this.fn);
		else if (this.fn2)
			// Call the second function for all matched elements
			this.elements.each(function(i, el) {
				query.fn2.apply(el);
			});

		// Clear out matched elements
		this.elements = [];

		// Stop the Live Query from running until restarted
		this.stopped = true;
	},

	run: function() {
		// Short-circuit if stopped
		if ( this.stopped ) return;
		var query = this;

		var oEls = this.elements,
			els  = $(this.selector, this.context),
			nEls = els.not(oEls);

		// Set elements to the latest set of matched elements
		this.elements = els;

		if (this.type) {
			// Bind events to newly matched elements
			nEls.bind(this.type, this.fn);

			// Unbind events to elements no longer matched
			if (oEls.length > 0)
				$.each(oEls, function(i, el) {
					if ( $.inArray(el, els) < 0 )
						$.event.remove(el, query.type, query.fn);
				});
		}
		else {
			// Call the first function for newly matched elements
			nEls.each(function() {
				query.fn.apply(this);
			});

			// Call the second function for elements no longer matched
			if ( this.fn2 && oEls.length > 0 )
				$.each(oEls, function(i, el) {
					if ( $.inArray(el, els) < 0 )
						query.fn2.apply(el);
				});
		}
	}
};

$.extend($.sc_query, {
	guid: 0,
	queries: [],
	queue: [],
	running: false,
	timeout: null,

	checkQueue: function() {
		if ( $.sc_query.running && $.sc_query.queue.length ) {
			var length = $.sc_query.queue.length;
			// Run each Live Query currently in the queue
			while ( length-- )
				$.sc_query.queries[ $.sc_query.queue.shift() ].run();
		}
	},

	pause: function() {
		// Don't run anymore Live Queries until restarted
		$.sc_query.running = false;
	},

	play: function() {
		// Restart Live Queries
		$.sc_query.running = true;
		// Request a run of the Live Queries
		$.sc_query.run();
	},

	registerPlugin: function() {
		$.each( arguments, function(i,n) {
			// Short-circuit if the method doesn't exist
			if (!$.fn[n]) return;

			// Save a reference to the original method
			var old = $.fn[n];

			// Create a new method
			$.fn[n] = function() {
				// Call the original method
				var r = old.apply(this, arguments);

				// Request a run of the Live Queries
				$.sc_query.run();

				// Return the original methods result
				return r;
			}
		});
	},

	run: function(id) {
		if (id != undefined) {
			// Put the particular Live Query in the queue if it doesn't already exist
			if ( $.inArray(id, $.sc_query.queue) < 0 )
				$.sc_query.queue.push( id );
		}
		else
			// Put each Live Query in the queue if it doesn't already exist
			$.each( $.sc_query.queries, function(id) {
				if ( $.inArray(id, $.sc_query.queue) < 0 )
					$.sc_query.queue.push( id );
			});

		// Clear timeout if it already exists
		if ($.sc_query.timeout) clearTimeout($.sc_query.timeout);
		// Create a timeout to check the queue and actually run the Live Queries
		$.sc_query.timeout = setTimeout($.sc_query.checkQueue, 20);
	},

	stop: function(id) {
		if (id != undefined)
			// Stop are particular Live Query
			$.sc_query.queries[ id ].stop();
		else
			// Stop all Live Queries
			$.each( $.sc_query.queries, function(id) {
				$.sc_query.queries[ id ].stop();
			});
	}
});

// Register core DOM manipulation methods
$.sc_query.registerPlugin('append', 'prepend', 'after', 'before', 'wrap', 'attr', 'removeAttr', 'addClass', 'removeClass', 'toggleClass', 'empty', 'remove', 'html');

// Run Live Queries when the Document is ready
$(function() { $.sc_query.play(); });
})(jQuery);
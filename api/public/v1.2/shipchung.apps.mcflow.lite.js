/*	jQuery API ShipChung.VN
	Author by KienNT
	Email: kiennt@peacesoft.net
	Develop by 1Top.vn & ShipChung.vn Team
*/
if(window.MooTools){
    jQuery.noConflict();
}
document.createElement("shipchung");
if (typeof SC == "undefined" || !SC) {
  var SC = {};
}
SC.apps = SC.apps || {};
(function () {
  var ll1 = {
    trigger: null,
    url: null
  };
  SC.apps.MCFlow = function (lll) {
    var jj = this;
    jj.UI = {};
    jj._lj(lll);
    jj.setTrigger = function (ll) {
      jj._l1(ll);
    };
    jj.startFlow = function (url) {
        jQuery('object').hide();
      var il = jj._li();
      if (il.location) {
        il.location = url;
      } else {
        il.src = url;
      }
    };
    jj.closeFlow = function () {
      jj._i();
    };
    jj.isOpen = function () {
      return jj.isOpen;
    };
  };
  SC.apps.MCFlow.prototype = {
    name: "SC_Popup_iFrame",
    isOpen: false,
    _lj: function (lll) {
      if (lll) {
        for (var key in ll1) {
          if (typeof lll[key] !== "undefined") {
            this[key] = lll[key];
          } else {
            this[key] = ll1[key];
          }
        }
      }
      if (this.trigger) {
        this._l1(this.trigger);
      }
      this._ij();
    },
    _li: function () {
      this._ii();
      this._j();
      this._ll();
      this._il();
      this.isOpen = true;
      return this.UI.ll;
    },
    _ii: function () {
      this.UI.l1 = document.createElement("div");
      this.UI.l1.id = this.name;
      this.UI.li = document.createElement("div");
      this.UI.li.className = "shipchung_panel";
      this.UI.close = document.createElement("div");
      this.UI.close.className = "close";
      try {
        this.UI.ll = document.createElement("<iframe name=\"" + this.name + "\">");
      } catch (e) {
        this.UI.ll = document.createElement("iframe");
        this.UI.ll.name = this.name;
        this.UI.ll.setAttribute("id", "iframe_sc");
      }
      this.UI.ll.frameBorder = 0;
      this.UI.ll.border = 0;
      this.UI.ll.scrolling = "no";
      this.UI.ll.allowTransparency = "true";
      this.UI.i1 = document.createElement("div");
      this.UI.i1.className = "mask";
      this.UI.li.appendChild(this.UI.close);
      this.UI.li.appendChild(this.UI.ll);
      this.UI.l1.appendChild(this.UI.i1);
      this.UI.l1.appendChild(this.UI.li);
      document.body.appendChild(this.UI.l1);
    },
    _j: function () {
      var windowWidth, windowHeight, scrollWidth, scrollHeight, width, height;
      if (window.innerHeight && window.scrollMaxY) {
        scrollWidth = window.innerWidth + window.scrollMaxX;
        scrollHeight = window.innerHeight + window.scrollMaxY;
      } else if (document.body.scrollHeight > document.body.offsetHeight) {
        scrollWidth = document.body.scrollWidth;
        scrollHeight = document.body.scrollHeight;
      } else {
        scrollWidth = document.body.offsetWidth;
        scrollHeight = document.body.offsetHeight;
      }
      if (window.innerHeight) {
        windowWidth = window.innerWidth;
        windowHeight = window.innerHeight;
      } else if (document.documentElement && document.documentElement.clientHeight) {
        windowWidth = document.documentElement.clientWidth;
        windowHeight = document.documentElement.clientHeight;
      } else if (document.body) {
        windowWidth = document.body.clientWidth;
        windowHeight = document.body.clientHeight;
      }
      var maxLikelyScrollbarWidth = 25; // It's 20px on my IE
      var hscroll = jQuery(document).width() > jQuery(window).width() + maxLikelyScrollbarWidth;
      var widthNoScrollbar = hscroll ? jQuery(document).width() : jQuery(window).width();
      width = windowWidth > scrollWidth ? windowWidth : scrollWidth;
      height = windowHeight > scrollHeight ? windowHeight: scrollHeight;
     
      this.UI.i1.style.width = widthNoScrollbar + "px";
      this.UI.i1.style.height = height + "px";

    },
    _ll: function (e) {
      var width, height, scrollY;
      if (window.innerWidth) {
        width = window.innerWidth;
        height = window.innerHeight;
        scrollY = window.pageYOffset;
      } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
        width = document.documentElement.clientWidth;
        height = document.documentElement.clientHeight;
        scrollY = document.documentElement.scrollTop;
      } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
        width = document.body.clientWidth;
        height = document.body.clientHeight;
        scrollY = document.body.scrollTop;
      }
      var maxLikelyScrollbarWidth = 25; // It's 20px on my IE
      var hscroll = jQuery(document).width() > jQuery(window).width() + maxLikelyScrollbarWidth;
      var widthNoScrollbar = hscroll ? jQuery(document).width() : jQuery(window).width();

      //this.UI.li.style.left = Math.round((widthNoScrollbar - this.UI.ll.offsetWidth) / 2) + "px";
      this.UI.li.style.left = "0px";
      var ij = Math.round((height - this.UI.ll.offsetHeight) / 2) + scrollY;
      if (ij < 5) {
        ij = 10;
      }
      this.UI.li.style.top = (ij + 30) + "px";
      
      this.UI.li.style.width = widthNoScrollbar + "px";
    },
    _il: function () {
      il(this.UI.close, "click", this._i, this);
      il(window, "resize", this._j, this);
      il(window, "resize", this._ll, this);
      il(window, "unload", this._i, this);
    },
    _l1: function (ll) {
      ll = document.getElementById(ll);
      if (ll && ll.form) {
        ll.form.target = this.name;
      } else if (ll && ll.tagName.toLowerCase() == "a") {
        ll.target = this.name;
      }
      il(ll, "click", this._i1, this);
    },
    _i1: function (e) {
      var il = this._li();
      if (this.url != null) {
        if (il.location) {
            il.location = url;
        } else {
            il.src = url;
        }
      }
    },
    _i: function (e) {
      if (this.isOpen && this.UI.l1.parentNode) {
        this.UI.l1.parentNode.removeChild(this.UI.l1);
      }
      jl(window, "resize", this._j);
      jl(window, "resize", this._ll);
      jl(window, "unload", this._i);
      this.isOpen = false;
    },
    _ij: function () {
      var css = "",
        lj = document.createElement("style");
      css += "#" + this.name + " { z-index:20002; position:absolute; top:0; left:0; }";
      css += "#" + this.name + " .shipchung_panel { z-index:20003; position:relative;}";
      css += "#" + this.name + " .shipchung_panel iframe { width:100%; height: 720px;border:0;  background: url(" + SC_DOMAIN_API + "/loader.gif) no-repeat scroll 50% 10%;}";
      css += "#" + this.name + " .shipchung_panel .close { width:24px; height:24px; border:0; display:block; position:absolute; margin-left:560px; cursor:pointer; display:none}";
      css += "#" + this.name + " .mask { z-index:20001; position:absolute; top:0; left:0; background-color:#000; opacity:0.6; filter:alpha(opacity=60); }";
      lj.type = "text/css";
      if (lj.styleSheet) {
        lj.styleSheet.cssText = css;
      } else {
        lj.appendChild(document.createTextNode(css));
      }
      document.getElementsByTagName("head")[0].appendChild(lj);
    }
  };
  var ii = [];
  function il(j, type, fn, scope) {
    scope = scope || j;
    var li;
    if (j.addEventListener) {
      li = function (e) {
        fn.call(scope, e);
      };
      j.addEventListener(type, li, false);
    } else if (j.attachEvent) {
      li = function () {
        var e = window.event;
        e.target = e.target || e.srcElement;
        e.llj = function () {
          window.event.returnValue = false;
        };
        fn.call(scope, e);
      };
      j.attachEvent("on" + type, li);
    }
    ii.push([j, type, fn, li]);
  }
  function jl(j, type, fn) {
    var li, item, len, i;
    for (i = 0; i < ii.length; i++) {
      item = ii[i];
      if (item[0] == j && item[1] == type && item[2] == fn) {
        li = item[3];
        if (li) {
          if (j.j1) {
            j.j1(type, li, false);
          } else if (j.lli) {
            j.lli("on" + type, li);
          }
        }
      }
    }
  }
  function ji(ij) {
    do {
      ij = ij.parentNode;
    } while (ij && ij.nodeType != 1);
    return ij;
  }
})();

var SC_Flow = new SC.apps.MCFlow();
var referrer_url = (window.location.hostname!='' && window.location.hostname!='undefined') ? window.location.hostname : window.location.referrer;

jQuery(document).keypress(function(e) {
  if (e.keyCode == 27) SC_Flow.closeFlow();
});



//***CLOSE IFRAME X DOMAIN
var receiveMessage = function(event) {
    if (event.data && event.data != 'Close-popup-sc') {
        var scStr = event.data.split('|');
        if(scStr[0]=='result'){
            window.location.href = scStr[1];
        }
    }
    else if(event.data && event.data == 'Close-popup-sc'){
        jQuery('object').show();
        jQuery("#SC_Popup_iFrame").remove();
    }
}

function scAddEvent(obj,type,SCfn) {
  if (obj.addEventListener) {
  obj.addEventListener(type,SCfn,false);
  return true;
  } else if (obj.attachEvent) {
  obj['e'+type+SCfn] = SCfn;
  obj[type+SCfn] = function() { obj['e'+type+SCfn]( window.event );}
  var r = obj.attachEvent('on'+type, obj[type+SCfn]);
  return r;
  } else {
  obj['on'+type] = SCfn;
  return true;
  }
}

scAddEvent(window,"message",receiveMessage);

//*** END CLOSE

jQuery(window).load(function() {
    jQuery('.iframe_sc').fadeIn();
});

jQuery(document).ready(function(){
  var SC_html_content       = '';
  var sc_stick_free_ship    = '&nbsp;';
  var sc_text_free_ship     = '';
  
  var sc_stick_free_cod     = '&nbsp;';
  var sc_text_free_cod      = '';
  
  var sc_stick_free_protected = '&nbsp;';
  var sc_text_free_protected= '';
  
  shipchung_frame_v2        = '';
  sc_disable_li_ship        = '';
  sc_disable_li_protected   = '';
  sc_disable_li_cod         = '';
  
  var sc_button_size 		= jQuery('shipchung').attr("size");
  var sc_button_type 		= jQuery('shipchung').attr("type");
  var sc_button_method 		= jQuery('shipchung').attr("method");
  var sc_button_id   		= jQuery('shipchung').attr("id");
  
  var sc_button_nl_level	= jQuery('shipchung').attr("level");
  var sc_button_nl_url	    = jQuery('shipchung').attr("nganluong_url");
  
  var sc_free_shipping      = jQuery('shipchung').attr("free_shipping");

  var sc_free_cod           = jQuery('shipchung').attr("free_cod");
  var sc_free_protected     = jQuery('shipchung').attr("free_protected");
  
  var sc_button_cod_option	= jQuery('shipchung').attr("cod");
  var sc_button_cod			= (sc_button_cod_option && sc_button_cod_option!='undefined' && (sc_button_cod_option.toLowerCase() == 'yes' || sc_button_cod_option.toLowerCase() == '1')) ? 'cod' : 'pas';
  var sc_ajax_file_option   = jQuery('shipchung').attr("ajax_file");
  var SC_AJAX				= (sc_ajax_file_option && sc_ajax_file_option!='undefined') ? sc_ajax_file_option : 'shipchung/ajax.php';
  var sc_tooltip 			= "<li>Tính phí vận chuyển Online hoàn toàn</li><li>Giao hàng thu tiền (CoD) toàn quốc</li><li>Hỗ trợ thanh toán trực tuyến qua NgânLượng.vn</li><li>Tra cứu hành trình hàng hóa mọi lúc mọi nơi</li><li>Hỗ trợ giải quyết khiếu nại chuyên nghiệp</li>";
  var SC_QUANTITY 			= jQuery('shipchung').attr("input_quantity");
  var SC_NOTE  			    = jQuery('shipchung').attr("input_note");
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
  
  
  if(sc_button_type && (sc_button_type.toLowerCase() =='payment' || sc_button_type.toLowerCase() =='detail') && sc_button_id)  {
	//jQuery('shipchung').replaceWith(SC_html_content);
    document.getElementsByTagName("shipchung")[0].innerHTML = SC_html_content;
  }
  else jQuery('shipchung').replaceWith('Bạn khai báo thiếu biến');
  jQuery("#cot_giua").css("overflow","visible");

jQuery('a#sc_event_click_button').click(function(e){
	e.stopPropagation();
    jQuery(this).attr('disabled', 'disabled');
    //if(sc_button_type && (sc_button_type.toLowerCase() =='detail' || sc_button_type.toLowerCase() =='payment') && sc_button_method.toLowerCase() =='ajax') 
    if((jQuery(this).attr('shipchung_type') =='detail' || jQuery(this).attr('shipchung_type') =='payment') && jQuery(this).attr('shipchung_method') =='ajax') 
    {
      var quantity = (jQuery('#'+SC_QUANTITY).val()) ? jQuery('#'+SC_QUANTITY).val() : jQuery('input[name$="'+SC_QUANTITY+'"]').val();
      var note 	   = (jQuery('#'+SC_NOTE).val()) ? jQuery('#'+SC_NOTE).val() : jQuery('input[name$="'+SC_NOTE+'"]').val();
      var shipchung_ajax = jQuery(this).attr('shipchung_ajax');
      if(Ajax && !window.MooTools) { 
        if(jQuery.isFunction(Ajax.Request))
        {
            new Ajax.Request(shipchung_ajax,{
                        method: 'post',
                        parameters: {"id":jQuery(this).attr("rel"),"quantity":quantity, "note":note},
                        onComplete: function(transport) {
                                jQuery(this).removeAttr('disabled');
                                 html      		= transport.responseText.evalJSON();
                            	if(html.result_code == '00'){
                            		if(sc_button_cod=='yes' || sc_button_cod =='true') {
                            			SC_Flow.startFlow(html.link_checkout_cod + '/?referrer=' + referrer_url);
                                    }
                            		else {
                            			SC_Flow.startFlow(html.link_checkout + '/?referrer=' + referrer_url);
                            		}
                            	}else{
                            		alert(html.result_description);
                            	}
                          }
                });
        }
        else
        {
            Ajax.call(shipchung_ajax, 'id=' + jQuery(this).attr("rel") + '&quantity=' + quantity + '&note=' + note, json_result, "POST", "TEXT");
        }
      }
      else {
        jQuery.ajax({
          url: shipchung_ajax,
          type: "post",
          data: {
            'id': jQuery(this).attr("rel"),
            'quantity': ((quantity > 0) ? quantity : 1),
            'note': note
            },
		  dataType: "json",
          cache: false,
          sync: false,
          success: function(html){
            jQuery(this).removeAttr('disabled');
            if(html.result_code == '00' && sc_button_cod){
              if(sc_button_cod.toLowerCase()=='yes' || sc_button_cod.toLowerCase()=='true') {
                SC_Flow.startFlow(html.link_checkout_cod + '/?referrer=' + referrer_url);
              }
              else {
                SC_Flow.startFlow(html.link_checkout + '/?referrer=' + referrer_url);
              }
            }else{
              alert(html.result_description);
            }
          }
        });
      }
    }
    else if(sc_button_type && (sc_button_type.toLowerCase() =='detail' || sc_button_type.toLowerCase() =='payment') && sc_button_method.toLowerCase() !='ajax') {
      SC_Flow.startFlow(SC_LINK_CHECKOUT);
    }

    return false;
  });
  
});

function json_result(result)
{ 
	jQuery('a#sc_event_click_button').removeAttr('disabled');
    sc_button_cod 	= jQuery('a#sc_event_click_button').attr("cod");
	html      		= JSON ? JSON.parse(result) : jQuery.parseJSON(result);
	if(html.result_code == '00'){
		if(sc_button_cod.toLowerCase()=='yes' || sc_button_cod.toLowerCase()=='true') {
			SC_Flow.startFlow(html.link_checkout_cod + '/?referrer=' + referrer_url);
		}
		else {
			SC_Flow.startFlow(html.link_checkout + '/?referrer=' + referrer_url);
		}
	}else{
		alert(html.result_description);
	}
}
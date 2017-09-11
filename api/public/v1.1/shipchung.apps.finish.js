/*	
	jQuery API ShipChung.VN
	Author by KienNT
	Email: kiennt@peacesoft.net
	Develop by 1Top.vn & ShipChung.vn Team
*/

var SC_DOMAIN_API = 'http://api.shipchung.vn/v1.1'; // ex: http://api.shipchung.vn

if(typeof Ajax == "undefined" || !Ajax) {
  var Ajax = false;
}

if(typeof(jQuery)=='undefined' || !jQuery){
	document.write('<script language="javascript" src="' + SC_DOMAIN_API + '/jquery.min.js"><\/script>');
}

if(Ajax){
	document.write('<script language="javascript" src="' + SC_DOMAIN_API + '/jquery.json-1.3.min.js"><\/script>');
}
	document.write('<script language="javascript" src="' + SC_DOMAIN_API + '/shipchung.apps.finish.lite.js"><\/script>');
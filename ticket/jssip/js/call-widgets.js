var sessions = null;var soundPlayer = null;
var selfView = null;var remoteView = null;
var on_accepted = function() {eventEmiter('on_accepted', {detail: {}});};
var on_ended = function(e) {eventEmiter('on_ended', {detail: {}});};
var on_denied = function(e) {eventEmiter('on_denied', {detail: {}});};
var on_popup = function(call) {eventEmiter('on_popup', {detail: {call: call}});};
var on_loggedin = function() {eventEmiter('on_loggedin', {detail: {}});};
var on_login_failed = function(e) {eventEmiter('on_login_failed', {detail: e});};
var on_disconnected = function() {eventEmiter('on_disconnected', {detail: {}});};
var eventEmiter = function (evtName, data){
	var event = new CustomEvent(evtName, data);
	document.dispatchEvent(event);
}
var list_nhaccho = [
	'nhaccho2.mp3',
	'nhaccho3.mp3',
	'nhaccho4.mp3',
	'nhaccho5.mp3',
	'nhaccho6.mp3',
	'nhaccho7.mp3',
];
var getRandomNhacCho = function (){
	var index = Math.floor(Math.random() * (5 - 0 + 1)) + 0;
	if(list_nhaccho[index]){
		return list_nhaccho[index];
	}
	return 'nhaccho7.mp3';
}



$(document).ready(function() {
	//JsSIP.debug.enable('*');
	if (window.rtcninjaTemasys) { rtcninjaTemasys({}, function() { JsSIP.rtcninja({plugin: rtcninjaTemasys}); }, function(data) { alert('WebRTC plugin required'); }, null); }
	var ws_servers = "wss://autodiscover.shipchung.vn/ws";
	soundPlayer = document.createElement("audio");
	soundPlayer.volume = 1;
	/*
		sessions = document.createElement('div');
		sessions.id = "sessions";
		document.body.appendChild(sessions);
	*/

	selfView = document.createElement('video');
	selfView.muted = true;
	selfView.autoplay = true;
	remoteView = document.createElement('video');
	remoteView.autoplay = true;
	var localStream, remoteStream;
	var localCanRenegotiateRTC = function() { return JsSIP.rtcninja.canRenegotiate; };
	function phoneInit(sip_uri, sip_password) {
		var configuration = {  
			uri: sip_uri, 
			password: sip_password, 
			ws_servers: ws_servers, 
			display_name: null, 
			authorization_user: null, 
			register: true, 
			register_expires: 6000, 
			registrar_server: null, 
			no_answer_timeout: 30, 
			session_timers: false, 
			use_preloaded_route: false, 
			connection_recovery_min_interval: 2, 
			connection_recovery_max_interval: 30, 
			hack_via_tcp: false, 
			hack_via_ws: false, 
			hack_ip_in_contact: false 
		};
		try {
			ua = new JsSIP.UA(configuration);
		} catch (e) {
			return;
		}

		ua.on('connected', function(e) {
			CALLMAN.is_online = true;
		});

		ua.on('disconnected', function(e) {
			$("#sessions > .session").each(function(i, session) {
				CALLMAN.removeSession(session, 500);
			});
			CALLMAN.is_online = false;
			on_disconnected();
		});

		ua.on('newRTCSession', function(e) {
			if(CALLMAN.is_online == true){
				CALLMAN.new_session(e);
			}
		});

		ua.on('registered', function(e) {
			CALLMAN.is_online = true;
			on_loggedin();
		});

		ua.on('unregistered', function(e) {
			CALLMAN.is_online = false;
		});

		ua.on('registrationFailed', function(e) {
			CALLMAN.is_online = false;
			on_login_failed(e);
		});
		ua.start();


	}

	//*****
	window.CALLMAN = {
		is_online: false, direction: "", call_id: null, display_name: "",
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
		login : function(sip_uri, sip_password) {
			sip_uri = 'sip:' + sip_uri + '@123.30.49.114';
			try { phoneInit(sip_uri, sip_password); } catch (err) { console.warn(err.toString()); }
		},
		// = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
		clean : function() {
			CALLMAN.direction = "";
			CALLMAN.call_id = null;
			CALLMAN.uri = null;
		},
		// = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
	    new_session : function(e) {

			var session = CALLMAN.getSession(uri);
	    	if (session) {
	    		console.log('Call session', session);
	      		e.session.terminate();
	      		return false;
	      	}


			var request = e.request;
			var call = e.session;
			var uri = call.remote_identity.uri.toString();
			
			var status;
			var display_name = call.remote_identity.display_name || call.remote_identity.uri.user;

			
			

			CALLMAN.direction = call.direction;
			CALLMAN.call_id = call.request.call_id;
			CALLMAN.display_name = display_name;

			if (call.direction === 'incoming') {
				status = "incoming";
				if (request.getHeader('X-Can-Renegotiate') === 'false') {
					call.data.remoteCanRenegotiateRTC = false;
				} else {
					call.data.remoteCanRenegotiateRTC = true;
				}
			} else {
				status = "trying";
			}

	      	

	      	on_popup(call);
	      	if (!session) {
	      		session = CALLMAN.createSession(display_name, uri, call.direction);
	      	}

	      	session.call = call;
	      	CALLMAN.setCallSessionStatus(session, status);

	      	call.on('connecting', function() {
		        if (call.connection.getLocalStreams().length > 0) {
		        	window.localStream = call.connection.getLocalStreams()[0];
		        }
	      	});

	      	call.on('progress',function(e) {
		        if (e.originator === 'remote') {
	        	  e.response.body = null;
		          CALLMAN.setCallSessionStatus(session, 'in-progress');
		        }
	      	});

	      	call.on('accepted',function(e) {
		        if (call.connection.getLocalStreams().length > 0) {
		          localStream = call.connection.getLocalStreams()[0];
		          selfView = JsSIP.rtcninja.attachMediaStream(selfView, localStream);
		          selfView.volume = 0;

		          window.localStream = localStream;
		        }

		        if (e.originator === 'remote') {
		          if (e.response.getHeader('X-Can-Renegotiate') === 'false') {
		            call.data.remoteCanRenegotiateRTC = false;
		          }
		          else {
		            call.data.remoteCanRenegotiateRTC = true;
		          }
		        }

		        CALLMAN.setCallSessionStatus(session, 'answered');
		        on_accepted();
	      	});

	      	call.on('addstream', function(e) {
		        remoteStream = e.stream;
		        remoteView = JsSIP.rtcninja.attachMediaStream(remoteView, remoteStream);
	      	});

	      	call.on('failed',function(e) {
		        var cause = e.cause;
		        var response = e.response;
		        on_denied(e);
		        if (e.originator === 'remote' && cause.match("SIP;cause=200", "i")) { cause = 'answered_elsewhere'; }
		        CALLMAN.setCallSessionStatus(session, 'terminated', cause);
		        soundPlayer.setAttribute("src", "jssip/sounds/end.mp3");
		        soundPlayer.play();
		        CALLMAN.removeSession(session, 900);
		        selfView.src = '';
		        remoteView.src = '';
	      	});

	      	call.on('hold',function(e) {
		        soundPlayer.setAttribute("src", "jssip/sounds/dialpad/pound.ogg");
		        soundPlayer.play();
		        CALLMAN.setCallSessionStatus(session, 'hold', e.originator);
	      	});

	      	call.on('unhold',function(e) {
		        soundPlayer.setAttribute("src", "jssip/sounds/dialpad/pound.ogg");
		        soundPlayer.play();
		        CALLMAN.setCallSessionStatus(session, 'unhold', e.originator);
	      	});

	      	call.on('ended', function(e) {
		        var cause = e.cause;
		        on_ended(e);
		        CALLMAN.setCallSessionStatus(session, "terminated", cause);
		        CALLMAN.removeSession(session, 900);
		        selfView.src = '';
		        remoteView.src = '';
		        JsSIP.rtcninja.closeMediaStream(localStream);
	      	});

	      	call.on('update', function(e) {
		        var request = e.request;
		        if (! request.body) { return; }
		        if (! localCanRenegotiateRTC() || ! call.data.remoteCanRenegotiateRTC) {
		          call.connection.reset();
		          call.connection.addStream(localStream);
		        }
	      	});

	      	call.on('reinvite', function(e) {
		        var request = e.request;
		        if (! request.body) { return; }
		        if (! localCanRenegotiateRTC() || ! call.data.remoteCanRenegotiateRTC) {
		        	call.connection.reset();
		        	call.connection.addStream(localStream);
		        }
	      	});
	    },
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
		getSession : function(uri) {
			var session_found = null;

			session_found = !$("#sessions > #call-widget").hasClass('ng-hide');

			if (session_found)
				return true;
			else
				return false;
	    },
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
	    createSession : function(display_name, uri, direction) {

			eventEmiter('on_create_session', {detail: {
				display_name : display_name,
				uri 		 : uri,
				direction 	 : direction
			}});

	      	var session = $("#sessions .session").filter(":last");
	      	var call_status = $(session).find(".call");
	      	var close = $(session).find("> .close");

	  		$(session).hover(function() {
	  			if ($(call_status).hasClass("inactive"))
	  				$(close).show();
	  		}, function() {
	  			$(close).hide();
	  		});

	      	close.click(function() {
	      		CALLMAN.removeSession(session, null, true);
	      	});

	      	$(session).fadeIn(100);
	      	return session;
	    },
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
	    setCallSessionStatus : function(session, status, description, realHack) {
	    	var session = session;

	    	var uri = $(session).find(".peer > .uri").text();
	    	var call = $(session).find(".call");

	    	var status_text = $(session).find(".call-status");

	    	var button_dial = $(session).find(".button.dial");
	    	var button_hangup = $(session).find(".button.hangup");
	    	var button_hold = $(session).find(".button.hold");
	    	var button_resume = $(session).find(".button.resume");

	    	if (status != "inactive" && status != "terminated") {
	    	    $(session).unbind("hover");
	    	    $(session).find("> .close").hide();
	    	}

	    	button_dial.unbind("click");
	    	button_hangup.unbind("click");
	    	button_hold.unbind("click");
	    	button_resume.unbind("click");

	    	if (session.call && session.call.status !== JsSIP.C.SESSION_TERMINATED) {
	    	    button_hangup.click(function() {
	    	        CALLMAN.setCallSessionStatus(session, "terminated", "terminated");
	    	        session.call.terminate();
	    	        CALLMAN.removeSession(session, 500);
	    	    });
	    	}

	    	switch(status) {
		    	case "inactive":
		    	    call.removeClass();
		    	    call.addClass("call inactive");
		    	    status_text.text("");
		    	    button_dial.click(function() { CALLMAN.call(uri); });
		    	    break;

		    	case "trying":
		    	    call.removeClass();
		    	    call.addClass("call trying");
		    	    status_text.text(description || "Đang kết nối");
		    	    break;

		    	case "in-progress":
		    	    call.removeClass();
		    	    call.addClass("call in-progress");

		    	    status_text.text(description || "Đang kết nối");
		    	    soundPlayer.setAttribute("src", "jssip/sounds/"+ getRandomNhacCho());
		    	    soundPlayer.play();
		    	    break;

		    	case "answered":
		    	    call.removeClass();
		    	    call.addClass("call answered");
							if(soundPlayer){

								soundPlayer.pause();
							}

		    	    status_text.text(description || "Đã kết nối");
		    	    button_hold.click(function(){
		    	        if (! session.call.isReadyToReOffer()) { return; }
		    	        if (! localCanRenegotiateRTC() || ! session.call.data.remoteCanRenegotiateRTC) {
		    	            session.call.connection.reset();
		    	            session.call.connection.addStream(localStream);
		    	        }
		    	        session.call.hold({useUpdate: false});
		    	    });
		    	    if (realHack) { return; }
		    	    break;

		    	case "hold":
		    	case "unhold":
		    	    if (session.call.isOnHold().local) {
		    	        call.removeClass();
		    	        call.addClass("call on-hold");
		    	        button_resume.click(function(){
		    	            if (! session.call.isReadyToReOffer()) { return; }
		    	            if (! localCanRenegotiateRTC() || ! session.call.data.remoteCanRenegotiateRTC) {
		    	                session.call.connection.reset();
		    	                session.call.connection.addStream(localStream);
		    	            }
		    	            session.call.unhold();
		    	        });
		    	    } else {
		    	        CALLMAN.setCallSessionStatus(session, 'answered', null, true);
		    	    }

		    	    var local_hold = session.call.isOnHold().local;
		    	    var remote_hold = session.call.isOnHold().remote;

		    	    var status = "Đang giữ máy";
		    	    status += local_hold?" local ":"";
		    	    if (remote_hold) {
		    	        if (local_hold)  status += "/";
		    	        status += " remote";
		    	    }
		    	    if (local_hold||remote_hold) {
		    	    	status_text.text(status);
		    	    }
		    	    break;

		    	case "terminated":
		    	    call.removeClass();
		    	    call.addClass("call terminated");
		    	    status_text.text(description || "Hủy cuộc gọi");
		    	    button_hangup.unbind("click");
		    	    break;

		    	case "incoming":
		    	    call.removeClass();
		    	    call.addClass("call incoming");
		    	    status_text.text("Có cuộc gọi đến...");
		    	    soundPlayer.setAttribute("src", "jssip/sounds/incoming.mp3");
		    	    soundPlayer.play();

		    	    button_dial.click(function() {
		    	        session.call.answer({
		    	            pcConfig: "{}",
		    	            mediaConstraints: {audio: true, video: false},
		    	            extraHeaders: [ 'X-Can-Renegotiate: ' + String(localCanRenegotiateRTC()) ],
		    	            rtcOfferConstraints: { offerToReceiveAudio: 1, offerToReceiveVideo: 0 },
		    	        });
		    	    });
		    	    break;

		    	default:
		    	    alert("ERROR: setCallSessionStatus() called with unknown status '" + status + "'");
		    	    break;
	    	}
	    },
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
	    removeSession : function(session, time, force) {
	    	//$(session).slideUp(800, function() { $(session).remove(); });
				
	    	eventEmiter('on_remove_session', {});
	    },
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
	    setDelayedCallSessionStatus : function(uri, status, description, force) {
	    	var session = CALLMAN.getSession(uri.toString());
	    	if (session) {
	    		CALLMAN.setCallSessionStatus(session, status, description, force);
	    	}
	    },
	    // = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =  = = = = = = = =
	    call : function(target) {
	        ua.call(target, {
	            pcConfig: "{}",
	            mediaConstraints: { audio: true, video: false },
	            extraHeaders: [ 'X-Can-Renegotiate: ' + String(localCanRenegotiateRTC()) ],
	            rtcOfferConstraints: { offerToReceiveAudio: 1, offerToReceiveVideo: 0 }
	        });
	    }
	};
});

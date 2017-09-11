'use strict';

/* Services */


// Demonstrate how to register services
angular.module('app')
    .service('Callcenter', ['$http', '$q', 'Api_Path', function ($http, $q, Api_Path) {

        var Callcenter  = {};
        Callcenter.host = '123.30.49.114';
        Callcenter.instance     = null;
        Callcenter.realm        = null;
        Callcenter.sip_account  = null;
        Callcenter.sip_pwd      = null;

        Callcenter.ringtone      = document.getElementById('ringtone');
        Callcenter.ringbacktone  = document.getElementById('ringbacktone');

        Callcenter.call_config  = {
            audio_remote: document.getElementById('audio_remote'),
            sip_caps: [
                { name: '+g.oma.sip-im' },
                { name: 'language', value: '\"en,fr\"' }
            ]
        };

        Callcenter.call_registered  = null;
        Callcenter.call_session     = null;



        Callcenter.set = function (key, value){
            if(value && key){
                Callcenter[key] = value;
            }
            return value;
        };


        Callcenter.setRingtone = function (status){
            if(status == 'play'){
                this.ringtone.play();
            }else {
                this.ringtone.pause();
            }
        };

        Callcenter.init = function (){
            var self = this;
            try {
                if(!self.realm || !self.sip_account || !self.sip_pwd){

                }

                if (window.webkitNotifications && window.webkitNotifications.checkPermission() != 0) {
                    window.webkitNotifications.requestPermission();
                }
                SIPml.setDebugLevel('true');

                SIPml.init(
                    function(e){
                        self.instance = new SIPml.Stack(
                            {
                                realm: self.realm,
                                impi: '1003',
                                impu: self.sip_account,
                                password: self.sip_pwd,
                                display_name: 'thinhnv', // optional
                                websocket_proxy_url: "ws://123.30.49.114:8088/ws",
                                outbound_proxy_url: "udp://123.30.49.114:5060",
                                ice_servers: "[{ url: 'stun:stun.l.google.com:19302'}]",
                                enable_rtcweb_breaker: false, // optional
                                enable_media_stream_cache: true,
                                sip_headers: [
                                    { name: 'User-Agent', value: 'IM-client/OMA1.0 sipML5-v1.2015.03.18' },
                                    { name: 'Organization', value: 'Shipchung' }
                                ],
                                video_size: null,
                                bandwidth: null,
                                enable_early_ims: true,
                                events_listener: { events: '*', listener: self.EventProcessing }
                            });

                        if(self.instance.start() != 0){

                        }
                    }
                );
            }catch (e){
                console.log('e', e);
            }
        };

        Callcenter.unregister = function (callback){
            if (this.instance) {
                this.instance.stop(); // shutdown all sessions
                callback()
            }
        };





        Callcenter.MakeCall = function (phoneNumber){
            this.call_config['events_listener'] =  { events: '*', listener: Callcenter.SesionEventProcessing};
            this.call_session = this.instance.newSession('call-audio', this.call_config);
            this.call_session.call(phoneNumber);
        };

        Callcenter.answerCall = function (){
            if(Callcenter.call_session){
                Callcenter.call_config['events_listener'] =  { events: '*', listener: Callcenter.SesionEventProcessing};
                Callcenter.call_session.accept(Callcenter.call_config);
            }
        };


        Callcenter.EventProcessing = function (e){
            console.log('EventProcessing', e);

            var self = Callcenter;
            switch (e.type){
                case 'started':
                    //self.MakeCall("0906262181");
                    try {
                        // LogIn (REGISTER) as soon as the stack finish starting
                        self.call_registered = this.newSession('register', {
                            expires: 200,
                            events_listener: { events: '*', listener: self.SesionEventProcessing },
                            sip_caps: [
                                { name: '+g.oma.sip-im', value: null },
                                //{ name: '+sip.ice' }, // rfc5768: FIXME doesn't work with Polycom TelePresence
                                { name: '+audio', value: null },
                                { name: 'language', value: '\"en,fr\"' }
                            ]
                        });
                        self.call_registered.register();
                    }
                    catch (e) {
                        console.log('started', e);
                    }

                    break;
                case 'stopping': case 'stopped': case 'failed_to_start': case 'failed_to_stop': // Disconnect
                    {
                        var bFailure = (e.type == 'failed_to_start') || (e.type == 'failed_to_stop');

                        self.call_session = null;
                        self.call_registered = null;
                        self.instance = null;

                        self.setRingtone('pause');

                        break;
                    }
                case 'i_new_call':
                    console.log('i_new_call', self.call_session);

                    if (self.call_session) {
                        e.newSession.hangup(); // comment this line for multi-line support
                    }
                    else {
                        self.call_session = e.newSession;

                        // start listening for events
                        self.call_config['events_listener'] =  { events: '*', listener: Callcenter.SesionEventProcessing};
                        self.call_session.setConfiguration(self.call_config);


                        self.setRingtone('play');

                        var sRemoteNumber = (self.call_session.getRemoteFriendlyName() || 'unknown');
                        console.log('------------------------------');
                        console.log(sRemoteNumber);
                        /*txtCallStatus.innerHTML = "<i>Incoming call from [<b>" + sRemoteNumber + "</b>]</i>";
                         showNotifICall(sRemoteNumber);*/
                    }
                    break;

            }
        };

        Callcenter.SesionEventProcessing = function (e){
            console.log('SesionEventProcessing---------------- ', e);
            var self = Callcenter;
            switch (e.type){

                case 'connecting': case 'connected':
                {
                    var bConnected = (e.type == 'connected');
                    if (e.session == self.call_registered) {
                        self.setRingtone('pause');
                        console.log('e.session == self.call_registered', e.description);
                    }
                    else if (e.session == self.call_session) {// Bắt đầu acll


                        if (bConnected) {
                            self.setRingtone('pause');
                        }
                        console.log('Starting call', e.description);

                    }
                    break;
                } // 'connecting' | 'connected'
                    case 'terminating': case 'terminated':
                {
                    if (e.session == self.call_registered) { // Hủy đăng ký
                        self.call_session = null;
                        self.call_registered = null;
                    }
                    else if (e.session == self.call_session) { // Hủy cuộc gọi
                        self.call_session = null;
                        self.setRingtone('pause');
                    }
                    break;
                } // 'terminating' | 'terminated'

            }

        };

        return Callcenter;

    }]);

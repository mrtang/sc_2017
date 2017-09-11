var Callcenter = (function () {
    function Callcenter() {
        this.session = null;
        this.ua = null;
        this.remoteStream = document.getElementById('remoteVideo');
        this.localStream = document.getElementById('localVideo');
        this.options = {
            uri: '1004@123.30.49.114',
            ws_servers: 'wss://autodiscover.shipchung.vn/ws',
            authorizationUser: '1004',
            password: 'Shipchung123',
            hackIpInContact: true
        };
        this.bootstrap = function (option) {
            if (option && option.hasOwnProperty('uri')) {
                this.options.uri = option.uri;
            }
            if (option && option.hasOwnProperty('ws_servers')) {
                this.options.ws_servers = option.ws_servers;
            }
            if (option && option.hasOwnProperty('authorizationUser')) {
                this.options.authorizationUser = option.authorizationUser;
            }
            if (option && option.hasOwnProperty('password')) {
                this.options.password = option.password;
            }
            if (option && option.hasOwnProperty('hackIpInContact')) {
                this.options.hackIpInContact = option.hackIpInContact;
            }
            this.ua = new SIP.UA(this.options);
            this.eventHandler();
        };
        this.eventHandler = function () {
            var _this = this;
            var self = this;
            this.ua.on('connected', function () {
                console.info('SIP connected', self.options.uri);
                this.session = null;
            });
            this.ua.on('invite', function (session) {
                session.accept({
                    media: {
                        render: {
                            remote: self.remoteStream
                        }
                    }
                });
                _this.session = session;
                _this.sessionHandler();
            });
        };
        this.sessionHandler = function () {
            var self = this;
            this.session.on('progress', function (session) {
                self.remoteStream.play();
                console.log('session progress', session);
            });
            this.session.on('accepted', function (session) {
                var stream = self.session.getRemoteStreams();
                console.log('stream', stream);
                self.remoteStream.play();
                console.log('session accepted', session);
            });
            this.session.on('terminated', function (session) {
                self.session = null;
            });
        };
        this.call = function (phone_number) {
            var self = this;
            this.session = this.ua.invite(phone_number, {
                media: {
                    constraints: {
                        audio: true,
                        video: false
                    },
                    render: {
                        remote: self.remoteStream
                    }
                }
            });
            this.sessionHandler();
        };
    }
    return Callcenter;
})();
var Caller = new Callcenter();
Caller.bootstrap({});

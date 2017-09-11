interface OptionsInterface {
	uri: string,
	ws_servers: string,
	authorizationUser: string,
	password: string,
	hackIpInContact: boolean,
}
class Callcenter {
	public session: any = null;
	public ua = null;
	public remoteStream = document.getElementById('remoteVideo');
	public localStream  = document.getElementById('localVideo');
	private options: OptionsInterface  = {
		uri: '1004@123.30.49.114',
		ws_servers: 'wss://autodiscover.shipchung.vn/ws',
		authorizationUser: '1004',
		password: 'Shipchung123',
		hackIpInContact: true,
	};
	constructor(){
	}

	bootstrap = function(option) {
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
	}
	eventHandler = function (){
		var self = this;

		this.ua.on('connected', function (){
			console.info('SIP connected', self.options.uri);
			this.session = null;
		})
		


		this.ua.on('invite', (session) => {
			session.accept({
				media: {
					render: {
						remote: self.remoteStream,
						local: self.localStream
					}
				}
			});
			this.session = session;
			this.sessionHandler();
		});

	}
	sessionHandler = function (){
		var self = this;
		this.session.on('progress', (session) => {
			self.remoteStream.play();
			console.log('session progress', session);
		});
		this.session.on('accepted', (session) => {
			var stream = self.session.getRemoteStreams();
			console.log('stream', stream);
			self.remoteStream.play();
			console.log('session accepted', session);
		});

		this.session.on('terminated', (session) =>{
			self.session = null;
		});

	}
	call = function (phone_number: string){
		var self = this;
		this.session = this.ua.invite(phone_number, {
			media: {
				constraints: {
					audio: true,
					video: false
				},
				render: {
					remote: self.remoteStream,
					local: self.localStream
				}
			}
		});
		this.sessionHandler()
	}
}

var Caller = new Callcenter();
Caller.bootstrap({});


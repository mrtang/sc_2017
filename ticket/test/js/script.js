var callbutton = document.getElementById("call");
console.log('callbutton', callbutton);

var config = {
  // Replace this IP address with your Asterisk IP address
  uri: '1004@123.30.49.114',
  // Replace this IP address with your Asterisk IP address,
  // and replace the port with your Asterisk port from the http.conf file
  ws_servers: 'wss://autodiscover.shipchung.vn/ws',
  // Replace this with the username from your sip.conf file
  authorizationUser: '1004',
  // Replace this with the password from your sip.conf file
  password: 'Shipchung123',
  // HackIpInContact for Asterisk
  hackIpInContact: true,

};

var ua = new SIP.UA(config);

/*// Invite with audio only
ua.invite('01626616817',{
  media: {
    constraints: {
      audio: true,
      video: false
    },
    render: {
        remote: document.getElementById('remoteVideo'),
        local: document.getElementById('localVideo')
    }
  }
});*/

ua.on('invite', function (session) {
	console.log('session', session)
    session.accept({
        media: {
            render: {
                remote: document.getElementById('remoteVideo'),
                local: document.getElementById('localVideo')
            }
        }
    });
});


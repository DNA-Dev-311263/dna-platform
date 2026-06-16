/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|   Author: ABR                                                             |
\ ======================================================================== */
var Htmlpage = {

    idItem: 0,
    videoComplete: 0,
    sendComplete: false,
    saveComplete: false,

    init: function(oConfig) {
        
        if (oConfig.idItem) this.idItem = oConfig.idItem;
        if (oConfig.videoComplete) this.videoComplete = oConfig.videoComplete;

		$( document ).ready(function() {
			H = Htmlpage;
            var plyrRef = document.getElementById('plyr-player');

            if (plyrRef) {
                // Utilizzo player plyr

                var link = document.createElement("link");
                link.href = "https://cdn.plyr.io/3.7.2/plyr.css";
                link.type = "text/css";
                link.rel = "stylesheet";
                document.head.appendChild(link);

                var script = document.createElement("script");  // create a script DOM node
                script.src = "https://cdn.plyr.io/3.7.2/plyr.polyfilled.js";  // set its src to the provided URL
                script.onload = function() { H.setPlyrPlayer() };

                document.head.appendChild(script);

            } else if ( $("iframe[src*='vimeo']") ) {
                // Utilizzo diretto player vimeo

                var script = document.createElement("script");  // create a script DOM node
                script.src = "https://player.vimeo.com/api/player.js";  // set its src to the provided URL
                script.onload = function() { H.setVimeoPlayer() };

                document.head.appendChild(script);
            }
        });
    },

    setVimeoPlayer: function(){
        
        var H = Htmlpage;
        if ( H.videoComplete <= 0 ) return false;

        var iframe = document.querySelector('iframe'); // Gestitsco solo un video per pagina
	    var player = new Vimeo.Player(iframe);

        /* 
        //Segnalibro, non implementato 
        player.setCurrentTime(120.456).then(function(seconds) {
	        // seconds = the actual time that the player seeked to
        }).catch(function(error) {
	        switch (error.name) {
		        case 'RangeError':
			        // the time was less than 0 or greater than the video’s duration
			        break;

		        default:
			        // some other error occurred
			        break;
	        }
        }),
        */
        
        player.on('seeked', function(data) {
	         //console.log(data);
        });
        
        player.on('timeupdate', function(data) {
            var progress = parseInt( data.percent * 100 );

            if ( !H.sendComplete && progress > 0 && progress >= H.videoComplete )
	            H.setComplete( progress );
        });

        player.on('play', function(data) {
          //console.log(\'Played the video\');
        });

        player.getVideoTitle().then(function(title) {
          //console.log('title:', title);
        });

        player.on('ended', function() {
	        player.destroy();
        });

    },

    setPlyrPlayer: function() {
        var H = Htmlpage;
	    var controls =
	    [
        	'play', // Play/pause playback
        	'restart', // Restart playback
        	'rewind', // Rewind by the seek time (default 10 seconds)
        	'duration', // The full duration of the media
        	'mute', // Toggle mute
        	'volume', // Volume control
        	'fullscreen', // Toggle fullscreen
        	'play', // Play/pause playback
        	'fast-forward', // Fast forward by the seek time (default 10 seconds)
        	'progress' // The progress bar and scrubber for playback and buffering
	    ];


       var vtg =  document.getElementById("plyr-player");

       // Controllo esistenza tag
       if (!vtg) return false;
       
       // Preparo player
       vtg.innerHTML = vtg.innerHTML.replace("-", "~");
       const player = new Plyr('#plyr-player', { controls });


       // Funzione da chiamare
       const handleTimeUpdate = function(e) {
           var progress = parseInt(player.currentTime / player.duration * 100);

            if ( !H.sendComplete && progress > 0 && progress >= H.videoComplete )
	            H.setComplete( progress );
       };

       // Evento
       player.on("timeupdate", handleTimeUpdate);


    },

    setComplete: function(progress) {

        var H = Htmlpage;
        var url = 'ajax.server.php?mn=organization&plf=lms&op=htmlpage_complete';

        H.sendComplete = true;

         $.ajax({
            type: 'POST',
            url: url,
            data: {idItem:H.idItem, progress:progress},
            success: function (data) {
              if (data.success) {
                H.saveComplete = true;
                //console.log(data.message);
              }
            }
          });
    }

}

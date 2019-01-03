( function() {

    var youtube = document.querySelectorAll( ".youtube" );
    
    for (var i = 0; i < youtube.length; i++)
	{
		var thisyoutube = youtube[i];		
		thisyoutube.parentNode.classList.add("video-container");
		
		var playbutton = document.createElement("button");
		playbutton.classList.add("play-video");
		playbutton.value = "Play";
		
		thisyoutube.parentNode.appendChild(playbutton);
		
		playbutton.addEventListener( "click", function(event)
		{
			event.preventDefault();
			event.stopPropagation();
			
			var iframe = document.createElement( "iframe" );

			iframe.setAttribute( "frameborder", "0" );
			iframe.setAttribute( "allowfullscreen", "" );
			iframe.setAttribute( "width", "560" );
			iframe.setAttribute( "height", "315" );
			iframe.setAttribute( "src", "https://www.youtube.com/embed/" + thisyoutube.id + "?rel=0&showinfo=0&autoplay=1" );

			var videocontainer = thisyoutube.parentNode
			videocontainer.innerHTML = "";
			videocontainer.appendChild( iframe );
		})(thisyoutube);
    };
} )();
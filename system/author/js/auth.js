/*************************************
**			LOGIN TIMER				**
*************************************/
	
var wait = document.getElementById('wait');
	
if(wait)
{
	var loginbtn 	= document.getElementById("loginbutton");
	var seconds 	= parseInt(wait.innerHTML);
		
	loginbtn.disabled = true;
	loginbtn.value = '';
		
	var counter = setInterval(function () {
			
		seconds = seconds - 1;
		wait.innerHTML = seconds;
			
		if (seconds == 0) {
			loginbtn.disabled = false;
			loginbtn.value = 'Login';
			var countdown = document.getElementById("counter");
			
			countdown.parentNode.removeChild(countdown);
			
			clearInterval(counter);
		}
	}, 1000);		
}
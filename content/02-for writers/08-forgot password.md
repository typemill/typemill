# Lost Your Password?

TYPEMILL does not provide a password recovery, but there are two ways to create a new password.

## Is there another admin?

If there is another user with admin rights, then contact him. He can delete your user, create a new one and tell you the new password. Change the password immediately after login. 

## No other admin?

If you are the only admin user, then please follow these steps: 

* Connect to your website (e.g. via FTP).
* Go to the folder `/settings` and backup the file `settings.yaml`.
* Then delete the file `settings.yaml` on your server.
* Go to `yoursite.com/setup`.
* Fill out the form. This will create a new admin user and a fresh settings-file.
* Upload your old settings-file, so your old settings are active again.
* If not done before e.g. via FTP, delete the old admin-user in the user management now.

It might look a bit uncomfortable but it makes sure, that you are the owner of the website.
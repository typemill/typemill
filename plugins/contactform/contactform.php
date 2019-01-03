<?php

namespace Plugins\contactform;

use \Typemill\Plugin;

class ContactForm extends Plugin
{
	protected $settings;
	protected $pluginSettings;
	protected $originalHtml;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onSessionSegmentsLoaded' 	=> 'onSessionSegmentsLoaded',
			'onHtmlLoaded' 				=> 'onHtmlLoaded',
		);
    }
	
	# add the path stored in user-settings to initiate session and csrf-protection
	public function onSessionSegmentsLoaded($segments)
	{
		$this->pluginSettings = $this->getPluginSettings('contactform');
		
		if($this->getPath() == $this->pluginSettings['page_value'])
		{
			$data 	= $segments->getData();
			$data[]	= $this->pluginSettings['page_value'];
			
			$segments->setData($data);
		}
	}

	# create the output
	public function onHtmlLoaded($html)
	{
		if($this->getPath() == $this->pluginSettings['page_value'])
		{
			$content = $html->getData();
			
			# add css 
			$this->addCSS('/contactform/css/contactform.css');
			
			# check if form data have been stored
			$formdata = $this->getFormdata('contactform');

			if($formdata)
			{
				# process the form-data
				$sendmail = $this->sendMail($formdata);
				if($sendmail)
				{
					$mailresult = '<div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_success']) . '</div>';
				}
				else
				{
					$mailresult = '<div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_error']) . '</div>';						
				}
	
				# add thank you to the content
				$content = $content . $mailresult;
			}
			else
			{
				# get the public forms for the plugin
				$contactform = $this->generateForm('contactform');				
				
				# add forms to the content
				$content = $content . $contactform;					
			}
			$html->setData($content);
		}
	}

	private function sendMail($formdata)
	{		
		$mailto 		= $this->pluginSettings['mailto'];
		$subject 		= $formdata['subject'];
		$message 		= wordwrap($formdata['message'],70);
		$header 		= 'From: ' . $formdata['email'] . "\r\n" .
						  'Reply-To: ' . $formdata['email'] . "\r\n" .
						  'X-Mailer: PHP/' . phpversion();

		return @mail($mailto, $subject, $message, $header);
	}
	
	# check if the mail-plugin is active
	/*
	public function onTwigLoaded()
	{
		$this->settings = $this->getSettings();
		
		if(isset($this->settings['plugins']['mail']) OR !$this->settings['plugins']['mail']['active'])
		{
			$this->container->flash->addMessage('error', 'You have to activate the mail-plugin to use the contactform.');
		}
	}
	*/
	
	# get the original html without manipulations from other plugins
	/*
	public function onOriginalLoaded($original)
	{				
		if(substr($this->getPath(), 0, strlen($this->pluginSettings['area_value'])) === $this->pluginSettings['area_value'])
		{
			$this->originalHtml = $original->getHTML($urlrel = false);
		}
	}
	*/
	
	# create the output
	/*
	public function onHtmlLoaded($html)
	{
		if(substr($this->getPath(), 0, strlen($this->pluginSettings['area_value'])) === $this->pluginSettings['area_value'])
		{
			$content = $this->originalHtml;
			
			if($this->getPath() == $this->pluginSettings['page_value'])
			{				
				# add css 
				$this->addCSS('/contactform/css/contactform.css');
			
				# check if form data have been stored
				$formdata = $this->getFormdata('contactform');
				
				if($formdata)
				{
					# process the form-data
					$sendmail = $this->sendMail($formdata);
					if($sendmail)
					{
						$mailresult = '<div class="mailresult"><h2>Thank you!</h2><p>Your Message has been send.</p></div>';
					}
					else
					{
						$mailresult = '<div class="mailresult"><h2>Error</h2><p>We could not send your message. Please send the mail manually.</p></div>';						
					}
					/*
					print_r($formdata);
					die();
					$mail = $this->container->mail->send('mail/welcome.twig', ['user' => $user, 'url' => $host], function($message) use ($user){
						  $message->to($user['email']);
						  $message->subject('Dein Account bei der Regierungsmannschaft');
					});
					
					if(!$mail)
					{
						$this->container->flash->addMessage('error', 'Die Bestätigungsmail konnte nicht verschickt werden.');
					}
					
					# create thank you page
					
					# add thank you to the content
					$content = $this->originalHtml . $mailresult;
				}
				else
				{
					# get the public forms for the plugin
					$contactform = $this->generateForm('contactform');				
				
					# add forms to the content
					$content = $this->originalHtml . $contactform;					
				}
			}
			
			$html->setData($content);
			$html->stopPropagation();
		}
	}
	*/
}
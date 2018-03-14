<?php

namespace Plugins\Share;

use \Typemill\Plugin;

class Share extends Plugin
{
	protected $item;
	protected $html;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onItemLoaded' 			=> 'onItemLoaded',
			'onPageReady'			=> 'onPageReady'
		);
    }
		
	public function onItemLoaded($item)
	{	
		$this->item = $item->getData();
	}
	
	public function onPageReady($pageData)
	{
		$data = $pageData->getData();

		$this->addCSS('/share/assets/fontello/css/fontello.css');		
		$this->addInlineCSS($this->getCSS());
		
		if(isset($this->item->elementType) && $this->item->elementType == 'file')
		{
			$url				= $this->item->urlAbs;
			$tags 				= 'website,cms';
			$tags 				.= str_replace(array('/','-'),',', $this->item->urlRel);
			$title				= $data['title'];
			$description		= $data['description'];
			$shortDescription 	= substr($data['description'], 0, strpos(wordwrap($data['description'], 100), "\n")) . '...';
			$image				= $data['image'];
			$image				= isset($image['img_url']) ? $image['img_url'] : false;
						
			$shareCard = $this->getShareCard($title, $description, $image, $url, $tags, $shortDescription);
			
			$content = $data['content'] . $shareCard;
			
			$data['content'] = $content;
		}
		
		$pageData->setData($data);
	}
	
	protected function getCSS()
	{
		return '.share-card{
				width: 100%;
			}
			.share-content{	
				width:100%;
				display: inline-block;
				vertical-align:top;
				box-sizing:border-box;
				border-right: 1px solid #fff;
			}
			.share-image{
				position:relative;
				border-top-left-radius:5px; 
				border-top-right-radius:5px;
				overflow: hidden;
				background: #eee;
				max-height: 250px;
				text-align: center;
			}
			.share-image{
				width:100%;
			}
			.share-image p{
				line-height: 150px;
				width: 100%;
				color: #ccc;
				position: absolute;
			}
			.share-text{
				box-sizing:border-box;
				overflow: hidden;
				border-left: 2px solid #eee;
				border-right: 2px solid #eee;
				font-size: 0.85em;
				line-height: 1.2em;
				padding: 0.5em;
			}
			.share-text h4{
				padding: 0px;
				margin: 8px 0;
			}
			.share-text p{
				padding: 0px;
				margin: 0px;
			}
			.share-box{
				width:100%;
				display: inline-block;
				vertical-align:top;
				box-sizing:border-box;
				background: #eee;
				overflow:hidden;
				border-bottom-left-radius: 5px;
				border-bottom-right-radius:5px;
				height: auto;
			}
			.share-button, .share-headline{
				width: 33.2%;
				display:inline-block;
				height: auto;
				text-align: center;
			}
			.share-headline{
				display:none;
				font-size:0.85em;
				font-weight: 300;
				color: #444;
				padding: 10px 10px;
				box-sizing:border-box;
			}
			.share-headline h4{
				padding:0px;
				margin:0px;
				font-size:1em;
			}
			.share-button a,.share-button a:link,.share-button a:visited{
				display: block;
				width: 100%;
				line-height: 60px;
				background: transparent;
				color: #fff;
				font-size: 1.5em;
			}
			.share-button.twitter a{
				background:#C8E0EF;
			}
			.share-button.twitter a:hover{
				background:#1DA1F2;
			}
			.share-button.facebook a{
				background:#CDD3DE;
			}
			.share-button.facebook a:hover{
				background: #3B5998;
			}
			.share-button.xing a{
				background: #E8EBC3;
			}
			.share-button.xing a:hover{
				background: #cfdc00;
			}
			@media only screen and (min-width: 550px){
				.share-headline{
					display: inline-block;
				}
				.share-headline, .share-button{
					width: 25%;
				}
			}
			@media only screen and (min-width: 1050px){
				.share-image{
					border-top-right-radius: 0px;
					height:250px;
				}
				.share-text{
					height: 115px;					
					border-right: 0px;
					border-bottom: 2px solid #eee;
					border-bottom-left-radius: 5px;
				}
				.share-button, .share-headline{
					width: 100%;
				}
				.share-headline{
					padding: 40px 10px;
				}
				.share-content{	
					width:70%;
				}
				.share-box{
					width:29%;
					height: 365px;
					border-bottom-left-radius: 0px;
					border-top-right-radius: 5px;					
				}
				.share-button, .share-headline{
					height: 91px;
					border-bottom: 1px solid #fff;
				}
				.share-headline h4{
					font-size: 1.1em;
				}
				.share-button a,.share-button a:link,.share-button a:visited{
					line-height: 91px;
				}
			}
			';
	}
	
	protected function getShareCard($title, $description, $image, $url, $tags, $shortDescription)
	{
		return '
			<div class="share-card">
			  <div class="share-content">
				  <div class="share-image">
					<img src="' . $image . '">
					<p>No Image</p>
				  </div>
				  <div class="share-text">
					<h4>' . $title . '</h4>
					<p>' . $shortDescription . '</p>
				  </div>
			  </div><div class="share-box">
				<div class="share-headline">
					<h4>Als Lektüre empfehlen</h4>
				</div><div class="share-button twitter">
					<a href="https://twitter.com/intent/tweet?source=webclient&text='. urlencode($title . ': ' . $shortDescription) . '&url='. $url .'&via=cmsstash&hashtags=' . $tags . '" target="_blank"><i class="icon-twitter"></i></a>
				</div><div class="share-button facebook">
					<a href="https://facebook.com/sharer/sharer.php?u='. $url .'&t='. urlencode($title) .'" target="_blank"><i class="icon-facebook"></i></a>
				</div><div class="share-button xing">
					<a href="https://www.xing.com/spi/shares/new?url='. $url .'" target="_blank"><i class="icon-xing"></i></a>
				</div>
			  </div>
			</div>		
		';
	}
}
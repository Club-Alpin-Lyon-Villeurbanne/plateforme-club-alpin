<?php

namespace App\Legacy\Rss;

/**
 * Univarsel Feed Writer class.
 *
 * Genarate RSS 1.0, RSS2.0 and ATOM Feed
 *
 * @author      Anis uddin Ahmad <anisniit@gmail.com>
 *
 * @see        http://www.ajaxray.com/projects/rss
 */
class FeedWriter
{
    public const RSS1 = 'RSS 1.0';
    public const RSS2 = 'RSS 2.0';
    public const ATOM = 'ATOM';

    private $channels = [];  // Collection of channel elements
     private $items = [];  // Collection of items as object of FeedItem class.
     private $data = [];  // Store some other version wise data
     private $CDATAEncoding = [];  // The tag names which have to encoded as CDATA

     private $version = null;

    /**
     * Constructor.
     *
     * @param constant    the version constant (RSS1/RSS2/ATOM)
     */
    public function __construct($version = self::RSS2)
    {
        $this->version = $version;

        // Setting default value for assential channel elements
        $this->channels['title'] = $version.' Feed';
        $this->channels['link'] = 'https://www.ajaxray.com/blog';

        //Tag names to encode in CDATA
        $this->CDATAEncoding = ['description', 'content:encoded', 'summary'];
    }

    // Start # public functions ---------------------------------------------

    /**
     * Set a channel element.
     *
     * @param string  name of the channel tag
     * @param string  content of the channel tag
     */
    public function setChannelElement($elementName, $content)
    {
        $this->channels[$elementName] = $content;
    }

    /**
     * Set multiple channel elements from an array. Array elements
     * should be 'channelName' => 'channelContent' format.
     *
     * @param array   array of channels
     */
    public function setChannelElementsFromArray($elementArray)
    {
        if (!\is_array($elementArray)) {
            return;
        }
        foreach ($elementArray as $elementName => $content) {
            $this->setChannelElement($elementName, $content);
        }
    }

    /**
     * Genarate the actual RSS/ATOM file.
     */
    public function generateFeed()
    {
        $out = '';
        $out .= $this->printHead();
        $out .= $this->printChannels();
        $out .= $this->printItems();
        $out .= $this->printTale();

        return $out;
    }

    /**
     * Create a new FeedItem.
     *
     * @return object instance of FeedItem class
     */
    public function createNewItem()
    {
        return new FeedItem($this->version);
    }

    /**
     * Add a FeedItem to the main class.
     *
     * @param object  instance of FeedItem class
     */
    public function addItem($feedItem)
    {
        $this->items[] = $feedItem;
    }

    // Wrapper functions -------------------------------------------------------------------

    /**
     * Set the 'title' channel element.
     *
     * @param string  value of 'title' channel tag
     */
    public function setTitle($title)
    {
        $this->setChannelElement('title', $title);
    }

    /**
     * Set the 'description' channel element.
     *
     * @param string  value of 'description' channel tag
     */
    public function setDescription($desciption)
    {
        $this->setChannelElement('description', $desciption);
    }

    /**
     * Set the 'link' channel element.
     *
     * @param string  value of 'link' channel tag
     */
    public function setLink($link)
    {
        $this->setChannelElement('link', $link);
    }

    /**
     * Set the 'image' channel element.
     *
     * @param string  title of image
     * @param string  link url of the imahe
     * @param string  path url of the image
     */
    public function setImage($title, $link, $url)
    {
        $this->setChannelElement('image', ['title' => $title, 'link' => $link, 'url' => $url]);
    }

    /**
     * Set the 'about' channel element. Only for RSS 1.0.
     *
     * @param string  value of 'about' channel tag
     */
    public function setChannelAbout($url)
    {
        $this->data['ChannelAbout'] = $url;
    }

    /**
     * Genarates an UUID.
     *
     * @param string  an optional prefix
     *
     * @return string the formated uuid
     *
     * @author     Anis uddin Ahmad <admin@ajaxray.com>
     */
    public static function uuid($key = null, $prefix = '')
    {
        $key = (null == $key) ? uniqid(rand(), true) : $key;
        $chars = md5($key);
        $uuid = substr($chars, 0, 8).'-';
        $uuid .= substr($chars, 8, 4).'-';
        $uuid .= substr($chars, 12, 4).'-';
        $uuid .= substr($chars, 16, 4).'-';
        $uuid .= substr($chars, 20, 12);

        return $prefix.$uuid;
    }

    // End # public functions ----------------------------------------------

    // Start # private functions ----------------------------------------------

    /**
     * Prints the xml and rss namespace.
     */
    private function printHead()
    {
        $out = '<?xml version="1.0" encoding="utf-8"?>'."\n";

        if (self::RSS2 == $this->version) {
            $out .= '<rss version="2.0"
					xmlns:content="https://purl.org/rss/1.0/modules/content/"
					xmlns:wfw="https://wellformedweb.org/CommentAPI/"
				  >'.\PHP_EOL;
        } elseif (self::RSS1 == $this->version) {
            $out .= '<rdf:RDF
					 xmlns:rdf="https://www.w3.org/1999/02/22-rdf-syntax-ns#"
					 xmlns="https://purl.org/rss/1.0/"
					 xmlns:dc="https://purl.org/dc/elements/1.1/"
					>'.\PHP_EOL;
        } elseif (self::ATOM == $this->version) {
            $out .= '<feed xmlns="https://www.w3.org/2005/Atom">'.\PHP_EOL;
        }

        return $out;
    }

    /**
     * Closes the open tags at the end of file.
     */
    private function printTale()
    {
        $out = '';

        if (self::RSS2 == $this->version) {
            $out .= '</channel>'.\PHP_EOL.'</rss>';
        } elseif (self::RSS1 == $this->version) {
            $out .= '</rdf:RDF>';
        } elseif (self::ATOM == $this->version) {
            $out .= '</feed>';
        }

        return $out;
    }

    /**
     * Creates a single node as xml format.
     *
     * @param string  name of the tag
     * @param mixed   tag value as string or array of nested tags in 'tagName' => 'tagValue' format
     * @param array   Attributes(if any) in 'attrName' => 'attrValue' format
     *
     * @return string formatted xml tag
     */
    private function makeNode($tagName, $tagContent, $attributes = null)
    {
        $nodeText = '';
        $attrText = '';

        if (\is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $attrText .= " $key=\"$value\" ";
            }
        }

        if (\is_array($tagContent) && self::RSS1 == $this->version) {
            $attrText = ' rdf:parseType="Resource"';
        }

        $attrText .= (\in_array($tagName, $this->CDATAEncoding, true) && self::ATOM == $this->version) ? ' type="html" ' : '';
        $nodeText .= (\in_array($tagName, $this->CDATAEncoding, true)) ? "<{$tagName}{$attrText}><![CDATA[" : "<{$tagName}{$attrText}>";

        if (\is_array($tagContent)) {
            foreach ($tagContent as $key => $value) {
                $nodeText .= $this->makeNode($key, $value);
            }
        } else {
            //			$nodeText .= (in_array($tagName, $this->CDATAEncoding))? $tagContent : htmlentities($tagContent);
            //			$nodeText .= (in_array($tagName, $this->CDATAEncoding))? $tagContent : htmlentities($tagContent, ENT_COMPAT, 'UTF-8');
            $nodeText .= (\in_array($tagName, $this->CDATAEncoding, true)) ? $tagContent : str_replace(['&', '”', "'", ''], ['&', '"', "'", '<', '>'], $tagContent);
        }

        $nodeText .= (\in_array($tagName, $this->CDATAEncoding, true)) ? "]]></$tagName>" : "</$tagName>";

        return $nodeText.\PHP_EOL;
    }

    /**
     * @desc     Print channels
     */
    private function printChannels()
    {
        $out = '';

        //Start channel tag
        switch ($this->version) {
             case self::RSS2:
                 $out .= '<channel>'.\PHP_EOL;
                 break;
             case self::RSS1:
                 $out .= (isset($this->data['ChannelAbout'])) ? "<channel rdf:about=\"{$this->data['ChannelAbout']}\">" : "<channel rdf:about=\"{$this->channels['link']}\">";
                 break;
         }

        //Print Items of channel
        foreach ($this->channels as $key => $value) {
            if (self::ATOM == $this->version && 'link' == $key) {
                // ATOM prints link element as href attribute
                $out .= $this->makeNode($key, '', ['href' => $value]);
                //Add the id for ATOM
                $out .= $this->makeNode('id', $this->uuid($value, 'urn:uuid:'));
            } else {
                $out .= $this->makeNode($key, $value);
            }
        }

        //RSS 1.0 have special tag <rdf:Seq> with channel
        if (self::RSS1 == $this->version) {
            $out .= '<items>'.\PHP_EOL.'<rdf:Seq>'.\PHP_EOL;
            foreach ($this->items as $item) {
                $thisItems = $item->getElements();
                $out .= "<rdf:li resource=\"{$thisItems['link']['content']}\"/>".\PHP_EOL;
            }
            $out .= '</rdf:Seq>'.\PHP_EOL.'</items>'.\PHP_EOL.'</channel>'.\PHP_EOL;
        }

        return $out;
    }

    /**
     * Prints formatted feed items.
     */
    private function printItems()
    {
        $out = '';

        foreach ($this->items as $item) {
            $thisItems = $item->getElements();

            //the argument is printed as rdf:about attribute of item in rss 1.0
            $out .= $this->startItem($thisItems['link']['content']);

            foreach ($thisItems as $feedItem) {
                $out .= $this->makeNode($feedItem['name'], $feedItem['content'], $feedItem['attributes']);
            }
            $out .= $this->endItem();
        }

        return $out;
    }

    /**
     * Make the starting tag of channels.
     *
     * @param string  The vale of about tag which is used for only RSS 1.0
     */
    private function startItem($about = false)
    {
        $out = '';

        if (self::RSS2 == $this->version) {
            $out .= '<item>'.\PHP_EOL;
        } elseif (self::RSS1 == $this->version) {
            if ($about) {
                $out .= "<item rdf:about=\"$about\">".\PHP_EOL;
            } else {
                throw new \RuntimeException('link element is not set .\n It\'s required for RSS 1.0 to be used as about attribute of item');
            }
        } elseif (self::ATOM == $this->version) {
            $out .= '<entry>'.\PHP_EOL;
        }

        return $out;
    }

    /**
     * Closes feed item tag.
     */
    private function endItem()
    {
        $out = '';

        if (self::RSS2 == $this->version || self::RSS1 == $this->version) {
            $out .= '</item>'.\PHP_EOL;
        } elseif (self::ATOM == $this->version) {
            $out .= '</entry>'.\PHP_EOL;
        }

        return $out;
    }
}

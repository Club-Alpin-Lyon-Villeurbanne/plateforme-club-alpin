<?php

// RSS 0.90  Officially obsoleted by 1.0
// RSS 0.91, 0.92, 0.93 and 0.94  Officially obsoleted by 2.0
// So, define constants for RSS 1.0, RSS 2.0 and ATOM

define('RSS1', 'RSS 1.0');
define('RSS2', 'RSS 2.0');
define('ATOM', 'ATOM');

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
    private $channels = [];  // Collection of channel elements
    private $items = [];  // Collection of items as object of FeedItem class.
    private $data = [];  // Store some other version wise data
    private $CDATAEncoding = [];  // The tag names which have to encoded as CDATA

    private $version;

    /**
     * Constructor.
     *
     * @param constant    the version constant (RSS1/RSS2/ATOM)
     */
    public function __construct($version = RSS2)
    {
        $this->version = $version;

        // Setting default value for assential channel elements
        $this->channels['title'] = $version . ' Feed';
        $this->channels['link'] = 'https://www.ajaxray.com/blog';

        // Tag names to encode in CDATA
        $this->CDATAEncoding = ['description', 'content:encoded', 'summary'];
    }

    // Start # public functions ---------------------------------------------

    /**
     * Set a channel element.
     *
     * @param string  name of the channel tag
     * @param string  content of the channel tag
     *
     * @return void
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
     *
     * @return void
     */
    public function setChannelElementsFromArray($elementArray)
    {
        if (!is_array($elementArray)) {
            return;
        }
        foreach ($elementArray as $elementName => $content) {
            $this->setChannelElement($elementName, $content);
        }
    }

    /**
     * Genarate the actual RSS/ATOM file.
     *
     * @return void
     */
    public function generateFeed()
    {
        header('Content-type: text/xml');

        $this->printHead();
        $this->printChannels();
        $this->printItems();
        $this->printTale();
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
     *
     * @return void
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
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->setChannelElement('title', $title);
    }

    /**
     * Set the 'description' channel element.
     *
     * @param string  value of 'description' channel tag
     *
     * @return void
     */
    public function setDescription($desciption)
    {
        $this->setChannelElement('description', $desciption);
    }

    /**
     * Set the 'link' channel element.
     *
     * @param string  value of 'link' channel tag
     *
     * @return void
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
     *
     * @return void
     */
    public function setImage($title, $link, $url)
    {
        $this->setChannelElement('image', ['title' => $title, 'link' => $link, 'url' => $url]);
    }

    /**
     * Set the 'about' channel element. Only for RSS 1.0.
     *
     * @param string  value of 'about' channel tag
     *
     * @return void
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
    public function uuid($key = null, $prefix = '')
    {
        $key = (null == $key) ? uniqid(rand()) : $key;
        $chars = md5($key);
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 12);

        return $prefix . $uuid;
    }

    // End # public functions ----------------------------------------------

    // Start # private functions ----------------------------------------------

    /**
     * Prints the xml and rss namespace.
     *
     * @return void
     */
    private function printHead()
    {
        $out = '<?xml version="1.0" encoding="utf-8"?>' . "\n";

        if (RSS2 == $this->version) {
            $out .= '<rss version="2.0"
					xmlns:content="https://purl.org/rss/1.0/modules/content/"
					xmlns:wfw="https://wellformedweb.org/CommentAPI/"
				  >' . \PHP_EOL;
        } elseif (RSS1 == $this->version) {
            $out .= '<rdf:RDF
					 xmlns:rdf="https://www.w3.org/1999/02/22-rdf-syntax-ns#"
					 xmlns="https://purl.org/rss/1.0/"
					 xmlns:dc="https://purl.org/dc/elements/1.1/"
					>' . \PHP_EOL;
        } elseif (ATOM == $this->version) {
            $out .= '<feed xmlns="https://www.w3.org/2005/Atom">' . \PHP_EOL;
        }
        echo $out;
    }

    /**
     * Closes the open tags at the end of file.
     *
     * @return void
     */
    private function printTale()
    {
        if (RSS2 == $this->version) {
            echo '</channel>' . \PHP_EOL . '</rss>';
        } elseif (RSS1 == $this->version) {
            echo '</rdf:RDF>';
        } elseif (ATOM == $this->version) {
            echo '</feed>';
        }
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

        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $attrText .= " $key=\"$value\" ";
            }
        }

        if (is_array($tagContent) && RSS1 == $this->version) {
            $attrText = ' rdf:parseType="Resource"';
        }

        $attrText .= (in_array($tagName, $this->CDATAEncoding, true) && ATOM == $this->version) ? ' type="html" ' : '';
        $nodeText .= (in_array($tagName, $this->CDATAEncoding, true)) ? "<{$tagName}{$attrText}><![CDATA[" : "<{$tagName}{$attrText}>";

        if (is_array($tagContent)) {
            foreach ($tagContent as $key => $value) {
                $nodeText .= $this->makeNode($key, $value);
            }
        } else {
            //			$nodeText .= (in_array($tagName, $this->CDATAEncoding))? $tagContent : htmlentities($tagContent);
            //			$nodeText .= (in_array($tagName, $this->CDATAEncoding))? $tagContent : htmlentities($tagContent, ENT_COMPAT, 'UTF-8');
            $nodeText .= (in_array($tagName, $this->CDATAEncoding, true)) ? $tagContent : str_replace('”', '"', $tagContent);
        }

        $nodeText .= (in_array($tagName, $this->CDATAEncoding, true)) ? "]]></$tagName>" : "</$tagName>";

        return $nodeText . \PHP_EOL;
    }

    /**
     * @desc     Print channels
     *
     * @return void
     */
    private function printChannels()
    {
        // Start channel tag
        switch ($this->version) {
            case RSS2:
                echo '<channel>' . \PHP_EOL;
                break;
            case RSS1:
                echo (isset($this->data['ChannelAbout'])) ? "<channel rdf:about=\"{$this->data['ChannelAbout']}\">" : "<channel rdf:about=\"{$this->channels['link']}\">";
                break;
        }

        // Print Items of channel
        foreach ($this->channels as $key => $value) {
            if (ATOM == $this->version && 'link' == $key) {
                // ATOM prints link element as href attribute
                echo $this->makeNode($key, '', ['href' => $value]);
                // Add the id for ATOM
                echo $this->makeNode('id', $this->uuid($value, 'urn:uuid:'));
            } else {
                echo $this->makeNode($key, $value);
            }
        }

        // RSS 1.0 have special tag <rdf:Seq> with channel
        if (RSS1 == $this->version) {
            echo '<items>' . \PHP_EOL . '<rdf:Seq>' . \PHP_EOL;
            foreach ($this->items as $item) {
                $thisItems = $item->getElements();
                echo "<rdf:li resource=\"{$thisItems['link']['content']}\"/>" . \PHP_EOL;
            }
            echo '</rdf:Seq>' . \PHP_EOL . '</items>' . \PHP_EOL . '</channel>' . \PHP_EOL;
        }
    }

    /**
     * Prints formatted feed items.
     *
     * @return void
     */
    private function printItems()
    {
        foreach ($this->items as $item) {
            $thisItems = $item->getElements();

            // the argument is printed as rdf:about attribute of item in rss 1.0
            echo $this->startItem($thisItems['link']['content']);

            foreach ($thisItems as $feedItem) {
                echo $this->makeNode($feedItem['name'], $feedItem['content'], $feedItem['attributes']);
            }
            echo $this->endItem();
        }
    }

    /**
     * Make the starting tag of channels.
     *
     * @param string  The vale of about tag which is used for only RSS 1.0
     *
     * @return void
     */
    private function startItem($about = false)
    {
        if (RSS2 == $this->version) {
            echo '<item>' . \PHP_EOL;
        } elseif (RSS1 == $this->version) {
            if ($about) {
                echo "<item rdf:about=\"$about\">" . \PHP_EOL;
            } else {
                exit('link element is not set .\n It\'s required for RSS 1.0 to be used as about attribute of item');
            }
        } elseif (ATOM == $this->version) {
            echo '<entry>' . \PHP_EOL;
        }
    }

    /**
     * Closes feed item tag.
     *
     * @return void
     */
    private function endItem()
    {
        if (RSS2 == $this->version || RSS1 == $this->version) {
            echo '</item>' . \PHP_EOL;
        } elseif (ATOM == $this->version) {
            echo '</entry>' . \PHP_EOL;
        }
    }
}

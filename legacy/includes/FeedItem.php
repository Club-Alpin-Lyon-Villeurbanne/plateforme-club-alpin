<?php

 /**
  * Univarsel Feed Writer.
  *
  * FeedItem class - Used as feed element in FeedWriter class
  *
  * @author          Anis uddin Ahmad <anisniit@gmail.com>
  *
  * @see            http://www.ajaxray.com/projects/rss
  */
 class FeedItem
 {
     private $elements = [];    // Collection of feed elements
     private $version;

     /**
      * Constructor.
      *
      * @param    contant     (RSS1/RSS2/ATOM) RSS2 is default
      */
     public function __construct($version = RSS2)
     {
         $this->version = $version;
     }

     /**
      * Add an element to elements array.
      *
      * @param    srting  The tag name of an element
      * @param    srting  The content of tag
      * @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
      *
      * @return   void
      */
     public function addElement($elementName, $content, $attributes = null)
     {
         $this->elements[$elementName]['name'] = $elementName;
         $this->elements[$elementName]['content'] = $content;
         $this->elements[$elementName]['attributes'] = $attributes;
     }

     /**
      * Set multiple feed elements from an array.
      * Elements which have attributes cannot be added by this method.
      *
      * @param    array   array of elements in 'tagName' => 'tagContent' format
      *
      * @return   void
      */
     public function addElementArray($elementArray)
     {
         if (!is_array($elementArray)) {
             return;
         }
         foreach ($elementArray as $elementName => $content) {
             $this->addElement($elementName, $content);
         }
     }

     /**
      * Return the collection of elements in this feed item.
      *
      * @return   array
      */
     public function getElements()
     {
         return $this->elements;
     }

     // Wrapper functions ------------------------------------------------------

     /**
      * Set the 'dscription' element of feed item.
      *
      * @param    string  The content of 'description' element
      *
      * @return   void
      */
     public function setDescription($description)
     {
         $tag = (ATOM == $this->version) ? 'summary' : 'description';
         $this->addElement($tag, $description);
     }

     /**
      * @desc     Set the 'title' element of feed item
      *
      * @param    string  The content of 'title' element
      *
      * @return   void
      */
     public function setTitle($title)
     {
         // Title does not support HTML, let's strip this using htmlentities
         $this->addElement('title', htmlentities($title));
     }

     /**
      * Set the 'date' element of feed item.
      *
      * @param    string  The content of 'date' element
      *
      * @return   void
      */
     public function setDate($date)
     {
         if (!is_numeric($date)) {
             $date = strtotime($date);
         }

         if (ATOM == $this->version) {
             $tag = 'updated';
             $value = date(\DATE_ATOM, $date);
         } elseif (RSS2 == $this->version) {
             $tag = 'pubDate';
             $value = date(\DATE_RSS, $date);
         } else {
             $tag = 'dc:date';
             $value = date('Y-m-d', $date);
         }

         $this->addElement($tag, $value);
     }

     /**
      * Set the 'link' element of feed item.
      *
      * @param    string  The content of 'link' element
      *
      * @return   void
      */
     public function setLink($link)
     {
         if (RSS2 == $this->version || RSS1 == $this->version) {
             $this->addElement('link', $link);
         } else {
             $this->addElement('link', '', ['href' => $link]);
             $this->addElement('id', FeedWriter::uuid($link, 'urn:uuid:'));
         }
     }

     /**
      * Set the 'encloser' element of feed item
      * For RSS 2.0 only.
      *
      * @param    string  The url attribute of encloser tag
      * @param    string  The length attribute of encloser tag
      * @param    string  The type attribute of encloser tag
      *
      * @return   void
      */
     public function setEncloser($url, $length, $type)
     {
         $attributes = ['url' => $url, 'length' => $length, 'type' => $type];
         $this->addElement('enclosure', '', $attributes);
     }
 } // end of class FeedItem

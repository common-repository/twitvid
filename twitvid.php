<?php
/*
Plugin Name: TwitVid
Plugin URI: https://wordpress.org/plugins/twitvid/
Description: Converts links to videos hostet at twitvid.com to embedded players. 
Version: 0.3
Author: kornelly
Author URI: https://profiles.wordpress.org/kornelly/
*/

/**
 * v0.3 2014-10-13 updated for wordpress 4.x 
 * v0.2 2010-06-07 minor xhtml improvements
 * v0.1 2009-09-21 initial release
 */
class TwitVid {
  var $id;
  var $title;
  var $plugin_url;
  var $version;
  var $name;
  var $url;
  var $options;
  var $locale;

  function TwitVid() {
    $this->id         = 'twitvid';
    $this->title      = 'TwitVid';
    $this->version    = '0.3';
    $this->plugin_url = 'https://wordpress.org/plugins/twitvid/';
    $this->name       = 'TwitVid v'. $this->version;
    $this->url        = get_bloginfo('wpurl'). '/wp-content/plugins/' . $this->id;
    $this->index      = -1;

	  $this->locale     = get_locale();
    $this->path       = dirname(__FILE__);

	  if(empty($this->locale)) {
		  $this->locale = 'en_US';
    }

    load_textdomain($this->id, sprintf('%s/%s.mo', $this->path, $this->locale));

    $this->loadOptions();

    if(!is_admin()) {
      add_filter('wp_head', array(&$this, 'blogHeader'));
      add_filter('the_content', array(&$this, 'contentFilter'));
    }
    else {
      add_action('admin_menu', array( &$this, 'optionMenu')); 
    }

  }

  function optionMenu() {
    add_options_page($this->title, $this->title, 8, __FILE__, array(&$this, 'optionMenuPage'));
  }

  function optionMenuPage() {
?>
<div class="wrap">
<h2><?=$this->title?></h2>
<div align="center"><p><?=$this->name?> <a href="<?php print( $this->plugin_url ); ?>" target="_blank">Plugin Homepage</a></p></div> 
<?php
  if(isset($_POST[$this->id])) {
    /**
     * nasty checkbox handling
     */
    foreach(array('download', 'link', 'embed') as $field ) {
      if(!isset($_POST[$this->id][$field])) {
        $_POST[$this->id][$field] = 0;
      }
    }

    $this->updateOptions($_POST[$this->id]);

    echo '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved!', $this->id) . '</strong></p></div>'; 
  }
?>
<form method="post" action="options-general.php?page=<?=$this->id?>/<?=$this->id?>.php">
<input type="hidden" name="<?=$this->id?>[dummy]" value="90" />
<table class="form-table">

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="<?=$this->id?>[link]" type="checkbox" id="" value="1" <?php echo $this->options['link']=='1'?'checked="checked"':''; ?> />
<?php _e('Show a link to the original video page below the player?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="<?=$this->id?>[download]" type="checkbox" id="" value="1" <?php echo $this->options['download']=='1'?'checked="checked"':''; ?> />
<?php _e('Show a video-download link below the player?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="<?=$this->id?>[embed]" type="checkbox" id="" value="1" <?php echo $this->options['embed']=='1'?'checked="checked"':''; ?> />
<?php _e('Show an embed this video button below the player?', $this->id); ?></label>
</th>
</tr>


</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('save', $this->id); ?>" class="button" />
</p>
</form>

</div>
<?php
  }

  function updateOptions($options) {

    foreach($this->options as $k => $v) {
      if(array_key_exists( $k, $options)) {
        $this->options[ $k ] = trim($options[ $k ]);
      }
    }

		update_option($this->id, $this->options);
	}
  
  function loadOptions() {

#delete_option($this->id);

    $this->options = get_option($this->id);

    if(!$this->options) {
      $this->options = array(
        'installed' => time(),
        'link'      => 1,
        'download'  => 1,
        'embed'     => 1
			);

      add_option($this->id, $this->options, $this->name, 'yes');
    }
  }
  
  function getEmbed($id) {
    if(intval($this->options['embed']) == 1) {
      return sprintf('<input id="twitvid-embed-%d" type="text" value=\'%s<br /><small><a href="https://wordpress.org/plugins/twitvid/">Twitvid</a></small>\' onclick="this.focus();this.select();" /></span>', $this->index, $this->getPlayer($id));
    }
  }
  
  function getToolbar($id) {
  
    $tools = array();
    
    if(intval($this->options['link']) == 1) {
      $tools[] = sprintf('<a href="http://twitvid.com/%s" target="_blank" rel="nofollow">Link</a>', $id);
    }
    
    if(intval($this->options['download']) == 1) {
      $tools[] = sprintf('<a href="http://www.degrab.de/?src=twitvid-wordpress-plugin&version=%s&url=http://twitvid.com/%s" target="_blank">Download</a>', $this->version, $id);
    }
    
    if(intval($this->options['embed']) == 1) {
      $tools[] = sprintf('<a href="" onclick="javascript:document.getElementById(\'twitvid-embed-%d\').style.display=\'inline\';this.style.display=\'none\';return false;">Embed</a>', $this->index);
    }

    if(count($tools) > 0) {
      return '<tr><td>'. implode(' | ', $tools). $this->getEmbed($id). '</td></tr>';
    }
  }
  
  function getPlayer($id) {
    return sprintf('<object width="425" height="344"><param name="movie" value="http://www.twitvid.com/player/%s"></param><param name="allowFullScreen" value="true"></param><embed type="application/x-shockwave-flash" src="http://www.twitvid.com/player/%s" quality="high" allowscriptaccess="always" allowNetworking="all" allowfullscreen="true" wmode="transparent" height="344" width="425"></object>', $id, $id);
  }
  
  function getCode($id) {
    $this->index ++;

    return sprintf('<div class="twitvid"><table border="0" cellpadding="0" cellspacing="0"><tr><td>%s</td></tr><tr><td align="right"><small><a href="https://wordpress.org/plugins/twitvid/">Twitvid Plugin</a></small></td></tr>%s</tr></table>',
      $this->getPlayer($id),
      $this->getToolbar($id)
    );
  }

  function contentFilter($buffer) {

    if(intval(preg_match_all('#<a[^>]*href="(http://(www\.)?twitvid\.com/(.*?))"(.*?)[^>]*>(.*?)</a>#is', $buffer, $matches, PREG_SET_ORDER)) > 0) {
    
      foreach($matches as $match) {
        $buffer = str_replace($match[0], $this->getCode($match[3]), $buffer);
      }
      
    }
    
    return $buffer;
  }

  function blogHeader() {
    printf('<meta name="%s" content="%s/%s" />' . "\n", $this->id, $this->id, $this->version);
    printf('<link rel="stylesheet" href="%s/styles/%s.css" type="text/css" media="screen" />'. "\n", $this->url, $this->id);
  }

}

add_action( 'plugins_loaded', create_function( '$TwitVid_dww32a9', 'global $TwitVid; $TwitVid = new TwitVid();' ) );

?>
<?php

compareWithOriginal::$base_dir = dirname(__FILE__) . '/../../';

require_once compareWithOriginal::$base_dir . 'src/phpbrowscap/Browscap.php';

use phpbrowscap\Browscap;

$x = new compareWithOriginal();

/**
 * Compares get_browser results for all matches in browscap.ini with results from Browscap class.
 * Also compares the execution times.
 */
class compareWithOriginal
{
  public static $base_dir;
  
  /**
   * @var Browscap
   */
  protected $browscap;
  
  protected $warnings = array(
    'Mozilla/5.0 (compatible; MSIE 7.0; MSIE 6.0; ScanAlert; +http://www.scanalert.com/bot.jsp) Firefox/2.0.0.3',
    'Automated Browscap.ini Updater. To report issues contact us at+http://www.skycomp.ca',
    'CatchBot/; +http://www.catchbot.com',
    'CatchBot/XY; +http://www.catchbot.com',
    'facebookexternalhit/1.0 (+http://www.facebook.com/externalhit_uatext.php)',
    'facebookexternalhit/1.0 (+httpXY://www.facebook.com/externalhit_uatext.php)XY',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'facebookexternalhit/1.1 (+httpXY://www.facebook.com/externalhit_uatext.php)XY',
    'Mozilla/5.0 (compatible; AhrefsBot/; +http://ahrefs.com/robot/)',
    'Mozilla/5.0 (compatible; AhrefsBot/4.0; +http://ahrefs.com/robot/)',
    'Mozilla/5.0 (compatible; AhrefsBot/XY; +http://ahrefs.com/robot/)',
    'Mozilla/5.0 (compatible; aiHitBot/; +http://www.aihit.com/)',
    'Mozilla/5.0 (compatible; aiHitBotXY/XY; +http://www.aihit.com/)',
    'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
    'Mozilla/5.0 (compatible; Chirp/1.0; +http://www.binarycanary.com/chirp.cfm)',
    'Mozilla/5.0 (compatible; Crawly/1.; +http:///crawler.html)',
    'Mozilla/5.0 (compatible; Crawly/1.XY; +http://XY/crawler.html)',
    'Mozilla/5.0 (compatible; Diffbot/0.1; +http://www.diffbot.com)',
    'Mozilla/5.0 (compatible; FriendFeedBot/0.; +Http://friendfeed.com/about/bot)',
    'Mozilla/5.0 (compatible; FriendFeedBot/0.XY; +Http://friendfeed.com/about/bot)',
    'Mozilla/5.0 (compatible; MSIE 7.0; MSIE 6.0; ScanAlert; +http://www.scanalert.com/bot.jsp) Firefox/',
    'Mozilla/5.0 (compatible; MSIE 7.0; MSIE 6.0; ScanAlert; +http://www.scanalert.com/bot.jsp) Firefox/XY',
    'Mozilla/5.0 (compatible; ScoutJet; +http://www.scoutjet.com/)',
    'Mozilla/5.0 (compatible; Scrubby/; +http://www.scrubtheweb.com/abs/meta-check.html)',
    'Mozilla/5.0 (compatible; Scrubby/XY; +http://www.scrubtheweb.com/abs/meta-check.html)',
    'Mozilla/5.0 (compatible; SuchbaerBot/0.; +http://bot.suchbaer.de/info.html)',
    'Mozilla/5.0 (compatible; SuchbaerBot/0.XY; +http://bot.suchbaer.de/info.html)',
    'Mozilla/5.0 (compatible; TweetedTimes Bot/1.0; +http://tweetedtimes.com)',
    'Mozilla/5.0 (compatible; Twitturls; +http://twitturls.com)',
    'Mozilla/5.0 (compatible; unwrapbot/2.; +http://www.unwrap.jp)',
    'Mozilla/5.0 (compatible; unwrapbot/2.XY; +http://www.unwrap.jpXY)',
    'Mozilla/5.0 (compatible; Webscan v0.; +http://otc.dyndns.org/webscan/)',
    'Mozilla/5.0 (compatible; Webscan v0.XY; +http://otc.dyndns.org/webscan/)',
    'msnbot-NewsBlogs/2. (+http://search.msn.com/msnbot.htm)',
    'msnbot-NewsBlogs/2.XY (+http://search.msn.com/msnbot.htm)',
    'SosospiderZ(+http://help.soso.com/webspider.htm)',
    'UniversalFeedParser/4. +http://feedparser.org/',
    'UniversalFeedParser/4.XY +http://feedparser.org/',
    
    'Gigabot',
    'Lycos',
    'Nutch',
    'Research Projects',
    'BlackBerry',
    'Sleipnir',
      
    'DefaultProperties',
    'Ask',
    'Baidu',
    'Google',
    'MSN',
    'Yahoo',
    'Yandex',
    'Best of the Web',
    'Boitho',
    'Convera',
    'DotBot',
    'Entireweb',
    'Envolk',
    'Exalead',
    'Facebook',
    'Fast/AllTheWeb',
    'Ilse',
    'iVia Project',
    'Jayde Online',
    'Snap',
    'Sogou',
    'YodaoBot',
    'General Crawlers',
    'Search Engines',
    'BitTorrent Clients',
    'Hatena',
    'Internet Archive',
    'Webaroo',
    'Word Press',
    'Copyright/Plagiarism',
    'DNS Tools',
    'Download Managers',
    'E-Mail Harvesters',
    'Feeds Blogs',
    'Feeds Syndicators',
    'General RSS',
    'HTML Validators',
    'Image Crawlers',
    'Link Checkers',
    'Microsoft',
    'Miscellaneous Browsers',
    'Offline Browsers',
    'Online Scanners',
    'Proxy Servers',
    'Rippers',
    'Site Monitors',
    'Social Networking',
    'Translators',
    'Version Checkers',
    'W3C',
    'Become',
    'Blue Coat Systems',
    'FeedHub',
    'Internet Content Rating Association',
    'Nagios',
    'NameProtect',
    'Netcraft',
    'NewsGator',
    'Chromium 10.0',
    'Chromium 11.0',
    'Chromium 12.0',
    'Chromium 13.0',
    'Chromium 14.0',
    'Chromium 15.0',
    'Chromium 16.0',
    'Chromium 17.0',
    'Chromium 18.0',
    'Chromium 19.0',
    'Chromium 20.0',
    'Chromium 21.0',
    'Chromium 22.0',
    'Chromium 23.0',
    'Chromium 24.0',
    'Chromium 25.0',
    'Chromium 26.0',
    'Chromium 27.0',
    'Chromium 28.0',
    'Chromium 29.0',
    'Chromium 30.0',
    'Chromium 31.0',
    'Chromium 32.0',
    'Chromium 6.0',
    'Chromium 7.0',
    'Chromium 8.0',
    'Chromium 9.0',
    'Chromium Generic',
    'Chrome 10.0',
    'Chrome 11.0',
    'Chrome 12.0',
    'Chrome 13.0',
    'Chrome 14.0',
    'Chrome 15.0',
    'Chrome 16.0',
    'Chrome 17.0',
    'Chrome 18.0',
    'Chrome 19.0',
    'Chrome 20.0',
    'Chrome 21.0',
    'Chrome 22.0',
    'Chrome 23.0',
    'Chrome 24.0',
    'Chrome 25.0',
    'Chrome 26.0',
    'Chrome 27.0',
    'Chrome 28.0',
    'Chrome 29.0',
    'Chrome 30.0',
    'Chrome 31.0',
    'Chrome 32.0',
    'Chrome 6.0',
    'Chrome 7.0',
    'Chrome 8.0',
    'Chrome 9.0',
    'Chrome Generic',
    'Google Code',
    'Iron 10.0',
    'Iron 11.0',
    'Iron 12.0',
    'Iron 13.0',
    'Iron 14.0',
    'Iron 15.0',
    'Iron 16.0',
    'Iron 17.0',
    'Iron 18.0',
    'Iron 19.0',
    'Iron 20.0',
    'Iron 21.0',
    'Iron 22.0',
    'Iron 23.0',
    'Iron 24.0',
    'Iron 25.0',
    'Iron 26.0',
    'Iron 27.0',
    'Iron 28.0',
    'Iron 29.0',
    'Iron 30.0',
    'Iron 31.0',
    'Iron 32.0',
    'Iron 6.0',
    'Iron 7.0',
    'Iron 8.0',
    'Iron 9.0',
    'Iron Generic',
    'Rockmelt',
    'Arora 0.10',
    'Arora 0.11',
    'Arora 0.8',
    'Arora 0.9',
    'Arora Generic',
    'Media Players',
    'Microsoft Zune',
    'Nintendo Wii',
    'Windows Media Player',
    'QuickTime 10.0',
    'QuickTime 5.0',
    'QuickTime 6.0',
    'QuickTime 7.0',
    'QuickTime 7.6',
    'Lotus Notes 5.0',
    'Lotus Notes 6.0',
    'Microsoft Outlook 2007',
    'Microsoft Outlook 2010',
    'Windows Live Mail',
    'Blazer',
    'Brew',
    'DoCoMo',
    'Dolfin',
    'IEMobile',
    'Jasmine',
    'KDDI',
    'Kindle',
    'Maemo',
    'Motorola Internet Browser',
    'Nokia',
    'Openwave Mobile Browser',
    'Palm Web',
    'Playstation',
    'Pocket PC',
    'Polaris',
    'SEMC Browser',
    'Silk',
    'Skyfire',
    'Teleca',
    'UC Browser',
    'Android Browser 3.0',
    'Android Browser 4.0',
    'Mobile Safari 3.0',
    'Mobile Safari 3.1',
    'Mobile Safari 4.0',
    'Mobile Safari 5.0',
    'Mobile Safari 5.1',
    'Mobile Safari 6.0',
    'Mobile Safari 6.1',
    'Mobile Safari 7.0',
    'Opera Mini 2.0',
    'Opera Mini 3.0',
    'Opera Mini 4.0',
    'Opera Mini 5.0',
    'Opera Mini 6.0',
    'Opera Mini 7.0',
    'Opera Mini 8.0',
    'Opera Mini 9.0',
    'Opera Mini Generic',
    'Opera Mobile',
    'NetFront 2.0',
    'NetFront 3.0',
    'Boxee',
    'GoogleTV',
    'Netbox',
    'PowerTV',
    'WebTV',
    'Amaya',
    'Links',
    'Lynx',
    'Mosaic',
    'w3m',
    'ELinks 0.10',
    'ELinks 0.11',
    'ELinks 0.12',
    'ELinks 0.13',
    'ELinks 0.9',
    'Camino',
    'Chimera',
    'Dillo',
    'Emacs/W3',
    'fantomas',
    'FrontPage',
    'Galeon',
    'HP Secure Web Browser',
    'IBrowse',
    'iCab',
    'iSiloX',
    'Lycoris Desktop/LX',
    'NetPositive',
    'Shiira',
    'K-Meleon 1.0',
    'K-Meleon 1.1',
    'K-Meleon 1.5',
    'K-Meleon 1.6',
    'Konqueror 3.0',
    'Konqueror 4.0',
    'Konqueror 4.5',
    'Konqueror 4.6',
    'Konqueror 4.7',
    'Konqueror 4.8',
    'Safari 2.0',
    'Safari 3.0',
    'Safari 4.0',
    'Safari 5.0',
    'Safari 5.1',
    'Safari 6.0',
    'Safari 6.1',
    'Safari 7.0',
    'Safari Generic',
    'Lunascape 5.0',
    'Lunascape 5.1',
    'Lunascape 6.0',
    'Maxthon 2.0',
    'Maxthon 3.0',
    'OmniWeb 5.0',
    'OmniWeb 5.10',
    'OmniWeb 5.11',
    'Opera 10.00',
    'Opera 11.00',
    'Opera 11.10',
    'Opera 11.50',
    'Opera 11.60',
    'Opera 12.00',
    'Opera 12.10',
    'Opera 12.11',
    'Opera 12.12',
    'Opera 12.13',
    'Opera 12.14',
    'Opera 12.15',
    'Opera 12.16',
    'Opera 2.00',
    'Opera 3.00',
    'Opera 4.00',
    'Opera 5.00',
    'Opera 6.00',
    'Opera 7.00',
    'Opera 8.00',
    'Opera 9.00',
    'Opera Generic',
    'Netscape 4.0',
    'Netscape 4.7',
    'Netscape 4.8',
    'Netscape 6.0',
    'Netscape 7.0',
    'Netscape 8.0',
    'Netscape 9.0',
    'Palemoon',
    'SeaMonkey 1.0',
    'SeaMonkey 1.1',
    'SeaMonkey 2.0',
    'SeaMonkey 2.1',
    'Seamonkey 2.2',
    'Seamonkey 2.3',
    'Seamonkey 2.4',
    'Seamonkey 2.5',
    'Flock 1.0',
    'Flock 2.0',
    'Flock 3.0',
    'Firefox 1.0',
    'Firefox 10.0',
    'Firefox 11.0',
    'Firefox 12.0',
    'Firefox 13.0',
    'Firefox 14.0',
    'Firefox 15.0',
    'Firefox 16.0',
    'Firefox 17.0',
    'Firefox 18.0',
    'Firefox 19.0',
    'Firefox 2.0',
    'Firefox 20.0',
    'Firefox 21.0',
    'Firefox 22.0',
    'Firefox 23.0',
    'Firefox 24.0',
    'Firefox 3.0',
    'Firefox 3.1',
    'Firefox 3.5',
    'Firefox 3.6',
    'Firefox 4.0',
    'Firefox 4.2',
    'Firefox 5.0',
    'Firefox 6.0',
    'Firefox 7.0',
    'Firefox 8.0',
    'Firefox 9.0',
    'Fennec 1.0',
    'Fennec 10.0',
    'Fennec 4.0',
    'Fennec 5.0',
    'Fennec 6.0',
    'Fennec 7.0',
    'Thunderbird 1.0',
    'Thunderbird 1.5',
    'Thunderbird 10.0',
    'Thunderbird 11.0',
    'Thunderbird 12.0',
    'Thunderbird 13.0',
    'Thunderbird 14.0',
    'Thunderbird 2.0',
    'Thunderbird 3.0',
    'Thunderbird 3.1',
    'Thunderbird 5.0',
    'Thunderbird 6.0',
    'Thunderbird 7.0',
    'Thunderbird 8.0',
    'Thunderbird 9.0',
    'Iceweasel',
    'Mozilla 1.0',
    'Mozilla 1.1',
    'Mozilla 1.2',
    'Mozilla 1.3',
    'Mozilla 1.4',
    'Mozilla 1.5',
    'Mozilla 1.6',
    'Mozilla 1.7',
    'Mozilla 1.8',
    'Mozilla 1.9',
    'AOL 9.0/IE 5.5',
    'AOL 9.0/IE 6.0',
    'AOL 9.0/IE 7.0',
    'AOL 9.0/IE 8.0',
    'AOL 9.1/IE 7.0',
    'AOL 9.1/IE 8.0',
    'AOL 9.5',
    'AOL 9.6',
    'AOL Generic',
    'IE 1.0',
    'IE 1.5',
    'IE 10.0',
    'IE 2.0',
    'IE 3.0',
    'IE 4.0',
    'IE 5.0',
    'IE 6.0',
    'IE 7.0',
    'IE 8.0',
    'IE 9.0'
  );
  
  protected $browscap_ini_path;
  
  protected $user_agents = array();
  
  protected $properties = array();
  
  public function __construct()
  {
    $this->browscap = new Browscap(self::$base_dir . 'cache/');
    
    $this->browscap_ini_path = ini_get('browscap');
    
    $this->browscap->localFile = $this->browscap_ini_path;
    $this->browscap->updateMethod = Browscap::UPDATE_LOCAL;
    
    $this->getUserAgents();
    
    $this->checkProperties();
    
    $this->runTest();
  }
  
  protected function runTest()
  {
    echo "\n";
    
    $errors_count = 0;
    $warnings_count = 0;
    $lib_time = 0;
    $lib_max_time = 0;
    $bc_time = 0;
    $bc_max_time = 0;
    
    foreach ($this->user_agents as $i => $user_agent)
    {
      $t = microtime(true);
      $lib_result = get_browser($user_agent);
      $ct = microtime(true) - $t;
      $lib_time += $ct;
      $lib_max_time = max($lib_max_time, $ct);
      
      $t = microtime(true);
      $bc_result = $this->browscap->getBrowser($user_agent);
      $ct = microtime(true) - $t;
      $bc_time += $ct;
      $bc_max_time = max($bc_max_time, $ct);
      
      $errors = array();
      
      if ($user_agent == Browscap::BROWSCAP_VERSION_KEY)
      {
        if ($this->browscap->getSourceVersion() != $lib_result->version)
        {
          $errors[] = "Source file version incorrect: {$lib_result->version} != {$this->browscap->getSourceVersion()}";
        }
      }
      else foreach ($this->properties as $bc_prop => $lib_prop)
      {
        $lib_value = $lib_result->{$lib_prop};
        
        $bc_value = $bc_result->{$bc_prop};
        
        if ($lib_value != $bc_value)
        {
          $errors[] = "$bc_prop: $lib_value != $bc_value";
        }
      }
      
      if ($errors && in_array($user_agent, $this->warnings))
      {
        $warnings_count++;
        
        echo "get_browser() error fixed for '$user_agent'\n\n";
      }
      elseif ($errors)
      {
        $errors_count++;
        
        $errors[] = "regex: '{$lib_result->browser_name_regex}' vs '{$bc_result->browser_name_regex}'";
        
        echo "Errors for '$user_agent'\n" . implode("\n", $errors) . "\n\n";
      }
      
      if ($i % 500 == 0 && $i != 0)
      {
        $this->printReport($i, $errors_count, $warnings_count, $lib_time, $lib_max_time, $bc_time, $bc_max_time);
      }
    }
    
    $this->printReport($i, $errors_count, $warnings_count, $lib_time, $lib_max_time, $bc_time, $bc_max_time);
  }
  
  protected function printReport($i, $errors_count, $warnings_count, $lib_time, $lib_max_time, $bc_time, $bc_max_time)
  {
    $lt = number_format($lib_time, 2) . ' sec';
    $ltpp = number_format($lib_time / $i * 1000, 1) . ' ms / item';
    $ltm = number_format($lib_max_time * 1000, 1) . ' ms';
    
    $bt = number_format($bc_time, 2) . ' sec';
    $btpp = number_format($bc_time / $i * 1000, 1) . ' ms / item';
    $btm = number_format($bc_max_time * 1000, 1) . ' ms';
    
    echo "$i: report\n";
    echo "$errors_count errors\n";
    echo "$warnings_count get_browser() errors fixed\n";
    echo "lib time: $lt ($ltpp, max $ltm)\n";
        echo "bc time: $bt ($btpp, max $btm)\n";
        echo "\n";
  }
  
  protected function checkProperties()
  {
    $lib_properties = get_object_vars(get_browser('x'));
    
    $bc_properties = get_object_vars($this->browscap->getBrowser('x'));
    
    foreach (array_keys($bc_properties) as $bc_prop)
    {
      if ('browser_name' == $bc_prop)
      {
        continue;
      }
      
      if (!isset($lib_properties[strtolower($bc_prop)]))
      {
        throw new Exception("Property `$bc_prop` from Browscap doesn't match anything in get_browser.");
      }
      
      if ('browser_name_regex' != $bc_prop)
      {
        $this->properties[$bc_prop] = strtolower($bc_prop);
      }
      
      unset($lib_properties[strtolower($bc_prop)]);
    }
    
    unset($lib_properties['renderingengine_description']);
    
    if (!empty($lib_properties))
    {
      throw new Exception('There are ' . count($lib_properties) . '(' . implode(', ', array_keys($lib_properties)) . ') properties in get_browser that do not match those in Browscap.');
    }
  }
  
  protected function getUserAgents()
  {
    if (empty($this->browscap_ini_path))
    {
      throw new Exception("You have to have php.ini 'browscap' directive set to run this test.");
    }
    
    if (!is_file($this->browscap_ini_path))
    {
      throw new Exception("There is no browscap file at {$this->browscap_ini_path} location.");
    }
    
    if (version_compare(PHP_VERSION, '5.3.0', '>='))
    {
      $browscap_data = parse_ini_file($this->browscap_ini_path, true, INI_SCANNER_RAW);
    }
    else
    {
      $browscap_data = parse_ini_file($this->browscap_ini_path, true);
    }
    
    $browscap_data = array_keys($browscap_data);
    
    $this->user_agents = explode("\n", file_get_contents('user-agent-examples.txt'));
    
    $this->user_agents[] = uniqid('Fake User Agent ', true);
    
    foreach ($browscap_data as $pattern)
    {
      $this->user_agents[] = str_replace(array('?', '*'), array('Z', 'XY'), $pattern);
      
      if (false !== strpos($pattern, '*'))
      {
        $this->user_agents[] = str_replace(array('?', '*'), array('Z', ''), $pattern);
      }
    }
    
    echo number_format(count($this->user_agents)) . " possible user agents\n";
  }
}


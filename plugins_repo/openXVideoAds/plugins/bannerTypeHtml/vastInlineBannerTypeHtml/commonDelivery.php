<?php 
/*
+---------------------------------------------------------------------------+
| OpenX v${RELEASE_MAJOR_MINOR}                                                                |
| =======${RELEASE_MAJOR_MINOR_DOUBLE_UNDERLINE}                                                                |
|                                                                           |
| Copyright (c) 2003-2009 OpenX Limited                                     |
| For contact details, see: http://www.openx.org/                           |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/


require_once MAX_PATH . '/plugins/bannerTypeHtml/vastInlineBannerTypeHtml/common.php';

function deliverVastAd($pluginType, &$aBanner, $zoneId=0, $source='', $ct0='', $withText=false, $logClick=true, $logView=true, $useAlt=false, $loc, $referer)
{
    activatePluginErrorHandler();
    global $format;
    extractVastParameters( $aBanner );
    //debugDump('DeliveryBannerData', $aBanner );
    $aOutputParams = array();
    $aOutputParams['format'] = $format;
    $aOutputParams['videoPlayerSwfUrl'] = getVideoPlayerUrl('path_to_videoplayer_swf_used_when_viewing_video_in_openx_admintool');
    $aOutputParams['videoPlayerJsUrl'] = getVideoPlayerUrl('path_to_videoplayer_js_used_when_viewing_video_in_openx_admintool');
    $aOutputParams['videoPlayerRtmpPluginUrl'] = getVideoPlayerUrl('path_to_videoplayer_rtmp_plugin_used_when_viewing_video_in_openx_admintool');
    $aOutputParams['videoPlayerControlsPluginUrl'] = getVideoPlayerUrl('path_to_videoplayer_controls_plugin_used_when_viewing_video_in_openx_admintool');
    
    // due to some wierdness with ini file handling - have to use "false" - with quotes - in the ini file to switch this off 
    $aOutputParams['videoPlayerAutoPlay'] = getVideoPlayerSetting('auto_play_when_viewing_video_in_openx_admintool', "true" );

    prepareCompanionBanner($aOutputParams, $aBanner, $zoneId, $source, $ct0, $withText, $logClick, $logView, $useAlt, $loc, $referer);
    prepareVideoParams( $aOutputParams, $aBanner );
    prepareOverlayParams( $aOutputParams, $aBanner );
    //$bannerMetaData = "BANNER DATA <pre>" . print_r($aBanner, true) . "</pre>";
    //debuglog( $bannerMetaData );
    $player = "";
    prepareTrackingParams( $aOutputParams, $aBanner, $zoneId, $source, $ct0, $logClick, $referer );
    if ( $format == 'vast' ){
       //$vastAdDescription = htmlentities( $vastAdDescription );
        if ( $pluginType == 'vastInline' ){
            $player .= renderVastOutput( $aOutputParams, $pluginType, "INLINE VIDEO AD" );
        }
        else if ( $pluginType == 'vastOverlay' ) {
            $player .= renderVastOutput( $aOutputParams, $pluginType, "OVERLAY VIDEO AD" );
        }
        else {
            throw new Exception("Uncatered for vast plugintype|$pluginType|");
        }
    } /* end of vast format required */ 
    else {
        if ( $pluginType == 'vastInline' ){
            $player .= renderPlayerInPage($aOutputParams);
            $player .= renderCompanionInAdminTool($aOutputParams);
        }
        else if ( $pluginType == 'vastOverlay' ) {
            $player .= renderOverlayInAdminTool($aOutputParams); 
            $player .= renderCompanionInAdminTool($aOutputParams);
            $player .= renderPlayerInPage($aOutputParams);
        }
        else {
            throw new Exception("Uncatered for vast plugintype|$pluginType|");
        }
    }/* js player required */
    dectivatePluginErrorHandler();
    return $player;
}

function getVastXMLHeader($charset)
{
	$header   = "<?xml version=\"1.0\" encoding=\"$charset\"?>\n";
    $header  .= "<VideoAdServingTemplate xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"vast.xsd\">\n";
    return $header;
}

function getVastXMLFooter()
{
	$footer = "</VideoAdServingTemplate>\n";
	return $footer;
}

/* 
 * By default we return something like this: 
 * http://dev.hccorp.co.uk/openx/www/delivery_dev/fc.php?script=deliveryLog:logVastEvent:logVastEvent&banner_id=7&zone_id=2&source=&vast_event=start
 */
function getVideoPlayerUrl($parameterId)
{
    $conf = $GLOBALS['_MAX']['CONF']; 
    $fullFileLocationUrl = "";
    $configFileLocation = $conf['vastServeVideoPlayer'][$parameterId];
    if ( $configFileLocation ){
        $fullFileLocationUrl = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == $conf['openads']['sslPort']) ?
            'https://' .$configFileLocation :
            'http://' . $configFileLocation;
    }
    else {	
        // you can set this by adding a setting under [vastServeVideoPlayer] in the hostname.conf.php config file
        $fullFileLocationUrl = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == $conf['openads']['sslPort']) ?
            'https://' . $conf['webpath']['deliverySSL'] :
            'http://' .  $conf['webpath']['delivery'];
         $fullFileLocationUrl .= "/fc.php?script=deliveryLog:vastServeVideoPlayer:player&file_to_serve=";
         if ( $parameterId == 'path_to_videoplayer_swf_used_when_viewing_video_in_openx_admintool' ){
             $fullFileLocationUrl .= 'flowplayer-3.0.5.swf';
         }
         else if (  $parameterId == 'path_to_videoplayer_js_used_when_viewing_video_in_openx_admintool' ) {
             $fullFileLocationUrl .= 'flowplayer-3.0.5.min.js';
         }
         else if (  $parameterId == 'path_to_videoplayer_rtmp_plugin_used_when_viewing_video_in_openx_admintool' ) {
             $fullFileLocationUrl .= 'flowplayer.rtmp-3.0.2.swf';
         }
         else if (  $parameterId == 'path_to_videoplayer_controls_plugin_used_when_viewing_video_in_openx_admintool' ) {
             $fullFileLocationUrl .= 'flowplayer.controls-3.0.3.swf';
         }
         else {
              throw new Exception( "Uncatered for setting type in getVideoPlayerUrl() |$parameterId|");   
         }
    }
    return $fullFileLocationUrl;
}


function getVideoPlayerSetting($parameterId, $default)
{
    $conf = $GLOBALS['_MAX']['CONF']; 
    $value = $default;
    if ( $conf['vastServeVideoPlayer'][$parameterId] ){
        $value = $conf['vastServeVideoPlayer'][$parameterId];
    }
    return $value;
}

function extractVastParameters( &$aBanner )
{
    if ( isset($aBanner['parameters']) ){
        $vastVariables = unserialize($aBanner['parameters']);
        $aBanner = array_merge($aBanner, $vastVariables);
    }
}

function splitRtmpUrl( $fullPathToVideo, &$aOutputParams)
{
    $fileDelimPosn = strrpos($fullPathToVideo, '/mp4:');
    if ( $fileDelimPosn !== false ) {
      $aOutputParams['videoFilePath'] = substr( $fullPathToVideo, 0, $fileDelimPosn  );
      $aOutputParams['videoFileName'] = substr( $fullPathToVideo, $fileDelimPosn +1, strlen($fullPathToVideo) );
    }
    else {
         // messy code - need to fix
         $fileDelimPosn = strrpos($fullPathToVideo, '/flv:');
         if ( $fileDelimPosn !== false ){
             $aOutputParams['videoFilePath'] = substr( $fullPathToVideo, 0, $fileDelimPosn  );
             $aOutputParams['videoFileName'] = substr( $fullPathToVideo, $fileDelimPosn +1, strlen($fullPathToVideo) );
         }                
    }
}

function prepareVideoParams(&$aOutputParams, $aBanner)
{
    if( isset( $aBanner['vast_video_outgoing_filename'] ) 
        && $aBanner['vast_video_outgoing_filename'] ) {
            
       $fullPathToVideo = $aBanner['vast_video_outgoing_filename']; 
       $aOutputParams['fullPathToVideo']  = $fullPathToVideo;
       splitRtmpUrl( $fullPathToVideo, $aOutputParams  );
       $aOutputParams['vastVideoDuration'] = secondsToVASTDuration( $aBanner['vast_video_duration'] ); 
       $aOutputParams['vastVideoBitrate'] = $aBanner['vast_video_bitrate'];
       $aOutputParams['vastVideoWidth']= $aBanner['vast_video_width'];
       $aOutputParams['vastVideoHeight'] = $aBanner['vast_video_height'];
       $aOutputParams['vastVideoId'] = $aBanner['vast_video_id']; 
       $aOutputParams['vastVideoType'] = $aBanner['vast_video_type']; 
       $aOutputParams['vastVideoDelivery'] = $aBanner['vast_video_delivery'];
    }
    else{
        //debuglog( "no video associated with the comment field for this banner id: $aBanner" );
    }  
}

function prepareOverlayParams(&$aOutputParams, $aBanner)
{
    if ( isset( $aBanner['htmltemplate'] )){
        $aOutputParams['overlayMarkupTemplate'] = $aBanner['htmltemplate'];         
        //$aOutputParams['overlayMarkupCache'] = $aBanner['htmlcache'];  
        $aOutputParams['overlayHeight'] = $aBanner['vast_overlay_height'];     
        $aOutputParams['overlayWidth'] = $aBanner['vast_overlay_width'];
        $aOutputParams['overlayDestinationUrl'] = $aBanner['url']; 
        $aOutputParams['overlayDestinationTarget'] = $aBanner['target'];
    }
}

function prepareCompanionBanner(&$aOutputParams, $aBanner, $zoneId=0, $source='', $ct0='', $withText=false, $logClick=true, $logView=true, $useAlt=false, $loc, $referer)
{
    // If we have a companion banner to serve
    if ( isset( $aBanner['vast_companion_banner_id']  ) 
        && ($aBanner['vast_companion_banner_id'] != 0) )
    {
        $companionBannerId = $aBanner['vast_companion_banner_id'];
        
        // VAST supports the concept of an ad having multlple  companions returned(each with different formats and sizes
        // its then the role of the player to choose the appropriate companion ad to display based on users screen size etc
        // However for now we just focus on serving a single companion banner. Also in vast - I think - the player should be adding the click tracking
        // for now we are doing this server side. 
        global $context;
        
        if (isset($context) && !is_array($context)) {
            $context = MAX_commonUnpackContext($context);
        }
        if (!is_array($context)) {
            $context = array();
        }
        $companionOutput = MAX_adSelect("bannerid:$companionBannerId", '', "", $source, $withText, '', $context, true, $ct0, $loc, $referer);
        if ( $companionOutput['html'] ){
            // We only regard  a companion existing, if we have some markup 
            // to output
            $aOutputParams['companionMarkup'] = $companionOutput['html'];
            $aOutputParams['companionWidth'] = $companionOutput['width']; 
            $aOutputParams['companionHeight'] = $companionOutput['height'];
            $aOutputParams['companionClickUrl'] ='http://openxhasalreadywrappedhtmlwithclickurl.com';           
        }                    
    }
}

function prepareTrackingParams(&$aOutputParams, $aBanner, $zoneId, $source, $ct0, $logClick, $referer)
{
    $conf = $GLOBALS['_MAX']['CONF'];
    // Get the image beacon...
    $aOutputParams['impressionUrl'] =  _adRenderBuildLogURL($aBanner, $zoneId, $source, $loc, $referer, '&');
    // Create the anchor tag..
    $aOutputParams['clickUrl'] = _adRenderBuildClickUrl($aBanner, $zoneId, $source, $ct0, $logClick);
    //debuglog( "CLICKURL: $clickUrl");
    /*
    if (!empty($aOutputParams['clickUrl'])) {  // There is a link
    
        //$status = _adRenderBuildStatusCode($aBanner);
        $target = !empty($aBanner['target']) ? $aBanner['target'] : '_blank'; 
                          
    } else {
        $clickTag = '';
        $clickTagEnd = '';
    }
    */   
    
    if ( $aOutputParams['format'] == 'vast' ){ 
       //$trackingUrl = "http://pathtofctracking/";
       $trackingUrl = 'http://' . $conf['webpath']['delivery'] . "/fc.php?script=deliveryLog:oxLogVast:logImpressionVast&banner_id=" . $aBanner['bannerid'] . "&zone_id=$zoneId&source=$source"; 
       $aOutputParams['trackUrlStart'] = $trackingUrl . '&vast_event=start';
       $aOutputParams['trackUrlMidPoint'] = $trackingUrl . '&vast_event=midpoint';
       $aOutputParams['trackUrlFirstQuartile'] = $trackingUrl . '&vast_event=firstquartile';
       $aOutputParams['trackUrlThirdQuartile'] = $trackingUrl . '&vast_event=thirdquartile';
       $aOutputParams['trackUrlComplete'] = $trackingUrl . '&vast_event=complete';
       $aOutputParams['trackUrlMute'] = $trackingUrl . '&vast_event=mute';
       $aOutputParams['trackUrlPause'] = $trackingUrl . '&vast_event=pause';
       $aOutputParams['trackReplay'] = $trackingUrl . '&vast_event=replay';
       $aOutputParams['trackUrlFullscreen'] = $trackingUrl . '&vast_event=fullscreen'; 
       $aOutputParams['trackUrlStop'] = $trackingUrl . '&vast_event=stop';
    }         
}

        
function getVastVideoAdOutput($aO){

    $vastVideoMarkup .=<<<VAST_VIDEO_AD_TEMPLATE
                <TrackingEvents>
                    <Tracking event="start">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlStart']}]]></URL>
                    </Tracking>
                    <Tracking event="midpoint">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlMidPoint']}]]></URL>
                    </Tracking>
                    <Tracking event="firstQuartile">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlFirstQuartile']}]]></URL>
                    </Tracking>
                    <Tracking event="thirdQuartile">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlThirdQuartile']}]]></URL>
                    </Tracking>
                    <Tracking event="complete">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlComplete']}]]></URL>
                    </Tracking>
                    <Tracking event="mute">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlMute']}]]></URL>
                    </Tracking>
                    <Tracking event="pause">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlPause']}]]></URL>
                    </Tracking>
                    <Tracking event="replay">
                        <URL id="myadsever"><![CDATA[${aO['trackReplay']}]]></URL>
                    </Tracking>
                    <Tracking event="fullscreen">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlFullscreen']}]]></URL>
                    </Tracking>
                    <Tracking event="stop">
                        <URL id="myadsever"><![CDATA[${aO['trackUrlStop']}]]></URL>
                    </Tracking>
                </TrackingEvents>                                       
                <Video>
                    <Duration>${aO['vastVideoDuration']}</Duration>
                    <AdID>${aO['vastVideoId']}</AdID>
                    <VideoClicks>
                        <ClickThrough>          
                            <URL id="destination"><![CDATA[${aO['clickUrl']}]]></URL>
                        </ClickThrough>
                    </VideoClicks>
                    <MediaFiles>
                        <MediaFile delivery="${aO['vastVideoDelivery']}" bitrate="${aO['vastVideoBitrate']}" width="${aO['vastVideoWidth']}" height="${aO['vastVideoHeight']}" type="${aO['vastVideoType']}">
                            <URL><![CDATA[${aO['fullPathToVideo']}]]></URL>
                        </MediaFile>
                    </MediaFiles>
                </Video>
VAST_VIDEO_AD_TEMPLATE;

    return $vastVideoMarkup;
}

function renderVastOutput( $aOut, $pluginType, $vastAdDescription )
{
    debuglog( "Plugin_BannerTypeText_delivery_adRender format is vast" );                       
    // Ensure that & etc in the name does not break the xml
    $adName = htmlspecialchars( $aOut['name'] );
    $player = "";
    $player .= "    <Ad id=\"{player_allocated_ad_id}\" >";
    $player .= "        <InLine>";
    $player .= "            <AdSystem>openx</AdSystem>";
    $player .= "                <AdTitle>$adName</AdTitle>";
    $player .= "                    <Description>$vastAdDescription</Description>";
    $player .= "                    <Impression>";
    $player .= "                        <URL id=\"myadsever\"><![CDATA[${aOut['impressionUrl']}]]></URL>";
    $player .= "                    </Impression>";
    if ( isset($aOut['fullPathToVideo']) ){ 
        $player .= getVastVideoAdOutput($aOut);                    
    }
    
    if ( isset($aOut['companionMarkup'])  ){
        //debugdump( '$companionOutput', $companionOutput );
        $player .= "             <CompanionAds>\n";
        $player .= "                <Companion id=\"companion\" width=\"${aOut['companionWidth']}\" height=\"${aOut['companionHeight']}\" resourceType=\"HTML\">\n";
        $player .= "                    <Code><![CDATA[${aOut['companionMarkup']}]]></Code>\n";
        $player .= "                    <CompanionClickThrough>\n";
        $player .= "                        <URL><![CDATA[${aOut['companionClickUrl']}]]></URL>\n";   
        $player .= "                    </CompanionClickThrough>\n";
        $player .= "                </Companion>\n";
        $player .= "            </CompanionAds>\n";
    }
    
    if ( $pluginType == 'vastOverlay' && isset( $aOut['overlayMarkupTemplate'] ) ){
        $player .= "             <NonLinearAds>\n";
        $player .= "                <NonLinear id=\"overlay\" width=\"${aOut['overlayWidth']}\" height=\"${aOut['overlayHeight']}\" resourceType=\"HTML\">\n";
        $player .= "                    <Code><![CDATA[${aOut['overlayMarkupTemplate']}]]></Code>\n";
        $player .= "                    <NonLinearClickThrough>\n";
        $player .= "                        <URL><![CDATA[${aOut['clickUrl']}]]></URL>\n";
        $player .= "                    </NonLinearClickThrough>\n";
        $player .= "                </NonLinear>\n";
        $player .= "            </NonLinearAds>\n";
    }
    $player .= "        </InLine>\n";
    $player .= "    </Ad>\n";
    return $player;
}


function renderPlayerInPage($aOut)
{
	$player = "";
	if ( isset($aOut['fullPathToVideo'] ) ){
		$player = <<<PLAYER
			<b>Video:</><br>
			<script type="text/javascript" src="{$aOut['videoPlayerJsUrl']}"></script>
			<style>
			a.player {
			    display:block;
			    width:640px;
			    height:360px;
			    margin:25px 0;
			    text-align:center;
			}
			</style>
			</head>
			<body>
			
			<a class="player" id="player"></a>
			
			<script language="JavaScript">
			flowplayer("a.player", "${aOut['videoPlayerSwfUrl']}", {
			   clip: {
			           url: '${aOut['videoFileName']}',
			           provider: 'streamer',
			           autoPlay: ${aOut['videoPlayerAutoPlay']},
			   },
			
			   plugins: {
			       streamer: {
			            // see http://flowplayer.org/forum/8/15861 for reason I use encode() function
			            url: escape('${aOut['videoPlayerRtmpPluginUrl']}'),
			            netConnectionUrl: '${aOut['videoFilePath']}'
			       },
			       controls: { 
			              url: escape('${aOut['videoPlayerControlsPluginUrl']}'), 
			       } 
			   }
			});
			</script> 
PLAYER;
}
   return $player;
}

function renderCompanionInAdminTool($aOut)
{
    $player = "";
    if(isset($aOut['companionMarkup'])) {
        $player .=  "<b>Companion:(" .$aOut['companionWidth'] . "x" . $aOut['companionHeight'] . ")</><br>";    
        $player .= $aOut['companionMarkup']; 
        $player .= "<br>";   
    }
    return $player;
}

function renderOverlayInAdminTool($aOut)
{
    $player = "";
    if ( isset( $aOut['overlayMarkupTemplate'] )){
        $player .=  "<b>Overlay(" . $aOut['overlayWidth'] . "x" . $aOut['overlayHeight'] . "):</><br>";
        if ( $aOut['overlayDestinationUrl'] ){
            $player .=  "CLICKABLE: <a target=\"${aOut['overlayDestinationTarget']}\" href=\"${aOut['overlayDestinationUrl']}\"> ${aOut['overlayMarkupTemplate']}</a>"; 
        }
        else {
            $player .=  $aOut['overlayMarkupTemplate']; // Think this should be the templated output markup
        }
        $player .= "<br>";
    }
    return $player;    
}

function secondsToVASTDuration($seconds)
{
    $hours = intval(intval($seconds) / 3600);
    if($hours > 0)
    {
        $ret .= "$hours hours ";
    }
    $minutes = bcmod((intval($seconds) / 60),60);
    $seconds = bcmod(intval($seconds),60);
    $ret = sprintf( "%02d:%02d:%02d", $hours, $minutes, $seconds );
    return $ret;
}
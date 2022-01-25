<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

// FB Ad Insights
require  '../vendor/autoload.php';
use FacebookAds\Object\Ad;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

class FaceAdsAPI extends Controller
{
  public static function insert_ad_list($ad_account_id) {
    // Credentials for your Facebook Dev App 
    $access_token = 'x';
    $app_secret = 'y';
    $app_id = 'z';
    
    // Initializing the api object
    $api = Api::init($app_id, $app_secret, $access_token);
    $api->setLogger(new CurlLogger());
    
    // Fields to be obtained in the Ad Insights API
    $fields = array(
      'account_id',
      'account_name',
      'campaign_name',
      'adset_name',
      'ad_name',
      'ad_id',
      'objective',
      'spend',
      'reach',
      'frequency',
      'impressions',
      'clicks'
    );

    // Parameters for the Ad Insights API call. Controls time range, amount of data and sets the API for ad level.
    // By default, it will run for yesterday's data, but I left the parameters for time ranges as well, just in case.
    $params = array(
      // 'time_range' => array('since' => '2021-09-24','until' => '2021-09-29'),
      // 'time_increment' => 1,
      'limit' => 500,
      'breakdowns' => array(),
      'date_preset' => 'yesterday',
      'filtering' => array(),
      'level' => 'ad'
    );

    // Executing the API call for all Ad Insights data for the specific ad_account_id.
    $fb_data = json_encode((new AdAccount($ad_account_id))->getInsights(
      $fields,
      $params
    )->getResponse()->getContent(), JSON_PRETTY_PRINT);
    
    // Debugging
    // echo $fb_data; // debug

    $json = json_decode($fb_data, TRUE);
    if ($json != null && $json["data"] != null) 
    {
      // Looping through each ad results to obtaing their respective Link URL,
      // through the AdCreatives API, by passing on the ad_id as the identifier.
      $dataset = [];
      foreach($json["data"] as $ad)
      {
        $face_ad_id = $ad["ad_id"];
        
        // The link URL is usually in these two fields.
        $fields = array(
          'object_story_spec',
          'asset_feed_spec{link_urls}'
        );
        $ad_creative_data = json_encode((new Ad($face_ad_id))->getAdCreatives(
          $fields
        )->getResponse()->getContent(),JSON_PRETTY_PRINT);
        
        // Conditionals to obtain the link URL, prioritizing object_story_spec fields first.
        $ad_creative_decoded = json_decode($ad_creative_data,true);
        $link = NULL;
        $ad_creative_data_0 = $ad_creative_decoded["data"][0];
        if(isset($ad_creative_data_0["object_story_spec"]["link_data"]["link"])) {
          $link = $ad_creative_data_0["object_story_spec"]["link_data"]["link"];
        }
        else if (isset($ad_creative_data_0["object_story_spec"]["link_data"]["child_attachments"][0]["link"])) {
          $link = $ad_creative_data_0["object_story_spec"]["link_data"]["child_attachments"][0]["link"];
        }
        else if(isset($ad_creative_data_0["object_story_spec"]["video_data"]["call_to_action"]["value"]["link"])) {
          $link = $ad_creative_data_0["object_story_spec"]["video_data"]["call_to_action"]["value"]["link"];
        }
        else if(isset($ad_creative_data_0["asset_feed_spec"]["link_urls"][0]["website_url"])) {
          $link = $ad_creative_data_0["asset_feed_spec"]["link_urls"][0]["website_url"];
        }

        // Initializing the database fields to prevent unset value errors in the API.
        $ad_link_path = NULL;
        $utm_campaign_notnull = NULL;
        $utm_medium_notnull = NULL;
        $utm_source_notnull = NULL;
        $utm_content_notnull = NULL;

        // Saving the Ad Creative data to the according database field name in our final dataset.
        if(isset($link)) {
          $values = parse_url($link);
          
          if(isset($values["path"])) {
              $ad_link_path = rtrim(ltrim($values["path"],'/'),'/');
          }
          
          if(isset($values['query'])) {
            parse_str($values['query'],$array_utms);
            if(isset($array_utms["utm_campaign"])) { 
              $utm_campaign_notnull = $array_utms["utm_campaign"];
            }
            if(isset($array_utms["utm_medium"])) { 
              $utm_medium_notnull = $array_utms["utm_medium"];
            }
            if(isset($array_utms["utm_source"])) { 
              $utm_source_notnull = $array_utms["utm_source"];
            }
            if(isset($array_utms["utm_content"])) { 
              $utm_content_notnull = $array_utms["utm_content"];
            }
          }
        }

        // Building the final with contents from both Ad Insights and AdCreatives data.
        $dataset[] = [
          "account_id" => $ad["account_id"],
          "account_name" => $ad["account_name"],
          "campaign_name" => $ad["campaign_name"],
          "adset_name" => $ad["adset_name"],
          "ad_id" => $face_ad_id,
          "ad_name" => $ad["ad_name"],
          "full_link" => $link,
          "full_link_path" => $ad_link_path,
          "utm_campaign" => $utm_campaign_notnull,
          "utm_medium" => $utm_medium_notnull,
          "utm_source" => $utm_source_notnull,
          "utm_content" => $utm_content_notnull,
          "objective" => $ad["objective"],
          "spend" => $ad["spend"],
          "impressions" => $ad["impressions"],
          "reach" => $ad["reach"],
          "frequency" => $ad["frequency"],
          "clicks" => $ad["clicks"],
          "ad_date" => $ad["date_start"]
        ];

        // Setting a small delay between each API call.
        sleep(0.3);
      }
      
      // Inserting all of the final dataset to the database.
      DB::table('ads')
          ->insert($dataset);
    }
    
    // // Debugging the final dataset.
    // return $json;
    // echo json_encode($dataset,JSON_PRETTY_PRINT);
  }

  public function faceads_api(Request $request) {
    // Function to be called in the endpoint. Repeats all actions for each Ad Account, and makes debugging easier to run on only one.
    self::insert_ad_list('x');    //first ad account
    self::insert_ad_list('y');    //second ad account
    self::insert_ad_list('z');    //third ad account
  }
}


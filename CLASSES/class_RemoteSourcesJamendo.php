<?php
/**
 * CCHits.net is a website designed to promote Creative Commons Music,
 * the artists who produce it and anyone or anywhere that plays it.
 * These files are used to generate the site.
 *
 * PHP version 5
 *
 * @category Default
 * @package  MusicSources
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     http://cchits.net Actual web service
 * @link     https://github.com/CCHits/Website/wiki Developers Web Site
 * @link     https://github.com/CCHits/Website Version Control Service
 */
/**
 * This class pulls appropriate data from Jamendo.com
 *
 * @category Default
 * @package  MusicSources
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     http://cchits.net Actual web service
 * @link     https://github.com/CCHits/Website/wiki Developers Web Site
 * @link     https://github.com/CCHits/Website Version Control Service
 */
class RemoteSourcesJamendo extends RemoteSources
{
    /**
     * Get all the source data we can pull from the source.
     *
     * @param string $src Source URL for the retriever
     *
     * @return const A value explaining the outcome of the fetch request
     */
    function __construct($src)
    {
        if (preg_match('/track\/(\d+)/', $src, $match) == 0) {
            return 406;
        }
        $url_base = "http://api.jamendo.com/v3.0/tracks/?client_id=" . 
            ConfigBroker::getConfig('JamendoClientID', null) . "&format=json&type=single%20albumtrack&id=";
        $file_contents = file_get_contents($url_base . $match[1]);
        if ($file_contents == false) {
            error_log("No response when trying to retrieve $url_base{$match[1]}");
            return 406;
        }
        $json_contents = json_decode($file_contents);
        if ($json_contents == false) {
            error_log("No content when trying to read $url_base{$match[1]}");
            return 406;
        }
        if ($json_contents->headers->results_count == 0) {
            error_log("Jamendo API returned an empty result when retrieving $url_base{$match[1]}");
            return 406;
        }
        preg_match("/licenses\/(.*)\/\d/", $json_contents->results[0]->license_ccurl, $matches);
        $this->set_strTrackName($json_contents->results[0]->name);
        $this->set_strTrackUrl($json_contents->results[0]->shareurl);
        $this->set_enumTrackLicense(LicenseSelector::validateLicense($matches[1]));
        $this->set_fileUrl(str_replace("https", "http", $json_contents->results[0]->audiodownload));

        $artist_id = $json_contents->results[0]->artist_id;
        $url_base = "http://api.jamendo.com/v3.0/artists/?client_id=" . 
            ConfigBroker::getConfig('JamendoClientID', null) . "&format=json&id=";
        $file_contents = file_get_contents($url_base . $artist_id);
        if ($file_contents == false) {
            error_log("No response when trying to retrieve $url_base{$artist_id}");
            return 406;
        }
        $json_contents = json_decode($file_contents);
        if ($json_contents == false) {
            error_log("No content when trying to read $url_base{$artist_id}");
            return 406;
        }
        $this->set_strArtistName($json_contents->results[0]->name);
        $this->set_strArtistUrl($json_contents->results[0]->shareurl);

        return $this->create_pull_entry();
    }

    /**
     * Find the download location for the file
     *
     * @param integer $track_id        The track ID at Jamendo.com
     * @param integer $download_server The server to check
     *
     * @return const|string Either a fault code or the URL to download
     */
    protected function find_download($track_id = "", $download_server = 0)
    {
        // Wondering why we start at 0 and count to 50? Because this way we can easily disable the bypass to see if 
        // things have changed.
        switch($download_server) {
        case 14:
        case 25:
        case 26:
        case 29:
        case 30:
        case 31:
            // Confirmed working delivery servers
            break;
        case 50:
            // Stop
            return false;
        default:
            // Bypass the numbers not listed above
            $download_server++;
            return $this->find_download($track_id, $download_server);
        }
        $status = $this->curl_get(
            "http://download{$download_server}.jamendo.com/request/track/" . $track_id . "/mp32/0." . rand(), 0
        );
        if (false == $status or !is_array($status) or count($status) == 0 or $status[1]['http_code'] == 404) {
            $download_server++;
            return $this->find_download($track_id, $download_server);
        } elseif ("<!DOCTYPE HTML PUBLIC" == substr($status[0], 0, strlen("<!DOCTYPE HTML PUBLIC"))) {
            $download_server++;
            return $this->find_download($track_id, $download_server);
        } elseif (1 == preg_match("/\('(\S+)','(\S+)'\)/", $status[0], $matches)) {
            if ($matches[1]=="ready") {
                return "http://download{$download_server}.jamendo.com/download/track/" . $track_id . 
                    "/mp32/{$matches[2]}/file.mp3";
            } else {
                $download_server++;
                sleep(5);
                return $this->find_download($track_id, $download_server);
            }
        } elseif (1==preg_match("/elsewhere/", $status[0], $matches)) {
            $download_server++;
            return $this->find_download($track_id, $download_server);
        } else {
            $download_server++;
            return $this->find_download($track_id, $download_server);
        }
    }
}


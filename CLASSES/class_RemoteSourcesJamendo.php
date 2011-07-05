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
 * @link     http://code.cchits.net Developers Web Site
 * @link     http://gitorious.net/cchits-net Version Control Service
 */
/**
 * This class pulls appropriate data from Jamendo.com
 *
 * @category Default
 * @package  MusicSources
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     http://cchits.net Actual web service
 * @link     http://code.cchits.net Developers Web Site
 * @link     http://gitorious.net/cchits-net Version Control Service
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
        if (preg_match('/(\d+)|track\/(\d+)', $src, $match) == 0) {
            return $this->INVALIDSRC;
        }
        if ($match[1] == "" and isset($match[2]) and $match[2] != "") {
            $match[1] = $match[2];
        }
        $url_base = "http://api.jamendo.com/get2/track_name+track_url+track_stream+license_url+artist_id+artist_name+artist_url/track/json/track_album+album_artist/track/?streamencoding=mp31&track_id=";
        $file_contents = file_get_contents($url_base . $match[1]);
        if ($file_contents == FALSE) {
            return $this->INVALIDSRC;
        }
        $json_contents = json_decode($file_contents);
        if ($json_contents == FALSE) {
            return $this->INVALIDSRC;
        }
        preg_match("/licenses\/(.*)\/\d/", $json_contents[0]->license_url, $matches);
        if (!isset($matches[1])) {
            return $this->INVALIDLIC;
        }
        $this->strTrackName = $json_contents[0]->track_name;
        $this->strArtistName = $json_contents[0]->artist_name;
        $this->strTrackUrl = $json_contents[0]->track_url;
        $this->strArtistUrl = $json_contents[0]->artist_url;
        $this->enumTrackLicense = $matches[1];
        $this->fileUrl = find_download($track);
        return is_valid_cchits_submission();
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
        // Wondering why we start at 0 and count to 50? Because this way we can easily disable the bypass to see if things have changed.
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
            return $this->NOFILEDL;
        default:
            // Bypass the numbers not listed above
            $download_server++;
            return $this->find_download($track_id, $download_server);
        }
        $status = curl_get("http://download{$download_server}.jamendo.com/request/track/" . $track_id . "/mp32/0." . rand(), 0);
        if (false == $status or !is_array($status) or count($status) == 0 or $status[1]['http_code'] == 404) {
            $download_server++;
            return $this->find_download($track_id, $download_server);
        } elseif ("<!DOCTYPE HTML PUBLIC" == substr($status[0], 0, strlen("<!DOCTYPE HTML PUBLIC"))) {
            $download_server++;
            return $this->find_download($track_id, $download_server);
        } elseif (1 == preg_match("/\('(\S+)','(\S+)'\)/", $status[0], $matches)) {
            if ($matches[1]=="ready") {
                return "http://download{$download_server}.jamendo.com/download/track/" . $track_id . "/mp32/{$matches[2]}/file.mp3";
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


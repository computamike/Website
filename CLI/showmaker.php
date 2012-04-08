<?php
/**
* CCHits.net is a website designed to promote Creative Commons Music,
* the artists who produce it and anyone or anywhere that plays it.
* These files are used to generate the site.
*
* PHP version 5
*
* @category Default
* @package  CCHitsClass
* @author   Jon Spriggs <jon@sprig.gs>
* @license  http://www.gnu.org/licenses/agpl.html AGPLv3
* @link     http://cchits.net Actual web service
* @link     http://code.cchits.net Developers Web Site
* @link     http://gitorious.net/cchits-net Version Control Service
*/

require_once 'config_default.php'; // Adjust config_local to set your own local variables.
require_once 'library.php';

$arrUri = getUri();
$date = null;
$daily = true;
$weekly = true;
$monthly = true;
$historic = false;
$GLOBALS['DEBUG'] = false;

$pre_sable = '<?xml version="1.0"?>
<!DOCTYPE SABLE PUBLIC "-//SABLE//DTD SABLE speech mark up//EN" "Sable.v0_2.dtd" []>
<SABLE>
  <SPEAKER NAME="cmu_us_clb_arctic_clunits">
    <RATE SPEED="+25%">
';
$post_sable = '
    </RATE>
  </SPEAKER>
</SABLE>';

$track_nsfw = array(' a track which may not be considered work or family safe <BREAK LEVEL="MEDIUM" /> It is ');
$show_nsfw = array(' the show for to day contains tracks which may not be considered work or family safe <BREAK LEVEL="MEDIUM" /> ');

foreach ($arrUri['parameters'] as $key => $value) {
    if ($date === null) {
        if (preg_match('/(\d\d\d\d\d\d\d\d)/', $value, $matches)) {
            $date = $matches[0];
        } elseif (preg_match('/(\d\d\d\d\d\d\d\d)/', $key, $matches)) {
            $date = $matches[0];
        }
    }
    if ($value === 'daily' || $key === 'daily') {
        if ($daily === true && $weekly === true && $monthly === true) {
            $weekly = false;
            $monthly = false;
        } else {
            $daily = true;
        }
    }
    if ($value === 'weekly' || $key === 'weekly') {
        if ($daily === true && $weekly === true && $monthly === true) {
            $daily = false;
            $monthly = false;
        } else {
            $weekly = true;
        }
    }
    if ($value === 'monthly' || $key === 'monthly') {
        if ($daily === true && $weekly === true && $monthly === true) {
            $daily = false;
            $weekly = false;
        } else {
            $monthly = true;
        }
    }
    if ($value === 'debug' || $key === 'debug') {
        $GLOBALS['DEBUG'] = true;
    }
    if ($value === 'historic' || $key === 'historic') {
        $historic = true;
    }
}
if ($date === null) {
    $date = date("Ymd");
}

echo "Doing: daily ($daily) weekly ($weekly) monthly ($monthly) historic ($historic) debug({$GLOBALS['DEBUG']}) $date\r\n";

$get = Configuration::getAPI() . '/runshows/' . $date;
if ($historic) {
    $get .= '?historic=true';
}
$data = curlGetResource($get, 0);
if ($data != false and isset($data[0]) and strlen($data[0]) > 0) {
    $json_data = makeArrayFromObjects(json_decode($data[0]));
    $f = fopen(Configuration::getWorkingDir() . '/showmaker.json', 'w');
    fwrite($f, print_r($json_data, true));
    fclose($f);

    if (isset($json_data['daily_show']) && $daily) {
        $show_data = $json_data['daily_show'];

        echo "Creating Daily Show...\r\n";
        echo sprintf('The Daily track is %1$s by %2$s ' . "\r\n", $show_data['arrTracks'][1]['strTrackName'], $show_data['arrTracks'][1]['strArtistName']);

        echo "Making intro bumper\r\n";
        $running_order = addEntryToJsonArray('', 0, 'intro');
        if ( ! generateSilenceWav(7, Configuration::getWorkingDir() . '/pre-show-silence.wav')) {
            echo "WARNING: Failed to create silence.\r\n";
        }
        $intro = "$pre_sable\r\n";
        $intro .= sprintf(
            randomTextSelect(
                array(
                    'Hello and welcome to the %1$s from %2$s <BREAK LEVEL="MEDIUM" /> To dayz show features ',
                    'Hey there <BREAK LEVEL="MEDIUM" /> You are listening to a feed from %2$s and this is the %1$s <BREAK LEVEL="MEDIUM" /> To day you can hear '
                )
            ),
            $show_data['strShowNameSpoken'], 
            $show_data['strSiteNameSpoken']
        );
        if ($show_data['isNSFW'] != 0) {
            $intro .= randomTextSelect($track_nsfw);
        }
        $intro .= sprintf(
            randomTextSelect(
                array(
                     '%1$s <BREAK LEVEL="SMALL" /> by <BREAK LEVEL="SMALL" /> %2$s',
                    ' a track by %2$s <BREAK LEVEL="SMALL" /> called <BREAK LEVEL="SMALL" /> %1$s'
                )
            ), 
            $show_data['arrTracks'][1]['strTrackNameSounds'], 
            $show_data['arrTracks'][1]['strArtistNameSounds']
        );
        $intro .= "\r\n$post_sable";
        if ( ! convertSableXmlToWav($intro, Configuration::getWorkingDir() . '/intro.wav')) {
            echo "WARNING: Failed to create intro using $intro\r\n";
        }
        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/pre-show-silence.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/showstart.wav')) {
            echo "WARNING: Failed to concatenate pre-show-silence with intro.wav\r\n";
        }
        copy(Configuration::getStaticDir() . '/intro.wav', Configuration::getWorkingDir() . '/intro.wav');
        if ( ! overlayAudioTracks(Configuration::getWorkingDir() . '/showstart.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/run.wav')) {
            echo "WARNING: Failed to overlay showstart.wav over intro.wav\r\n";
        }
        $arrTracks[$show_data['arrTracks'][1]['intTrackID']] = $show_data['arrTracks'][1];
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/run.wav'), $show_data['arrTracks'][1]['intTrackID']);

        echo "Downloading and merging audio file\r\n";
        $track = downloadFile($show_data['arrTracks'][1]['localSource']);
        if ($track === false) {
            debugUnlink(Configuration::getWorkingDir() . '/run.wav');
            die("The track is not currently available.\r\n");
        }

        copy($track, Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource']);
        debugUnlink($track);

        if ( ! trackTrimSilence(Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource'])) {
            echo "WARNING: Failed to trim the silence from {$show_data['arrTracks'][1]['fileSource']}\r\n";
        }

        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource'], Configuration::getWorkingDir() . '/runplustrack.wav')) {
            echo "WARNING: Failed to concatenate run.wav with {$show_data['arrTracks'][1]['fileSource']}\r\n";
        }
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/runplustrack.wav'), 'outro');

        $outro = "$pre_sable\r\n<BREAK LEVEL=\"LARGE\" />";
        $outro .= sprintf( 
            randomTextSelect(
                array(
                    'That was <BREAK LEVEL="SMALL" /> %1$s <BREAK LEVEL="SMALL" /> by <BREAK LEVEL="SMALL" /> %2$s <BREAK LEVEL="MEDIUM" /> It was a %3$s licensed track',
                    'You were listening to a %3$s licensed track by %2$s <BREAK LEVEL="SMALL" /> called <BREAK LEVEL="SMALL" /> %1$s'
                )
            ),
            $show_data['arrTracks'][1]['strTrackNameSounds'],
            $show_data['arrTracks'][1]['strArtistNameSounds'],
            $show_data['arrTracks'][1]['pronouncable_enumTrackLicense']
        );
        $outro .= sprintf(
            randomTextSelect(
                array(
                    ' <BREAK LEVEL="MEDIUM" /> Every track we play is selected by a listener like you <BREAK LEVEL="LARGE" /> to find out more <BREAK LEVEL="SMALL" /> please visit %1$s slash <BREAK LEVEL="MEDIUM" /> eff <BREAK LEVEL="SMALL" /> ay <BREAK LEVEL="SMALL" /> queue <BREAK LEVEL="LARGE" /> If you liked to dayz track, you can vote for it at %2$s <BREAK LEVEL="MEDIUM" /> These votes decide whether this track will be on the weekly show and eventually if it will make it into the chart <BREAK LEVEL="MEDIUM" /> both of these can be found by visiting %1$s ',
                    ' <BREAK LEVEL="MEDIUM" /> Remember, you can vote for this track by visiting %2$s <BREAK LEVEL="MEDIUM" /> Your vote will decide whether it makes it into the best-of-the-week <BREAK LEVEL="SMALL" /> weekly show which is available from %1$s slash weekly '
                )
            ), 
            $show_data['strSiteNameSpoken'],
            $show_data['strShowUrlSpoken']
        );
        $outro .= sprintf(' <BREAK LEVEL="LARGE" /> The theem is an exerpt from Gee Em Zed By Scott All-tim <BREAK LEVEL="SMALL" />for details, please visit %1$s slash theem', $show_data['strSiteNameSpoken']) . "\r\n" . $post_sable;

        echo "Making the outro bumper\r\n";
        if ( ! convertSableXmlToWav($outro, Configuration::getWorkingDir() . '/outro.wav')) {
            echo "WARNING: Failed to generate the sable file or create outro.wav\r\n";
        }

        if ( ! generateSilenceWav(34, Configuration::getWorkingDir() . '/post-show-silence.wav')) {
            echo "WARNING: Failed to create silence.\r\n";
        }

        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/outro.wav', Configuration::getWorkingDir() . '/post-show-silence.wav', Configuration::getWorkingDir() . '/showend.wav')) {
            echo "WARNING: Failed to concatenate outro.wav with post-show-silence.wav\r\n";
        }

        if ( ! reverseTrackAudio(Configuration::getWorkingDir() . '/showend.wav', Configuration::getWorkingDir() . '/showend_rev.wav')) {
            echo "WARNING: Failed to reverse showend.wav into showend_rev.wav.\r\n";
        }

        if ( ! reverseTrackAudio(Configuration::getStaticDir() . '/outro.wav', Configuration::getWorkingDir() . '/outro_rev.wav', false)) {
            echo "WARNING: Failed to reverse outro.wav into outro_rev.wav\r\n";
        }

        if ( ! overlayAudioTracks(Configuration::getWorkingDir() . '/showend_rev.wav', Configuration::getWorkingDir() . '/outro_rev.wav', Configuration::getWorkingDir() . '/run_rev.wav')) {
            echo "WARNING: Failed to overlay showend_rev.wav with outro_rev.wav\r\n";
        }
        if ( ! reverseTrackAudio(Configuration::getWorkingDir() . '/run_rev.wav', Configuration::getWorkingDir() . '/run.wav')) {
            echo "WARNING: Failed to reverse run_rev.wav into run.wav\r\n";
        }

        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/runplustrack.wav', Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/daily.wav')) {
            echo "WARNING: Failed to concatenate runplustrack.wav with run.wav\r\n";
        }
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/daily.wav'), 'end');

        $arrRunningOrder = makeArrayFromObjects(json_decode($running_order));

        foreach ($arrRunningOrder as $timestamp => $entry) {
            if (0 + $entry > 0) {
                $arrRunningOrder_final[(string) $timestamp] = $arrTracks[$entry];
            } else {
                $arrRunningOrder_final[(string) $timestamp] = $entry;
            }
        }

        echo "Getting the coverart\r\n";
        $coverart = downloadFile($show_data['qrcode']);
        if ($coverart != false) {
            copy($coverart, Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png');
            debugUnlink($coverart);
            $coverart = Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png';
        } else {
            $coverart = '';
        }

        echo "Converting the show to the various formats\r\n";
        generateOutputTracks(
            Configuration::getWorkingDir() . '/daily.wav',
            Configuration::getWorkingDir() . '/daily.' . $show_data['intShowUrl'] . '.',
            array(
                'Title' => $show_data['strShowName'],
                'Artist' => 'CCHits.net',
                'AlbumArt' => $coverart,
                'RunningOrder' => $arrRunningOrder_final
            )
        );
        if ($coverart != '') {
            debugUnlink($coverart);
        }
        finalize(
            $show_data['intShowID'],
            Configuration::getWorkingDir() . '/daily.' . $show_data['intShowUrl'] . '.', 
            updateStatusNet(
                array(
                    sprintf(
                        randomTextSelect(
                            array(
                                'A new !daily show has been created for %1$s-%2$s-%3$s. Get it from %4$s'
                            )
                        ), 
                        substr($show_data['intShowUrl'], 0, 4),
                        substr($show_data['intShowUrl'], 4, 2),
                        substr($show_data['intShowUrl'], 6, 2),
                        $show_data['shorturl']
                    ),
                    sprintf(
                        randomTextSelect(
                            array(
                                'The @%1$s daily show for today (%2$s) features %3$s by %4$s'
                            )
                        ),
                        Configuration::getStatusNetUser(),
                        $show_data['shorturl'],
                        $show_data['arrTracks'][1]['strTrackName'],
                        $show_data['arrTracks'][1]['strArtistName']
                    )
                )
            )
        );
        echo "Done.\r\n\r\n";
    }
    if (isset($json_data['weekly_show']) && $weekly) {
        $show_data = makeArrayFromObjects($json_data['weekly_show']);

        echo "Creating Weekly Show...\r\n";

        echo "These tracks are ";
        foreach ($show_data['arrTracks'] as $intTrackID => $arrTrack) {
            if ($intTrackID > 1) {
                echo ", ";
            }
            echo $arrTrack['strTrackName'] . ' by ' . $arrTrack['strArtistName'];
        }
        echo "\r\n";
        
        $running_order = addEntryToJsonArray('', 0, 'intro');
        generateSilenceWav(7, Configuration::getWorkingDir() . '/pre-show-silence.wav');

        echo "Making intro bumper\r\n";
        $running_order = addEntryToJsonArray('', 0, 'intro');
        if ( ! generateSilenceWav(7, Configuration::getWorkingDir() . '/pre-show-silence.wav')) {
            echo "WARNING: Failed to create silence.\r\n";
        }
        $intro = "$pre_sable\r\n";
        $intro .= sprintf(
            randomTextSelect(
                array(
                    'Hello and welcome to the %1$s from %2$s <BREAK LEVEL="MEDIUM" /> This show reviews the last 7 days of daily tracks, and the top 3 rated tracks from the week before <BREAK LEVEL="MEDIUM" /> ',
                    'Hey there <BREAK LEVEL="MEDIUM" /> You are listening to a feed from %2$s and this is the %1$s <BREAK LEVEL="MEDIUM" /> In this show you will hear ten great tracks that we played over the past two weeks <BREAK LEVEL="MEDIUM" /> '
                )
            ),
            $show_data['strShowNameSpoken'], 
            $show_data['strSiteNameSpoken']
        );
        if ($show_data['isNSFW'] != 0) {
            $intro .= randomTextSelect($show_nsfw);
        }
        $intro .= "\r\n$post_sable";
        if ( ! convertSableXmlToWav($intro, Configuration::getWorkingDir() . '/intro.wav')) {
            echo "WARNING: Failed to create intro using $intro\r\n";
        }

        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/pre-show-silence.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/showstart.wav')) {
            echo "WARNING: Failed to concatenate pre-show-silence with intro.wav\r\n";
        }

        copy(Configuration::getStaticDir() . '/intro.wav', Configuration::getWorkingDir() . '/intro.wav');
        if ( ! overlayAudioTracks(Configuration::getWorkingDir() . '/showstart.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/run.wav')) {
            echo "WARNING: Failed to overlay showstart.wav over intro.wav\r\n";
        }

        $arrLastTrack = array();
        foreach ($show_data['arrTracks'] as $intTrackID => $arrTrack) {
            $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/run.wav'), 'Track Bumpers');
            $arrTracks[$arrTrack['intTrackID']] = $arrTrack;

            echo "Making track bumper ($intTrackID)\r\n";
            $bumper = "$pre_sable\r\n";
            if ($intTrackID != 1) {
                $bumper .= '<BREAK LEVEL="LARGE" />';
            }
            switch($intTrackID) {
            case 1:
                $bumper .= sprintf(
                    randomTextSelect(
                        array(
                            'Up first to day <BREAK LEVEL="SMALL" /> we have %1$s by %2$s',
                            'To dayz first track is from %2$s and is called %1$s'
                        )
                    ),
                    $arrTrack['strTrackNameSounds'], 
                    $arrTrack['strArtistNameSounds']
                );
                break;
            case count($show_data['arrTracks']):
                $bumper .= sprintf(
                    randomTextSelect(
                        array(
                            'That was a %1$s licensed track called %2$s by %3$s <BREAK LEVEL="MEDIUM" /> Our last track for to day is %4$s by %5$s',
                            'You have been listening to %3$s with their track %2$s which is released under a %1$s license <BREAK LEVEL="MEDIUM" /> I am sad to say that this is the last track this week <BREAK LEVEL="MEDIUM" /> but not sad to say that it is %5$s with their track %4$s'
                        )
                    ),
                    $arrLastTrack['pronouncable_enumTrackLicense'],
                    $arrLastTrack['strTrackNameSounds'],
                    $arrLastTrack['strArtistNameSounds'],
                    $arrTrack['strTrackNameSounds'], 
                    $arrTrack['strArtistNameSounds']
                );
                break;
            case 8:
                $bumper .= sprintf(
                    randomTextSelect(
                        array(
                            'That was a %1$s licensed track called %2$s by %3$s and the point where we move into the highest rated tracks from the week before this one. Up first is %4$s by %5$s',
                            'You have been listening to %3$s with their track %2$s which is released under a %1$s license <BREAK LEVEL="MEDIUM" /> and now lets play some tracks from the week before this. Here we have %5$s with their track %4$s',
                        )
                    ),
                    $arrLastTrack['pronouncable_enumTrackLicense'],
                    $arrLastTrack['strTrackNameSounds'],
                    $arrLastTrack['strArtistNameSounds'],
                    $arrTrack['strTrackNameSounds'], 
                    $arrTrack['strArtistNameSounds']
                );
                break;
            case 3:
            case 5:
            case 7:
            case 9:
                $bumper .= sprintf(
                    randomTextSelect(
                        array(
                            'That was a %1$s licensed track called %2$s by %3$s <BREAK LEVEL="MEDIUM" /> You are listening to a feed from %6$s <BREAK LEVEL="MEDIUM" /> If you like any of these tracks <BREAK LEVEL="SMALL" /> you could vote for them at %7$s <BREAK LEVEL="MEDIUM" /> Up next is %4$s by %5$s',
                            'You have been listening to %3$s with their track %2$s which is released under a %1$s license <BREAK LEVEL="MEDIUM" /> Remember that you can vote for any track in to dayz show by visiting %7$s <BREAK LEVEL="LARGE" /> Moving on <BREAK LEVEL="SMALL" /> we have %5$s with their track %4$s',
                        )
                    ),
                    $arrLastTrack['pronouncable_enumTrackLicense'],
                    $arrLastTrack['strTrackNameSounds'],
                    $arrLastTrack['strArtistNameSounds'],
                    $arrTrack['strTrackNameSounds'], 
                    $arrTrack['strArtistNameSounds'],
                    $show_data['strSiteNameSpoken'],
                    $show_data['strShowUrlSpoken']
                );
                break;
            default:
                $bumper .= sprintf(
                    randomTextSelect(
                        array(
                            'That was a %1$s licensed track called %2$s by %3$s <BREAK LEVEL="MEDIUM" /> Up next is %4$s by %5$s',
                            'You have been listening to %3$s with their track %2$s which is released under a %1$s license <BREAK LEVEL="MEDIUM" /> Moving on <BREAK LEVEL="SMALL" /> we have %5$s with their track %4$s',
                        )
                    ),
                    $arrLastTrack['pronouncable_enumTrackLicense'],
                    $arrLastTrack['strTrackNameSounds'],
                    $arrLastTrack['strArtistNameSounds'],
                    $arrTrack['strTrackNameSounds'], 
                    $arrTrack['strArtistNameSounds']
                );
                break;
            }
            if ($arrTrack['isNSFW'] != 0) {
                $bumper .= randomTextSelect($track_nsfw);
            }
            $bumper .= "\r\n$post_sable";
            $arrLastTrack = $arrTrack;

            if ( ! convertSableXmlToWav($bumper, Configuration::getWorkingDir() . '/bumper.' . $intTrackID . '.wav')) {
                echo "WARNING: Failed to create track bumper\r\n";
            }

            if ( ! concatenateTracks(Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/bumper.' . $intTrackID . '.wav', Configuration::getWorkingDir() . '/runplusbumper.wav')) {
                echo "WARNING: Failed to concatenate existing show to date with the new track bumper\r\n";
            }

            $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/runplusbumper.wav'), $arrTrack['intTrackID']);            
            
            echo "Downloading and merging audio file ($intTrackID)\r\n";
            $track = downloadFile($arrTrack['localSource']);
            if ($track === false) {
                debugUnlink(Configuration::getWorkingDir() . '/runplusbumper.wav');
                die("The tracks are not currently available.");
            }
            copy($track, Configuration::getWorkingDir() . '/' . $arrTrack['fileSource']);
            debugUnlink($track);

            if ( ! trackTrimSilence(Configuration::getWorkingDir() . '/' . $arrTrack['fileSource'])) {
                echo "WARNING: Failed to trim the silence from {$arrTrack['fileSource']}\r\n";
            }

            if ( ! concatenateTracks(Configuration::getWorkingDir() . '/runplusbumper.wav', Configuration::getWorkingDir() . '/' . $arrTrack['fileSource'], Configuration::getWorkingDir() . '/run.wav')) {
                echo "WARNING: Failed to concatenate run.wav with {$arrTrack['fileSource']}\r\n";
            }
        }
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/run.wav'), 'outro');

        $outro = "$pre_sable\r\n<BREAK LEVEL=\"LARGE\" />";
        $outro .= sprintf( 
            randomTextSelect(
                array(
                    'That was <BREAK LEVEL="SMALL" /> %1$s <BREAK LEVEL="SMALL" /> by <BREAK LEVEL="SMALL" /> %2$s <BREAK LEVEL="MEDIUM" /> It was a %3$s licensed track',
                    'You were listening to a %3$s licensed track by %2$s <BREAK LEVEL="SMALL" /> called <BREAK LEVEL="SMALL" /> %1$s'
                )
            ),
            $arrLastTrack['strTrackNameSounds'],
            $arrLastTrack['strArtistNameSounds'],
            $arrLastTrack['pronouncable_enumTrackLicense']
        );
        $outro .= sprintf(
            randomTextSelect(
                array(
                    ' <BREAK LEVEL="MEDIUM" /> Every track we play is selected by a listener like you <BREAK LEVEL="LARGE" /> to find out more <BREAK LEVEL="SMALL" /> go to %1$s slash <BREAK LEVEL="MEDIUM" /> eff <BREAK LEVEL="SMALL" /> ay <BREAK LEVEL="SMALL" /> queue <BREAK LEVEL="LARGE" /> If you like any of these tracks today, you can vote for them at %2$s <BREAK LEVEL="MEDIUM" /> These votes decide if each track will make it into the chart <BREAK LEVEL="MEDIUM" /> which can be found by visiting %1$s slash monthly ',
                    ' <BREAK LEVEL="MEDIUM" /> Remember, you can vote for any of these tracks by visiting %2$s <BREAK LEVEL="MEDIUM" /> Your vote will decide whether it makes it into the monthly chart show which is available from %1$s slash monthly '
                )
            ), 
            $show_data['strSiteNameSpoken'],
            $show_data['strShowUrlSpoken']
        );
        $outro .= sprintf(' <BREAK LEVEL="LARGE" /> The theem is an exerpt from Gee Em Zed By Scott All-tim <BREAK LEVEL="SMALL" />for details, please visit %1$s slash theem', $show_data['strSiteNameSpoken']) . "\r\n" . $post_sable;

        echo "Making the outro bumper\r\n";
        if ( ! convertSableXmlToWav($outro, Configuration::getWorkingDir() . '/outro.wav')) {
            echo "WARNING: Failed to generate the sable file or create outro.wav\r\n";
        }

        if ( ! generateSilenceWav(34, Configuration::getWorkingDir() . '/post-show-silence.wav')) {
            echo "WARNING: Failed to create silence.\r\n";
        }

        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/outro.wav', Configuration::getWorkingDir() . '/post-show-silence.wav', Configuration::getWorkingDir() . '/showend.wav')) {
            echo "WARNING: Failed to concatenate outro.wav with post-show-silence.wav\r\n";
        }

        if ( ! reverseTrackAudio(Configuration::getWorkingDir() . '/showend.wav', Configuration::getWorkingDir() . '/showend_rev.wav')) {
            echo "WARNING: Failed to reverse showend.wav into showend_rev.wav.\r\n";
        }

        if ( ! reverseTrackAudio(Configuration::getStaticDir() . '/outro.wav', Configuration::getWorkingDir() . '/outro_rev.wav', false)) {
            echo "WARNING: Failed to reverse outro.wav into outro_rev.wav\r\n";
        }

        if ( ! overlayAudioTracks(Configuration::getWorkingDir() . '/showend_rev.wav', Configuration::getWorkingDir() . '/outro_rev.wav', Configuration::getWorkingDir() . '/run_rev.wav')) {
            echo "WARNING: Failed to overlay showend_rev.wav with outro_rev.wav\r\n";
        }
        if ( ! reverseTrackAudio(Configuration::getWorkingDir() . '/run_rev.wav', Configuration::getWorkingDir() . '/run.wav')) {
            echo "WARNING: Failed to reverse run_rev.wav into run.wav\r\n";
        }

        if ( ! concatenateTracks(Configuration::getWorkingDir() . '/runplustrack.wav', Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/daily.wav')) {
            echo "WARNING: Failed to concatenate runplustrack.wav with run.wav\r\n";
        }
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/daily.wav'), 'end');

        $arrRunningOrder = makeArrayFromObjects(json_decode($running_order));

        foreach ($arrRunningOrder as $timestamp => $entry) {
            if (0 + $entry > 0) {
                $arrRunningOrder_final[(string) $timestamp] = $arrTracks[$entry];
            } else {
                $arrRunningOrder_final[(string) $timestamp] = $entry;
            }
        }

        echo "Getting the coverart\r\n";
        $coverart = downloadFile($show_data['qrcode']);
        if ($coverart != false) {
            copy($coverart, Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png');
            debugUnlink($coverart);
            $coverart = Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png';
        } else {
            $coverart = '';
        }

        echo "Converting the show to the various formats\r\n";
        generateOutputTracks(
            Configuration::getWorkingDir() . '/weekly.wav',
            Configuration::getWorkingDir() . '/weekly.' . $show_data['intShowUrl'] . '.',
            array(
                'Title' => $show_data['strShowName'],
                'Artist' => 'CCHits.net',
                'AlbumArt' => $coverart,
                'RunningOrder' => $arrRunningOrder_final
            )
        );
        if ($coverart != '') {
            debugUnlink($coverart);
        }
        echo "Uploading and finalizing\r\n";
        $show_summary = '';
        $track_pointer = 0;
        foreach ($show_data['arrTracks'] as $track) {
            if ($show_summary != '') {
                if (++$track_pointer === count($show_data['arrTracks'])) {
                    $show_summary .= ' and ';
                } else {
                    $show_summary .= ', ';
                }
            }
            $show_summary .= '"' . $track['strTrackName'] . '" by "' . $track['strArtistName'] . '"';
        }
        finalize(
            $show_data['intShowID'],
            Configuration::getWorkingDir() . '/weekly.' . $show_data['intShowUrl'] . '.', 
            updateStatusNet(
                array(
                    randomTextSelect(array('A new !weekly show has been created for ' . substr($show_data['intShowUrl'], 0, 4) . '-' . substr($show_data['intShowUrl'], 4, 2) . '-' . substr($show_data['intShowUrl'], 6, 2) . '. Get it from ' . $show_data['shorturl'])),
                    randomTextSelect(array('The @' . Configuration::getStatusNetUser() . ' weekly show (' . $show_data['shorturl'] . ') features ' . $show_summary))
                )
            )
        );
        echo "Done.\r\n\r\n";
    }
    if (isset($json_data['monthly_show']) && $monthly) {
        echo "Creating Monthly Show...\r\n";
        $show_data = $json_data['monthly_show'];
        $running_order = addEntryToJsonArray('', 0, 'intro');
        generateSilenceWav(7, Configuration::getWorkingDir() . '/pre-show-silence.wav');

        echo "Making intro bumper\r\n";
        $intro = "$pre_sable\r\n";
        $intro .= randomTextSelect(
            array(
                'Hello and welcome to the ' . $show_data['strShowNameSpoken'] . ' from ' . $show_data['strSiteNameSpoken'] . ' <BREAK LEVEL="MEDIUM" /> This show plays the top rated fourty tracks across all of cee cee hits <BREAK LEVEL="MEDIUM" /> ',
                'Your listening to a feed from ' . $show_data['strSiteNameSpoken'] . ' and this is the ' . $show_data['strShowNameSpoken'] . ' <BREAK LEVEL="MEDIUM" /> In this show you will hear the top four-tee tracks that you have been voting for at ' . $show_data['strSiteNameSpoken'] . ' <BREAK LEVEL="MEDIUM" /> '
            )
        );
        if ($show_data['isNSFW'] != 0) {
            $intro .= randomTextSelect($show_nsfw);
        }
        $intro .= "\r\n$post_sable";
        convertSableXmlToWav($intro, Configuration::getWorkingDir() . '/intro.wav');
        concatenateTracks(Configuration::getWorkingDir() . '/pre-show-silence.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/showstart.wav');
        copy(Configuration::getStaticDir() . '/intro.wav', Configuration::getWorkingDir() . '/intro.wav');
        overlayAudioTracks(Configuration::getWorkingDir() . '/showstart.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/run.wav');

        echo "These tracks are ";
        foreach ($show_data['arrTracks'] as $intTrackID => $arrTrack) {
            if ($intTrackID > 1) {
                echo ", ";
            }
            echo $arrTrack['strTrackName'] . ' by ' . $arrTrack['strArtistName'];
        }
        echo "\r\n";

        foreach ($show_data['arrTracks'] as $intTrackID => $arrTrack) {
            $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/run.wav'), 'Track Bumpers');
            $arrTracks[$arrTrack['intTrackID']] = $arrTrack;

            echo "Making track bumper ($intTrackID)\r\n";
            $bumper = "$pre_sable\r\n";
            if ($intTrackID != 1) {
                $bumper .= '<BREAK LEVEL="LARGE" />';
            }
            switch($intTrackID) {
            case 1:
                $bumper .= randomTextSelect(
                    array(
                        'The first track, at number ' . count($show_data['arrTracks']) . ' is ' . $arrTrack['strTrackNameSounds'] . ' by ' . $arrTrack['strArtistNameSounds'],
                        'lets start to dayz show with ' . $arrTrack['strArtistNameSounds'] . ' and is called ' . $arrTrack['strTrackNameSounds']
                    )
                );
                break;
            case count($show_data['arrTracks']):
                $bumper .= randomTextSelect(
                    array(
                        'That was a ' . $arrLastTrack['pronouncable_enumTrackLicense'] .' licensed track called ' . $arrLastTrack['strTrackNameSounds'] . ' by ' . $arrLastTrack['strArtistNameSounds'] . ' <BREAK LEVEL="MEDIUM" /> Our last track and top rated track for to day is ' . $arrTrack['strTrackNameSounds'] . ' by ' . $arrTrack['strArtistNameSounds'],
                        'You have been listening to ' . $arrLastTrack['strArtistNameSounds'] . ' with their track '  . $arrLastTrack['strTrackNameSounds'] . ' which is released under a ' . $arrLastTrack['pronouncable_enumTrackLicense'] . ' license <BREAK LEVEL="MEDIUM" /> At number one <BREAK LEVEL="SMALL" /> our final track today is ' . $arrTrack['strArtistNameSounds'] . ' with '  . $arrTrack['strTrackNameSounds'],
                    )
                );
                break;
            case 4:
            case 8:
            case 12:
            case 16:
            case 20:
            case 24:
            case 28:
            case 32:
            case 36:
                $bumper .= randomTextSelect(
                    array(
                        'That was a ' . $arrLastTrack['pronouncable_enumTrackLicense'] .' licensed track called ' . $arrLastTrack['strTrackNameSounds'] . ' by ' . $arrLastTrack['strArtistNameSounds'] . ' <BREAK LEVEL="MEDIUM" /> You are listening to a feed from ' . $show_data['strSiteNameSpoken'] . ' <BREAK LEVEL="MEDIUM" /> If you like any of these tracks <BREAK LEVEL="SMALL" /> you could vote for them at ' . $show_data['strShowUrlSpoken'] . '<BREAK LEVEL="MEDIUM" /> Up next, at number ' . ((1 + count($show_data['arrTracks']) ) - $intTrackID) . ' is ' . $arrTrack['strTrackNameSounds'] . ' by ' . $arrTrack['strArtistNameSounds'],
                        'The last track you were listening to was ' . $arrLastTrack['strArtistNameSounds'] . ' with their track '  . $arrLastTrack['strTrackNameSounds'] . ' which is released under a ' . $arrLastTrack['pronouncable_enumTrackLicense'] . ' license <BREAK LEVEL="LARGE" /> Remember that you can vote for any track in this show by visiting ' . $show_data['strShowUrlSpoken'] . ' Moving on <BREAK LEVEL="SMALL" /> we have ' . $arrTrack['strArtistNameSounds'] . ' with their track '  . $arrTrack['strTrackNameSounds'],
                    )
                );
                break;
            default:
                $bumper .= randomTextSelect(
                    array(
                        'That was ' . $arrLastTrack['strTrackNameSounds'] . ' by ' . $arrLastTrack['strArtistNameSounds'] . ' <BREAK LEVEL="MEDIUM" /> Up next at number ' .  ((1 + count($show_data['arrTracks']) ) - $intTrackID) . ' is ' . $arrTrack['strTrackNameSounds'] . ' by ' . $arrTrack['strArtistNameSounds'],
                        'You have been listening to ' . $arrLastTrack['strArtistNameSounds'] . ' with their track '  . $arrLastTrack['strTrackNameSounds'] . ' <BREAK LEVEL="MEDIUM" /> Now, at number ' .  ((1 + count($show_data['arrTracks']) ) - $intTrackID) . ', we have ' . $arrTrack['strArtistNameSounds'] . ' with their track '  . $arrTrack['strTrackNameSounds'],
                    )
                );
                break;
            }
            if ($arrTrack['isNSFW'] != 0) {
                $bumper .= randomTextSelect($track_nsfw);
            }

            $bumper .= "\r\n$post_sable";
            $arrLastTrack = $arrTrack;
            convertSableXmlToWav($bumper, Configuration::getWorkingDir() . '/bumper.' . $intTrackID . '.wav');
            concatenateTracks(Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/bumper.' . $intTrackID . '.wav', Configuration::getWorkingDir() . '/runplusbumper.wav');

            $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/runplusbumper.wav'), $arrTrack['intTrackID']);

            echo "Downloading and merging audio file ($intTrackID)\r\n";
            $track = downloadFile($arrTrack['localSource']);
            if ($track === false) {
                debugUnlink(Configuration::getWorkingDir() . '/runplusbumper.wav');
                die("The tracks are not currently available.");
            }
            copy($track, Configuration::getWorkingDir() . '/' . $arrTrack['fileSource']);
            debugUnlink($track);

            trackTrimSilence(Configuration::getWorkingDir() . '/' . $arrTrack['fileSource']);

            concatenateTracks(Configuration::getWorkingDir() . '/runplusbumper.wav', Configuration::getWorkingDir() . '/' . $arrTrack['fileSource'], Configuration::getWorkingDir() . '/run.wav');
        }
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/run.wav'), 'outro');

        echo "Making the outro bumper\r\n";
        $outro = "$pre_sable\r\n<BREAK LEVEL=\"LARGE\" />";
        $outro .= randomTextSelect(
            array(
                'That was <BREAK LEVEL="SMALL" /> ' . $arrLastTrack['strTrackNameSounds'] . ' <BREAK LEVEL="SMALL" /> by <BREAK LEVEL="SMALL" /> ' . $arrLastTrack['strArtistNameSounds'] . ' <BREAK LEVEL="MEDIUM" /> It was a ' . $arrLastTrack['pronouncable_enumTrackLicense'] . ' licensed track',
                'You were listening to a ' . $arrLastTrack['pronouncable_enumTrackLicense'] . ' licensed track by ' . $arrLastTrack['strArtistNameSounds'] . ' <BREAK LEVEL="SMALL" /> called <BREAK LEVEL="SMALL" /> ' . $arrLastTrack['strTrackNameSounds']
            )
        );
        $outro .= randomTextSelect(
            array(
                ' <BREAK LEVEL="MEDIUM" /> Every track we play are selected by listeners like you <BREAK LEVEL="MEDIUM" /> to find out more, go to ' . $show_data['strSiteNameSpoken'] . ' slash eff ay queue <BREAK LEVEL="LARGE" /> If you liked any of these tracks, you can vote for them at ' . $show_data['strShowUrlSpoken'] . ' <BREAK LEVEL="MEDIUM" /> You have just listened to the chart for this month but your votes for these and other tracks will decide the state of the chart for next month <BREAK LEVEL="MEDIUM" /> which can be found by visiting ' . $show_data['strSiteNameSpoken'] . ' slash monthly ',
                ' <BREAK LEVEL="MEDIUM" /> Remember, you can vote for any of these tracks by visiting ' . $show_data['strShowUrlSpoken'] . ' <BREAK LEVEL="MEDIUM" /> Your votes will select the tracks in the next chart show which you can find at ' . $show_data['strSiteNameSpoken'] . ' slash monthly '
            )
        );
        $outro .= ' <BREAK LEVEL="LARGE" /> The theem is an exerpt from Gee Em Zed By Scott All-tim <BREAK LEVEL="SMALL" />for details, please visit Cee-Cee-Hits dot net slash theem' . "\r\n" . $post_sable;

        convertSableXmlToWav($outro, Configuration::getWorkingDir() . '/outro.wav');
        generateSilenceWav(34, Configuration::getWorkingDir() . '/post-show-silence.wav');
        concatenateTracks(Configuration::getWorkingDir() . '/outro.wav', Configuration::getWorkingDir() . '/post-show-silence.wav', Configuration::getWorkingDir() . '/showend.wav');
        reverseTrackAudio(Configuration::getWorkingDir() . '/showend.wav', Configuration::getWorkingDir() . '/showend_rev.wav');
        reverseTrackAudio(Configuration::getStaticDir() . '/outro.wav', Configuration::getWorkingDir() . '/outro_rev.wav', false);

        overlayAudioTracks(Configuration::getWorkingDir() . '/showend_rev.wav', Configuration::getWorkingDir() . '/outro_rev.wav', Configuration::getWorkingDir() . '/run_rev.wav');
        reverseTrackAudio(Configuration::getWorkingDir() . '/run_rev.wav', Configuration::getWorkingDir() . '/run.wav');

        concatenateTracks(Configuration::getWorkingDir() . '/runplustrack.wav', Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/monthly.wav');
        $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/monthly.wav'), 'end');

        $arrRunningOrder = makeArrayFromObjects(json_decode($running_order));

        foreach ($arrRunningOrder as $timestamp => $entry) {
            if (0 + $entry > 0) {
                $arrRunningOrder_final[(string) $timestamp] = $arrTracks[$entry];
            } else {
                $arrRunningOrder_final[(string) $timestamp] = $entry;
            }
        }

        echo "Getting the coverart\r\n";
        $coverart = downloadFile($show_data['qrcode']);
        if ($coverart != false) {
            copy($coverart, Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png');
            debugUnlink($coverart);
            $coverart = Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png';
        } else {
            $coverart = '';
        }

        echo "Converting the show to the various formats\r\n";
        generateOutputTracks(
            Configuration::getWorkingDir() . '/monthly.wav',
            Configuration::getWorkingDir() . '/monthly.' . $show_data['intShowUrl'] . '.',
            array(
                'Title' => $show_data['strShowName'],
                'Artist' => 'CCHits.net',
                'AlbumArt' => $coverart,
                'RunningOrder' => $arrRunningOrder_final
            )
        );
        if ($coverart != '') {
            debugUnlink($coverart);
        }
        echo "Uploading and finalizing\r\n";
        $show_summary = '';
        $track_pointer = 0;
        foreach ($show_data['arrTracks'] as $track) {
            if ($show_summary != '') {
                if (++$track_pointer === count($show_data['arrTracks'])) {
                    $show_summary .= ' and ';
                } else {
                    $show_summary .= ', ';
                }
            }
            $show_summary .= '"' . $track['strTrackName'] . '" by "' . $track['strArtistName'] . '"';
        }
        finalize(
            $show_data['intShowID'],
            Configuration::getWorkingDir() . '/monthly.' . $show_data['intShowUrl'] . '.', 
            updateStatusNet(
                array(
                    randomTextSelect(array('A new !monthly show has been created for ' . substr($show_data['intShowUrl'], 0, 4) . '-' . substr($show_data['intShowUrl'], 4, 2) . '. Get it from ' . $show_data['shorturl'])),
                    randomTextSelect(array('The @' . Configuration::getStatusNetUser() . ' monthly show (' . $show_data['shorturl'] . ') features ' . $show_summary))
                )
            )
        );
        echo "Done.\r\n\r\n";
    }
}

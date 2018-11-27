<?php
require_once 'config_default.php'; // Adjust config_local to set your own local variables.
require_once 'library.php';

/**
 * Audio Utility Functions
 */
class Utility{
    /**
     * This function creates a period of silence.
     *
     * @param float $duration The duration to create of silence
     * @param path  $output   The output path to place the file
     *
     * @return boolean Success or Failure
     */
    static function generateSilenceWav($duration, $output)
    {
        $cmd = 'sox -q -n -r ' . $GLOBALS['RATE'] . ' -c 2 "' . $output . '" trim 0.0 ' . $duration;
        if (debugExec($cmd) != 0) {
            if (file_exists($output)) {
                debugUnlink($output);
            }
            return false;
        }
        return true;
    }

    /**
     * Provided an array of strings is provided, select one at random and return it
     *
     * @param array $array An array of strings to return.
     *
     * @return string|boolean One string selected at random, or nothing at all.
     */
    static function randomTextSelect($array)
    {
        if (! is_array($array) or count($array) == 0) {
            return false;
        }
        return $array[rand(0, count($array) -1)];
    }
}



// THIS IS THE CODE THAT WAS RUNNING THE DAILY SHOW
// if (isset($json_data['daily_show']) && $daily) {
//     $show_data = $json_data['daily_show'];
//     echo "Creating Daily Show..." . PHP_EOL;
//     echo sprintf('The Daily track is %1$s by %2$s ' . PHP_EOL . "", $show_data['arrTracks'][1]['strTrackName'], $show_data['arrTracks'][1]['strArtistName']);
//     debugout::add("Making intro bumper");
//     $running_order = addEntryToJsonArray('', 0, 'intro');
//     if ( ! generateSilenceWav(7, Configuration::getWorkingDir() . '/pre-show-silence.wav')) {
//         debugout::dump("WARNING: Failed to create silence.");
//     }
//     $intro = "$pre_sable" . PHP_EOL;
//     $intro .= sprintf(
//         randomTextSelect(
//             array(
//                 'Hello and welcome to the %1$s from %2$s <BREAK LEVEL="MEDIUM" /> To dayz show features ',
//                 'Hey there <BREAK LEVEL="MEDIUM" /> You are listening to a feed from %2$s and this is the %1$s <BREAK LEVEL="MEDIUM" /> To day you can hear '
//             )
//         ),
//         $show_data['strShowNameSpoken'], 
//         $show_data['strSiteNameSpoken']
//     );
//     if ($show_data['isNSFW'] != 0) {
//         $intro .= randomTextSelect($track_nsfw);
//     }
//     $intro .= sprintf(
//         randomTextSelect(
//             array(
//                  '%1$s <BREAK LEVEL="SMALL" /> by <BREAK LEVEL="SMALL" /> %2$s',
//                 ' a track by %2$s <BREAK LEVEL="SMALL" /> called <BREAK LEVEL="SMALL" /> %1$s'
//             )
//         ),
//         preg_replace('/\&/', ' and ', $show_data['arrTracks'][1]['strTrackNameSounds']),
//         preg_replace('/\&/', ' and ', $show_data['arrTracks'][1]['strArtistNameSounds'])
//     );
//     $intro .= PHP_EOL . "$post_sable";
//     if ( ! convertSableXmlToWav($intro, Configuration::getWorkingDir() . '/intro.wav')) {
//         debugout::dump("WARNING: Failed to create intro using $intro");
//         die('Thats it... im done');
//     }
//     if ( ! concatenateTracks(Configuration::getWorkingDir() . '/pre-show-silence.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/showstart.wav')) {
//         debugout::dump("WARNING: Failed to concatenate pre-show-silence with intro.wav");
//     }
//     copy(Configuration::getStaticDir() . '/intro.wav', Configuration::getWorkingDir() . '/intro.wav');
//     if ( ! overlayAudioTracks(Configuration::getWorkingDir() . '/showstart.wav', Configuration::getWorkingDir() . '/intro.wav', Configuration::getWorkingDir() . '/run.wav')) {
//         debugout::dump("WARNING: Failed to overlay showstart.wav over intro.wav");
//     }
//     $arrTracks[$show_data['arrTracks'][1]['intTrackID']] = $show_data['arrTracks'][1];
//     $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/run.wav'), $show_data['arrTracks'][1]['intTrackID']);
//     debugout::add("Downloading and merging audio file");
//     $track = downloadFile($show_data['arrTracks'][1]['localSource']);
//     if ($track === false) {
//         debugUnlink(Configuration::getWorkingDir() . '/run.wav');
//         debugout::dump();
//         die("The track is not currently available." . PHP_EOL);
//     }
//     copy($track, Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource']);
//     debugUnlink($track);
//     if ( ! trackTrimSilence(Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource'])) {
//         debugout::dump("WARNING: Failed to trim the silence from {$show_data['arrTracks'][1]['fileSource']}");
//     }
//     debugUnlink(Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource']);
//     if ( ! concatenateTracks(Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/' . $show_data['arrTracks'][1]['fileSource'] . '.trim.wav', Configuration::getWorkingDir() . '/runplustrack.wav')) {
//         debugout::dump("WARNING: Failed to concatenate run.wav with {$show_data['arrTracks'][1]['fileSource']}");
//     }
//     $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/runplustrack.wav'), 'outro');
//     $outro = "$pre_sable" . PHP_EOL . "<BREAK LEVEL=\"LARGE\" />";
//     $outro .= sprintf(
//         randomTextSelect(
//             array(
//                 'That was <BREAK LEVEL="SMALL" /> %1$s <BREAK LEVEL="SMALL" /> by <BREAK LEVEL="SMALL" /> %2$s <BREAK LEVEL="MEDIUM" /> It was a %3$s licensed track',
//                 'You were listening to a %3$s licensed track by %2$s <BREAK LEVEL="SMALL" /> called <BREAK LEVEL="SMALL" /> %1$s'
//             )
//         ),
//         preg_replace('/\&/', ' and ', $show_data['arrTracks'][1]['strTrackNameSounds']),
//         preg_replace('/\&/', ' and ', $show_data['arrTracks'][1]['strArtistNameSounds']),
//         preg_replace('/\&/', ' and ', $show_data['arrTracks'][1]['pronouncable_enumTrackLicense'])
//     );
//     $outro .= sprintf(
//         randomTextSelect(
//             array(
//                 ' <BREAK LEVEL="MEDIUM" /> Every track we play is selected by a listener like you <BREAK LEVEL="LARGE" /> to find out more <BREAK LEVEL="SMALL" /> please visit %1$s slash <BREAK LEVEL="MEDIUM" /> eff <BREAK LEVEL="SMALL" /> ay <BREAK LEVEL="SMALL" /> queue <BREAK LEVEL="LARGE" /> If you liked to dayz track, you can vote for it at %2$s <BREAK LEVEL="MEDIUM" /> These votes decide whether this track will be on the weekly show and eventually if it will make it into the chart <BREAK LEVEL="MEDIUM" /> both of these can be found by visiting %1$s ',
//                 ' <BREAK LEVEL="MEDIUM" /> Remember, you can vote for this track by visiting %2$s <BREAK LEVEL="MEDIUM" /> Your vote will decide whether it makes it into the best-of-the-week <BREAK LEVEL="SMALL" /> weekly show which is available from %1$s slash weekly '
//             )
//         ), 
//         $show_data['strSiteNameSpoken'],
//         $show_data['strShowUrlSpoken']
//     );
//     $outro .= sprintf(' <BREAK LEVEL="LARGE" /> The theem is an exerpt from Gee Em Zed By Scott All-tim <BREAK LEVEL="SMALL" />for details, please visit %1$s slash theem', $show_data['strSiteNameSpoken']) . PHP_EOL . $post_sable;
//     debugout::add("Making the outro bumper");
//     if ( ! convertSableXmlToWav($outro, Configuration::getWorkingDir() . '/outro.wav')) {
//         debugout::dump("WARNING: Failed to generate the sable file or create outro.wav");
//     }
//     if ( ! generateSilenceWav(34, Configuration::getWorkingDir() . '/post-show-silence.wav')) {
//         debugout::dump("WARNING: Failed to create silence.");
//     }
//     if ( ! concatenateTracks(Configuration::getWorkingDir() . '/outro.wav', Configuration::getWorkingDir() . '/post-show-silence.wav', Configuration::getWorkingDir() . '/showend.wav')) {
//         debugout::dump("WARNING: Failed to concatenate outro.wav with post-show-silence.wav");
//     }
//     if ( ! reverseTrackAudio(Configuration::getWorkingDir() . '/showend.wav', Configuration::getWorkingDir() . '/showend_rev.wav')) {
//         debugout::dump("WARNING: Failed to reverse showend.wav into showend_rev.wav.");
//     }
//     if ( ! reverseTrackAudio(Configuration::getStaticDir() . '/outro.wav', Configuration::getWorkingDir() . '/outro_rev.wav', false)) {
//         debugout::dump("WARNING: Failed to reverse outro.wav into outro_rev.wav");
//     }
//     if ( ! overlayAudioTracks(Configuration::getWorkingDir() . '/showend_rev.wav', Configuration::getWorkingDir() . '/outro_rev.wav', Configuration::getWorkingDir() . '/run_rev.wav')) {
//         debugout::dump("WARNING: Failed to overlay showend_rev.wav with outro_rev.wav");
//     }
//     if ( ! reverseTrackAudio(Configuration::getWorkingDir() . '/run_rev.wav', Configuration::getWorkingDir() . '/run.wav')) {
//         debugout::dump("WARNING: Failed to reverse run_rev.wav into run.wav");
//     }
//     if ( ! concatenateTracks(Configuration::getWorkingDir() . '/runplustrack.wav', Configuration::getWorkingDir() . '/run.wav', Configuration::getWorkingDir() . '/daily.wav')) {
//         debugout::dump("WARNING: Failed to concatenate runplustrack.wav with run.wav");
//     }
//     $running_order = addEntryToJsonArray($running_order, getTrackLength(Configuration::getWorkingDir() . '/daily.wav'), 'end');
//     $arrRunningOrder = makeArrayFromObjects(json_decode($running_order));
//     foreach ($arrRunningOrder as $timestamp => $entry) {
//         if (0 + $entry > 0) {
//             $arrRunningOrder_final[(string) $timestamp] = $arrTracks[$entry];
//         } else {
//             $arrRunningOrder_final[(string) $timestamp] = $entry;
//         }
//     }
//     debugout::add("Getting the coverart");
//     $coverart = downloadFile($show_data['qrcode']);
//     if ($coverart != false) {
//         copy($coverart, Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png');
//         debugUnlink($coverart);
//         $coverart = Configuration::getWorkingDir() . '/' . $show_data['intShowID'] . '.png';
//     } else {
//         $coverart = '';
//     }
//     debugout::add("Converting the daily show to the various formats");
//     WaitForSomeKeyboardDuty();
//     generateOutputTracks(
//         Configuration::getWorkingDir() . '/daily.wav',
//         Configuration::getWorkingDir() . '/daily.' . $show_data['intShowUrl'] . '.',
//         array(
//             'Title' => $show_data['strShowName'],
//             'Artist' => 'CCHits.net',
//             'AlbumArt' => $coverart,
//             'RunningOrder' => $arrRunningOrder_final
//         )
//     );
//     if ($coverart != '') {
//         debugUnlink($coverart);
//     }
//     finalize(
//         $show_data['intShowID'],
//         Configuration::getWorkingDir() . '/daily.' . $show_data['intShowUrl'] . '.', 
//         updateStatusNet(
//             array(
//                 sprintf(
//                     randomTextSelect(
//                         array(
//                             'A new !daily show has been created for %1$s-%2$s-%3$s. Get it from %4$s'
//                         )
//                     ), 
//                     substr($show_data['intShowUrl'], 0, 4),
//                     substr($show_data['intShowUrl'], 4, 2),
//                     substr($show_data['intShowUrl'], 6, 2),
//                     $show_data['shorturl']
//                 ),
//                 sprintf(
//                     randomTextSelect(
//                         array(
//                             'The @%1$s daily show for today (%2$s) features %3$s by %4$s'
//                         )
//                     ),
//                     Configuration::getStatusNetUser(),
//                     $show_data['shorturl'],
//                     $show_data['arrTracks'][1]['strTrackName'],
//                     $show_data['arrTracks'][1]['strArtistName']
//                 )
//             )
//         ),
//         json_encode($arrRunningOrder_final),
//         'daily',
//         $show_data['intShowUrl']
//     );
//     echo "Done." . PHP_EOL . PHP_EOL;
//     debugout::reset();
// }
///

/**
 * Information about Tracks (this may move back as it probably already exists)
 */
class showTrack{
    public $TrackID;
    public $TrackName;
    public $Artist;
    public $TrackURL;

}

class DailyShow{
    // echo sprintf('The Daily track is %1$s by %2$s ' . PHP_EOL . "", $show_data['arrTracks'][1]['strTrackName'], $show_data['arrTracks'][1]['strArtistName']);
    private $showName = "Daily Show";
    private $siteName = 'CCHits';
    private $introScript = [
        'Hello and welcome to the %1$s from %2$s <BREAK LEVEL="MEDIUM" /> To dayz show features ',
        'Hey there <BREAK LEVEL="MEDIUM" /> You are listening to a feed from %2$s and this is the %1$s <BREAK LEVEL="MEDIUM" /> To day you can hear '];

    private $available_Intro_text =  'INTRO: The @%1$s daily show for today (%2$s) features %3$s by %4$s';
    private $track_list = [];
    private $outro_text = 'OUTTRO: You have been listening to CChits - ';

    public function __construct(){
        $this->available_Intro_text = sprintf(Utility::randomTextSelect($this->introScript),$this->showName, $this->siteName);
    }

    // 2. Create this method and add your desired read-only public property.
    public function __get($property)
    {
        if ($property == 'showName') { return $this->showName; }
        if ($property == 'siteName') { return $this->siteName; }
    }
 
    public function add_track($track){
        echo('✨ Adding a Track :' . $track->TrackName .PHP_EOL);
        array_push ( $this->track_list,$track);
    }

    public function process_show(){
        echo('✨ Processing Show'.PHP_EOL);
        echo(' 🎹  DJ PLAY : CChits Intro' . PHP_EOL );
        echo(' 👄  DJ SAY : ' . $this->name . PHP_EOL );
        echo(' 👄  DJ SAY2 : ' . $this->available_Intro_text. PHP_EOL );
        
        // var_dump($this);
        foreach ($this->track_list  as $track)
        {
            echo(' 👄  DJ SAY  : ' . $track->TrackName . PHP_EOL );
            echo(' 🎹  DJ PLAY : ' . $track->TrackURL . PHP_EOL );
            echo(' 👄  DJ SAY  : That was ' . $track->TrackName . ' by ' . $track->Artist  .  PHP_EOL );
        }
        echo(' 👄  DJ SAY : ' . $this->outro_text . PHP_EOL );
        echo(' ✨  Convert to MP3, OGG and MP4');
    }
}

/**
 * The show maker class is a factory, making an executing the required show class objects.
 * 
 * For now - we'll just generate a daily show - nice and simple.
 */
class ShowMaker{
    function  __construct (){
        echo('✨ Constructing a ShowMaker' . PHP_EOL);
    }

    public function MakeShow(){
        return new DailyShow();
    }
}


$sm = new ShowMaker();

// This should eventually provide a weekly monthly or daily object...
$dailyShow = $sm->MakeShow();

// Let's create a track : - this should be 
$Track1 = new showTrack();
$Track1->TrackName = 'This is My Jam';
$Track1->TrackID = 100;
$Track1->Artist = 'Jam Master 5000';
$Track1->TrackURL = 'http://foobar';

// so we've set up our show
$dailyShow->add_track($Track1);
// var_dump($dailyShow);
// Now we tell it process...
$dailyShow->process_show();

$s = serialize($dailyShow);

echo('serialized DAILY SHOW'.PHP_EOL);
print_r($s);

// All Done.
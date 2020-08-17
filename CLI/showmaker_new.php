<?php
/// Notes : 
// Basic Process . 
//
// Create a Show Object
// Load it with Tracks (the correct number, and in the correct order.)
// Generate the IntroText script object
// Generate the Bumper object(s)
// Add the Outro 

 
require_once '/var/www/html/CLASSES/class_ConfigBroker.php';
require_once '/var/www/html/CLASSES/class_Database.php';
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

    // /**
    //  * Provided an array of strings is provided, select one at random and return it
    //  *
    //  * @param array $array An array of strings to return.
    //  *
    //  * @return string|boolean One string selected at random, or nothing at all.
    //  */
    // static function randomTextSelect($array)
    // {
    //     if (! is_array($array) or count($array) == 0) {
    //         return false;
    //     }
    //     return $array[rand(0, count($array) -1)];
    // }
}

/**
 * Information about Tracks (this may move back as it probably already exists)
 */
class showTrack{
    public $TrackID;
    public $TrackName;
    public $Artist;
    public $TrackURL;
    public function process(){
        echo(' 🎹  DJ PLAY : ' . $this->TrackName . PHP_EOL );
        $this->duration=rand(60,120);
    }
}
//implements JsonSerializable
class showSpeech {
    public function __construct($script,$description){
        $this->script = $script;
        $this->description = $description;
    }
    public function process(){
        // echo(Configuration::getWorkingDir() );
        //convertSableXmlToWav($this->script , Configuration::getWorkingDir() . '/bumper.sdf.wav');
        echo(' 👄  DJ SAY : ' . $this->script . PHP_EOL );
        $this->duration=rand(10,20);
    }
}
abstract class baseShow{
    private $track_list = [];
    public function process_show(){
        echo('✨ Processing Show'.PHP_EOL);
        //echo(' 🎹  DJ PLAY : CChits Intro' . PHP_EOL );
        //echo(' 👄  DJ SAY : ' . $this->name . PHP_EOL );
        //echo(' 👄  DJ SAY2 : ' . $this->available_Intro_text. PHP_EOL );
        $updatedtrack_list = [];
        $TimeOffset = 0;
        foreach ($this->track_list  as $track)
        {
            $element = reset($track);
            $element->process();
            array_push ($updatedtrack_list, array(strval($TimeOffset) => $element));
            $TimeOffset  = $TimeOffset  + $element->duration ;
        }
        $this->track_list = $updatedtrack_list;
        echo(' ✨  Convert to MP3, OGG and MP4');
    }
    /**
     * Add a Show element (voice over, track...)
     *
     * @param [type] $TimeOffset
     * @param [type] $object
     * @return void
     */
    public function addShowElement($TimeOffset,$object){
        array_push ($this->track_list, array($TimeOffset => $object));
    }
    public function toJSON(){
        return json_encode($this->track_list, JSON_PRETTY_PRINT);
    }
}

/**
 * Devloper Notes :
 * It is worth talking briefly about how the introscript wil be created - 
 * A track has a private collection of $introScript, and uses PHP's string template strstr to inject parameters, as this seems a nice way of formatting longer strings.
 */

class DailyShow extends baseShow{
    private $showName = "Daily Show";
    private $siteName = "CCHits";
    
    private $introScript = [
        'Hello and welcome to the %1$s from %2$s <BREAK LEVEL="MEDIUM" /> To dayz show features ',
        'Hey there <BREAK LEVEL="MEDIUM" /> You are listening to a feed from %2$s and this is the %1$s <BREAK LEVEL="MEDIUM" /> To day you can hear '];

    private $available_Intro_text =  'INTRO: The @%1$s daily show for today (%2$s) features %3$s by %4$s';
    private $outro_text = 'OUTRO: You have been listening to CChits - ';
    
    public function __construct(){
        $this->siteName = ConfigBroker::getConfig('Site Name', 'CCHits.net');
        $this->available_Intro_text = sprintf(randomTextSelect($this->introScript),$this->showName, $this->siteName);  




        // $intro = new showSpeech($this->available_Intro_text,'Intro');
        // $this->addShowElement("1",$intro);
        // // This track object would be returned from the database usually
        // $Track1 = new showTrack();
        // $Track1->TrackName = 'This is My Jam';
        // $Track1->TrackID = 100;
        // $Track1->Artist = 'Jam Master 5000';
        // $Track1->TrackURL = 'http://foobar';
        // $this->addShowElement("15",$Track1);
        // $this->addShowElement("40",new showSpeech($this->outro_text,'Outro'));
        
    }

    // 2. Create this method and add your desired read-only public property.
    public function __get($property)
    {
        if ($property == 'showName') { return $this->showName; }
        if ($property == 'siteName') { return $this->siteName; }
        if ($property == 'available_Intro_text') { return $this->available_Intro_text; }
        
    }
 
    public function add_track($track){
        echo('✨ Adding a Track :' . $track->TrackName .PHP_EOL);
        //$this->addShowElement("19.83",$track);
        //array_push ( $this->track_list,$track);
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


// $sm = new ShowMaker();

// // This should eventually provide a weekly monthly or daily object...
// $dailyShow = $sm->MakeShow();

// // Let's create a track : - this should be 


// // so we've set up our show
// // $dailyShow->add_track($Track1);
// // var_dump($dailyShow);
// // Now we tell it process...
// $dailyShow->process_show();

// $t = $dailyShow->toJSON();
// //echo($t);

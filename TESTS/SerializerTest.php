<?php
/**
 * CCHits.net is a website designed to promote Creative Commons Music,
 * the artists who produce it and anyone or anywhere that plays it.
 * These files are used to generate the site.
 *
 * PHP version 5
 *
 * @category Default
 * @package  Tests
 * @author   Mike Hingley <computa_mike@hotmail.com>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     http://cchits.net Actual web service
 * @link     https://github.com/CCHits/Website/wiki Developers Web Site
 * @link     https://github.com/CCHits/Website Version Control Service
 */

require_once __DIR__ . '/../vendor/antecedent/patchwork/Patchwork.php';
use PHPUnit\Framework\TestCase;
use function Patchwork\redefine as redefine;
use function Patchwork\restoreAll as restoreAll;
use function Patchwork\getFunction as getFunction;
use function Patchwork\getMethod as getMethod;
use function Patchwork\always as always;
use function Patchwork\relay as relay;
require __DIR__ . "/../CLI/showmaker_new.php";

/**
 * Unit testing the Daily Show Serialization.
 *
 * @category Default
 * @package  Tests
 * @author   Mike Hingley <computa_mike@hotmail.com>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     http://cchits.net Actual web service
 * @link     https://github.com/CCHits/Website/wiki Developers Web Site
 * @link     https://github.com/CCHits/Website Version Control Service
 */
final class SerializerTests extends TestCase
{
    /// These tests check that a SCript or Song object serlialize correctly.
    
    public function testThatADailyShowWithAnIntroSerializesCorrectly(){
        // Arrange
        $SUT = new  DailyShow();
        $intro = new showSpeech($SUT->available_Intro_text,'Intro');
        $SUT->addShowElement("1",$intro);
        // Act
        $res = $SUT->toJSON();
        $data = json_decode($res, true);

        // Assert
        $this->assertArrayHasKey('script', $data[0][1]);
        $this->assertEquals(NULL, $data[0][1]["script"]); 
    }
    public function testThatADailyShowWithAnIntroAndAScriptSerializesCorrectly(){
        // Arrange
        $SUT = new  DailyShow();
        $intro = new showSpeech($SUT->available_Intro_text,'Intro');
        $SUT->addShowElement("1",$intro);
        // Act
        $res = $SUT->toJSON();
        $data = json_decode($res, true);
        // Assert
        $this->assertArrayHasKey('script', $data[0][1]);
        $this->assertEquals(NULL, $data[0][1]["script"]); 
    }
}


/**
 * Unit testing the Daily Show.
 *
 * @category Default
 * @package  Tests
 * @author   Mike Hingley <computa_mike@hotmail.com>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     http://cchits.net Actual web service
 * @link     https://github.com/CCHits/Website/wiki Developers Web Site
 * @link     https://github.com/CCHits/Website Version Control Service
 */
final class DailyShowTests extends TestCase
{   /**
    * @dataProvider MessageDataProvider
    */
    public function testThatDailyShowHasTheCorrectIntroTextx($ID,$ExpectedString){
        // Arrange

        $GLOBALS["id"] = $ID; 
        redefine('randomTextSelect', function($array){
            return ($array[$GLOBALS["id"]]);
        });

        $ExpectedString = sprintf($ExpectedString,"DailyShow", "UnitHits");
        //Act

        $SUT = new  DailyShow();
        //Assert
        //  Constructor should have filled in the blanks from the configuration
        $this->assertEquals($ExpectedString, $SUT->available_Intro_text); 
        restoreAll();
 
    }
    public function MessageDataProvider()
    {
        return [
            [0,"Hello and welcome to the Daily Show from CCHits <BREAK LEVEL=\"MEDIUM\" /> To dayz show features "],
            [1,"Hey there <BREAK LEVEL=\"MEDIUM\" /> You are listening to a feed from CCHits and this is the Daily Show <BREAK LEVEL=\"MEDIUM\" /> To day you can hear "],
        ];
    }


}
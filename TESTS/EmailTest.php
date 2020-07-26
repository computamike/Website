<?php
namespace testRandom;

$GLOBALS['mockRand'] = \Mockery::mock('EmailTest');
function rand($v1,$v2) {

    return $GLOBALS['mockRand'].rand($v1,$v2);

    // return 5;
}


use PHPUnit\Framework\TestCase;
require_once realpath(__DIR__ . '/../CLI/showmaker_new.php');
final class EmailTest extends TestCase
{
    public function rand($v1,$v2){
        return 100;
    } 

    public function xxxssstestgenerateSilenceWavGenerates2Seconds(){
        $GLOBALS['RATE'] = 44100;
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri']; // eg: /tmp/phpFx0513a
        echo(' * Temporary file created : ' . $path);
        fclose($file); // this removes the file
        $res = \Utility::generateSilenceWav(2,$path);
        $this->assertEquals('bar', 'bar');
    }

    public function testRandomTextSelect(){
        $GLOBALS['mockRand']->shouldReceive('rand')->withArgs([5,15])->andReturn('some result from parent');
    

        //echo($service());
        // $service = \Mockery::mock(__NAMESPACE__, "rand");
        // $service ->once()->andReturn(3);
        // $res = \Utility::generateSilenceWav(2,$path);
        // self::$functions->shouldReceive('shell_exec')->with($cmd)->once();
        $res = rand(5, 15);
        $this->assertEquals($res,5);
        $this->assertEquals('bar', 'bar');
    }

}
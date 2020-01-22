<?php
use App\Logger;
use Psr\Log\LogLevel;
class ApiTest extends TestCase
{

    protected $fileName = 'testapi.log';
    protected $message = 'Testing the read and write action to the log files';

    public function testApiV1Write()
    {
        $this->json('POST', 'api/v1/logfile/', ['errortype' => 'debug', 'message' => $this->message, 'context' => '{"ErrorType":"RuntimeException", "File":"Logger.php","Lineno":"45", "ErrorCode":"4"}', 'loggeroption' => '{"filename":"' . $this->fileName . '"}'])
                ->seeJson([
                    'code' => 200,
        ]);
    }
     public function testApiV1View()
    {
        $this->json('GET', 'api/v1/logfile/')
                ->seeJson([
                    'fileName' => $this->fileName,
        ]);
    }
       public function testApiV1Read()
    {

        $response = $this->json('POST', 'api/v1/logfile/read/', ['filename' => $this->fileName])->response->getContent();

        $this->assertStringContainsString($this->message, $response);
        
    }

    public function testDelete() : void {
         @unlink(storage_path()."/customlogs/".$this->fileName);
         $this->assertTrue(true);
    }

}

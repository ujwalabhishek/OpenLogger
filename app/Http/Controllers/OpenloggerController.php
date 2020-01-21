<?php

namespace App\Http\Controllers;

require '../app/Logger.php';

use App\Logger\Logger;
use App\Traits\ApiResponser;
use DirectoryIterator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use function env;
use function storage_path;

class OpenloggerController extends Controller
{

    use ApiResponser;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $logdirectory;
    protected $logLevelThreshold;
    protected $loggerOptions = array();

    public function __construct()
    {
        $this->logdirectory = (null !== env('LOGDIRECTORY')) ? env('LOGDIRECTORY') : 'customlogs';
        $this->logLevelThreshold = (null !== env('LOGLEVEL')) ? env('LOGLEVEL') : 'DEBUG';

        $this->loggerOptions['extension'] = (null !== env('FILEEXTENTION')) ? env('FILEEXTENTION') : 'log';
        $this->loggerOptions['dateFormat'] = (null !== env('DATEFORMAT')) ? $this->loggerOptions['dateFormat'] = env('DATEFORMAT') : 'Y-m-d G:i:s';
        $this->loggerOptions['filename'] = (null !== env('FILENAME')) ? env('FILENAME') : 'false';
        $this->loggerOptions['flushFrequency'] = (null !== env('FLUSHFREQUENCY')) ? env('FLUSHFREQUENCY') : '1000';
        $this->loggerOptions['prefix'] = (null !== env('PREFIX')) ? env('PREFIX') : 'log_';
        $this->loggerOptions['logFormat'] = (null !== env('LOGFORMAT')) ? env('LOGFORMAT') : 'false';
        $this->loggerOptions['appendContext'] = (null !== env('APPENDCONTEXT')) ? env('APPENDCONTEXT') : 'true';
    }
    /**
     * 
     */
    public function index()
    {
        echo 'Welcome to OpenLogger Services !';
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function write(Request $request)
    {
        $rules = [
            'errortype' => 'required|string|in:emergency,alert,critical,error,warning,notice,info,debug',
            'message' => 'required|string|max:255',
            'context' => 'max:255|json',
        ];
        $this->validate($request, $rules);

        $errorType = $request->input('errortype');
        $message = $request->input('message');
        $context = empty($request->input('context')) ? array() : json_decode($request->input('context'), true);



        $logger = new Logger(storage_path() . "/{$this->logdirectory}", $this->logLevelThreshold, $this->loggerOptions);

        return $logger->$errorType($message, $context) ? $this->successResponse("Log entry created successfully") : $this->errorResponse("Error creating log entry", Response::HTTP_BAD_REQUEST);
    }
    /**
     * 
     * @param Request $request
     * @return type
     * @throws RuntimeException
     */
    public function viewlogdir(Request $request)
    {
        try {

            foreach (new DirectoryIterator(storage_path() . "/{$this->logdirectory}") as $file) {
                if ($file->isFile()) {
                    $filelist[] = array(
                        'fileName' => $file->getFilename(),
                        'fileExtention' => $file->getExtension(),
                        'filePath' => $file->getPathname(),
                    );
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->successResponse($filelist);
    }

    /**
     * 
     * @param Request $request
     * @return type
     * @throws RuntimeException
     */
    public function search(Request $request)
    {
        $rules = [
            'year' => 'required|digits:4|integer|min:1900|max:' . (date('Y')),
            'month' => 'digits:2|min:1|max:12',
            'day' => 'digits:2|min:1|max:31'
        ];
        $this->validate($request, $rules);



        $searchByYear = (!null == $request->input('year')) ? $request->input('year') . '-' : '';
        $searchByMonth = (!null == $request->input('month')) ? $request->input('month') . '-' : '';
        $searchByDay = (!null == $request->input('day')) ? $request->input('day') : '';
        $searchByDate = $searchByYear . $searchByMonth . $searchByDay;
        $path = storage_path() . "/{$this->logdirectory}/" . $this->loggerOptions['prefix'] . $searchByDate . "*";
        $filelist = array();
        try {

            foreach (glob($path) as $file) {

                $fileDetails = pathinfo($file);
                $filelist[] = array(
                    'fileName' => $fileDetails['basename'],
                    'fileExtention' => $fileDetails['extension'],
                    'filePath' => $file,
                );
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->successResponse($filelist);
    }

    /**
     * 
     * @param Request $request
     * @return type
     * @throws RuntimeException
     */
    public function read(Request $request)
    {
        $rules = [
            'filename' => 'required|string|min:3|max:255',
        ];
        $this->validate($request, $rules);

        $path = storage_path() . "/{$this->logdirectory}/" . $request->input('filename');

        try {
            if (!file_exists($path)) {
                throw new RuntimeException('File not found.', Response::HTTP_NOT_FOUND);
            }
            $fp = fopen($path, "rb");
            if (!$fp) {
                throw new RuntimeException('File open failed.');
            }
            $fileData = array();
            foreach (file($path) as $line) {
                $fileData[] = $line;
            }

            return empty($fileData) ? $this->errorResponse("Nothing to read. The file {$request->input('filename')} is empty.", Response::HTTP_UNPROCESSABLE_ENTITY) : $this->successResponse($fileData);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

}

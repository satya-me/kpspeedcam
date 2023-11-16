<?php

namespace App\Http\Controllers\Api;

use CURLFile;
use DateTime;
use ZipArchive;
use Carbon\Carbon;
use SimpleXMLElement;
use GuzzleHttp\Client;
use App\Models\Analyser;
use App\Models\Location;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AnalyserMedia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class FileController extends Controller
{
    public function extractZipFile(Request $request)
    {
        $zipFile = $request->file('zip');
        $carbon = Carbon::now();
        $ticket_num = $carbon->timestamp;

        if ($zipFile) {
            $originalName = $zipFile->getClientOriginalName(); // Get the original file name
            $extension = $zipFile->getClientOriginalExtension(); // Get the file extension
            $size = $zipFile->getSize(); // Get the file size in bytes
            $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);

            // You can then use these values as needed
            // For example, to display them or save them in a database.

            $zipFilePath = $request->file('zip');
            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFilePath, ZipArchive::ER_READ) === TRUE) {
                $isZip = $zipArchive->extractTo(storage_path("app/unzip/" . $filenameWithoutExtension));
                $zipArchive->close();
                // return "hi";
                if ($isZip) {

                    // Load the XML content from a file or a string
                    $xmlContent = Storage::get("unzip/" . $filenameWithoutExtension . "/event.xml"); // Adjust the path accordingly

                    // Parse the XML content
                    $xml = new SimpleXMLElement($xmlContent);

                    $timestamp = (int) $xml->timing->timestamp;
                    $timestamp = $timestamp / 1000;

                    $carbon = Carbon::createFromTimestampMs($timestamp);

                    // Format the Carbon object to a human-readable date and time.
                    $formattedDate = $carbon->toDateTimeString();

                    // Extract and save position data
                    $macId = (String) $xml->cameraMacAddress;
                    $latitude = (float) $xml->position->latitude;
                    $longitude = (float) $xml->position->longitude;
                    $speedLimit_kph = (float) $xml->speed->speedLimit_kph;
                    $speedTrigger_kph = (float) $xml->speed->speedTrigger_kph;
                    $calculatedSpeed_kph = (float) $xml->speed->calculatedSpeed_kph;
                    $targetSpeed_kph = (float) $xml->speed->targetSpeed_kph;

                    $location = Location::where(['mac_id' => $macId])->first();

                    $location_id = $location->location_id;

                    $Analyser = new Analyser;
                    $Analyser->cameraMacAddress = $macId;
                    $Analyser->location_id = $location_id;
                    $Analyser->ticket = $filenameWithoutExtension;
                    $Analyser->ticket_number = $ticket_num;
                    $Analyser->speedLimit_kph = $speedLimit_kph;
                    $Analyser->speedTrigger_kph = $speedTrigger_kph;
                    $Analyser->calculatedSpeed_kph = $calculatedSpeed_kph;
                    $Analyser->targetSpeed_kph = $targetSpeed_kph;
                    $Analyser->latitude = $latitude;
                    $Analyser->longitude = $longitude;
                    $Analyser->capture_at = $formattedDate;
                    $Analyser->save();


                    $destinationDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension);

                    if (!File::exists($destinationDirectory)) {
                        File::makeDirectory($destinationDirectory, 0755, true, true);
                    }

                    // Extract and save pictures data
                    $pictures = [];
                    foreach ($xml->pictures->image as $image) {
                        $fullFileName = (string) $image->fullFileName;
                        $cutFileName = (string) $image->cutFileName;

                        $AnalyserMedia = new AnalyserMedia;
                        $AnalyserMedia->analyser_id = $Analyser->id;
                        $AnalyserMedia->filepath_full = $fullFileName;
                        $AnalyserMedia->filepath_cut = $cutFileName;
                        $AnalyserMedia->save();


                        // fullFileName
                        $sourceFilePath = storage_path("app/unzip/" . $filenameWithoutExtension . "/" . $fullFileName);
                        $destinationDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension . "/");
                        // Make sure the destination directory exists; create it if necessary
                        File::ensureDirectoryExists($destinationDirectory);
                        // Copy the file from the source to the destination
                        File::copy($sourceFilePath, $destinationDirectory . $fullFileName);

                        // cutFileName
                        $sourceFilePath = storage_path("app/unzip/" . $filenameWithoutExtension . "/" . $cutFileName);
                        $destinationDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension . "/");
                        // Make sure the destination directory exists; create it if necessary
                        File::ensureDirectoryExists($destinationDirectory);
                        // Copy the file from the source to the destination
                        File::copy($sourceFilePath, $destinationDirectory . $cutFileName);

                        // Copy the event.xml file
                        $sourceEventXmlPath = storage_path("app/unzip/" . $filenameWithoutExtension . "/event.xml");
                        $destinationEventXmlDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension . "/");

                        File::ensureDirectoryExists($destinationEventXmlDirectory);
                        File::copy($sourceEventXmlPath, $destinationEventXmlDirectory . "event.xml");

                        $pictures[] = [
                            'fullFileName' => env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension . "/" . $fullFileName,
                            'cutFileName' => env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension . "/" . $cutFileName
                        ];
                    }


                    // CallTheAnalyzer
                    $mediaForProcess = AnalyserMedia::where('analyser_id', $Analyser->id)->first();
                    if (env('APP_ENV') == 'local') {
                        $image_url = 'https://kpspeedcam.com/app/storage/app/unzipMedia/ticket23-09-23_04-36-04_518/cut_2566_22.png';
                    } else {
                        $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension . "/" . $mediaForProcess->filepath_cut;
                    }

                    $plate_number = $this->CallTheAnalyzer($image_url);
                    // strtoupper
                    // return $plate_number;
                    Analyser::where('id', $Analyser->id)->update(['license_number' => strtoupper($plate_number->plate), 'type' => $plate_number->vehicle->type]);
                    sleep(1);
                    $this->deleteUnzipFile($filenameWithoutExtension);

                    $fileInfo = [
                        'File Name' => $originalName,
                        'File Extension' => $extension,
                        'File Size (bytes)' => $size,
                        'File Name without Extension' => $filenameWithoutExtension,
                        //
                        'pictures' => $pictures,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'speedLimit_kph' => $speedLimit_kph,
                        'speedTrigger_kph' => $speedTrigger_kph,
                        'calculatedSpeed_kph' => $calculatedSpeed_kph,
                        'targetSpeed_kph' => $targetSpeed_kph,
                        'plate_number' => $plate_number->plate,
                        'vehicle_type' => $plate_number->vehicle->type,
                        'data' => $Analyser

                    ];

                    // Return the file information as a JSON response
                    return response()->json($fileInfo);
                }
            }
            return response()->json([
                "message" => "Unsupported file type",
                'File Name' => $originalName,
                'File Extension' => $extension,
                'File Size (bytes)' => $size,
                'File Name without Extension' => $filenameWithoutExtension,
            ]);
        }

    }

    public function deleteUnzipFile($folderPath)
    {
        // $folderPath = 'ticket23-09-23_04-36-04_518'; // Specify the folder path you want to delete

        // Check if the folder exists before attempting to delete it
        if (Storage::exists("unzip/" . $folderPath)) {
            // Delete the folder and its contents
            Storage::deleteDirectory("unzip/" . $folderPath);
            return "Folder deleted successfully.";
        } else {
            return "Folder does not exist.";
        }
    }

    public function CallTheAnalyzer($imag)
    {
        // return $imag;
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api.platerecognizer.com/v1/plate-reader',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('upload_url' => $imag),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Token a901326ae6e47edf8a715f8029038f8c68e765e0'
                ),
            )
        );


        $response = curl_exec($curl);

        curl_close($curl);
        // echo gettype($response);
        // return $response;
        $jsObj = json_decode($response);
        debugLog('handle', ['CallTheAnalyzer' => $jsObj]);
        return $jsObj->results[0];
    }

    public function LatestRecord()
    {
        $AnalyserLatest = Analyser::latest()->first();
        $id = $AnalyserLatest->id;
        $media = AnalyserMedia::where('analyser_id', $id)->get();
        $data = array();
        foreach ($media as $key => $value) {
            $img_link[] = env('APP_URL') . '/storage/app/unzipMedia/' . $AnalyserLatest->ticket . '/' . $value->filepath_full;
            # code...
        }

        $data['ticket_number'] = $AnalyserLatest->ticket_number;
        $data['license_number'] = $AnalyserLatest->license_number;
        $data['speedLimit_kph'] = $AnalyserLatest->speedLimit_kph;
        $data['speedTrigger_kph'] = $AnalyserLatest->speedTrigger_kph;
        $data['calculatedSpeed_kph'] = $AnalyserLatest->calculatedSpeed_kph;
        $data['targetSpeed_kph'] = $AnalyserLatest->targetSpeed_kph;
        $data['latitude'] = $AnalyserLatest->latitude;
        $data['longitude'] = $AnalyserLatest->longitude;
        $data['capture_at'] = $AnalyserLatest->capture_at;
        $data['type'] = $AnalyserLatest->type;
        $data['img_link'] = $img_link;

        return response()->json($data);
    }

    // R&D
    public function uploadFiles(Request $request)
    {
        $uploadDir = 'uploads'; // Specify the directory where you want to save the uploaded files
        // Check if it's a GET request
        if ($request->isMethod('get')) {
            if ($request->has('file_content')) {
                $fileContent = $request->input('file_content');
                $decodedContent = base64_decode($fileContent);
                $fileName = 'file_' . time() . '.txt'; // Example file name
                Storage::disk('local')->put($uploadDir . '/' . $fileName, $decodedContent);
                return response()->json([
                    'message' => 'File uploaded successfully via GET request',
                    'file_name' => $fileName,
                ]);
            } else {
                return response()->json(['message' => 'No file content in the GET request.']);
            }
        }
        // Get all files from the request


        $files = $request->allFiles();

        // Check if any files are present in the request
        if (!empty($files)) {
            foreach ($files as $key => $fileArray) {
                if (is_array($fileArray)) {
                    foreach ($fileArray as $file) {
                        if ($file->isValid()) {
                            $file->storeAs($uploadDir, $file->getClientOriginalName());
                            // echo 'File uploaded successfully: ' . $file->getClientOriginalName();
                            return response()->json([
                                'message' => "File uploaded successfully",
                                'file' => $file->getClientOriginalName(),
                            ]);
                        } else {
                            echo 'Error occurred during file upload: ' . $file->getClientOriginalName();
                        }
                    }
                } else {
                    if ($fileArray->isValid()) {
                        $fileArray->storeAs($uploadDir, $fileArray->getClientOriginalName());
                        // echo 'File uploaded successfully: ' . $fileArray->getClientOriginalName();
                        return response()->json([
                            'message' => "File uploaded successfully",
                            'file_name' => $fileArray->getClientOriginalName(),
                        ]);
                    } else {
                        echo 'Error occurred during file upload: ' . $fileArray->getClientOriginalName();
                    }
                }
            }
        } else {
            echo 'No file uploaded or invalid request.';
        }
    }

    public function handleData(Request $request)
    {
        $requestData = $this->getRequestData($request);

        // Perform operations with the received data
        // Example: You can access the data in the $requestData variable and process it further

        // For demonstration purposes, let's simply return the received data
        return response()->json(['data' => $requestData]);
    }

    private function getRequestData(Request $request)
    {
        // dd($request);
        if ($this->isJSON($request)) {
            debugLog('handle', ['isJSON' => $request->json()->all()]);
            return $request->json()->all();
        } elseif ($this->isXML($request)) {
            $xmlString = $request->getContent();
            debugLog('handle', ['isXML' => $xmlString]);
            return $this->xmlToArray($xmlString);
        } else {
            debugLog('handle', ['data' => $request->all()]);
            return $request->all();
        }
    }

    private function isJSON(Request $request)
    {
        return Str::contains($request->header('content-type'), ['/json', '+json']);
    }

    private function isXML(Request $request)
    {
        return Str::contains($request->header('content-type'), ['/xml', '+xml']);
    }

    private function xmlToArray($xml)
    {
        $xmlObject = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xmlObject);
        return json_decode($json, true);
    }

}
